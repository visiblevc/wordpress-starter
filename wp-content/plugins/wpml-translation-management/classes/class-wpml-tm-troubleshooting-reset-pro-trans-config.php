<?php

class WPML_TM_Troubleshooting_Reset_Pro_Trans_Config extends WPML_TM_AJAX_Factory_Obsolete {
	/**
	 * @var wpdb $wpdb
	 */
	private $wpdb;

	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var WPML_Translation_Proxy_API $TranslationProxy
	 */
	private $TranslationProxy;

	private $script_handle = 'wpml_reset_pro_trans_config';

	/**
	 * WPML_TM_Troubleshooting_Clear_TS constructor.
	 *
	 * @param SitePress                  $sitepress
	 * @param WPML_Translation_Proxy_API $TranslationProxy
	 * @param WPML_WP_API                $wpml_wp_api
	 * @param wpdb                       $wpdb
	 */
	public function __construct( &$sitepress, &$TranslationProxy, &$wpml_wp_api, &$wpdb ) {
		parent::__construct( $wpml_wp_api );

		$this->sitepress        = &$sitepress;
		$this->TranslationProxy = &$TranslationProxy;
		$this->wpdb             = &$wpdb;
		add_action( 'init', array( $this, 'load_action' ) );

		$this->add_ajax_action( 'wp_ajax_wpml_reset_pro_trans_config', array( $this, 'reset_pro_translation_configuration_action' ) );
		$this->init();
	}

	public function load_action() {
		$page           = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		$should_proceed = SitePress_Setup::setup_complete()
											&& ! $this->wpml_wp_api->is_heartbeat()
											&& ! $this->wpml_wp_api->is_ajax()
											&& ! $this->wpml_wp_api->is_cron_job()
											&& strpos( $page, 'sitepress-multilingual-cms/menu/troubleshooting.php' ) === 0;

		if ( $should_proceed ) {
			$this->add_hooks();
		}
	}

	private function add_hooks() {
		add_action( 'after_setup_complete_troubleshooting_functions', array( $this, 'render_ui' ) );
	}

	public function register_resources() {
		wp_register_script( $this->script_handle, WPML_TM_URL . '/res/js/reset-pro-trans-config.js', array( 'jquery', 'jquery-ui-dialog' ), false, true );
	}

	public function enqueue_resources( $hook_suffix ) {
		if ( $this->wpml_wp_api->is_troubleshooting_page() ) {
			$this->register_resources();
			$translation_service_name = $this->TranslationProxy->get_current_service_name();
			$strings                  = array(
				'placeHolder'  => 'icl_reset_pro',
				'reset'        => wp_create_nonce( 'reset_pro_translation_configuration' ),
				'confirmation' => sprintf( __( 'Are you sure you want to reset the %1$s translation process?', 'wpml-translation-management' ), $translation_service_name ),
				'action'       => $this->script_handle,
				'nonce'        => wp_create_nonce( $this->script_handle ),
			);
			wp_localize_script( $this->script_handle, $this->script_handle . '_strings', $strings );
			wp_enqueue_script( $this->script_handle );
		}
	}

	public function render_ui() {
		$clear_ts = new WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI();
		$clear_ts->show();
	}

	public function reset_pro_translation_configuration_action() {
		$action      = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		$nonce       = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		$valid_nonce = wp_verify_nonce( $nonce, $action );
		if ( $valid_nonce && isset( $_POST ) && $_POST ) {
			return $this->wpml_wp_api->wp_send_json_success( $this->reset_pro_translation_configuration() );
		} else {
			return $this->wpml_wp_api->wp_send_json_error( __( "You can't do that!", 'wpml-translation-management' ) );
		}
	}

	function reset_pro_translation_configuration() {
		$translation_service_name = $this->TranslationProxy->get_current_service_name();

		$this->sitepress->set_setting( 'content_translation_languages_setup', false );
		$this->sitepress->set_setting( 'content_translation_setup_complete', false );
		$this->sitepress->set_setting( 'content_translation_setup_wizard_step', false );
		$this->sitepress->set_setting( 'translator_choice', false );
		$this->sitepress->set_setting( 'icl_lang_status', false );
		$this->sitepress->set_setting( 'icl_balance', false );
		$this->sitepress->set_setting( 'icl_support_ticket_id', false );
		$this->sitepress->set_setting( 'icl_current_session', false );
		$this->sitepress->set_setting( 'last_get_translator_status_call', false );
		$this->sitepress->set_setting( 'last_icl_reminder_fetch', false );
		$this->sitepress->set_setting( 'icl_account_email', false );
		$this->sitepress->set_setting( 'translators_management_info', false );
		$this->sitepress->set_setting( 'site_id', false );
		$this->sitepress->set_setting( 'access_key', false );
		$this->sitepress->set_setting( 'ts_site_id', false );
		$this->sitepress->set_setting( 'ts_access_key', false );

		if ( class_exists( 'TranslationProxy_Basket' ) ) {
			//Cleaning the basket
			TranslationProxy_Basket::delete_all_items_from_basket();
		}

		$sql_for_remote_rids = $this->wpdb->prepare( "FROM {$this->wpdb->prefix}icl_translation_status
								 				WHERE translation_service != 'local'
								 					AND translation_service != 0
													AND status IN ( %d, %d )", ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS );

		//Delete all translation service jobs with status "waiting for translator" or "in progress"
		$this->wpdb->query( "DELETE FROM {$this->wpdb->prefix}icl_translate_job WHERE rid IN (SELECT rid {$sql_for_remote_rids})" );

		//Delete all translation statuses with status "waiting for translator" or "in progress"
		$this->wpdb->query( "DELETE {$sql_for_remote_rids}" );

		//Cleaning up Translation Proxy settings
		$this->sitepress->set_setting( 'icl_html_status', false );
		$this->sitepress->set_setting( 'language_pairs', false );

		if ( ! $this->TranslationProxy->has_preferred_translation_service() ) {
			$this->sitepress->set_setting( 'translation_service', false );
			$this->sitepress->set_setting( 'icl_translation_projects', false );
		}

		$this->sitepress->save_settings();

		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}icl_core_status" );
		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}icl_content_status" );
		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}icl_string_status" );
		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}icl_node" );
		$this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->prefix}icl_reminders" );

		if ( $this->TranslationProxy->has_preferred_translation_service() && $translation_service_name ) {
			$confirm_message = 'The translation process with %1$s was reset.';
		} elseif ( $translation_service_name ) {
			$confirm_message = 'Your site was successfully disconnected from %1$s. Go to the translators tab to connect a new %1$s account or use a different translation service.';
		} else {
			$confirm_message = 'PRO translation has been reset.';
		}

		$response = sprintf( __( $confirm_message, 'wpml-translation-management' ), $translation_service_name );

		return $response;
	}
}