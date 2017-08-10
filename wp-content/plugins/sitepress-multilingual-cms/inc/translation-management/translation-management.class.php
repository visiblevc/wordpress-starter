<?php
/**
 * @package wpml-core
 */

if ( ! class_exists( 'WPML_Translator' ) ) {
	require_once ICL_PLUGIN_PATH . '/inc/translation-management/wpml-translator.class.php';
}

/**
 * Class TranslationManagement
 *
 * @package wpml-core
 */
class TranslationManagement {
	/**
	 * @var WPML_Translator
	 */
	private $selected_translator;
	/**
	 * @var WPML_Translator
	 */
	private $current_translator;
	private $messages                 = array();
	public  $dashboard_select         = array();
	public  $settings;
	public  $admin_texts_to_translate = array();
	private $comment_duplicator;

	/** @var WPML_Custom_Field_Setting_Factory $settings_factory */
	private $settings_factory;

	/** @var  WPML_Cache_Facotry */
	private $cache_factory;

	/**
	 * Keep list of message ID suffixes.
	 *
	 * @access private
	 */
	private $message_ids               = array( 'add_translator', 'edit_translator', 'remove_translator', 'save_notification_settings', 'cancel_jobs' );

	function __construct( ){

		global $sitepress, $wpml_cache_factory;

		$this->selected_translator     = new WPML_Translator();
		$this->selected_translator->ID = 0;
		$this->current_translator      = new WPML_Translator();
		$this->current_translator->ID  = 0;
		$this->cache_factory = $wpml_cache_factory;

		add_action( 'init', array( $this, 'init' ), 1500 );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'delete_post', array( $this, 'delete_post_actions' ), 1, 1 ); // calling *before* the Sitepress actions
		add_action( 'icl_ajx_custom_call', array( $this, 'ajax_calls' ), 10, 2 );

		// 1. on Translation Management dashboard and jobs tabs
		// 2. on Translation Management dashboard tab (called without sm parameter as default page)
		// 3. Translations queue
		if ( ( isset( $_GET[ 'sm' ] ) && ( $_GET[ 'sm' ] == 'dashboard' || $_GET[ 'sm' ] == 'jobs' ) )
		     || ( isset( $_GET[ 'page' ] ) && preg_match( '@/menu/main\.php$@', $_GET[ 'page' ] ) && ! isset( $_GET[ 'sm' ] ) )
		     || ( isset( $_GET[ 'page' ] ) && preg_match( '@/menu/translations-queue\.php$@', $_GET[ 'page' ] ) )
		) {
			@session_start();
		}
		add_filter( 'icl_additional_translators', array( $this, 'icl_additional_translators' ), 99, 3 );

		add_action( 'user_register', array( $this, 'clear_cache' ) );
		add_action( 'profile_update', array( $this, 'clear_cache' ) );
		add_action( 'delete_user', array( $this, 'clear_cache' ) );
		add_action( 'added_existing_user', array( $this, 'clear_cache' ) );
		add_action( 'remove_user_from_blog', array( $this, 'clear_cache' ) );

		add_action( 'wp_ajax_icl_tm_abort_translation', array( $this, 'abort_translation' ) );

		add_action( 'display_basket_notification', array( $this, 'display_basket_notification' ), 10, 1 );
		add_action( 'wpml_tm_send_post_jobs', array( $this, 'send_posts_jobs' ), 10, 5 );
		add_action( 'wpml_tm_send_jobs', array( $this, 'send_jobs' ), 10, 1 );
		$this->init_comments_synchronization();
		add_action('wpml_loaded', array($this, 'wpml_loaded_action'));

		/**
		 * @api
		 * @use \TranslationManagement::get_translation_job_id
		 *
		 */
		add_filter( 'wpml_translation_job_id', array( $this, 'get_translation_job_id_filter' ), 10, 2 );
		
		$this->filters_and_actions = new WPML_Translation_Management_Filters_And_Actions( $this, $sitepress );
	}

	public function wpml_loaded_action() {
		$this->load_settings_if_required();
		if ( is_admin() ) {
			add_action( 'wpml_config', array( $this, 'wpml_config_action' ), 10, 1 );
		}
	}

	public function load_settings_if_required() {
		if ( ! $this->settings ) {
			$this->settings = apply_filters( 'wpml_setting', null, 'translation-management' );
		}
	}
	
	/**
	 * @param array $args      {
	 *
	 * @type string $section
	 * @type string $key
	 * @type mixed  $value     (when used as translation action: 0: do not translate, 1: copy, 2: translate)
	 * @type bool   $read_only Options. Default to true.
	 * }
	 */
	public function wpml_config_action( $args ) {
		if ( current_user_can( 'manage_options' ) ) {
			$this->update_section_translation_setting( $args );
		}
	}

	/**
	 * @return WPML_Custom_Field_Setting_Factory
	 */
	public function settings_factory() {
		$this->settings_factory = $this->settings_factory
			? $this->settings_factory
			: new WPML_Custom_Field_Setting_Factory( $this );

		return $this->settings_factory;
	}

	/**
	 * @param WP_User         $current_user
	 * @param WPML_Translator $current_translator
	 *
	 * @return WPML_Translator
	 */
	private function init_translator_language_pairs( WP_User $current_user, WPML_Translator $current_translator ) {
		global $wpdb;
		$current_translator_language_pairs  = get_user_meta( $current_user->ID, $wpdb->prefix . 'language_pairs', true );
		$current_translator->language_pairs = $this->sanitize_language_pairs( $current_translator_language_pairs );
		if ( ! count( $current_translator->language_pairs ) ) {
			$current_translator->language_pairs = array();
		}

		return $current_translator;
	}

	/**
	 * @param $code
	 *
	 * @return bool
	 */
	private function is_valid_language_code_format( $code ) {
		return $code && is_string( $code ) && strlen( $code ) >= 2;
	}

	/**
	 * @param array $language_pairs
	 *
	 * @return array
	 */
	private function sanitize_language_pairs( $language_pairs ) {
		if(!$language_pairs || !is_array($language_pairs)) {
			$language_pairs = array();
		} else {
			$language_codes_from = array_keys( $language_pairs );
			foreach ( $language_codes_from as $code_from ) {
				$language_codes_to = array_keys( $language_pairs[ $code_from ] );

				foreach ( $language_codes_to as $code_to ) {
					if ( ! $this->is_valid_language_code_format( $code_to ) ) {
						unset( $language_pairs[ $code_from ][ $code_to ] );
					}
				}

				if ( ! $this->is_valid_language_code_format( $code_from ) || ! count( $language_pairs[ $code_from ] ) ) {
					unset( $language_pairs[ $code_from ] );
				}
			}
		}
		return $language_pairs;
	}

	/**
	 * @param array $args @see \TranslationManagement::wpml_config_action
	 */
	private function update_section_translation_setting( $args ) {
		$section   = $args[ 'section' ];
		$key       = $args[ 'key' ];
		$value     = $args[ 'value' ];
		$read_only = isset( $args[ 'read_only' ] ) ? $args[ 'read_only' ] : true;

		$section                        = preg_replace( '/-/', '_', $section );
		$config_section                 = $this->get_translation_setting_name( $section );
		$custom_config_readonly_section = $this->get_custom_readonly_translation_setting_name( $section );
		if ( isset( $this->settings[ $config_section ] ) ) {
			$this->settings[ $config_section ][ esc_sql( $key ) ] = esc_sql( $value );
			if(!isset($this->settings[ $custom_config_readonly_section ])) {
				$this->settings[ $custom_config_readonly_section ] = array();
			}
			if ( $read_only === true && ! in_array( $key, $this->settings[ $custom_config_readonly_section ] ) ) {
				$this->settings[ $custom_config_readonly_section ][ ] = esc_sql( $key );
			}
			$this->save_settings();
		}
	}

	public function init() {
		$this->init_comments_synchronization();
		$this->init_default_settings();

		WPML_Config::load_config();

		if(is_admin()) {
			if ( $GLOBALS[ 'pagenow' ] === 'edit.php' ) { // use standard WP admin notices
				add_action( 'admin_notices', array( $this, 'show_messages' ) );
			} else {                               // use custom WP admin notices
				add_action( 'icl_tm_messages', array( $this, 'show_messages' ) );
			}

			// Add duplicate identifier actions.
			$this->wpml_add_duplicate_check_actions();

			// default settings
			if ( empty( $this->settings[ 'doc_translation_method' ] ) || ! defined( 'WPML_TM_VERSION' ) ) {
				$this->settings[ 'doc_translation_method' ] = ICL_TM_TMETHOD_MANUAL;
			}
		}
	}

	public function get_settings() {
		return $this->settings;
	}

	public function wpml_add_duplicate_check_actions() {
		global $pagenow;
		if (
			'post.php' === $pagenow
			||
			( isset( $_POST['action'] ) && 'check_duplicate' === $_POST['action'] && DOING_AJAX )
		) {
			return new WPML_Translate_Independently();
		}
	}

	public function wp_loaded() {
		if ( isset( $_POST['icl_tm_action'] ) ) {
			$this->process_request( $_POST );
		} elseif ( isset( $_GET['icl_tm_action'] ) ) {
			$this->process_request( $_GET );
		}
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( ! defined( 'WPML_TM_FOLDER' ) ) {
			return;
		}
		$valid_hook = 'wpml_page_' . WPML_TM_FOLDER . '/menu/main';
		$submenu    = filter_input( INPUT_GET, 'sm' );
		if ( ! defined( 'WPML_TM_FOLDER' ) || ( $hook != $valid_hook && ! $submenu ) ) {
			return;
		}
		if ( ! $submenu ) {
			$submenu = 'dashboard';
		}

		switch ( $submenu ) {
			case 'jobs':
				wp_register_style( 'translation-jobs', WPML_TM_URL . '/res/css/translation-jobs.css', array(), WPML_TM_VERSION );

				wp_register_script( 'headjs', '//cdnjs.cloudflare.com/ajax/libs/headjs/1.0.3/head.min.js', array(), false, true );
				wp_register_script( 'translation-jobs-main', WPML_TM_URL . '/res/js/listing/main.js', array( 'jquery', 'backbone', 'headjs' ), WPML_TM_VERSION, true );

				$l10n = array(
					'TJ_JS' => array(
						'listing_lib_path' => WPML_TM_URL . '/res/js/listing/',
					),
				);

				wp_enqueue_style( 'translation-jobs' );

				wp_localize_script( 'translation-jobs-main', 'Translation_Jobs_settings', $l10n );
				wp_enqueue_script( 'translation-jobs-main' );

				break;
			case 'translators':
				wp_register_style( 'translation-translators', WPML_TM_URL . '/res/css/translation-translators.css', array(), WPML_TM_VERSION );
				wp_enqueue_style( 'translation-translators' );
				break;
			default:
				wp_register_style( 'translation-dashboard', WPML_TM_URL . '/res/css/translation-dashboard.css', array(), WPML_TM_VERSION );
				wp_enqueue_style( 'translation-dashboard' );
		}
	}

	public static function get_batch_name( $batch_id ) {
		$batch_data = self::get_batch_data( $batch_id );
		if ( ! $batch_data || ! isset( $batch_data->batch_name ) ) {
			$batch_name = __( 'No Batch', 'wpml-translation-management' );
		} else {
			$batch_name = $batch_data->batch_name;
		}

		return $batch_name;
	}

	public static function get_batch_url( $batch_id ) {
		$batch_data = self::get_batch_data( $batch_id );
		$batch_url  = '';
		if ( $batch_data && isset( $batch_data->tp_id ) && $batch_data->tp_id != 0 ) {
			$batch_url = OTG_TRANSLATION_PROXY_URL . "/projects/{$batch_data->tp_id}/external";
		}

		return $batch_url;
	}

	public static function get_batch_last_update( $batch_id ) {
		$batch_data = self::get_batch_data( $batch_id );

		return $batch_data ? $batch_data->last_update : false;
	}

	public static function get_batch_tp_id( $batch_id ) {
		$batch_data = self::get_batch_data( $batch_id );

		return $batch_data ? $batch_data->tp_id : false;
	}

	public static function get_batch_data( $batch_id ) {
		$cache_key   = $batch_id;
		$cache_group = 'get_batch_data';
		$cache_found = false;

		$batch_data = wp_cache_get( $cache_key, $cache_group, false, $cache_found );

		if ( $cache_found ) {
			return $batch_data;
		}

		global $wpdb;
		$batch_data_sql      = "SELECT * FROM {$wpdb->prefix}icl_translation_batches WHERE id=%d";
		$batch_data_prepared = $wpdb->prepare( $batch_data_sql, array( $batch_id ) );
		$batch_data          = $wpdb->get_row( $batch_data_prepared );

		wp_cache_set( $cache_key, $batch_data, $cache_group );

		return $batch_data;
	}

	function save_settings() {
		global $sitepress;

		//@todo: [WPML 3.2.1] refactor this, make it readable.
		$icl_settings[ 'translation-management' ] = $this->settings;
		$cpt_sync_option                          = $sitepress->get_setting( 'custom_posts_sync_option', array() );
		$cpt_sync_option                          = (bool) $cpt_sync_option === false ? $sitepress->get_setting( 'custom-types_sync_option', array() ) : $cpt_sync_option;

		if ( ! isset( $icl_settings[ 'custom_posts_sync_option' ] ) ) {
			$icl_settings[ 'custom_posts_sync_option' ] = array( );
		}

		foreach ( $cpt_sync_option as $k => $v ) {
			$icl_settings[ 'custom_posts_sync_option' ][ $k ] = $v;
		}
		$icl_settings[ 'translation-management' ][ 'custom-types_readonly_config' ] = isset( $icl_settings[ 'translation-management' ][ 'custom-types_readonly_config' ] ) ? $icl_settings[ 'translation-management' ][ 'custom-types_readonly_config' ] : array();
		foreach ( $icl_settings[ 'translation-management' ][ 'custom-types_readonly_config' ] as $k => $v ) {
			$icl_settings[ 'custom_posts_sync_option' ][ $k ] = $v;
		}
		$sitepress->set_setting('translation-management', $icl_settings[ 'translation-management' ], true);
		$sitepress->set_setting('custom_posts_sync_option', $icl_settings[ 'custom_posts_sync_option' ], true);
		$this->settings = $sitepress->get_setting( 'translation-management' );
	}

	/**
	 * @return string[]
	 */
	public function initial_custom_field_translate_states() {
		global $wpdb;

		$this->initial_term_custom_field_translate_states();

		return $this->initial_translation_states( $wpdb->postmeta );
	}

	/**
	 * @return string[]
	 */
	public function initial_term_custom_field_translate_states() {
		global $wpdb;

		return ! empty( $wpdb->termmeta )
			? $this->initial_translation_states( $wpdb->termmeta)
			: array();
	}

	function process_request($data){
		$action = $data['icl_tm_action'];
		$data = stripslashes_deep($data);
		switch( $action ){
			case 'add_translator':
				if ( wp_verify_nonce( $data[ 'add_translator_nonce' ], 'add_translator' ) ) {
					$this->icl_tm_add_translator($data);
					wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=translators' );
					exit;
				}
				break;
			case 'edit_translator':
				$this->icl_tm_edit_translator( $data );
				wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=translators' );
				exit;
				break;
			case 'remove_translator':
				if ( wp_verify_nonce( $data[ 'remove_translator_nonce' ], 'remove_translator' ) ) {
					$this->icl_tm_remove_translator($data);
					wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=translators');
					exit;
				}
				break;
			case 'edit':
				$this->selected_translator->ID = intval( $data[ 'user_id' ] );
				break;
			case 'dashboard_filter':
				$_SESSION[ 'translation_dashboard_filter' ] = $data[ 'filter' ];
				wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=dashboard' );
				break;
			case 'sort':
				if ( isset( $data[ 'sort_by' ] ) ) {
					$_SESSION[ 'translation_dashboard_filter' ][ 'sort_by' ] = $data[ 'sort_by' ];
				}
				if ( isset( $data[ 'sort_order' ] ) ) {
					$_SESSION[ 'translation_dashboard_filter' ][ 'sort_order' ] = $data[ 'sort_order' ];
				}
				break;
			case 'reset_filters':
				unset( $_SESSION[ 'translation_dashboard_filter' ] );
				break;
			case 'add_jobs':
				if ( isset( $data[ 'iclnonce' ] ) && wp_verify_nonce( $data[ 'iclnonce' ], 'pro-translation-icl' ) ) {
					TranslationProxy_Basket::add_posts_to_basket( $data );
					do_action( 'wpml_tm_add_to_basket', $data );
				}
				break;
			case 'jobs_filter':
				$_SESSION[ 'translation_jobs_filter' ] = $data[ 'filter' ];
				wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=jobs' );
				break;
			case 'ujobs_filter':
				$_SESSION[ 'translation_ujobs_filter' ] = $data[ 'filter' ];
				wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php' );
				break;
			case 'save_translation':
				if ( ! empty( $data[ 'resign' ] ) ) {
					$this->resign_translator( $data[ 'job_id' ] );
					wp_safe_redirect( admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&resigned=' . $data[ 'job_id' ] ) );
					exit;
				} else {
					do_action( 'wpml_save_translation_data', $data );
				}
				break;
			case 'save_notification_settings':
				$this->icl_tm_save_notification_settings( $data );
				break;
			case 'create_job':
				global $current_user;
				if ( ! isset( $this->current_translator->ID ) && isset( $current_user->ID ) ) {
					$this->current_translator->ID = $current_user->ID;
				}
				$data[ 'translator' ] = $this->current_translator->ID;

				$job_ids = $this->send_jobs( $data );
				wp_safe_redirect( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . array_pop( $job_ids ) );
				break;
			case 'cancel_jobs':
				$this->icl_tm_cancel_jobs( $data );
				break;
		}
	}

	function ajax_calls( $call, $data ) {
		global $wpdb, $sitepress;
		switch ( $call ) {
			case 'assign_translator':

				$translator_data        = TranslationProxy_Service::get_translator_data_from_wpml( $data[ 'translator_id' ] );
				$service_id             = $translator_data[ 'translation_service' ];
				$translator_id          = $translator_data[ 'translator_id' ];
				$assign_translation_job = $this->assign_translation_job( $data[ 'job_id' ], $translator_id, $service_id, $data['job_type'] );
				if ( $assign_translation_job ) {
					$translator_edit_link = '';
					if ( $translator_id ) {
						if ( $service_id == TranslationProxy::get_current_service_id() ) {
							$job = $this->get_translation_job( $data[ 'job_id' ] );
							/** @var $ICL_Pro_Translation WPML_Pro_Translation */
							global $ICL_Pro_Translation;
							$ICL_Pro_Translation->send_post( $job->original_doc_id, array( $job->language_code ), $translator_id, $data[ 'job_id' ] );
							$project = TranslationProxy::get_current_project();

							$translator_edit_link =
								TranslationProxy_Popup::get_link( $project->translator_contact_iframe_url( $translator_id ), array( 'title' => __( 'Contact the translator', 'sitepress' ), 'unload_cb' => 'icl_thickbox_refresh' ) )
								. esc_html( TranslationProxy_Translator::get_translator_name( $translator_id ) )
								. "</a> ($project->service->name)";
						} else {
							$translator_edit_link =
								'<a href="'
								. TranslationManagement::get_translator_edit_url( $data[ 'translator_id' ] )
								. '">'
								. esc_html( $wpdb->get_var( $wpdb->prepare( "SELECT display_name FROM {$wpdb->users} WHERE ID=%d", $data[ 'translator_id' ] ) ) )
								. '</a>';
						}
					}
					echo wp_json_encode( array( 'error' => 0, 'message' => $translator_edit_link, 'status' => TranslationManagement::status2text( ICL_TM_WAITING_FOR_TRANSLATOR ), 'service' => $service_id ) );
				} else {
					echo wp_json_encode( array( 'error' => 1 ) );
				}
				break;
			case 'icl_cf_translation':
			case 'icl_tcf_translation':
				if ( ! empty( $data['cf'] ) ) {
					foreach ( $data['cf'] as $k => $v ) {
						$cft[ base64_decode( $k ) ] = $v;
					}
					if ( isset( $cft ) ) {
						$this->settings[ $call === 'icl_tcf_translation'
							? WPML_TERM_META_SETTING_INDEX_PLURAL
							: WPML_POST_META_SETTING_INDEX_PLURAL ] = $cft;
						$this->save_settings();
					}
				}
				echo '1|';
				break;
			case 'icl_doc_translation_method':
				$this->settings[ 'doc_translation_method' ] = intval( $data[ 't_method' ] );
				$sitepress->set_setting( 'doc_translation_method', $this->settings[ 'doc_translation_method' ] );
				$sitepress->save_settings( array( 'hide_how_to_translate' => empty( $data[ 'how_to_translate' ] ) ) );
				if ( isset( $data[ 'tm_block_retranslating_terms' ] ) ) {
					$sitepress->set_setting( 'tm_block_retranslating_terms', $data[ 'tm_block_retranslating_terms' ] );
				} else {
					$sitepress->set_setting( 'tm_block_retranslating_terms', '' );
				}
				if ( isset( $data[ 'tm_block_retranslating_terms' ] ) ) {
					$sitepress->set_setting( 'tm_block_retranslating_terms', $data[ 'tm_block_retranslating_terms' ] );
				} else {
					$sitepress->set_setting( 'tm_block_retranslating_terms', '' );
				}
				$this->save_settings();
				echo '1|';
				break;
			case 'reset_duplication':
				$this->reset_duplicate_flag( $_POST[ 'post_id' ] );
				break;
			case 'set_duplication':
				$new_id = $this->set_duplicate( $_POST[ 'wpml_original_post_id' ], $_POST[ 'post_lang' ] );
				wp_send_json_success( array( 'id' => $new_id ) );
				break;
			case 'make_duplicates':
				$mdata[ 'iclpost' ] = array( $data[ 'post_id' ] );
				$langs              = explode( ',', $data[ 'langs' ] );
				foreach ( $langs as $lang ) {
					$mdata[ 'duplicate_to' ][ $lang ] = 1;
				}
				$this->make_duplicates( $mdata );
				do_action( 'wpml_new_duplicated_terms', (array) $mdata[ 'iclpost' ], false );
				break;
		}
	}

	/**
	 * @param $element_type_full
	 *
	 * @return mixed
	 */
	public function get_element_prefix( $element_type_full ) {
		$element_type_parts = explode( '_', $element_type_full );
		$element_type       = $element_type_parts[ 0 ];

		return $element_type;
	}

	/**
	 * @param int $job_id
	 *
	 * @return mixed
	 */
	public function get_element_type_prefix_from_job_id( $job_id ) {
		$job = $this->get_translation_job( $job_id );

		return $job ? $this->get_element_type_prefix_from_job( $job ) : false;
	}

	/**
	 * @param $job
	 *
	 * @return mixed
	 */
	public function get_element_type_prefix_from_job( $job ) {
		if ( is_object( $job ) ) {
			$element_type        = $this->get_element_type( $job->trid );
			$element_type_prefix = $this->get_element_prefix( $element_type );
		} else {
			$element_type_prefix = false;
		}

		return $element_type_prefix;
	}

	/**
	 * Display admin notices.
	 */
	public function show_messages() {
		foreach ( $this->message_ids as $message_suffix ) {
			$message_id = 'icl_tm_message_' . $message_suffix;
			$message = ICL_AdminNotifier::get_message( $message_id );
			if ( isset( $message[ 'type' ], $message[ 'text' ] ) ) {
				echo '<div class="' . esc_attr( $message[ 'type' ] ) . ' below-h2"><p>' . esc_html( $message[ 'text' ] ) . '</p></div>';
				ICL_AdminNotifier::remove_message( $message_id );
			}
		}
	}

	/* TRANSLATORS */
	/* ******************************************************************************************** */
	function add_translator( $user_id, $language_pairs ) {
		global $wpdb;

		$user = new WP_User( $user_id );
		$user->add_cap( 'translate' );

		$um = get_user_meta( $user_id, $wpdb->prefix . 'language_pairs', true );
		if ( ! empty( $um ) ) {
			foreach ( $um as $fr => $to ) {
				if ( isset( $language_pairs[ $fr ] ) ) {
					$language_pairs[ $fr ] = array_merge( $language_pairs[ $fr ], $to );
				}
			}
		}

		update_user_meta( $user_id, $wpdb->prefix . 'language_pairs', $language_pairs );
		$this->clear_cache();
	}

	function edit_translator( $user_id, $language_pairs ) {
		global $wpdb;
		$result = false;
		$_user = new WP_User( $user_id );
		if ( empty( $language_pairs ) ) {
			$this->remove_translator( $user_id );
			if ( $user_id == $this->current_translator->ID ) {
				$this->current_translator = null;
			}
		} else {
			if ( ! $_user->has_cap( 'translate' ) ) {
				$_user->add_cap( 'translate' );
			}
			update_user_meta( $user_id, $wpdb->prefix . 'language_pairs', $language_pairs );
			$result = true;

			if ( $user_id == $this->current_translator->ID ) {
				$this->current_translator->language_pairs = get_user_meta( $user_id, $wpdb->prefix . 'language_pairs', true );
			}

		}
		return $result;
	}

	function remove_translator( $user_id ) {
		global $wpdb;
		$user = new WP_User( $user_id );
		$user->remove_cap( 'translate' );
		delete_user_meta( $user_id, $wpdb->prefix . 'language_pairs' );
		$this->clear_cache();
	}

	function set_default_translator( $id, $from, $to, $type = 'local' ) {
		global $sitepress, $sitepress_settings;
		$iclsettings[ 'default_translators' ]                 = isset( $sitepress_settings[ 'default_translators' ] ) ? $sitepress_settings[ 'default_translators' ] : array();
		$iclsettings[ 'default_translators' ][ $from ][ $to ] = array( 'id' => $id, 'type' => $type );
		$sitepress->save_settings( $iclsettings );
	}

	function get_default_translator( $from, $to ) {
		global $sitepress_settings;
		if ( isset( $sitepress_settings[ 'default_translators' ][ $from ][ $to ] ) ) {
			$dt = $sitepress_settings[ 'default_translators' ][ $from ][ $to ];
		} else {
			$dt = array();
		}

		return $dt;
	}

	public function get_blog_not_translators() {
		global $wpdb;

		$args = array(
			'fields'     => array( 'user_login', 'display_name', 'ID' ),
			'meta_query' => array(
				array(
					'key'     => "{$wpdb->prefix}capabilities",
					'value'   => 'translate',
					'compare' => 'NOT LIKE'
				),
			)
		);
		$users = new WP_User_Query( $args );

		return $users->get_results();
	}

	public static function get_blog_translators( $args = array() ) {
		global $wpdb;
		$args_default = array( 'from' => false, 'to' => false );
		extract( $args_default );
		extract( $args, EXTR_OVERWRITE );

		$cached_translators = get_option( $wpdb->prefix . 'icl_translators_cached', array() );

		if ( empty( $cached_translators ) ) {
			$sql = "SELECT u.ID FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.id=m.user_id AND m.meta_key = '{$wpdb->prefix}language_pairs' ORDER BY u.display_name";
			$res = $wpdb->get_results( $sql );
			update_option( $wpdb->prefix . 'icl_translators_cached', $res );
		} else {
			$res = $cached_translators;
		}

		$users = array();
		foreach ( $res as $row ) {
			$user                 = new WP_User( $row->ID );
			$user->language_pairs = (array) get_user_meta( $row->ID, $wpdb->prefix . 'language_pairs', true );
			if ( ! empty( $from ) && ! empty( $to ) && ( ! isset( $user->language_pairs[ $from ][ $to ] ) || ! $user->language_pairs[ $from ][ $to ] ) ) {
				continue;
			}
			if ( $user->has_cap( 'translate' ) ) {
				$users[ ] = $user;
			}
		}

		return apply_filters( 'blog_translators', $users, $args );
	}

	/**
	 * @return WPML_Translator
	 */
	function get_selected_translator() {
		global $wpdb;
		if ( $this->selected_translator && $this->selected_translator->ID ) {
			$user                                      = new WP_User( $this->selected_translator->ID );
			$this->selected_translator->display_name   = $user->data->display_name;
			$this->selected_translator->user_login     = $user->data->user_login;
			$this->selected_translator->language_pairs = get_user_meta( $this->selected_translator->ID, $wpdb->prefix . 'language_pairs', true );
		} else {
			$this->selected_translator->ID = 0;
		}

		return $this->selected_translator;
	}

	/**
	 * @return WPML_Translator
	 */
	function get_current_translator() {
		$current_translator = $this->current_translator;
		$current_translator_is_set = $current_translator && $current_translator->ID > 0 && $current_translator->language_pairs;

		if ( ! $current_translator_is_set ) {
			$this->init_current_translator();
		}

		return $this->current_translator;
	}

	public static function get_translator_edit_url( $translator_id ) {
		$url = '';
		if ( ! empty( $translator_id ) ) {
			$url = 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&amp;sm=translators&icl_tm_action=edit&amp;user_id=' . $translator_id;
		}

		return $url;
	}

	/* HOOKS */
	/* ******************************************************************************************** */

	function make_duplicates( $data ) {
		foreach ( $data[ 'iclpost' ] as $master_post_id ) {
			foreach ( $data[ 'duplicate_to' ] as $lang => $one ) {
				$this->make_duplicate( $master_post_id, $lang );
			}
		}
	}

	function make_duplicate( $master_post_id, $lang ) {
		global $sitepress;

		return $sitepress->make_duplicate( $master_post_id, $lang );
	}

	function make_duplicates_all( $master_post_id ) {
		global $sitepress;

		$master_post = get_post( $master_post_id );
		if ( $master_post->post_status == 'auto-draft' || $master_post->post_type == 'revision' ) {
			return;
		}

		$language_details_original = $sitepress->get_element_language_details( $master_post_id, 'post_' . $master_post->post_type );

		if ( ! $language_details_original ) {
			return;
		}

		$data[ 'iclpost' ] = array( $master_post_id );
		foreach ( $sitepress->get_active_languages() as $lang => $details ) {
			if ( $lang != $language_details_original->language_code ) {
				$data[ 'duplicate_to' ][ $lang ] = 1;
			}
		}

		$this->make_duplicates( $data );
	}

	function reset_duplicate_flag( $post_id ) {
		global $sitepress;

		$post = get_post( $post_id );

		$trid         = $sitepress->get_element_trid( $post_id, 'post_' . $post->post_type );
		$translations = $sitepress->get_element_translations( $trid, 'post_' . $post->post_type );

		foreach ( $translations as $tr ) {
			if ( $tr->element_id == $post_id ) {
				$this->update_translation_status( array(
					                                  'translation_id' => $tr->translation_id,
					                                  'status'         => ICL_TM_COMPLETE
				                                  ) );
			}
		}

		delete_post_meta( $post_id, '_icl_lang_duplicate_of' );
	}

	function set_duplicate( $master_post_id, $post_lang ) {
		$new_id = 0;
		if ( $master_post_id && $post_lang ) {
			$new_id = $this->make_duplicate( $master_post_id, $post_lang );
		}

		return $new_id;
	}

	function duplication_delete_comment( $comment_id ) {
		global $wpdb;

		$original_comment = (bool) get_comment_meta( $comment_id, '_icl_duplicate_of', true ) === false;
		if ( $original_comment ) {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id
														   FROM {$wpdb->commentmeta}
														   WHERE meta_key='_icl_duplicate_of'
														   AND meta_value=%d", $comment_id ) );
			foreach ( $duplicates as $dup ) {
				wp_delete_comment( $dup, true );
			}
		}
	}

	function duplication_edit_comment( $comment_id ) {
		global $wpdb;

		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_ID=%d", $comment_id ), ARRAY_A );
		unset( $comment[ 'comment_ID' ], $comment[ 'comment_post_ID' ] );

		$comment_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->commentmeta} WHERE comment_id=%d AND meta_key <> '_icl_duplicate_of'", $comment_id ) );

		$original_comment = get_comment_meta( $comment_id, '_icl_duplicate_of', true );
		if ( $original_comment ) {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $original_comment ) );
			$duplicates = array( $original_comment ) + array_diff( $duplicates, array( $comment_id ) );
		} else {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $comment_id ) );
		}

		if ( ! empty( $duplicates ) ) {
			foreach ( $duplicates as $dup ) {

				$wpdb->update( $wpdb->comments, $comment, array( 'comment_ID' => $dup ) );

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id=%d AND meta_key <> '_icl_duplicate_of'", $dup ) );

				if ( $comment_meta ) {
					foreach ( $comment_meta as $key => $value ) {
						wp_cache_delete( $dup, 'comment_meta' );
						update_comment_meta( $dup, $value->meta_key, $value->meta_value );
					}
				}
			}
		}
	}

	function duplication_status_comment( $comment_id, $comment_status ) {
		global $wpdb;

		static $_avoid_8_loop;

		if ( isset( $_avoid_8_loop ) ) {
			return;
		}
		$_avoid_8_loop = true;

		$original_comment = get_comment_meta( $comment_id, '_icl_duplicate_of', true );
		if ( $original_comment ) {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $original_comment ) );
			$duplicates = array( $original_comment ) + array_diff( $duplicates, array( $comment_id ) );
		} else {
			$duplicates = $wpdb->get_col( $wpdb->prepare( "SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $comment_id ) );
		}

		if ( ! empty( $duplicates ) ) {
			foreach ( $duplicates as $duplicate ) {
				wp_set_comment_status( $duplicate, $comment_status );
			}
		}

		unset( $_avoid_8_loop );
	}

	function duplication_insert_comment( $comment_id ) {
		global $wpdb, $sitepress;

		$duplicator = $this->get_comment_duplicator();

		$comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_ID=%d", $comment_id ), ARRAY_A );

		// loop duplicate posts, add new comment
		$post_id = $comment[ 'comment_post_ID' ];

		// if this is a duplicate post
		$duplicate_of = get_post_meta( $post_id, '_icl_lang_duplicate_of', true );
		if ( $duplicate_of ) {
			$post_duplicates = $sitepress->get_duplicates( $duplicate_of );
			$duplicator->move_to_original( $duplicate_of, $post_duplicates, $comment );
			$this->duplication_insert_comment( $comment_id );

			return;
		} else {
			$post_duplicates = $sitepress->get_duplicates( $post_id );
		}
		unset( $comment[ 'comment_ID' ], $comment[ 'comment_post_ID' ] );
		foreach ( $post_duplicates as $lang => $dup_id ) {
			$comment[ 'comment_post_ID' ] = $dup_id;

			if ( $comment[ 'comment_parent' ] ) {
				$translated_parent = $duplicator->get_correct_parent( $comment, $dup_id );
				if ( ! $translated_parent ) {
					$this->duplication_insert_comment( $comment[ 'comment_parent' ] );
					$translated_parent = $duplicator->get_correct_parent( $comment, $dup_id );
				}
				$comment[ 'comment_parent' ] = $translated_parent;
			}

			$duplicator->insert_duplicated_comment( $comment, $dup_id, $comment_id );
		}
	}

	private function get_comment_duplicator() {

		if ( ! $this->comment_duplicator ) {
			require ICL_PLUGIN_PATH . '/inc/post-translation/wpml-comment-duplication.class.php';
			$this->comment_duplicator = new WPML_Comment_Duplication();
		}

		return $this->comment_duplicator;
	}

	function delete_post_actions( $post_id ) {
		global $wpdb;
		$post_type = $wpdb->get_var( $wpdb->prepare( "SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $post_id ) );
		if ( ! empty( $post_type ) ) {
			$translation_id = $wpdb->get_var( $wpdb->prepare( "SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $post_id, 'post_' . $post_type ) );
			if ( $translation_id ) {
				$rid = $wpdb->get_var( $wpdb->prepare( "SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id ) );
				if ( $rid ) {
					$jobs = $wpdb->get_col( $wpdb->prepare( "SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d", $rid ) );
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d", $rid ) );
					if ( ! empty( $jobs ) ) {
						$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id IN (" . wpml_prepare_in( $jobs, '%d' ) . ")" );
					}
				}
			}
		}
	}

	/* TRANSLATIONS */
	/* ******************************************************************************************** */
	/**
	 * calculate post md5
	 *
	 * @param object|int $post
	 *
	 * @return string
	 */
	function post_md5( $post ) {

		return apply_filters( 'wpml_tm_element_md5', $post );
	}

	function get_element_translation( $element_id, $language, $element_type = 'post_post' ) {
		global $wpdb, $sitepress;
		$trid        = $sitepress->get_element_trid( $element_id, $element_type );
		$translation = array();
		if ( $trid ) {
			$translation = $wpdb->get_row( $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}icl_translations tr
				JOIN {$wpdb->prefix}icl_translation_status ts ON tr.translation_id = ts.translation_id
				WHERE tr.trid=%d AND tr.language_code= %s
			", $trid, $language ) );
		}

		return $translation;
	}

	function get_element_translations( $element_id, $element_type = 'post_post', $service = false ) {
		global $wpdb, $sitepress;
		$trid         = $sitepress->get_element_trid( $element_id, $element_type );
		$translations = array();
		if ( $trid ) {
			$service      = $service ? $wpdb->prepare( " AND translation_service = %s ", $service ) : '';
			$translations = $wpdb->get_results( $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}icl_translations tr
				JOIN {$wpdb->prefix}icl_translation_status ts ON tr.translation_id = ts.translation_id
				WHERE tr.trid=%d {$service}
			", $trid ) );
			foreach ( $translations as $k => $v ) {
				$translations[ $v->language_code ] = $v;
				unset( $translations[ $k ] );
			}
		}

		return $translations;
	}

	/**
	 * returns icon file name according to status code
	 *
	 * @param int $status
	 * @param int $needs_update
	 *
	 * @return string
	 */
	public function status2img_filename( $status, $needs_update = 0 ) {
		if ( $needs_update ) {
			$img_file = 'needs-update.png';
		} else {
			switch ( $status ) {
				case ICL_TM_NOT_TRANSLATED:
					$img_file = 'not-translated.png';
					break;
				case ICL_TM_WAITING_FOR_TRANSLATOR:
					$img_file = 'in-progress.png';
					break;
				case ICL_TM_IN_PROGRESS:
					$img_file = 'in-progress.png';
					break;
				case ICL_TM_IN_BASKET:
					$img_file = 'in-basket.png';
					break;
				case ICL_TM_NEEDS_UPDATE:
					$img_file = 'needs-update.png';
					break;
				case ICL_TM_DUPLICATE:
					$img_file = 'copy.png';
					break;
				case ICL_TM_COMPLETE:
					$img_file = 'complete.png';
					break;
				default:
					$img_file = '';
			}
		}

		return $img_file;
	}

	/**
	 * returns icon class according to status code
	 *
	 * @param int $status
	 * @param int $needs_update
	 *
	 * @return string
	 */
	public function status2icon_class( $status, $needs_update = 0 ) {
		if ( $needs_update ) {
			$icon_class = 'otgs-ico-needs-update';
		} else {
			switch ( $status ) {
				case ICL_TM_NOT_TRANSLATED:
					$icon_class = 'otgs-ico-not-translated';
					break;
				case ICL_TM_WAITING_FOR_TRANSLATOR:
					$icon_class = 'otgs-ico-waiting';
					break;
				case ICL_TM_IN_PROGRESS:
					$icon_class = 'otgs-ico-in-progress';
					break;
				case ICL_TM_IN_BASKET:
					$icon_class = 'otgs-ico-basket';
					break;
				case ICL_TM_NEEDS_UPDATE:
					$icon_class = 'otgs-ico-needs-update';
					break;
				case ICL_TM_DUPLICATE:
					$icon_class = 'otgs-ico-duplicate';
					break;
				case ICL_TM_COMPLETE:
					$icon_class = 'otgs-ico-translated';
					break;
				default:
					$icon_class = 'otgs-ico-not-translated';
			}
		}

		return $icon_class;
	}

	public static function status2text( $status ) {
		switch ( $status ) {
			case ICL_TM_NOT_TRANSLATED:
				$text = __( 'Not translated', 'sitepress' );
				break;
			case ICL_TM_WAITING_FOR_TRANSLATOR:
				$text = __( 'Waiting for translator', 'sitepress' );
				break;
			case ICL_TM_IN_PROGRESS:
				$text = __( 'In progress', 'sitepress' );
				break;
			case ICL_TM_NEEDS_UPDATE:
				$text = __( 'Needs update', 'sitepress' );
				break;
			case ICL_TM_DUPLICATE:
				$text = __( 'Duplicate', 'sitepress' );
				break;
			case ICL_TM_COMPLETE:
				$text = __( 'Complete', 'sitepress' );
				break;
			default:
				$text = '';
		}

		return $text;
	}

	public function decode_field_data( $data, $format ) {
		if ( $format == 'base64' ) {
			$data = base64_decode( $data );
		} elseif ( $format == 'csv_base64' ) {
			$exp = explode( ',', $data );
			foreach ( $exp as $k => $e ) {
				$exp[ $k ] = base64_decode( trim( $e, '"' ) );
			}
			$data = $exp;
		}

		return $data;
	}

	/**
	 * create translation package
	 *
	 * @param object|int $post
	 *
	 * @return array
	 */
	function create_translation_package( $post ) {

		return apply_filters('wpml_post_to_translation_package', false, $post);
	}

	function messages_by_type( $type ) {
		$messages = $this->messages;

		$result = false;
		foreach ( $messages as $message ) {
			if ( $type === false || ( ! empty( $message[ 'type' ] ) && $message[ 'type' ] == $type ) ) {
				$result[ ] = $message;
			}
		}

		return $result;
	}

	function add_message( $message ) {
		$this->messages[ ] = $message;
		$this->messages    = array_unique( $this->messages, SORT_REGULAR );
	}

	/**
	 * add/update icl_translation_status record
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	function update_translation_status( $data ) {
		global $wpdb;
		if ( ! isset( $data[ 'translation_id' ] ) ) {
			return array( false, false );
		}
		$rid = $wpdb->get_var( $wpdb->prepare( "	SELECT rid
													FROM {$wpdb->prefix}icl_translation_status
													WHERE translation_id = %d",
			$data['translation_id'] ) );
		$update = (bool) $rid;
		if ( true === $update ) {
			$data_where = array( 'rid' => $rid );
			$wpdb->update( $wpdb->prefix . 'icl_translation_status', $data, $data_where );
		} else {
			$wpdb->insert( $wpdb->prefix . 'icl_translation_status', $data );
			$rid    = $wpdb->insert_id;
		}
		$data[ 'rid' ] = $rid;

		do_action( 'wpml_updated_translation_status', $data );

		return array( $rid, $update );
	}

	/* TRANSLATION JOBS */
	/* ******************************************************************************************** */

	function send_jobs( $data ) {
		global $wpdb, $sitepress;

		if ( ! isset( $data[ 'tr_action' ] ) && isset( $data[ 'translate_to' ] ) ) { //adapt new format
			$data[ 'tr_action' ] = $data[ 'translate_to' ];
			unset( $data[ 'translate_to' ] );
		}

		if ( isset( $data[ 'iclpost' ] ) ) { //adapt new format
			$data[ 'posts_to_translate' ] = $data[ 'iclpost' ];
			unset( $data[ 'iclpost' ] );
		}
		if ( isset( $data[ 'post' ] ) ) { //adapt new format
			$data[ 'posts_to_translate' ] = $data[ 'post' ];
			unset( $data[ 'post' ] );
		}

		$batch_name = isset( $data[ 'batch_name' ] ) ? $data[ 'batch_name' ] : false;

		$translate_from = TranslationProxy_Basket::get_source_language();
		$data_default   = array(
			'translate_from' => $translate_from
		);
		extract( $data_default );
		extract( $data, EXTR_OVERWRITE );

		// no language selected ?
		if ( ! isset( $tr_action ) || empty( $tr_action ) ) {
			$this->dashboard_select = $data; // prepopulate dashboard
			return false;
		}
		// no post selected ?
		if ( ! isset( $posts_to_translate ) || empty( $posts_to_translate ) ) {
			$this->dashboard_select = $data; // pre-populate dashboard
			return false;
		}

		$selected_posts       = $posts_to_translate;
		$selected_translators = isset( $translators ) ? $translators : array();
		$selected_languages   = $tr_action;
		$job_ids              = array();

		$element_type_prefix = 'post';
		if ( isset( $data[ 'element_type_prefix' ] ) ) {
			$element_type_prefix = $data[ 'element_type_prefix' ];
		}

		foreach ( $selected_posts as $post_id ) {
			$post = $this->get_post( $post_id, $element_type_prefix );
			if ( ! $post ) {
				continue;
			}

			$element_type        = $element_type_prefix . '_' . $post->post_type;
			$post_trid           = $sitepress->get_element_trid( $post_id, $element_type );
			$post_translations   = $sitepress->get_element_translations( $post_trid, $element_type );
			$md5                 = $this->post_md5( $post );
			$translation_package = $this->create_translation_package( $post );

			foreach ( $selected_languages as $lang => $action ) {

				// making this a duplicate?
				if ( $action == 2 ) {
					// don't send documents that are in progress
					$current_translation_status = $this->get_element_translation( $post_id, $lang, $element_type );
					if ( $current_translation_status && $current_translation_status->status == ICL_TM_IN_PROGRESS ) {
						continue;
					}

					$job_ids[ ] = $this->make_duplicate( $post_id, $lang );
				} elseif ( $action == 1 ) {

					if ( empty( $post_translations[ $lang ] ) ) {
						$translation_id = $sitepress->set_element_language_details( null, $element_type, $post_trid, $lang, $translate_from );
					} else {
						$translation_id = $post_translations[ $lang ]->translation_id;
						$sitepress->set_element_language_details( $post_translations[ $lang ]->element_id, $element_type, $post_trid, $lang, $translate_from );
					}

					// don't send documents that are in progress
					// don't send documents that are already translated and don't need update
					$current_translation_status = $this->get_element_translation( $post_id, $lang, $element_type );

					if ( $current_translation_status && $current_translation_status->status == ICL_TM_IN_PROGRESS ) {
						continue;
					}

					$_status = ICL_TM_WAITING_FOR_TRANSLATOR;

					if ( isset( $selected_translators[ $lang ] ) ) {
						$translator = $selected_translators[ $lang ];
					} else {
						$translator = get_current_user_id(); // returns current user id or 0 if user not logged in
					}
					$translation_data = TranslationProxy_Service::get_translator_data_from_wpml( $translator );

					$translation_service = $translation_data[ 'translation_service' ];

					$translator_id = $translation_data[ 'translator_id' ];

					// set as default translator
					if ( $translator_id > 0 ) {
						$this->set_default_translator( $translator_id, $translate_from, $lang, $translation_service );
					}

					// add translation_status record
					$data = array(
						'translation_id'      => $translation_id,
						'status'              => $_status,
						'translator_id'       => $translator_id,
						'needs_update'        => 0,
						'md5'                 => $md5,
						'translation_service' => $translation_service,
						'translation_package' => serialize( $translation_package ),
						'batch_id'            => TranslationProxy_Batch::update_translation_batch( $batch_name ),
					);

					$_prevstate = $wpdb->get_row( $wpdb->prepare( "
						SELECT status, translator_id, needs_update, md5, translation_service, translation_package, timestamp, links_fixed
						FROM {$wpdb->prefix}icl_translation_status
						WHERE translation_id = %d
					", $translation_id ), ARRAY_A );
					if ( $_prevstate ) {
						$data[ '_prevstate' ] = serialize( $_prevstate );
					}

					$backup_translation_status = $this->get_translation_status_data( $data['translation_id'] );
					$update_translation_status = $this->update_translation_status( $data );
					$rid                       = $update_translation_status[0];

					$job_id     = $this->add_translation_job( $rid, $translator_id, $translation_package );
					$job_ids[ ] = $job_id;

					if ( $translation_service !== 'local' ) {
						/** @global WPML_Pro_Translation $ICL_Pro_Translation */
						global $ICL_Pro_Translation;
						$sent = $ICL_Pro_Translation->send_post( $post, array( $lang ), $translator_id, $job_id );
						if ( ! $sent ) {
							$job_id = array_pop( $job_ids );
							$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id ) );
							$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}icl_translate_job SET revision = NULL WHERE rid=%d ORDER BY job_id DESC LIMIT 1", $rid ) );
							$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id=%d", $job_id ) );
							if ( $backup_translation_status ) {
								$wpdb->update( "{$wpdb->prefix}icl_translation_status", $backup_translation_status, array( 'translation_id' => $data['translation_id'] ) );
							} else {
								$wpdb->delete( "{$wpdb->prefix}icl_translation_status", array( 'translation_id' => $data['translation_id'] )  );
							}
							foreach ( $ICL_Pro_Translation->errors as $error ) {
								if ( is_subclass_of( $error, 'Exception' ) ) {
									/** @var Exception $error */
									$message = array(
										'type' => 'error',
										'text' => $error->getMessage(),
									);
									$this->add_message( $message );
								}
							}
						}
					}
				} // if / else is making a duplicate
			}
		}

		icl_cache_clear();
		do_action('wpml_tm_empty_mail_queue');

		return $job_ids;
	}

	private function get_translation_status_data( $translation_id ) {
		global $wpdb;
		$data = $wpdb->get_results( $wpdb->prepare( "SELECT *
													FROM {$wpdb->prefix}icl_translation_status
													WHERE translation_id = %d",
			$translation_id ), ARRAY_A );

		return isset( $data[0] ) ? $data[0] : array();
	}

	/**
	 * Adds a translation job record in icl_translate_job
	 *
	 * @param mixed $rid
	 * @param mixed $translator_id
	 * @param       $translation_package
	 *
	 * @return bool|int
	 */
	function add_translation_job( $rid, $translator_id, $translation_package ) {
		do_action( 'wpml_add_translation_job', $rid, $translator_id, $translation_package );

		return apply_filters( 'wpml_rid_to_untranslated_job_id', false, $rid );
	}

	function get_translation_jobs( $args = array() ) {

		return apply_filters( 'wpml_translation_jobs', array(), $args );
	}

	function get_translation_job_types( $args = array() ) {

		return apply_filters( 'wpml_translation_job_types', array(), $args );
	}

	/**
	 * Clean orphan jobs in posts
	 *
	 * @param array $posts
	 */
	function cleanup_translation_jobs_cart_posts( $posts ) {
		if ( empty( $posts ) ) {
			return;
		}

		foreach ( $posts as $post_id => $post_data ) {
			if ( ! get_post( $post_id ) ) {
				TranslationProxy_Basket::delete_item_from_basket( $post_id );
			}
		}
	}

	/**
	 * Incorporates posts in cart data with post title, post date, post notes,
	 * post type, post status
	 *
	 * @param array $posts
	 *
	 * @return boolean | array
	 */
	function get_translation_jobs_basket_posts( $posts ) {
		if ( empty( $posts ) ) {
			return false;
		}

		$this->cleanup_translation_jobs_cart_posts( $posts );

		global $sitepress;

		$posts_ids = array_keys( $posts );

		$args = array(
			'posts_per_page' => - 1,
			'include'        => $posts_ids,
			'post_type'      => get_post_types(),
			'post_status'    => get_post_stati(), // All post statuses
		);

		$new_posts = get_posts( $args );

		$final_posts = array();

		foreach ( $new_posts as $post_data ) {
			// set post_id
			$final_posts[ $post_data->ID ] = false;
			// set post_title
			$final_posts[ $post_data->ID ][ 'post_title' ] = $post_data->post_title;
			// set post_date
			$final_posts[ $post_data->ID ][ 'post_date' ] = $post_data->post_date;
			// set post_notes
			$final_posts[ $post_data->ID ][ 'post_notes' ] = get_post_meta( $post_data->ID, '_icl_translator_note', true );;
			// set post_type
			$final_posts[ $post_data->ID ][ 'post_type' ] = $post_data->post_type;
			// set post_status
			$final_posts[ $post_data->ID ][ 'post_status' ] = $post_data->post_status;
			// set from_lang
			$final_posts[ $post_data->ID ][ 'from_lang' ]        = $posts[ $post_data->ID ][ 'from_lang' ];
			$final_posts[ $post_data->ID ][ 'from_lang_string' ] = ucfirst( $sitepress->get_display_language_name( $posts[ $post_data->ID ][ 'from_lang' ], $sitepress->get_admin_language() ) );
			// set to_langs
			$final_posts[ $post_data->ID ][ 'to_langs' ] = $posts[ $post_data->ID ][ 'to_langs' ];
			// set comma separated to_langs -> to_langs_string
			$language_names = array();
			foreach ( $final_posts[ $post_data->ID ][ 'to_langs' ] as $language_code => $value ) {
				$language_names[ ] = ucfirst( $sitepress->get_display_language_name( $language_code, $sitepress->get_admin_language() ) );
			}
			$final_posts[ $post_data->ID ][ 'to_langs_string' ] = implode( ", ", $language_names );
		}

		return $final_posts;
	}

	/**
	 * Incorporates strings in cart data
	 *
	 * @param array       $strings
	 * @param bool|string $source_language
	 *
	 * @return boolean | array
	 */
	function get_translation_jobs_basket_strings( $strings, $source_language = false ) {
		$final_strings = array();
		if ( class_exists( 'WPML_String_Translation' ) ) {
			global $sitepress;

			$source_language = $source_language ? $source_language : TranslationProxy_Basket::get_source_language();
			foreach ( $strings as $string_id => $data ) {
				if ( $source_language ) {
					// set post_id
					$final_strings[ $string_id ] = false;
					// set post_title
					$final_strings[ $string_id ][ 'post_title' ] = icl_get_string_by_id( $string_id );
					// set post_type
					$final_strings[ $string_id ][ 'post_type' ] = 'string';
					// set from_lang
					$final_strings[ $string_id ][ 'from_lang' ]        = $source_language;
					$final_strings[ $string_id ][ 'from_lang_string' ] = ucfirst( $sitepress->get_display_language_name( $source_language, $sitepress->get_admin_language() ) );
					// set to_langs
					$final_strings[ $string_id ][ 'to_langs' ] = $data[ 'to_langs' ];
					// set comma separated to_langs -> to_langs_string
					// set comma separated to_langs -> to_langs_string
					$language_names = array();
					foreach ( $final_strings[ $string_id ][ 'to_langs' ] as $language_code => $value ) {
						$language_names[ ] = ucfirst( $sitepress->get_display_language_name( $language_code, $sitepress->get_admin_language() ) );
					}
					$final_strings[ $string_id ][ 'to_langs_string' ] = implode( ", ", $language_names );
				}
			}
		}

		return $final_strings;
	}

	function get_translation_job( $job_id, $include_non_translatable_elements = false, $auto_assign = false, $revisions = 0 ) {
		return apply_filters( 'wpml_get_translation_job', $job_id, $include_non_translatable_elements, $revisions );
	}

	function get_translation_job_id_filter( $empty, $args ) {
		$trid = $args['trid'];
		$language_code = $args['language_code'];
		return $this->get_translation_job_id($trid, $language_code);
	}

	function get_translation_job_id( $trid, $language_code ) {
		global $wpdb;

		$found = false;
		$cache = $this->cache_factory->get( 'TranslationManagement::get_translation_job_id' );
		$job_ids = $cache->get( $trid, $found );
		if ( ! $found ) {

			$results = $wpdb->get_results( $wpdb->prepare( "
				SELECT tj.job_id, t.language_code FROM {$wpdb->prefix}icl_translate_job tj
					JOIN {$wpdb->prefix}icl_translation_status ts ON tj.rid = ts.rid
					JOIN {$wpdb->prefix}icl_translations t ON ts.translation_id = t.translation_id
					WHERE t.trid = %d
					ORDER BY tj.job_id DESC 
					", $trid ) );

			$job_ids = array();
			foreach ( $results as $result ) {
				if ( ! isset( $job_ids[ $result->language_code ] ) ) {
					$job_ids[ $result->language_code ] = $result->job_id;
				}
			}
			$cache->set( $trid, $job_ids );
		}

		return isset( $job_ids[ $language_code ] ) ? $job_ids[ $language_code ] : null;
	}

	function save_translation( $data ) {
		do_action( 'wpml_save_translation_data', $data );
	}

	/**
	 * Saves the contents a job's post to the job itself
	 *
	 * @deprecated since WPML 3.2.3 use the action hook wpml_save_job_fields_from_post
	 *
	 * @param int   $job_id
	 *
	 * @hook wpml_save_job_fields_from_post
	 */
	function save_job_fields_from_post( $job_id ) {
		do_action( 'wpml_save_job_fields_from_post', $job_id );
	}

	function mark_job_done( $job_id ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'icl_translate_job', array( 'translated' => 1 ), array( 'job_id' => $job_id ) );
		$wpdb->update( $wpdb->prefix . 'icl_translate', array( 'field_finished' => 1 ), array( 'job_id' => $job_id ) );
		do_action('wpml_tm_empty_mail_queue');
	}

	function resign_translator( $job_id ) {
		global $wpdb;
		list( $translator_id, $rid ) = $wpdb->get_row( $wpdb->prepare( "SELECT translator_id, rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id ), ARRAY_N );
		if ( !empty( $translator_id ) && $this->settings[ 'notification' ][ 'resigned' ] != ICL_TM_NOTIFICATION_NONE && $job_id ) {
			do_action( 'wpml_tm_resign_job_notification', $translator_id, $job_id );
		}
		$wpdb->update( $wpdb->prefix . 'icl_translate_job', array( 'translator_id' => 0 ), array( 'job_id' => $job_id ) );
		$wpdb->update( $wpdb->prefix . 'icl_translation_status', array( 'translator_id' => 0, 'status' => ICL_TM_WAITING_FOR_TRANSLATOR ), array( 'rid' => $rid ) );
	}

	function remove_translation_job( $job_id, $new_translation_status = ICL_TM_WAITING_FOR_TRANSLATOR, $new_translator_id = 0 ) {
		global $wpdb;

		$error = false;

		list( $prev_translator_id, $rid ) = $wpdb->get_row( $wpdb->prepare( "SELECT translator_id, rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id ), ARRAY_N );

		$wpdb->update( $wpdb->prefix . 'icl_translate_job', array( 'translator_id' => $new_translator_id ), array( 'job_id' => $job_id ) );
		$wpdb->update( $wpdb->prefix . 'icl_translate', array( 'field_data_translated' => '', 'field_finished' => 0 ), array( 'job_id' => $job_id ) );

		if ( $rid ) {
			$data       = array( 'status' => $new_translation_status, 'translator_id' => $new_translator_id );
			$data_where = array( 'rid' => $rid );
			$wpdb->update( $wpdb->prefix . 'icl_translation_status', $data, $data_where );

			if ( $this->settings[ 'notification' ][ 'resigned' ] == ICL_TM_NOTIFICATION_IMMEDIATELY && ! empty( $prev_translator_id ) ) {
				do_action( 'wpml_tm_remove_job_notification', $prev_translator_id, $job_id );
			}
		} else {
			$error = sprintf( __( 'Translation entry not found for: %d', 'wpml-translation-management' ), $job_id );
		}

		return $error;
	}

	function abort_translation() {
		$job_id  = $_POST[ 'job_id' ];
		$message = '';

		$error = $this->remove_translation_job( $job_id, ICL_TM_WAITING_FOR_TRANSLATOR, 0 );
		if ( ! $error ) {
			$message = __( 'Job removed', 'wpml-translation-management' );
		}

		echo wp_json_encode( array( 'message' => $message, 'error' => $error ) );
		exit;
	}

	// $translation_id - int or array
	function cancel_translation_request( $translation_id ) {
		global $wpdb, $WPML_String_Translation;;

		if ( is_array( $translation_id ) ) {
			foreach ( $translation_id as $id ) {
				$this->cancel_translation_request( $id );
			}
		} else {

			if ( $WPML_String_Translation && wpml_mb_strpos( $translation_id, 'string|' ) === 0 ) {
				//string translations get handled in wpml-string-translation
				//first remove the "string|" prefix
				$id = substr( $translation_id, 7 );
				//then send it to the respective function in wpml-string-translation
				$WPML_String_Translation->cancel_local_translation( $id );

				return;
			}

			list( $rid, $translator_id ) = $wpdb->get_row( $wpdb->prepare( "SELECT rid, translator_id
                     FROM {$wpdb->prefix}icl_translation_status
                     WHERE translation_id=%d
                       AND ( status = %d OR status = %d )", $translation_id, ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ), ARRAY_N );
			if ( ! $rid ) {
				return;
			}
			$job_id = $wpdb->get_var( $wpdb->prepare( "SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d AND revision IS NULL ", $rid ) );

			if ( isset($this->settings[ 'notification' ][ 'resigned' ])
				 && $this->settings[ 'notification' ][ 'resigned' ] == ICL_TM_NOTIFICATION_IMMEDIATELY && !empty( $translator_id ) ) {
				do_action( 'wpml_tm_remove_job_notification', $translator_id, $job_id );
			}

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id=%d", $job_id ) );

			$max_job_id = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(job_id) FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d", $rid ) );
			if ( $max_job_id ) {
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}icl_translate_job SET revision = NULL WHERE job_id=%d", $max_job_id ) );
				$previous_state = $wpdb->get_var( $wpdb->prepare( "SELECT _prevstate FROM {$wpdb->prefix}icl_translation_status WHERE translation_id = %d", $translation_id ) );
				if ( ! empty( $previous_state ) ) {
					$previous_state = unserialize( $previous_state );
					$arr_data       = array(
						'status'              => $previous_state[ 'status' ],
						'translator_id'       => $previous_state[ 'translator_id' ],
						'needs_update'        => $previous_state[ 'needs_update' ],
						'md5'                 => $previous_state[ 'md5' ],
						'translation_service' => $previous_state[ 'translation_service' ],
						'translation_package' => $previous_state[ 'translation_package' ],
						'timestamp'           => $previous_state[ 'timestamp' ],
						'links_fixed'         => $previous_state[ 'links_fixed' ]
					);
					$data_where     = array( 'translation_id' => $translation_id );
					$wpdb->update( $wpdb->prefix . 'icl_translation_status', $arr_data, $data_where );
				}
			} else {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id ) );
			}

			// delete record from icl_translations if trid is null
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d AND element_id IS NULL", $translation_id ) );
			icl_cache_clear();
		}
	}

	function render_option_writes( $name, $value, $key = '' ) {
		if ( ! defined( 'WPML_ST_FOLDER' ) ) {
			return;
		}
		//Cache the previous option, when called recursively
		static $option = false;

		if ( ! $key ) {
			$option = maybe_unserialize( get_option( $name ) );
			if ( is_object( $option ) ) {
				$option = (array) $option;
			}
		}

		$admin_option_names = get_option( '_icl_admin_option_names' );

		// determine theme/plugin name (string context)
		$es_context = '';

		$context = '';
		$slug    = '';
		foreach ( $admin_option_names as $context => $element ) {
			$found = false;
			foreach ( (array) $element as $slug => $options ) {
				$found = false;
				foreach ( (array) $options as $option_key => $option_value ) {
					$found      = false;
					$es_context = '';
					if ( $option_key == $name ) {
						if ( is_scalar( $option_value ) ) {
							$es_context = 'admin_texts_' . $context . '_' . $slug;
							$found      = true;
						} elseif ( is_array( $option_value ) && is_array( $value ) && ( $option_value == $value ) ) {
							$es_context = 'admin_texts_' . $context . '_' . $slug;
							$found      = true;
						}
					}
					if ( $found ) {
						break;
					}
				}
				if ( $found ) {
					break;
				}
			}
			if ( $found ) {
				break;
			}
		}

		echo '<ul class="icl_tm_admin_options">';
		echo '<li>';

		$context_html = '';
		if ( ! $key ) {
			$context_html = '[' . esc_html( $context ) . ': ' . esc_html( $slug ) . '] ';
		}

		if ( is_scalar( $value ) ) {
			preg_match_all( '#\[([^\]]+)\]#', $key, $matches );

			if ( count( $matches[ 1 ] ) > 1 ) {
				$o_value = $option;
				for ( $i = 1; $i < count( $matches[ 1 ] ); $i ++ ) {
					$o_value = $o_value[ $matches[ 1 ][ $i ] ];
				}
				$o_value   = $o_value[ $name ];
				$edit_link = '';
			} else {
				if ( is_scalar( $option ) ) {
					$o_value = $option;
				} elseif ( isset( $option[ $name ] ) ) {
					$o_value = $option[ $name ];
				} else {
					$o_value = '';
				}

				if ( ! $key ) {
					if ( icl_st_is_registered_string( $es_context, $name ) ) {
						$edit_link = '[<a href="' . admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . esc_html( $es_context ) ) . '">' . esc_html__( 'translate', 'sitepress' ) . '</a>]';
					} else {
						$edit_link = '<div class="updated below-h2">' . esc_html__( 'string not registered', 'sitepress' ) . '</div>';
					}
				} else {
					$edit_link = '';
				}
			}

			if ( false !== strpos( $name, '*' ) ) {
				$o_value = '<span style="color:#bbb">{{ ' . esc_html__( 'Multiple options', 'wpml-translation-management' ) . ' }}</span>';
			} else {
				$o_value = esc_html( $o_value );
				if ( strlen( $o_value ) > 200 ) {
					$o_value = substr( $o_value, 0, 200 ) . ' ...';
				}
			}
			echo '<li>' . $context_html . esc_html( $name ) . ': <i>' . $o_value . '</i> ' . $edit_link . '</li>';
		} else {
			$edit_link = '[<a href="' . admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . esc_html( $es_context ) ) . '">' . esc_html__( 'translate', 'sitepress' ) . '</a>]';
			echo '<strong>' . $context_html . $name . '</strong> ' . $edit_link;
			if ( ! icl_st_is_registered_string( $es_context, $name ) ) {
				$notice = '<div class="updated below-h2">' . esc_html__( 'some strings might be not registered', 'sitepress' ) . '</div>';
				echo $notice;
			}

			foreach ( (array) $value as $o_key => $o_value ) {
				$this->render_option_writes( $o_key, $o_value, $o_key . '[' . $name . ']' );
			}

			//Reset cached data
			$option = false;
		}
		echo '</li>';
		echo '</ul>';
	}

	/**
	 * @param array $info
	 *
	 * @deprecated @since 3.2 Use TranslationProxy::get_current_service_info instead
	 * @return array
	 */
	public static function current_service_info( $info = array() ) {
		return TranslationProxy::get_current_service_info( $info );
	}

	public function clear_cache() {
		global $wpdb;
		delete_option( $wpdb->prefix . 'icl_translators_cached' );
		delete_option( $wpdb->prefix . 'icl_non_translators_cached' );
	}

	// set slug according to user preference
	static function set_page_url( $post_id ) {

		global $wpdb;

		if (  wpml_get_setting_filter( false, 'translated_document_page_url' ) === 'copy-encoded' ) {

			$post            = $wpdb->get_row( $wpdb->prepare( "SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $post_id ) );
			$translation_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $post_id, 'post_' . $post->post_type ) );

			$encode_url = $wpdb->get_var( $wpdb->prepare( "SELECT encode_url FROM {$wpdb->prefix}icl_languages WHERE code=%s", $translation_row->language_code ) );
			if ( $encode_url ) {

				$trid               = $translation_row->trid;
				$original_post_id   = $wpdb->get_var( $wpdb->prepare( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $trid ) );
				$post_name_original = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE ID = %d", $original_post_id ) );

				$post_name_to_be = $post_name_original;
				$incr            = 1;
				do {
					$taken = $wpdb->get_var( $wpdb->prepare( "
						SELECT ID FROM {$wpdb->posts} p
						JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id
						WHERE ID <> %d AND t.element_type = %s AND t.language_code = %s AND p.post_name = %s
						", $post_id, 'post_' . $post->post_type, $translation_row->language_code, $post_name_to_be ) );
					if ( $taken ) {
						$incr ++;
						$post_name_to_be = $post_name_original . '-' . $incr;
					} else {
						$taken = false;
					}
				} while ( $taken == true );
				$post_to_update = new WPML_WP_Post( $wpdb, $post_id );
				$post_to_update->update( array( 'post_name' => $post_name_to_be ), true );
			}
		}
	}

	/**
	 * @param $postarr
	 * @param $lang
	 *
	 * @return int|WP_Error
	 */
	public function icl_insert_post( $postarr, $lang ) {
		$create_post_helper = wpml_get_create_post_helper();

		return $create_post_helper->insert_post( $postarr, $lang );
	}

	/**
	 * Add missing language to posts
	 *
	 * @param array $post_types
	 */
	private function add_missing_language_to_posts( $post_types ) {
		global $wpdb;

		//This will be improved when it will be possible to pass an array to the IN clause
		$posts_prepared = "SELECT ID, post_type, post_status FROM {$wpdb->posts} WHERE post_type IN ('" . implode( "', '", esc_sql( $post_types ) ) . "')";
		$posts          = $wpdb->get_results( $posts_prepared );
		if ( $posts ) {
			foreach ( $posts as $post ) {
				$this->add_missing_language_to_post( $post );
			}
		}
	}

	/**
	 * Add missing language to a given post
	 *
	 * @param WP_Post $post
	 */
	private function add_missing_language_to_post( $post ) {
		global $sitepress, $wpdb;

		$query_prepared = $wpdb->prepare( "SELECT translation_id, language_code FROM {$wpdb->prefix}icl_translations WHERE element_type=%s AND element_id=%d", array( 'post_' . $post->post_type, $post->ID ) );
		$query_results  = $wpdb->get_row( $query_prepared );

		//if translation exists
		if ( ! is_null( $query_results ) ) {
			$translation_id = $query_results->translation_id;
			$language_code  = $query_results->language_code;
		} else {
			$translation_id = null;
			$language_code  = null;
		}

		$urls             = $sitepress->get_setting( 'urls' );
		$is_root_page     = $urls && isset( $urls[ 'root_page' ] ) && $urls[ 'root_page' ] == $post->ID;
		$default_language = $sitepress->get_default_language();

		if ( ! $translation_id && ! $is_root_page && ! in_array( $post->post_status, array( 'auto-draft' ) ) ) {
			$sitepress->set_element_language_details( $post->ID, 'post_' . $post->post_type, null, $default_language );
		} elseif ( $translation_id && $is_root_page ) {
			$trid = $sitepress->get_element_trid( $post->ID, 'post_' . $post->post_type );
			if ( $trid ) {
				$sitepress->delete_element_translation( $trid, 'post_' . $post->post_type );
			}
		} elseif ( $translation_id && ! $language_code && $default_language ) {
			$where = array( 'translation_id' => $translation_id );
			$data  = array( 'language_code' => $default_language );
			$wpdb->update( $wpdb->prefix . 'icl_translations', $data, $where );

			do_action(
				'wpml_translation_update',
				array(
					'type' => 'update',
					'element_id' => $post->ID,
					'element_type' => 'post_' . $post->post_type,
					'translation_id' => $translation_id,
					'context' => 'post'
				)
			);
		}
	}

	/**
	 * Add missing language to taxonomies
	 *
	 * @param array $post_types
	 */
	private function add_missing_language_to_taxonomies( $post_types ) {
		global $sitepress, $wpdb;
		$taxonomy_types = array();
		foreach ( $post_types as $post_type ) {
			$taxonomy_types = array_merge( $sitepress->get_translatable_taxonomies( true, $post_type ), $taxonomy_types );
		}
		$taxonomy_types = array_unique( $taxonomy_types );
		$taxonomies     = $wpdb->get_results( "SELECT taxonomy, term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN (" . wpml_prepare_in( $taxonomy_types ) . ")" );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $taxonomy ) {
				$this->add_missing_language_to_taxonomy( $taxonomy );
			}
		}
	}

	/**
	 * Add missing language to a given taxonomy
	 *
	 * @param OBJECT $taxonomy
	 */
	private function add_missing_language_to_taxonomy( $taxonomy ) {
		global $sitepress, $wpdb;
		$tid_prepared = $wpdb->prepare( "SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type=%s AND element_id=%d", 'tax_' . $taxonomy->taxonomy, $taxonomy->term_taxonomy_id );
		$tid          = $wpdb->get_var( $tid_prepared );
		if ( ! $tid ) {
			$sitepress->set_element_language_details( $taxonomy->term_taxonomy_id, 'tax_' . $taxonomy->taxonomy, null, $sitepress->get_default_language() );
		}
	}

	/**
	 * Add missing language information to entities that don't have this
	 * information configured.
	 */
	public function add_missing_language_information() {
		global $sitepress;
		$translatable_documents = array_keys( $sitepress->get_translatable_documents( false ) );
		if ( $translatable_documents ) {
			$this->add_missing_language_to_posts( $translatable_documents );
			$this->add_missing_language_to_taxonomies( $translatable_documents );
		}
	}

	public static function include_underscore_templates( $name ) {
		$dir_str = WPML_TM_PATH . '/res/js/' . $name . '/templates/';
		$dir     = opendir( $dir_str );
		while ( ( $currentFile = readdir( $dir ) ) !== false ) {
			if ( $currentFile == '.' || $currentFile == '..' || $currentFile[ 0 ] == '.' ) {
				continue;
			}

			/** @noinspection PhpIncludeInspection */
			include $dir_str . $currentFile;
		}
		closedir( $dir );
	}

	public static function get_job_status_string( $status_id, $needs_update = false ) {
		$job_status_text = TranslationManagement::status2text( $status_id );
		if ( $needs_update ) {
			$job_status_text .= __( ' - (needs update)', 'wpml-translation-management' );
		}

		return $job_status_text;
	}

	function display_basket_notification( $position ) {
		if ( class_exists( 'ICL_AdminNotifier' ) && class_exists( 'TranslationProxy_Basket' ) ) {
			$positions = TranslationProxy_Basket::get_basket_notification_positions();
			if ( isset( $positions[ $position ] ) ) {
				ICL_AdminNotifier::display_messages( 'translation-basket-notification' );
			}
		}
	}

	/**
	 * @param $item_type_name
	 * @param $item_type
	 * @param $posts_basket_items
	 * @param $translators
	 * @param $basket_name
	 */
	public function send_posts_jobs( $item_type_name, $item_type, $posts_basket_items, $translators, $basket_name ) {
		// for every post in cart
		// prepare data for send_jobs() and do it
		foreach ( $posts_basket_items as $basket_item_id => $basket_item ) {
			$jobs_data                  = array();
			$jobs_data[ 'iclpost' ][ ]  = $basket_item_id;
			$jobs_data[ 'tr_action' ]   = $basket_item[ 'to_langs' ];
			$jobs_data[ 'translators' ] = $translators;
			$jobs_data[ 'batch_name' ]  = $basket_name;
			$this->send_jobs( $jobs_data );
		}
	}

	public function get_element_type( $trid ) {
		global $wpdb;
		$element_type_query   = "SELECT element_type FROM {$wpdb->prefix}icl_translations WHERE trid=%d LIMIT 0,1";
		$element_type_prepare = $wpdb->prepare( $element_type_query, $trid );

		return $wpdb->get_var( $element_type_prepare );
	}

	/**
	 * @param $type
	 *
	 * @return bool
	 */
	public function is_external_type( $type ) {
		return apply_filters( 'wpml_is_external', false, $type );
	}

	/**
	 * @param int    $post_id
	 * @param string $element_type_prefix
	 *
	 * @return mixed|null|void|WP_Post
	 */
	public function get_post( $post_id, $element_type_prefix ) {
		$item = null;
		if ( $this->is_external_type( $element_type_prefix ) ) {
			$item = apply_filters( 'wpml_get_translatable_item', null, $post_id );
		}

		if ( ! $item ) {
			$item = get_post( $post_id );
		}

		return $item;
	}

	private function init_comments_synchronization() {
		if ( wpml_get_setting_filter( null, 'sync_comments_on_duplicates' ) ) {
			add_action( 'delete_comment', array( $this, 'duplication_delete_comment' ) );
			add_action( 'edit_comment', array( $this, 'duplication_edit_comment' ) );
			add_action( 'wp_set_comment_status', array( $this, 'duplication_status_comment' ), 10, 2 );
			add_action( 'wp_insert_comment', array( $this, 'duplication_insert_comment' ), 100 );
		}
	}

	private function init_default_settings() {
		if ( ! isset( $this->settings[ 'notification' ][ 'new-job' ] ) ) {
			$this->settings[ 'notification' ][ 'new-job' ] = ICL_TM_NOTIFICATION_IMMEDIATELY;
		}
		if ( ! isset( $this->settings[ 'notification' ][ 'completed' ] ) ) {
			$this->settings[ 'notification' ][ 'completed' ] = ICL_TM_NOTIFICATION_IMMEDIATELY;
		}
		if ( ! isset( $this->settings[ 'notification' ][ 'resigned' ] ) ) {
			$this->settings[ 'notification' ][ 'resigned' ] = ICL_TM_NOTIFICATION_IMMEDIATELY;
		}
		if ( ! isset( $this->settings[ 'notification' ][ 'dashboard' ] ) ) {
			$this->settings[ 'notification' ][ 'dashboard' ] = true;
		}
		if ( ! isset( $this->settings[ 'notification' ][ 'purge-old' ] ) ) {
			$this->settings[ 'notification' ][ 'purge-old' ] = 7;
		}

		if ( ! isset( $this->settings[ $this->get_translation_setting_name('custom-fields') ] ) ) {
			$this->settings[ $this->get_translation_setting_name('custom-fields') ] = array();
		}

		if ( ! isset( $this->settings[ $this->get_readonly_translation_setting_name('custom-fields') ] ) ) {
			$this->settings[ $this->get_readonly_translation_setting_name('custom-fields') ] = array();
		}

		if ( ! isset( $this->settings[ $this->get_custom_translation_setting_name('custom-fields') ] ) ) {
			$this->settings[ $this->get_custom_translation_setting_name('custom-fields') ] = array();
		}

		if ( ! isset( $this->settings[ $this->get_custom_readonly_translation_setting_name('custom-fields') ] ) ) {
			$this->settings[ $this->get_custom_readonly_translation_setting_name('custom-fields') ] = array();
		}

		if ( ! isset( $this->settings[ 'doc_translation_method' ] ) ) {
			$this->settings[ 'doc_translation_method' ] = ICL_TM_TMETHOD_MANUAL;
		}
	}

	private function init_current_translator( ) {
		if(did_action('init')) {
			global $current_user;
			$current_translator = null;
			$user = false;
			if ( isset( $current_user->ID ) ) {
				$user = new WP_User( $current_user->ID );
			}

			if ( $user && isset( $user->data ) && $user->data ) {
				$current_translator                 = new WPML_Translator();
				$current_translator->ID             = $current_user->ID;
				$current_translator->user_login     = isset( $user->data->user_login ) ? $user->data->user_login : false;
				$current_translator->display_name   = isset( $user->data->display_name ) ? $user->data->display_name : $current_translator->user_login;
				$current_translator                 = $this->init_translator_language_pairs( $current_user, $current_translator );
			}

			$this->current_translator = $current_translator;
		}
	}

	public function get_translation_setting_name( $section ) {
		return $this->get_sanitized_translation_setting_section( $section ) . '_translation';
	}

	public function get_custom_translation_setting_name( $section ) {
		return $this->get_translation_setting_name( $section ) . '_custom';
	}

	public function get_custom_readonly_translation_setting_name( $section ) {
		return $this->get_custom_translation_setting_name( $section ) . '_readonly';
	}

	public function get_readonly_translation_setting_name( $section ) {
		return $this->get_sanitized_translation_setting_section( $section ) . '_readonly_config';
	}

	private function get_sanitized_translation_setting_section( $section ) {
		$section = preg_replace( '/-/', '_', $section );
		return $section;
	}

	private function assign_translation_job( $job_id, $translator_id, $service = 'local', $type = 'post' ) {
		do_action( 'wpml_tm_assign_translation_job', $job_id, $translator_id, $service, $type );

		return true;
	}

	/**
	 * @param string $table
	 *
	 * @return string[]
	 */
	private function initial_translation_states( $table ) {
		global $wpdb;

		$custom_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$table}" );

		return $custom_keys;
	}

	/**
	 * Add new translator.
	 *
	 * @param array $data  Request data
	 */
	public function icl_tm_add_translator( $data ) {
		// Initial adding
		if (isset($data['from_lang']) && isset($data['to_lang'])) {
			$data['lang_pairs'] = array();
			$data['lang_pairs'][$data['from_lang']] = array($data['to_lang'] => 1);
		}
		$this->add_translator($data['user_id'], $data['lang_pairs']);
		$_user = new WP_User($data['user_id']);
		// Store admin notice.
		$message = array(
			'id' => 'icl_tm_message_add_translator',
			'type' => 'updated',
			'text' => sprintf(__('%s has been added as a translator for this site.', 'sitepress'), $_user->data->display_name)
		);
		ICL_AdminNotifier::add_message( $message );
	}

	/**
	 * Edit existing translator.
	 *
	 * @param array $data  Request data
	 */
	public function icl_tm_edit_translator( $data ) {
		$message      = null;
		$message_type = 'updated';
		if ( wp_verify_nonce( $data['edit_translator_nonce'], 'edit_translator' ) ) {
			$result = $this->edit_translator( $data['user_id'], isset( $data['lang_pairs'] ) ? $data['lang_pairs'] : array() );
			$_user  = new WP_User( $data['user_id'] );
			if ( $result ) {
				$message = sprintf( __( 'Language pairs for %s have been edited.', 'sitepress' ), $_user->data->display_name );
			}
		} elseif ( isset( $_user ) && ! empty( $_user->ID ) ) {
			$message = sprintf( __( '%s has been removed as a translator for this site.', 'sitepress' ), $_user->data->display_name );
		} elseif ( isset( $data['user_id'] ) ) {
			$message = sprintf( __( "I can't find user ID %d: he might have been removed as a translator for this site.", 'sitepress' ), $data['user_id'] );
		} else {
			$message      = sprintf( __( "You can't do that.", 'sitepress' ), $data['user_id'] );
			$message_type = 'error';
		}
		if ( $message ) {
			$message = array(
				'id' => 'icl_tm_message_edit_translator',
				'type' => $message_type,
				'text' => $message
			);
			// Store admin notice.
			ICL_AdminNotifier::add_message( $message );
		}
	}

	/**
	 * Remove existing translator.
	 *
	 * @param array $data  Request data
	 */
	public function icl_tm_remove_translator( $data ) {
		$this->remove_translator( $data[ 'user_id' ] );
		$_user = new WP_User( $data[ 'user_id' ] );
		$message = array(
			'id'   => 'icl_tm_message_remove_translator',
			'type' => 'updated',
			'text' => sprintf( __( '%s has been removed as a translator for this site.', 'sitepress' ), $_user->data->display_name )
		);
		// Store admin notice.
		ICL_AdminNotifier::add_message( $message );
	}

	/**
	 * Save notification settings.
	 *
	 * @param array $data  Request data
	 */
	public function icl_tm_save_notification_settings( $data ) {
		$this->settings[ 'notification' ] = $data[ 'notification' ];
		$this->save_settings();
		$message = array(
			'id'   => 'icl_tm_message_save_notification_settings',
			'type' => 'updated',
			'text' => __( 'Preferences saved.', 'sitepress' )
		);
		ICL_AdminNotifier::add_message( $message );
	}

	/**
	 * Cancel translation jobs.
	 *
	 * @param array $data  Request data
	 */
	public function icl_tm_cancel_jobs( $data ) {
		$message = array(
			'id'   => 'icl_tm_message_cancel_jobs',
			'type' => 'updated'
		);
		if ( isset( $data[ 'icl_translation_id' ] ) ) {
			$this->cancel_translation_request( $data[ 'icl_translation_id' ] );
			$message['text'] = __( 'Translation requests cancelled.', 'sitepress' );
		} else {
			$message['text'] = __( 'No Translation requests selected.', 'sitepress' );
		}
		ICL_AdminNotifier::add_message( $message );
	}
}