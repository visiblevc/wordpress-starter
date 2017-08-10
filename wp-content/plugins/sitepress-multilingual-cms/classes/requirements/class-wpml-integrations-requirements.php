<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Integrations_Requirements {
	const NOTICE_GROUP = 'requirements';
	const MISSING_REQ_NOTICE_ID = 'missing-requirements';
	const EDITOR_NOTICE_ID = 'enable-translation-editor';
	const DOCUMENTATION_LINK = 'https://wpml.org/documentation/plugins-compatibility/page-builders/';

	private $issues = array();
	private $tm_settings = array();
	private $should_create_editor_notice = false;
	private $integrations;

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var  WPML_Third_Party_Dependencies $third_party_dependencies */
	private $third_party_dependencies;

	/** @var  WPML_Requirements_Notification $requirements_notification */
	private $requirements_notification;

	/**
	 * WPML_Integrations_Requirements constructor.
	 *
	 * @param SitePress                      $sitepress
	 * @param WPML_Third_Party_Dependencies  $third_party_dependencies
	 * @param WPML_Requirements_Notification $requirements_notification
	 * @param array                          $integrations
	 */
	public function __construct(
		SitePress $sitepress,
		WPML_Third_Party_Dependencies $third_party_dependencies = null,
		WPML_Requirements_Notification $requirements_notification = null,
		$integrations = null
	) {
		$this->sitepress                 = $sitepress;
		$this->third_party_dependencies  = $third_party_dependencies;
		$this->requirements_notification = $requirements_notification;
		$this->tm_settings               = $this->sitepress->get_setting( 'translation-management' );
		$this->integrations              = $this->get_integrations();

		if ( $integrations ) {
			$this->integrations = $integrations;
		}

	}

	public function init_hooks() {
		if ( $this->sitepress->get_setting( 'setup_complete' ) ) {
			add_action( 'admin_init', array( $this, 'init' ) );
			add_action( 'wp_ajax_wpml_set_translation_editor', array( $this, 'set_translation_editor_callback' ) );
		}
	}

	public function init() {
		if ( $this->sitepress->get_wp_api()->is_back_end() ) {
			$this->update_issues();
			$this->update_notices();
		}
	}

	private function update_notices() {
		$wpml_admin_notices = wpml_get_admin_notices();

		if ( ! $this->issues ) {
			$wpml_admin_notices->remove_notice( self::NOTICE_GROUP, self::MISSING_REQ_NOTICE_ID );
		}
		if ( ! $this->should_create_editor_notice ) {
			$wpml_admin_notices->remove_notice( self::NOTICE_GROUP, self::EDITOR_NOTICE_ID );
		}

		if ( $this->issues || $this->should_create_editor_notice ) {

			$notice_model = $this->get_notice_model();
			$wp_api       = $this->sitepress->get_wp_api();

			$this->add_requirements_notice( $notice_model, $wpml_admin_notices, $wp_api );
			$this->add_tm_editor_notice( $notice_model, $wpml_admin_notices, $wp_api );
		}

	}

	private function update_issues() {
		$this->issues = $this->get_third_party_dependencies()->get_issues();
		$this->update_should_create_editor_notice();
	}

	private function update_should_create_editor_notice() {
		$editor_translation_set = ( 1 === (int) $this->tm_settings['doc_translation_method'] );

		$this->should_create_editor_notice = ! $editor_translation_set && ! $this->issues && $this->integrations;
	}

	public function set_translation_editor_callback() {
		if ( ! $this->is_valid_request() ) {
			wp_send_json_error( __( 'This action is not allowed', 'sitepress' ) );
		} else {
			$wpml_admin_notices = wpml_get_admin_notices();

			$this->tm_settings['doc_translation_method'] = 1;
			$this->sitepress->set_setting( 'translation-management', $this->tm_settings, true );
			$this->sitepress->set_setting( 'doc_translation_method', 1, true );

			$wpml_admin_notices->remove_notice( self::NOTICE_GROUP, self::EDITOR_NOTICE_ID );

			wp_send_json_success();
		}
	}

	private function is_valid_request() {
		$valid_request = true;
		if ( ! array_key_exists( 'nonce', $_POST ) ) {
			$valid_request = false;
		}
		if ( $valid_request ) {
			$nonce = $_POST['nonce'];

			$nonce_is_valid = wp_verify_nonce( $nonce, 'wpml_set_translation_editor' );
			if ( ! $nonce_is_valid ) {
				$valid_request = false;
			}
		}

		return $valid_request;
	}

	private function get_integrations() {
		$integrations       = new WPML_Integrations( $this->sitepress->get_wp_api() );

		return $integrations->get_results();
	}

	/**
	 * @return array
	 */
	private function get_integrations_names() {
		$integrations = $this->integrations;

		return array_values( wp_list_pluck( $integrations, 'name' ) );
	}

	/**
	 * @return WPML_Requirements_Notification
	 */
	private function get_notice_model() {
		if ( ! $this->requirements_notification ) {
			$template_paths   = array(
				ICL_PLUGIN_PATH . '/templates/warnings/',
			);
			$twig_loader      = new Twig_Loader_Filesystem( $template_paths );
			$environment_args = array();
			if ( WP_DEBUG ) {
				$environment_args['debug'] = true;
			}
			$twig         = new Twig_Environment( $twig_loader, $environment_args );
			$twig_service = new WPML_Twig_Template( $twig );

			$this->requirements_notification = new WPML_Requirements_Notification( $twig_service );
		}

		return $this->requirements_notification;
	}

	/**
	 * @param WPML_Notice $notice
	 */
	private function add_actions_to_notice( WPML_Notice $notice ) {
		$dismiss_action = new WPML_Notice_Action( __( 'Dismiss', 'sitepress' ), '#', true, false, true, false );
		$notice->add_action( $dismiss_action );

		if ( $this->has_page_builders_issues() ) {
			$document_action = new WPML_Notice_Action( __( 'Translating content created with page builders', 'sitepress' ), self::DOCUMENTATION_LINK );
			$notice->add_action( $document_action );
		}
	}

	private function has_page_builders_issues() {
		if ( array_key_exists( 'causes', $this->issues ) ) {
			foreach ( (array) $this->issues['causes'] as $cause ) {
				if ( 'page-builders' === $cause['type'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param WPML_Notice $notice
	 * @param WPML_WP_API $wp_api
	 */
	private function add_callbacks( WPML_Notice $notice, WPML_WP_API $wp_api ) {
		$notice->add_display_callback( array( $wp_api, 'is_core_page' ) );
		$notice->add_display_callback( array( $wp_api, 'is_plugins_page' ) );
		$notice->add_display_callback( array( $wp_api, 'is_themes_page' ) );
	}

	/**
	 * @param WPML_Requirements_Notification $notice_model
	 * @param WPML_Notices $wpml_admin_notices
	 * @param WPML_WP_API $wp_api
	 */
	private function add_requirements_notice( WPML_Requirements_Notification $notice_model, WPML_Notices $wpml_admin_notices, WPML_WP_API $wp_api ) {
		if ( $this->issues ) {
			$message = $notice_model->get_message( $this->issues, 1 );

			$requirements_notice = new WPML_Notice( self::MISSING_REQ_NOTICE_ID, $message, self::NOTICE_GROUP );

			$this->add_actions_to_notice( $requirements_notice );
			$this->add_callbacks( $requirements_notice, $wp_api );
			$wpml_admin_notices->add_notice( $requirements_notice, true );
		}
	}

	/**
	 * @param WPML_Requirements_Notification $notice_model
	 * @param WPML_Notices $wpml_admin_notices
	 * @param WPML_WP_API $wp_api
	 */
	private function add_tm_editor_notice( WPML_Requirements_Notification $notice_model, WPML_Notices $wpml_admin_notices, WPML_WP_API $wp_api ) {
		if ( $this->should_create_editor_notice ) {
			$requirements_scripts = new WPML_Integrations_Requirements_Scripts();
			$requirements_scripts->init();

			$text   = $notice_model->get_settings( $this->get_integrations_names() );
			$notice = new WPML_Notice( self::EDITOR_NOTICE_ID, $text, self::NOTICE_GROUP );
			$notice->set_css_class_types( 'info' );

			$enable_action = new WPML_Notice_Action( _x( 'Enable it now', 'Integration requirement notice title for translation editor: enable action', 'sitepress' ), '#', false, false, true );
			$enable_action->set_js_callback( 'js-set-translation-editor' );
			$notice->add_action( $enable_action );

			$this->add_callbacks( $notice, $wp_api );
			$this->add_actions_to_notice( $notice );
			$wpml_admin_notices->add_notice( $notice );
		}
	}

	/**
	 * @return WPML_Third_Party_Dependencies
	 */
	private function get_third_party_dependencies() {
		if ( ! $this->third_party_dependencies ) {
			$integrations                   = new WPML_Integrations( $this->sitepress->get_wp_api() );
			$requirements                   = new WPML_Requirements();
			$this->third_party_dependencies = new WPML_Third_Party_Dependencies( $integrations, $requirements );
		}

		return $this->third_party_dependencies;
	}
}
