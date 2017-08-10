<?php
/**
 * Main SitePress Class
 *
 * @package wpml-core
 */
class SitePress extends WPML_WPDB_User{
	private $template_real_path;
	/** @var  WPML_Meta_Boxes_Post_Edit_HTML $post_edit_metabox */
	private $post_edit_metabox;
	/** @var WPML_Post_Translation $post_translation */
	private $post_translation;
	/** @var WPML_Term_Translation $term_translation */
	private $term_translation;
	/** @var WPML_Post_Duplication $post_duplication */
    private $post_duplication;
	/** @var WPML_Term_Actions $term_actions */
	private $term_actions;
	/** @var WPML_Admin_Scripts_Setup $scripts_handler */
	private $scripts_handler;
	/** @var WPML_Set_Language $language_setter */
	private $language_setter;
	/** @var array $settings */
	private $settings;
	private $active_languages = array();
	private $_admin_notices = array();
	private $this_lang;
	private $wp_query;
	private $admin_language = null;
	private $user_preferences = array();
	private $is_mobile;
    private $is_tablet;
	private $always_translatable_post_types;
	private $always_translatable_taxonomies;
	/** @var  WPML_WP_API $wp_api */
	private $wp_api;
	/** @var WPML_Records $records */
	private $records;
	/** @var  @var int $loaded_blog_id */
	private $loaded_blog_id;

	/**
	 * @var string $original_language caches the initial language when calling
	 * \SitePress::switch_lang() for the first time.
	 */
	private $original_language;

	/**
	 * @var string $original_language_cookie caches the initial language value
	 * in the user's cookie when calling \SitePress::switch_lang() for the
	 * first time.
	 */
	private $original_language_cookie;

	/** @var  WPML_Locale $locale_utils */
	public $locale_utils;

	public $footer_preview = false;

	/**
	 * @var icl_cache
	 */
	public $icl_translations_cache;
	/**
	 * @var WPML_Flags
	 */
	private $flags;
	/**
	 * @var icl_cache
	 */
	public $icl_language_name_cache;
	/**
	 * @var icl_cache
	 */
	public $icl_term_taxonomy_cache;
	private $wpml_helper;

	/**
	 * @var array $current_request_data - Use to store temporary information during the current request
	 */
	private $current_request_data = array();

	function __construct() {
		do_action('wpml_before_startup');
		/** @var array $sitepress_settings */
		global $pagenow, $sitepress_settings, $wpdb, $wpml_post_translations, $locale, $wpml_term_translations;
		$this->wpml_helper = new WPML_Helper( $wpdb );

		parent::__construct( $wpdb );
		$this->locale_utils = new WPML_Locale( $wpdb, $this, $locale );
        $sitepress_settings = get_option('icl_sitepress_settings');
		$this->settings = &$sitepress_settings;
		$this->always_translatable_post_types = array( 'post', 'page' );
		$this->always_translatable_taxonomies = array( 'category', 'post_tag' );
		$this->post_translation = &$wpml_post_translations;
		$this->term_translation = &$wpml_term_translations;
		//TODO: [WPML 3.5] To remove in WPML 3.5
		//@since 3.1
		if(is_admin() && !$this->get_setting('icl_capabilities_verified')) {
			icl_enable_capabilities();
			$sitepress_settings = get_option('icl_sitepress_settings');
		}

		if ( is_null( $pagenow ) && is_multisite() ) {
			include ICL_PLUGIN_PATH . '/inc/hacks/vars-php-multisite.php';
		}

		if ( false != $this->settings ) {
			$this->verify_settings();
		}

		if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php' && isset( $_GET[ 'debug_action' ] ) ) {
			ob_start();
		}

		if ( isset( $_REQUEST[ 'icl_ajx_action' ] ) ) {
			add_action( 'init', array( $this, 'ajax_setup' ), 15 );
		}

		// Process post requests
		if ( !empty( $_POST ) ) {
			add_action( 'init', array( $this, 'process_forms' ) );
		}

		$this->initialize_cache( );

		$this->flags = new WPML_Flags( $wpdb );

		add_action( 'plugins_loaded', array( $this, 'init' ), 1 );
		add_action( 'wp_loaded', array( $this, 'maybe_set_this_lang' ) );
		add_action( 'switch_blog', array( $this, 'init_settings' ), 10, 1 );
		// Administration menus
		add_action( 'admin_menu', array( $this, 'administration_menu' ) );
		add_action( 'admin_menu', array( $this, 'administration_menu2' ), 30 );

		add_action( 'init', array( $this, 'plugin_localization' ) );

		if ( $this->get_setting('existing_content_language_verified') && ( $this->get_setting('setup_complete') || ( !empty($_GET[ 'page' ]) && $this->get_setting('setup_wizard_step') > 1 && $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/languages.php'  ) ) ) {

		// Post/page language box

			add_filter( 'comment_feed_join', array( $this, 'comment_feed_join' ) );

			add_filter( 'comments_clauses', array( $this, 'comments_clauses' ), 10, 2 );

			// Allow us to filter the Query vars before the posts query is being built and executed
			add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

			add_filter( 'the_posts', array( $this, 'the_posts' ), 10 );

			if ( $pagenow == 'edit.php' ) {
				add_action( 'quick_edit_custom_box', array( 'WPML_Terms_Translations', 'quick_edit_terms_removal' ), 10, 2 );
			}

			add_filter( 'get_pages', array( $this, 'exclude_other_language_pages2' ), 10, 2 );
			add_filter( 'wp_dropdown_pages', array( $this, 'wp_dropdown_pages' ) );

			add_filter( 'get_comment_link', array( $this, 'get_comment_link_filter' ) );

			$this->set_term_filters_and_hooks();

			add_action( 'parse_query', array( $this, 'parse_query' ) );

			// AJAX Actions for the post edit screen
			add_action( 'wp_ajax_wpml_save_term', array( 'WPML_Post_Edit_Ajax', 'wpml_save_term_action' ) );
			add_action( 'wp_ajax_wpml_switch_post_language', array( 'WPML_Post_Edit_Ajax', 'wpml_switch_post_language' ) );
			add_action( 'wp_ajax_wpml_get_default_lang', array( 'WPML_Post_Edit_Ajax', 'wpml_get_default_lang' ) );

			//AJAX Actions for the taxonomy translation screen
			add_action( 'wp_ajax_wpml_get_terms_and_labels_for_taxonomy_table', array( 'WPML_Taxonomy_Translation_Table_Display', 'wpml_get_terms_and_labels_for_taxonomy_table' ) );

			// Ajax Action for the updating of term names on the troubleshooting page
			add_action( 'wp_ajax_wpml_update_term_names_troubleshoot', array( 'WPML_Troubleshooting_Terms_Menu', 'wpml_update_term_names_troubleshoot' ) );

			// short circuit get default category
			add_filter( 'pre_option_default_category', array( $this, 'pre_option_default_category' ) );
			add_filter( 'update_option_default_category', array( $this, 'update_option_default_category' ), 1, 2 );

			// front end js
			add_action( 'wp_head', array( $this, 'front_end_js' ) );

			add_action( 'wp_head', array( $this, 'rtl_fix' ) );
			add_action( 'admin_print_styles', array( $this, 'rtl_fix' ) );

			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );

			// adjacent posts links
			add_filter( 'get_previous_post_join', array( $this, 'get_adjacent_post_join' ) );
			add_filter( 'get_next_post_join', array( $this, 'get_adjacent_post_join' ) );
			add_filter( 'get_previous_post_where', array( $this, 'get_adjacent_post_where' ) );
			add_filter( 'get_next_post_where', array( $this, 'get_adjacent_post_where' ) );

			// feeds links
			add_filter( 'feed_link', array( $this, 'feed_link' ) );

			// commenting links
			add_filter( 'post_comments_feed_link', array( $this, 'post_comments_feed_link' ) );
			add_filter( 'trackback_url', array( $this, 'trackback_url' ) );
			add_filter( 'user_trailingslashit', array( $this, 'user_trailingslashit' ), 1, 2 );

			// date based archives
			add_filter( 'getarchives_join', array( $this, 'getarchives_join' ), 10, 2 );
			add_filter( 'getarchives_where', array( $this, 'getarchives_where' ) );
			add_filter( 'pre_option_home', array( $this, 'pre_option_home' ) );

			if ( !is_admin() ) {
				add_filter( 'attachment_link', array( $this, 'attachment_link_filter' ), 10, 2 );
			}

			// Filter custom type archive link (since WP 3.1)
			add_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link_filter' ), 10, 2 );

			add_filter( 'author_link', array( $this, 'author_link' ) );

			// language negotiation
			add_action( 'query_vars', array( $this, 'query_vars' ) );
			add_filter( 'language_attributes', array( $this, 'language_attributes' ) );
			add_filter( 'locale', array( $this, 'locale_filter' ), 10, 1 );
			add_filter( 'pre_option_page_on_front', array( $this, 'pre_option_page_on_front' ) );
			add_filter( 'pre_option_page_for_posts', array( $this, 'pre_option_page_for_posts' ) );
			add_filter( 'pre_option_sticky_posts', array( $this, 'option_sticky_posts' ), 10, 2 );

			add_filter( 'trashed_post', array( $this, 'fix_trashed_front_or_posts_page_settings' ) );
			add_filter( 'delete_post', array( $this, 'fix_trashed_front_or_posts_page_settings' ) );

			add_action( 'wp', array( $this, 'set_wp_query' ) );
			add_action( 'personal_options_update', array( $this, 'save_user_options' ) );
			add_action( 'edit_user_profile_update', array( $this, 'save_user_options' ) );

			if ( !is_admin() ) {
				add_action( 'wp_head', array( $this, 'meta_generator_tag' ) );
			}

			require_once ICL_PLUGIN_PATH . '/inc/wp-nav-menus/class-wpml-nav-menu.php';
			$icl_nav_menu = new WPML_Nav_Menu( $this, $wpdb, $wpml_post_translations, $wpml_term_translations );
			$icl_nav_menu->init_hooks();

			add_action( 'wp_login', array( $this, 'reset_admin_language_cookie' ) );

			$this->handle_head_hreflang();

			/**
			 * add extra debug information
			 */
			add_filter( 'icl_get_extra_debug_info', array( $this, 'add_extra_debug_info' ) );

		} //end if the initial language is set - existing_content_language_verified

		add_filter( 'core_version_check_locale', array( $this, 'wp_upgrade_locale' ) );

		if ( $pagenow === 'post.php' && isset( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] == 'edit' && isset( $_GET[ 'post' ] ) ) {
			add_action( 'init', '_icl_trash_restore_prompt' );
		}

		add_action( 'init', array( $this, 'js_load' ), 2 ); // enqueue scripts - higher priority
		add_filter('url_to_postid', array($this, 'url_to_postid'));
		//cron job to update WPML config index file from CDN

		$wpml_config_update_integrator = new WPML_Config_Update_Integrator();
		$wpml_config_update_integrator->add_hooks();

		add_action('core_upgrade_preamble', array($this, 'update_index_screen'));
		add_filter('get_search_form', array($this, 'get_search_form_filter'));
		$this->api_hooks();
		add_action('wpml_loaded', array($this, 'load_dependencies'), 10000);
		do_action('wpml_after_startup');
	}

	/**
	 * @since 3.2
	 */
	public function api_hooks() {
		//TODO: [WPML 3.5] to deprecate in favour of lowercased namespaces
		add_filter( 'WPML_get_setting', array( $this, 'filter_get_setting' ), 10, 2 );
		add_filter( 'WPML_get_current_language', array( $this, 'get_current_language' ), 10, 0 );
		add_filter( 'WPML_get_user_admin_language', array( $this, 'get_user_admin_language_filter' ), 10, 2 );
		add_filter( 'WPML_is_admin_action_from_referer', array( $this, 'check_if_admin_action_from_referer' ), 10, 0 );
		add_filter( 'WPML_current_user', array( $this, 'get_current_user' ), 10, 0 );

		add_filter( 'wpml_get_setting', array( $this, 'filter_get_setting' ), 10, 2 );
		add_action( 'wpml_set_setting', array( $this, 'action_set_setting' ), 10, 3 );
		add_filter( 'wpml_get_language_cookie', array( $this, 'get_language_cookie' ), 10, 0 );
		add_filter( 'wpml_current_language', array( $this, 'get_current_language' ), 10, 0 );
		add_filter( 'wpml_get_user_admin_language', array( $this, 'get_user_admin_language_filter' ), 10, 2 );
		add_filter( 'wpml_is_admin_action_from_referer', array( $this, 'check_if_admin_action_from_referer' ), 10, 0 );
		add_filter( 'wpml_current_user', array( $this, 'get_current_user' ), 10, 0 );

		add_filter( 'wpml_new_post_source_id', array ( $this, 'get_new_post_source_id'), 10, 1);

		/**
		 * @use \SitePress::get_translatable_documents_filter
		 */
		add_filter( 'wpml_translatable_documents', array( $this, 'get_translatable_documents_filter' ), 10, 2 );
		add_filter( 'wpml_is_translated_post_type', array( $this, 'is_translated_post_type_filter' ), 10, 2 );

		add_filter( 'wpml_is_translated_taxonomy', array( $this, 'is_translated_taxonomy_filter' ), 10, 2 );

		/**
		 * @deprecated it has a wrong hook tag
		 * @since 3.2
		 */
		add_filter( 'wpml_get_element_translations_filter', array( $this, 'get_element_translations_filter' ), 10, 6 );
		add_filter( 'wpml_get_element_translations', array( $this, 'get_element_translations_filter' ), 10, 6 );
		add_filter( 'wpml_is_original_content', array( $this, 'is_original_content_filter'), 10, 3 );
		add_filter( 'wpml_original_element_id', array( $this, 'get_original_element_id_filter'), 10, 3 );
		add_filter( 'wpml_element_trid', array( $this, 'get_element_trid_filter'), 10, 3 );

		add_filter( 'wpml_is_rtl', array( $this, 'is_rtl' ) );

		add_filter( 'wpml_home_url', 'wpml_get_home_url_filter', 10 );
		add_filter( 'wpml_active_languages', 'wpml_get_active_languages_filter', 10, 2 );
		add_filter( 'wpml_display_language_names', 'wpml_display_language_names_filter', 10, 5 );
		add_filter( 'wpml_display_single_language_name', array($this, 'get_display_single_language_name_filter'), 10, 2 );
		add_filter( 'wpml_element_link', 'wpml_link_to_element_filter', 10, 7 );
		add_filter( 'wpml_object_id', 'wpml_object_id_filter', 10, 4 );
		add_filter( 'wpml_translated_language_name', 'wpml_translated_language_name_filter', 10, 3 );
		add_filter( 'wpml_default_language', 'wpml_get_default_language_filter', 10, 1);
		add_filter( 'wpml_post_language_details', 'wpml_get_language_information', 10, 2 );

		add_action( 'wpml_add_language_form_field', 'wpml_add_language_form_field_action' );
		add_shortcode( 'wpml_language_form_field', 'wpml_language_form_field_shortcode' );

		add_filter( 'wpml_element_translation_type', 'wpml_get_element_translation_type_filter', 10, 3 );
		add_filter( 'wpml_element_has_translations', 'wpml_element_has_translations_filter', 10, 3 );
		add_filter( 'wpml_content_translations', 'wpml_get_content_translations_filter', 10, 3 );
		add_filter( 'wpml_master_post_from_duplicate', 'wpml_get_master_post_from_duplicate_filter' );
		add_filter( 'wpml_post_duplicates', 'wpml_get_post_duplicates_filter' );
		add_filter( 'wpml_element_type', 'wpml_element_type_filter' );

		add_filter( 'wpml_setting', 'wpml_get_setting_filter', 10, 3 );
		add_filter( 'wpml_sub_setting', 'wpml_get_sub_setting_filter', 10, 4 );
		add_filter( 'wpml_language_is_active', 'wpml_language_is_active_filter', 10, 2 );

		add_action( 'wpml_admin_make_post_duplicates', 'wpml_admin_make_post_duplicates_action', 10, 1 );
		/**
		 * @deprecated This actions will be removed in future releases.
		 */
		add_action( 'wpml_make_post_duplicates', 'wpml_make_post_duplicates_action', 10, 1 );

		add_filter( 'wpml_element_language_details', 'wpml_element_language_details_filter', 10, 2 );
		add_action( 'wpml_set_element_language_details', array($this, 'set_element_language_details_action'), 10, 1 );
		add_filter( 'wpml_element_language_code', 'wpml_element_language_code_filter', 10, 2 );
		add_filter( 'wpml_elements_without_translations', 'wpml_elements_without_translations_filter', 10, 2 );

		add_filter( 'wpml_permalink', 'wpml_permalink_filter', 10, 2 );
		add_action( 'wpml_switch_language', 'wpml_switch_language_action', 10, 1 );
	}

	function init() {

		do_action('wpml_before_init');
		$this->locale_utils->init();
		$this->maybe_set_this_lang();

		if ( function_exists( 'w3tc_add_action' ) ) {
			w3tc_add_action( 'w3tc_object_cache_key', 'w3tc_translate_cache_key_filter' );
		}

		$this->get_user_preferences();
		$this->set_admin_language();

		// default value for theme_localization_type OR
		// reset theme_localization_type if string translation was on (theme_localization_type was set to 2) and then it was deactivated
		global $sitepress_settings;
		$theme_localization_type = new WPML_Theme_Localization_Type( $this );
		if ( ! isset( $this->settings['theme_localization_type'] ) || ( $theme_localization_type->is_st_type() && ! defined( 'WPML_ST_VERSION' ) && ! defined( 'WPML_DOING_UPGRADE' ) ) ) {
			$sitepress_settings['theme_localization_type_previous'] = $sitepress_settings['theme_localization_type'];
			$this->settings['theme_localization_type'] = $sitepress_settings['theme_localization_type'] = WPML_Theme_Localization_Type::USE_MO_FILES;
		} else if ( defined( 'WPML_ST_VERSION' ) && isset( $sitepress_settings['theme_localization_type_previous'] ) ) {
			$this->settings['theme_localization_type'] = $sitepress_settings['theme_localization_type'] = $sitepress_settings['theme_localization_type_previous'];
			unset( $sitepress_settings['theme_localization_type_previous'] );
		}

		$theme_localization_type->add_hooks();

		//Run only if existing content language has been verified, and is front-end or settings are not corrupted
		if ( ! empty( $this->settings['existing_content_language_verified'] ) ) {
			add_action( 'wpml_verify_post_translations', array(
				$this,
				'verify_post_translations_action'
			), 10, 1 );

			if ($this->settings[ 'language_negotiation_type' ] == 2) {
				add_filter( 'allowed_redirect_hosts', array( $this, 'allowed_redirect_hosts' ) );
			}

			//reorder active language to put 'this_lang' in front
            $active_languages = $this->get_active_languages();
			foreach ( $active_languages as $k => $active_lang ) {
				if ( $k === $this->this_lang ) {
					unset( $this->active_languages[ $k ] );
					$this->active_languages = array_merge( array( $k => $active_lang ), $this->active_languages );
				}
			}

			add_filter( 'mod_rewrite_rules', array( $this, 'rewrite_rules_filter' ), 10 ,1 );

			if ( is_admin() && $this->get_setting( 'setup_complete' ) ) {
				new WPML_Custom_Columns_Hooks( $this->wpdb, $this );
				$this->post_edit_metabox = new WPML_Meta_Boxes_Post_Edit_HTML( $this, $this->post_translation );

				if ( ! $this->get_wp_api()->is_translation_queue_page() && ! $this->get_wp_api()->is_string_translation_page() ) {
					// Admin language switcher goes to the WP admin bar
					if ( apply_filters( 'wpml_show_admin_language_switcher', true ) ) {
						add_action( 'wp_before_admin_bar_render', array( $this, 'admin_language_switcher' ) );
					} else {
						$this->this_lang = 'all';
					}
				}
			}

			if ( !is_admin() && defined( 'DISQUS_VERSION' ) ) {
				include ICL_PLUGIN_PATH . '/modules/disqus.php';
			}
		}

		if ( $this->is_rtl() ) {
			$GLOBALS[ 'text_direction' ] = 'rtl';
		}

		if ( !wpml_is_ajax() && is_admin() && empty( $this->settings[ 'dont_show_help_admin_notice' ] ) ) {
			WPML_Troubleshooting_Terms_Menu::display_terms_with_suffix_admin_notice();

			if ( !$this->get_setting( 'setup_wizard_step' )
			     && strpos( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_URL ), 'menu/languages.php' ) === false
			) {
				add_action( 'admin_notices', array( $this, 'help_admin_notice' ) );
			}
		}

		$short_v = implode( '.', array_slice( explode( '.', ICL_SITEPRESS_VERSION ), 0, 3 ) );
		if ( is_admin() && ( !isset( $this->settings[ 'hide_upgrade_notice' ] ) || $this->settings[ 'hide_upgrade_notice' ] != $short_v ) ) {
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
		}

		require ICL_PLUGIN_PATH . '/inc/template-constants.php';
		if ( defined( 'WPML_LOAD_API_SUPPORT' ) ) {
			require ICL_PLUGIN_PATH . '/inc/wpml-api.php';
		}

		add_action( 'wp_footer', array( $this, 'display_wpml_footer' ), 20 );

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			add_action( 'xmlrpc_call', array( $this, 'xmlrpc_call_actions' ) );
			add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc_methods' ) );
		}

		global $pagenow;

		// set language to default and remove language switcher when in Taxonomy Translation page
		// If the page uses AJAX and the language must be forced to default, please use the
		// if ( $pagenow == 'admin-ajax.php' )above
		if ( is_admin()
		     && ( isset( $_GET[ 'page' ] )
		          && ( $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/taxonomy-translation.php'
		               || $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/menu-sync/menus-sync.php'
		               || $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/term-taxonomy-menus/taxonomy-translation-display.class.php' )
		          || ( $pagenow == 'admin-ajax.php'
		               && isset( $_POST[ 'action' ] )
		               && $_POST[ 'action' ] == 'wpml_tt_save_labels_translation' ) )
		) {
			$default_language = $this->get_admin_language();
			$this->switch_lang( $default_language, true );
			add_action( 'init', array( $this, 'remove_admin_language_switcher' ) );
		}

		/* Posts and new inline created terms, can only be saved in an active language.
		 * Also the content of the post-new.php should always be filtered for one specific
		 * active language, so to display the correct taxonomy terms for selection.
		 */
		if ( $pagenow == 'post-new.php' ) {
			if ( ! $this->is_active_language( $this->get_current_language() ) ) {
				$default_language = $this->get_admin_language();
				$this->switch_lang( $default_language, true );
			}
		}

		//Code to run when reactivating the plugin
		$recently_activated = $this->get_setting('just_reactivated');
		if($recently_activated) {
			add_action( 'init', array( $this, 'rebuild_language_information' ), 1000 );
		}
		if ( is_admin() ) {
			$mo_file_search = new WPML_MO_File_Search( $this );
			add_action('after_switch_theme', array($mo_file_search, 'reload_theme_dirs'));
		}

		do_action('wpml_after_init');
		do_action('wpml_loaded');

		if ( is_admin()
		     && isset( $_GET[ 'page' ] )
		          && $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/taxonomy-translation.php' ) {
			$this->taxonomy_translation = new WPML_Taxonomy_Translation( '', array(), new WPML_UI_Screen_Options_Factory( $this ) );
		}

		if ( WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN === (int) $this->get_setting( 'language_negotiation_type' )
		     && $this->get_setting( 'language_per_domain_sso_enabled', false )
		) {
			$sso = new WPML_Language_Per_Domain_SSO( $this );
			$sso->init_hooks();
		}
	}

	/**
	 * Sets the current language in \SitePress::$this_lang, redirects if
	 * frontend requests point to incomplete or incorrect urls, un-sets the
	 * $_GET['lang'] and $_GET['admin_bar'] values so that upload.php is able to
	 * enqueue 'media-grid' correctly without url parameters breaking its
	 * functionality.
	 */
	public function maybe_set_this_lang() {
		global $wpml_request_handler, $pagenow, $wpml_language_resolution;

		if ( ! defined( 'WP_ADMIN' ) && isset( $_SERVER['HTTP_HOST'] ) && did_action( 'init' ) ) {
			require_once ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-frontend-redirection.php';
			/** @var WPML_Frontend_Request $wpml_request_handler */
			$redirect_helper = _wpml_get_redirect_helper();
			$redirection = new WPML_Frontend_Redirection( $this,
				$wpml_request_handler, $redirect_helper,
				$wpml_language_resolution );
			$this->this_lang = $redirection->maybe_redirect();
		} else {
			$this->this_lang = $wpml_request_handler->get_requested_lang();
		}

		$wpml_request_handler->set_language_cookie( $this->this_lang );
		if ( $pagenow === 'upload.php' ) {
			$_GET['lang']      = null;
			$_GET['admin_bar'] = null;
		}
	}

	function load_dependencies() {
		do_action( 'wpml_load_dependencies' );
	}

	/**
	 * Sets up all term/taxonomy actions for use outside Translations Management or the Post Edit screen
	 */
	function set_term_filters_and_hooks(){
		// The delete filter only ensures the synchronizing of delete actions between translations of a term.
		add_action( 'delete_term', array( $this, 'delete_term' ), 1, 3 );
		add_action( 'set_object_terms', array( 'WPML_Terms_Translations', 'set_object_terms_action' ), 10, 6 );

		add_action( 'created_term_translation', array( 'WPML_Terms_Translations', 'sync_ttid_action' ), 10, 3 );
		// filters terms by language for the term/tag-box autoselect
		if ( ( isset( $_GET['action'] ) && 'ajax-tag-search' === $_GET['action'] ) || ( isset( $_POST['action'] ) && 'get-tagcloud' === $_POST['action'] ) ) {
			add_filter( 'get_terms', array( 'WPML_Terms_Translations', 'get_terms_filter' ), 10, 2 );
		}

		add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 4 );
		add_action( 'create_term', array( $this, 'create_term' ), 1, 3 );
		add_action( 'edit_term', array( $this, 'create_term' ), 1, 3 );
		add_filter( 'get_terms_args', array( $this, 'get_terms_args_filter' ), 10, 2 );
		add_filter( 'get_edit_term_link', array( $this, 'get_edit_term_link' ), 1, 4 );
		add_action( 'deleted_term_relationships', array( $this, 'deleted_term_relationships' ), 10, 2 );
		add_action('wp_ajax_icl_repair_broken_type_and_language_assignments', 'icl_repair_broken_type_and_language_assignments');
		// adjust queried categories and tags ids according to the language
		if ( (bool) $this->get_setting('auto_adjust_ids' ) ) {
			add_action( 'wp_list_pages_excludes', array( $this, 'adjust_wp_list_pages_excludes' ) );
			if ( ! $this->get_wp_api()->is_admin()
			     || $this->get_wp_api()->constant( 'DOING_AJAX' )
			) {
				add_filter( 'get_term', array( $this, 'get_term_adjust_id' ), 1, 1 );
				add_filter( 'category_link', array( $this, 'category_link_adjust_id' ), 1, 2 );
				add_filter( 'get_pages', array( $this, 'get_pages_adjust_ids' ), 1, 2 );
			}
		}
		add_action( 'clean_term_cache', array( $this, 'clear_elements_cache' ), 10, 2 );

	}

	function remove_admin_language_switcher() {
		remove_action( 'wp_before_admin_bar_render', array( $this, 'admin_language_switcher' ) );
	}

	function rebuild_language_information() {
		$this->set_setting('just_reactivated', 0);
		$this->save_settings();
        /** @var TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement;
		if ( $iclTranslationManagement ) {
			$iclTranslationManagement->add_missing_language_information();
		}
	}

	function setup()
	{
		$setup_complete = $this->get_setting('setup_complete');
		if(!$setup_complete) {
			$this->set_setting('setup_complete', false);
		}
		return $setup_complete;
	}

	public function user_lang_by_authcookie() {
		global $current_user;

		if ( !isset( $current_user ) ) {
			$username = '';
			if ( function_exists ( 'wp_parse_auth_cookie' ) ) {
				$cookie_data = wp_parse_auth_cookie ();
				$username    = isset( $cookie_data[ 'username' ] ) ? $cookie_data[ 'username' ] : null;
			}
			$user_obj = new WP_User( null, $username );
		} else {
			$user_obj = $current_user;
		}
		$user_id   = isset( $user_obj->ID ) ? $user_obj->ID : 0;
		$user_lang = $this->get_user_admin_language ( $user_id );
		$user_lang = $user_lang ? $user_lang : $this->get_current_language ();

		return $user_lang;
	}

    function get_current_user() {
        global $current_user;

        return $current_user !== null ? $current_user : new WP_User();
    }

	function ajax_setup()
	{
		require ICL_PLUGIN_PATH . '/ajax.php';
	}

	function check_if_admin_action_from_referer() {
		$referer = isset( $_SERVER[ 'HTTP_REFERER' ] ) ? $_SERVER[ 'HTTP_REFERER' ] : '';

		return strpos ( $referer, strtolower ( admin_url () ) ) === 0;
	}

	/**
	 * Check translation mangement column screen option.
	 *
	 * @param string $post_type Current post type.
	 *
	 * @return bool
	 */
	public function show_management_column_content( $post_type ) {
		$custom_columns = new WPML_Custom_Columns( $this->wpdb, $this );

		return $custom_columns->show_management_column_content( $post_type );
	}

	function the_posts( $posts ) {
		global $wpml_post_translations;

		$ids = array();
		foreach ( $posts as $single_post ) {
			$ids[ ] = $single_post->ID;
		}

		$wpml_post_translations->prefetch_ids ( $ids );

		if ( !is_admin() && $this->get_setting( 'show_untranslated_blog_posts'  ) && $this->get_current_language() != $this->get_default_language() ) {
			// show untranslated posts

			global $wp_query;
			$default_language = $this->get_default_language();
			$current_language = $this->get_current_language();

			$debug_backtrace = $this->get_backtrace(4, true); //Limit to first 4 stack frames, since 3 is the highest index we use

			/** @var $custom_wp_query WP_Query */
			$custom_wp_query = isset( $debug_backtrace[ 3 ][ 'object' ] ) ? $debug_backtrace[ 3 ][ 'object' ] : false;
			//exceptions
			if ( ( $current_language == $default_language )
				 // original language
				 ||
				 ( $wp_query != $custom_wp_query )
				 // called by a custom query
				 ||
				 ( !$custom_wp_query->is_posts_page && !$custom_wp_query->is_home )
				 // not the blog posts page
				 ||
				 $wp_query->is_singular
				 //is singular
				 ||
				 !empty( $custom_wp_query->query_vars[ 'category__not_in' ] )
				 //|| !empty($custom_wp_query->query_vars['category__in'])
				 //|| !empty($custom_wp_query->query_vars['category__and'])
				 ||
				 !empty( $custom_wp_query->query_vars[ 'tag__not_in' ] ) ||
				 !empty( $custom_wp_query->query_vars[ 'post__in' ] ) ||
				 !empty( $custom_wp_query->query_vars[ 'post__not_in' ] ) ||
				 !empty( $custom_wp_query->query_vars[ 'post_parent' ] )
			) {
				return $posts;
			}

			// get the posts in the default language instead
			$this_lang       = $this->this_lang;
			$this->this_lang = $default_language;

			remove_filter( 'the_posts', array( $this, 'the_posts' ) );

			$custom_wp_query->query_vars[ 'suppress_filters' ] = 0;

			if ( isset( $custom_wp_query->query_vars[ 'pagename' ] ) && !empty( $custom_wp_query->query_vars[ 'pagename' ] ) ) {
				if ( isset( $custom_wp_query->queried_object_id ) && !empty( $custom_wp_query->queried_object_id ) ) {
					$page_id = $custom_wp_query->queried_object_id;
				} else {
					// urlencode added for languages that have urlencoded post_name field value
					$custom_wp_query->query_vars[ 'pagename' ] = urlencode( $custom_wp_query->query_vars[ 'pagename' ] );
					$page_id                                   = $this->wpdb->get_var(
						$this->wpdb->prepare("SELECT ID FROM {$this->wpdb->posts}
                                                                                    WHERE post_name = %s
                                                                                      AND post_type='page'",
                                                                                   $custom_wp_query->query_vars['pagename'] ) );
				}
				if ( $page_id ) {
					$tr_page_id = icl_object_id( $page_id, 'page', false, $default_language );
					if ( $tr_page_id ) {
						$custom_wp_query->query_vars[ 'pagename' ] = $this->wpdb->get_var( $this->wpdb->prepare("SELECT post_name
                                                                                                     FROM {$this->wpdb->posts}
                                                                                                     WHERE ID = %d ",
                                                                                                    $tr_page_id ) );
					}
				}
			}

			// look for posts without translations
			if ( $posts ) {
				$pids = false;
				foreach ( $posts as $p ) {
					$pids[ ] = $p->ID;
				}
				if ( $pids ) {
					$trids = $this->wpdb->get_col( $this->wpdb->prepare("
						SELECT trid
						FROM {$this->wpdb->prefix}icl_translations
						WHERE element_type='post_post'
						  AND element_id IN (" . wpml_prepare_in( $pids, '%d' )  . ")
						  AND language_code = %s", $this_lang ) );

					if ( !empty( $trids ) ) {
						$posts_not_translated = $this->wpdb->get_col( "
							SELECT element_id, COUNT(language_code) AS c
							FROM {$this->wpdb->prefix}icl_translations
							WHERE trid IN (" . join( ',', $trids ) . ") GROUP BY trid HAVING c = 1
						" );
						if ( !empty( $posts_not_translated ) ) {
							$GLOBALS[ '__icl_the_posts_posts_not_translated' ] = $posts_not_translated;
							add_filter( 'posts_where', array( $this, '_posts_untranslated_extra_posts_where' ), 99 );
						}
					}
				}
			}

			//fix page for posts
			unset( $custom_wp_query->query_vars[ 'pagename' ] );
			unset( $custom_wp_query->query_vars[ 'page_id' ] );
			unset( $custom_wp_query->query_vars[ 'p' ] );

			$my_query = new WP_Query( $custom_wp_query->query_vars );

			add_filter( 'the_posts', array( $this, 'the_posts' ) );
			$this->this_lang = $this_lang;

			// create a map of the translated posts
			foreach ( $posts as $post ) {
				$trans_posts[ $post->ID ] = $post;
			}

			// loop original posts
			foreach ( $my_query->posts as $k => $post ) { // loop posts in the default language
				$trid         = $this->get_element_trid( $post->ID );
				$translations = $this->get_element_translations( $trid ); // get translations

				if ( isset( $translations[ $current_language ] ) ) { // if there is a translation in the current language
					if ( isset( $trans_posts[ $translations[ $current_language ]->element_id ] ) ) { //check the map of translated posts
						$my_query->posts[ $k ] = $trans_posts[ $translations[ $current_language ]->element_id ];
					} else { // check if the translated post exists in the database still
						$_post = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} WHERE ID = %d AND post_status='publish' LIMIT 1", $translations[ $current_language ]->element_id ) );
						if ( !empty( $_post ) ) {
							$_post                 = sanitize_post( $_post );
							$my_query->posts[ $k ] = $_post;
						} else {
							$my_query->posts[ $k ]->original_language = true;
						}
					}
				} else {
					$my_query->posts[ $k ]->original_language = true;
				}
			}
			if ( $custom_wp_query == $wp_query ) {
				$wp_query->max_num_pages = $my_query->max_num_pages;
			}
			$merged_posts = array_merge( $my_query->posts, $posts );
			$unique_posts = $this->wp_api->array_unique( $merged_posts, SORT_REGULAR );
			$posts        = array_values( $unique_posts );
			unset( $GLOBALS[ '__icl_the_posts_posts_not_translated' ] );

			remove_filter( 'posts_where', array( $this, '_posts_untranslated_extra_posts_where' ), 99 );
		}

		return $posts;
	}

	function _posts_untranslated_extra_posts_where( $where ) {

		return $where . ' OR ' . $this->wpdb->posts . '.ID IN (' . wpml_prepare_in( $GLOBALS['__icl_the_posts_posts_not_translated'],
		                                                                            '%d' ) . ') ';
	}

	function initialize_cache() {
		require_once ICL_PLUGIN_PATH . '/inc/cache.php';
	}

	/**
	 * @return icl_cache
	 */
	function get_translations_cache() {
		if ( ! isset( $this->icl_translations_cache ) ) {
			$this->icl_translations_cache = new icl_cache();
		}

		return $this->icl_translations_cache;
	}

	/**
	 * @return icl_cache
	 */
	function get_language_name_cache() {
		if ( ! isset( $this->icl_language_name_cache ) ) {
			$this->icl_language_name_cache = new icl_cache( 'language_name', true );
		}

		return $this->icl_language_name_cache;
	}

	public function set_admin_language( $admin_language = false ) {
		$default_language     = $this->get_default_language ();
		$this->admin_language = $admin_language ? $admin_language : $this->user_lang_by_authcookie ();

		$lang_codes = array_keys ( $this->get_languages () );
		if ( (bool) $this->admin_language === true && ! in_array( $this->admin_language, $lang_codes, true ) ) {
			delete_user_meta ( $this->get_current_user ()->ID, 'icl_admin_language' );
		}
		if ( empty( $this->settings[ 'admin_default_language' ] ) || !in_array (
				$this->settings[ 'admin_default_language' ], array_merge( $lang_codes, array( '_default_' ) ), true
			)
		) {
			$this->settings[ 'admin_default_language' ] = '_default_';
			$this->save_settings ();
		}

		if ( !$this->admin_language ) {
			$this->admin_language = $this->settings[ 'admin_default_language' ];
		}
		if ( $this->admin_language === '_default_' && $default_language ) {
			$this->admin_language = $default_language;
		}
	}

	function get_admin_language() {
		$current_user = $this->get_current_user();
		if (
			( ! empty( $current_user->ID ) && $this->get_wp_api()->get_user_meta( $current_user->ID,
				'icl_admin_language_for_edit',
				true ) && $this->is_post_edit_screen() )
			|| $this->is_wpml_switch_language_triggered()
		) {
			$admin_language = $this->get_current_language();
		} else {
			$admin_language = $this->user_lang_by_authcookie();
		}

		return $admin_language;
	}

	private function is_wpml_switch_language_triggered() {
		return isset( $GLOBALS['icl_language_switched'] ) ? true : false ;
	}

	/**
	 * @return bool
	 */
	function is_post_edit_screen() {
		global $pagenow;

		$action = isset( $_GET['action'] ) ? $_GET['action'] : "";

		return $pagenow == 'post-new.php' || ( $pagenow == 'post.php' && ( 0 === strcmp( $action, 'edit' ) ) );
	}

	function get_user_admin_language_filter( $value, $user_id ) {
		$value = $this->get_user_admin_language ( $user_id );

		return $value;
	}

	function get_user_admin_language( $user_id, $reload = false ) {
		$cache_key   = $user_id;
		$cache_group = 'get_user_admin_language';
		$cache_found = false;
		$cache       = new WPML_WP_Cache( $cache_group );
		$lang        = $cache->get( $cache_key, $cache_found );

		if ( ! $cache_found || $reload ) {
			$lang = get_user_meta( $user_id, 'icl_admin_language_for_edit', true ) ? $this->get_current_language() : get_user_meta( $user_id, 'icl_admin_language', true );
			if ( empty( $lang ) ) {
				$admin_default_language = $this->get_setting( 'admin_default_language' );
				if ( $admin_default_language ) {
						$lang = $admin_default_language;
				}
				if ( empty( $lang ) || '_default_' == $lang ) {
					$default = $this->get_default_language();
					$lang = $default ? $default : 'en';
				}
			}

			$cache->set( $cache_key, $lang );
		}
		return $lang;
	}

	function administration_menu() {
		ICL_AdminNotifier::removeMessage( 'setup-incomplete' );
		$main_page = apply_filters( 'icl_menu_main_page', basename( ICL_PLUGIN_PATH ) . '/menu/languages.php' );
		$wpml_setup_is_complete = SitePress_Setup::setup_complete();
		if ( $wpml_setup_is_complete ) {
			add_menu_page( __( 'WPML', 'sitepress' ), __( 'WPML', 'sitepress' ), 'wpml_manage_languages', $main_page, null, ICL_PLUGIN_URL . '/res/img/icon16.png' );

			add_submenu_page( $main_page, __( 'Languages', 'sitepress' ), __( 'Languages', 'sitepress' ), 'wpml_manage_languages', basename( ICL_PLUGIN_PATH ) . '/menu/languages.php' );
			//By Gen, moved Translation management after language, because problems with permissions
			do_action( 'icl_wpml_top_menu_added' );

			$wpml_setup_is_complete = $this->get_setting( 'existing_content_language_verified' ) && 2 <= count( $this->get_active_languages() );
			if ( $wpml_setup_is_complete ) {
				add_submenu_page( $main_page, __( 'Theme and plugins localization', 'sitepress' ), __( 'Theme and plugins localization', 'sitepress' ), 'wpml_manage_theme_and_plugin_localization',
				                  basename( ICL_PLUGIN_PATH )
				                  . '/menu/theme-localization.php' );

				if ( ! defined( 'WPML_TM_VERSION' ) ) {
					add_submenu_page( $main_page, __( 'Translation options', 'sitepress' ), __( 'Translation options', 'sitepress' ), 'wpml_manage_translation_options', basename( ICL_PLUGIN_PATH ) . '/menu/translation-options.php' );
				}
			}

			$wpml_admin_menus_args = array(
				'existing_content_language_verified' => $this->get_setting( 'existing_content_language_verified' ),
				'active_languages_count'             => count( $this->get_active_languages() ),
				'wpml_setup_is_ok'                   => $wpml_setup_is_complete
			);
			do_action( 'wpml_admin_menus', $wpml_admin_menus_args );
		} else {
			$main_page = basename( ICL_PLUGIN_PATH ) . '/menu/languages.php';
			add_menu_page( __( 'WPML', 'sitepress' ), __( 'WPML', 'sitepress' ), 'manage_options', $main_page, null, ICL_PLUGIN_URL . '/res/img/icon16.png' );
			add_submenu_page( $main_page, __( 'Languages', 'sitepress' ), __( 'Languages', 'sitepress' ), 'wpml_manage_languages', $main_page );

			if ( ! $this->is_troubleshooting_page() && ! SitePress_Setup::languages_table_is_complete() ) {
				$troubleshooting_url  = admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php' );
				$troubleshooting_link = '<a href="' . $troubleshooting_url . '" title="' . esc_attr( __( 'Troubleshooting', 'sitepress' ) ) . '">' . __( 'Troubleshooting', 'sitepress' ) . '</a>';
				$message = '';
				$message .= __( 'WPML is missing some records in the languages tables and it cannot fully work until this issue is fixed.', 'sitepress' );
				$message .= '<br />';
				$message .= sprintf( __( 'Please go to the %s page and click on "%s" to fix this problem.', 'sitepress' ), $troubleshooting_link, __( 'Clear language information and repopulate languages', 'sitepress' ) );
				$message .= '<br />';
				$message .= '<br />';
				$message .= __( 'This warning will disappear once this issue is fixed.', 'sitepress' );
				ICL_AdminNotifier::removeMessage( 'setup-incomplete' );
				ICL_AdminNotifier::addMessage( 'setup-incomplete', $message, 'error', false, false, false, 'setup', true );
				ICL_AdminNotifier::displayMessages( 'setup' );
			}
		}

		add_submenu_page( $main_page, __( 'Support', 'sitepress' ), __( 'Support', 'sitepress' ), 'wpml_manage_support', ICL_PLUGIN_FOLDER . '/menu/support.php' );
		$this->troubleshooting_menu(ICL_PLUGIN_FOLDER . '/menu/support.php');
		$this->debug_information_menu(ICL_PLUGIN_FOLDER . '/menu/support.php');
	}

	private function troubleshooting_menu( $main_page ) {
		$submenu_slug = basename( ICL_PLUGIN_PATH ) . '/menu/troubleshooting.php';
			add_submenu_page( $main_page, __( 'Troubleshooting', 'sitepress' ), __( 'Troubleshooting', 'sitepress' ), 'wpml_manage_troubleshooting', $submenu_slug );

			return $submenu_slug;
	}

	private function debug_information_menu( $main_page ) {
		$submenu_slug = basename( ICL_PLUGIN_PATH ) . '/menu/debug-information.php';
		add_submenu_page( $main_page, __( 'Debug information', 'sitepress' ), __( 'Debug information', 'sitepress' ), 'wpml_manage_troubleshooting', $submenu_slug );
		return $submenu_slug;
	}

	// lower priority
	function administration_menu2() {
		$main_page = apply_filters( 'icl_menu_main_page', ICL_PLUGIN_FOLDER . '/menu/languages.php' );
		if ( $this->setup() ) {
			add_submenu_page( $main_page, __( 'Taxonomy Translation', 'sitepress' ), __( 'Taxonomy Translation', 'sitepress' ), 'wpml_manage_taxonomy_translation', ICL_PLUGIN_FOLDER . '/menu/taxonomy-translation.php', array( $this, 'taxonomy_translation_page' ) );
		}
	}

	function taxonomy_translation_page() {
		$this->taxonomy_translation->render();
	}

	/**
	 * @param int|string $blog_id
	 */
	function init_settings( $blog_id ) {
		$blog_id = (int) $blog_id;

		if ( ! isset( $this->loaded_blog_id ) || $this->loaded_blog_id != $blog_id ) {
			$this->loaded_blog_id = $blog_id;
			$this->settings       = get_option( 'icl_sitepress_settings' );
			$default_lang_code    = isset( $this->settings[ 'default_language' ] ) ? $this->settings[ 'default_language' ] : false;
			load_wpml_url_converter( $this->settings, false, $default_lang_code );
		}
	}

	function save_settings( $settings = null ) {
		if ( ! is_null( $settings ) ) {
			foreach ( $settings as $k => $v ) {
				if ( is_array( $v ) ) {
					foreach ( $v as $k2 => $v2 ) {
						$this->settings[ $k ][ $k2 ] = $v2;
					}
				} else {
					$this->settings[ $k ] = $v;
				}
			}
		}
		if ( ! empty( $this->settings ) ) {
			update_option( 'icl_sitepress_settings', $this->settings );
		}
		do_action( 'icl_save_settings', $settings );
	}

	/**
	 * @since 3.1
	 */
	function get_settings() {
		return $this->settings;
	}

	function filter_get_setting($value, $key) {
		return $this->get_setting($key, $value);
	}

	/**
	 * @param string     $key
	 * @param mixed|bool $default
	 *
	 * @since 3.1
	 *
	 * @return bool|mixed
	 */
	function get_setting( $key, $default = false ) {
		return wpml_get_setting_filter( $default, $key );
	}

	function action_set_setting($key, $value, $save_now) {
		$this->set_setting($key, $value, $save_now);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $save_now	Immediately update the settings record in the DB
	 *
	 * @since 3.1
     */
    function set_setting($key, $value, $save_now = false) {
        icl_set_setting($key, $value, $save_now);
    }

	function get_user_preferences() {
		if ( ! isset( $this->user_preferences ) || ! $this->user_preferences ) {
			$this->user_preferences = get_user_meta( $this->get_current_user()->ID, '_icl_preferences', true );
		}
		if ( (is_array( $this->user_preferences) && $this->user_preferences == array(0 => false)) || !$this->user_preferences ) {
			$this->user_preferences = array();
		}
		if ( ! is_array( $this->user_preferences ) ) {
			$this->user_preferences = (array) $this->user_preferences;
		}

		return $this->user_preferences;
	}

	function set_user_preferences($value) {
		$this->user_preferences = $value;
	}

	function save_user_preferences()
	{
		update_user_meta( $this->get_current_user()->ID, '_icl_preferences', $this->user_preferences );
	}

	function get_option( $option_name )
	{
		return isset( $this->settings[ $option_name ] ) ? $this->settings[ $option_name ] : null;
	}

	function verify_settings() {

		$default_settings = array(
			'interview_translators'              => 1,
			'existing_content_language_verified' => 0,
			'language_negotiation_type'          => 3,
			'theme_localization_type'            => 1,
			'icl_lso_link_empty'                 => 0,
			'sync_page_ordering'                 => 1,
			'sync_page_parent'                   => 1,
			'sync_page_template'                 => 1,
			'sync_ping_status'                   => 1,
			'sync_comment_status'                => 1,
			'sync_sticky_flag'                   => 1,
			'sync_password'                      => 1,
			'sync_private_flag'                  => 1,
			'sync_post_format'                   => 1,
			'sync_delete'                        => 0,
			'sync_delete_tax'                    => 0,
			'sync_post_taxonomies'               => 1,
			'sync_post_date'                     => 0,
			'sync_taxonomy_parents'              => 0,
			'translation_pickup_method'          => 0,
			'notify_complete'                    => 1,
			'translated_document_status'         => 1,
			'remote_management'                  => 0,
			'auto_adjust_ids'                    => 1,
			'alert_delay'                        => 0,
			'promote_wpml'                       => 0,
			'automatic_redirect'                 => 0,
			'remember_language'                  => 24,
			'icl_lang_sel_copy_parameters'       => '',
			'translated_document_page_url'       => 'auto-generate',
			'sync_comments_on_duplicates '       => 0,
			'seo'                                => array( 'head_langs' => 1, 'canonicalization_duplicates' => 1, 'head_langs_priority' => 1 ),
			'posts_slug_translation'             => array( 'on' => 0 ),
			'languages_order'                    => '',
			'urls'                               => array( 'directory_for_default_language' => 0, 'show_on_root' => '', 'root_html_file_path' => '', 'root_page' => 0, 'hide_language_switchers' => 1 ),
			'xdomain_data'						 => $this->get_wp_api()->constant( 'WPML_XDOMAIN_DATA_GET' ),
		);

		//configured for three levels
		$update_settings = false;
		foreach ( $default_settings as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $k2 => $v2 ) {
					if ( is_array( $v2 ) ) {
						foreach ( $v2 as $k3 => $v3 ) {
							if ( !isset( $this->settings[ $key ][ $k2 ][ $k3 ] ) ) {
								$this->settings[ $key ][ $k2 ][ $k3 ] = $v3;
								$update_settings                      = true;
							}
						}
					} else {
						if ( !isset( $this->settings[ $key ][ $k2 ] ) ) {
							$this->settings[ $key ][ $k2 ] = $v2;
							$update_settings               = true;
						}
					}
				}
			} else {
				if ( !isset( $this->settings[ $key ] ) ) {
					$this->settings[ $key ] = $value;
					$update_settings        = true;
				}
			}
		}


		if ( $update_settings ) {
			$this->save_settings();
		}
	}

    function get_active_languages( $refresh = false, $major_first = false, $order_by = 'english_name' ) {
	    /** @var WPML_Request  $wpml_request_handler*/
		global $wpml_request_handler;

        $in_language = defined( 'WP_ADMIN' ) && $this->admin_language ? $this->admin_language : null;
        $in_language = $in_language === null ? $this->get_current_language() : $in_language;
        $in_language = $in_language ? $in_language : $this->get_default_language();

        $active_languages       = $this->get_languages( $in_language, true, $refresh, $major_first, $order_by );
        $active_languages       = isset($active_languages[$in_language]) ? $active_languages
                                    : $this->get_languages( $in_language, true, true, $major_first, $order_by );
        $active_languages       = $active_languages ? $active_languages : array();
	    $this->active_languages = $wpml_request_handler->show_hidden()
		    ? $active_languages
		    : array_diff_key( $active_languages, array_fill_keys($this->get_setting ( 'hidden_languages', array()), 1) );

        return $this->active_languages;
    }

	/**
	 * Returns an input array of languages, that are in the form of associative arrays,
	 * ordered by the user-chosen language order
	 *
	 * @param array[] $languages
	 *
	 * @return array[]
	 */
	function order_languages( $languages ) {

		$ordered_languages = array();
		if ( is_array( $this->settings[ 'languages_order' ] ) ) {
			foreach ( $this->settings[ 'languages_order' ] as $code ) {
				if ( isset( $languages[ $code ] ) ) {
					$ordered_languages[ $code ] = $languages[ $code ];
					unset( $languages[ $code ] );
				}
			}
		} else {
			// initial save
			$iclsettings[ 'languages_order' ] = array_keys( $languages );
			$this->save_settings( $iclsettings );
		}

		if ( ! empty( $languages ) ) {
			foreach ( $languages as $code => $lang ) {
				$ordered_languages[ $code ] = $lang;
			}

		}

		return $ordered_languages;
	}

	/**
	 * @param $lang_code
	 * Checks if a given language code belongs to a currently active language.
	 * @return bool
	 */
	function is_active_language( $lang_code ) {
		$result = false;
		$active_languages = $this->get_active_languages();
		foreach ( $active_languages as $lang ) {
			if ( $lang_code == $lang[ 'code' ] ) {
				$result = true;
				break;
			}
		}

		return $result;
	}

	public function get_languages( $lang = false, $active_only = false, $refresh = false, $major_first = false, $order_by = 'english_name') {
		$res = false;
		if ( !$lang ) {
            $lang = $this->get_default_language();
        }

		if ( $active_only && ! $refresh ) {
			$res = $this->get_language_name_cache()->get( 'in_language_' . $lang . '_' . $major_first . '_' . $order_by );
        }

		if ( ! $res && ! $active_only && ! $refresh ) {
			$res = $this->get_language_name_cache()->get( 'all_language_' . $lang . '_' . $major_first . '_' . $order_by  );
        }

		if ( ! $res ) {
			$setup_instance = wpml_get_setup_instance();
			$res = $setup_instance->refresh_active_lang_cache( $lang, $active_only, $major_first, $order_by );
		}

		return $res;
    }

    function get_language_details( $code ) {
        if ( $this->get_wp_api()->is_admin() ) {
            $dcode = $this->admin_language;
        } else {
            $dcode = $code;
        }
	    $details = $this->get_language_name_cache()->get( 'language_details_' . $code . $dcode );

        if ( !$details ) {
            $language_details = $this->get_languages( $dcode );
            $details = isset( $language_details[ $code ] ) ? $language_details[ $code ] : false;
        }

        return $details;
    }

	function get_language_code( $english_name ) {
		$query  = $this->wpdb->prepare( " SELECT code FROM {$this->wpdb->prefix}icl_languages WHERE english_name = %s LIMIT 1", $english_name );
		$code   = $this->wpdb->get_var( $query );

		return $code;
	}

	function get_language_code_from_locale( $locale ) {
		$query  = $this->wpdb->prepare( " SELECT code FROM {$this->wpdb->prefix}icl_languages WHERE default_locale = %s LIMIT 1", $locale );
		$code   = $this->wpdb->get_var( $query );

		return $code;
	}

	function get_locale_from_language_code( $code ) {
		$query  = $this->wpdb->prepare( " SELECT default_locale FROM {$this->wpdb->prefix}icl_languages WHERE code = %s LIMIT 1", $code );
		$locale   = $this->wpdb->get_var( $query );

		return $locale;
	}

	function get_default_language() {

		return isset( $this->settings[ 'default_language' ] ) ? $this->settings[ 'default_language' ] : false;
	}

	function get_current_language() {
		/**
		 * @var WPML_Request             $wpml_request_handler
		 * @var WPML_Language_Resolution $wpml_language_resolution
		 */
		global $wpml_request_handler, $wpml_language_resolution;

		$this->this_lang = $this->this_lang ? $this->this_lang : $wpml_request_handler->get_requested_lang();
		$this->this_lang = $this->this_lang ? $this->this_lang : $this->get_default_language();

		return $wpml_language_resolution->current_lang_filter( $this->this_lang );
	}

	/**
	 * Switches whole site to the given language or back to the current language
	 * that was set when first calling this function.
	 *
	 * @param null|string $code language code to switch into, will revert to
	 * initial language if null is given
	 * @param bool|string $cookie_lang optionally also switch the cookie language
	 * to the value given
	 */
	public function switch_lang( $code = null, $cookie_lang = false ) {
		/**
		 * @var WPML_Request $wpml_request_handler
		 * @var WPML_Language_Resolution $wpml_language_resolution
		 */
        global $wpml_language_resolution, $wpml_request_handler;

		$this->original_language = $this->original_language === null
			? $this->get_current_language() : $this->original_language;
		if ( is_null( $code ) ) {
			$this->this_lang = $this->original_language;
			if ( ! empty( $this->original_language_cookie ) ) {
				$wpml_request_handler->set_language_cookie( $this->original_language_cookie );
				$this->original_language_cookie = false;
			}
		} else {
			if ( $code === 'all' || in_array( $code, $wpml_language_resolution->get_active_language_codes(), true ) ) {
				$this->this_lang = $code;
			}
			if ( $cookie_lang ) {
				$this->original_language_cookie = $wpml_request_handler->get_cookie_lang();
				$wpml_request_handler->set_language_cookie( $code );
			}
		}
		$GLOBALS['icl_language_switched'] = true;
		do_action( 'wpml_language_has_switched', $code, $cookie_lang, $this->original_language );
	}

	function set_default_language( $code ) {
		$previous_default = $this->get_setting('default_language');
		$this->set_setting( 'default_language', $code);
		$this->set_setting('admin_default_language', $code);
		$this->save_settings();

		do_action('icl_after_set_default_language',$code, $previous_default);

		// change WP locale
		$locale = $this->get_locale( $code );
		if ( $locale ) {
			update_option( 'WPLANG', $locale );
		}

		$user_language = new WPML_User_Language( $this );
		$user_language->sync_default_admin_user_languages();

		return $code !== 'en' && ! file_exists( WP_LANG_DIR . '/' . $locale . '.mo' ) ? 1 : true;
	}

	function js_load()
	{
		global $pagenow, $wpdb, $wpml_post_translations, $wpml_term_translations;
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$page                  = filter_input( INPUT_GET, 'page' );
			$page                  = $page !== null ? basename( $_GET['page'] ) : null;
			$page_basename         = $page === null ? false : preg_replace( '/[^\w-]/',
			                                                                '',
			                                                                str_replace( '.php', '', $page ) );
			$this->scripts_handler = new WPML_Admin_Scripts_Setup( $wpdb,
			                                                       $this,
			                                                       $wpml_post_translations,
			                                                       $wpml_term_translations,
			                                                       $page_basename );

			if ( isset( $_SERVER[ 'SCRIPT_NAME' ] ) && ( strpos( $_SERVER[ 'SCRIPT_NAME' ], 'post-new.php' ) || strpos( $_SERVER[ 'SCRIPT_NAME' ], 'post.php' ) ) ) {
				wp_register_script( 'sitepress-post-edit-tags', ICL_PLUGIN_URL . '/res/js/post-edit-terms.js', array( 'jquery', 'underscore' ) );
				$post_edit_messages = array(
					'switch_language_title' => __( 'You are about to change the language of {post_name}.', 'sitepress' ),
					'switch_language_alert' => __( 'All categories and tags will be translated if possible.', 'sitepress' ),
					'connection_loss_alert' => __( 'The following terms do not have a translation in the chosen language and will be disconnected from this post:', 'sitepress' ),
					'loading'               => __( 'Loading Language Data for {post_name}', 'sitepress' ),
					'switch_language_message' => __( 'Please make sure that you\'ve saved all the changes. We will have to reload the page.', 'sitepress' ),
					'switch_language_confirm' => __( 'Do you want to continue?', 'sitepress' ),
                    '_nonce'                  => wp_create_nonce('wpml_switch_post_lang_nonce')
				);
				wp_localize_script( 'sitepress-post-edit-tags', 'icl_post_edit_messages', $post_edit_messages );
				wp_enqueue_script( 'sitepress-post-edit-tags' );
			}

			if ( isset( $_SERVER[ 'SCRIPT_NAME' ] ) &&  strpos( $_SERVER[ 'SCRIPT_NAME' ], 'edit.php' ) ) {
				wp_register_script( 'sitepress-post-list-quickedit', ICL_PLUGIN_URL . '/res/js/post-list-quickedit.js', array( 'jquery') );
				wp_enqueue_script( 'sitepress-post-list-quickedit' );
			}

			wp_enqueue_script( 'sitepress-scripts', ICL_PLUGIN_URL . '/res/js/scripts.js', array( 'jquery' ), ICL_SITEPRESS_VERSION );
			if ( isset( $page_basename ) && file_exists( ICL_PLUGIN_PATH . '/res/js/' . $page_basename . '.js' ) ) {
				$dependencies = array();
				$localization = false;
				$color_picker_handler = 'wp-color-picker';
				switch ( $page_basename ) {
					case 'languages':
						$dependencies[ ] = $color_picker_handler;
						$dependencies[ ] = 'sitepress-scripts';
						$dependencies[ ] = 'wpml-domain-validation';
						$dependencies[ ] = 'jquery-ui-dialog';
						break;
					case 'troubleshooting':
						$dependencies [ ] = 'jquery-ui-dialog';
						$localization = array(
							'object_name' => 'troubleshooting_strings',
							'strings'     => array(
								'success_1'       => __( "Post type and source language assignment have been fixed for ", 'sitepress' ),
								'success_2'       => __( " elements", 'sitepress' ),
								'no_problems'     => __( "No errors were found in the assignment of post types." ),
								'suffixesRemoved' => __( "Language suffixes were removed from the selected terms." ),
								'done'            => __( 'Done', 'sitepress' ),
								'termNamesNonce'  => wp_create_nonce('update_term_names_nonce'),
								'cacheClearNonce' => wp_create_nonce('cache_clear'),
							)
						);
						wp_enqueue_style("wp-jquery-ui-dialog");
						break;
					case 'menus-sync':
						$localization = array(
							'object_name' => 'menus_sync',
							'strings'     => array(
								'text1' => esc_html__( "Your menu includes custom items, which you need to translate using WPML's String Translation.", 'sitepress' ),
								'text2' => esc_html__( '1. Translate these strings: ', 'sitepress' ),
								'text3' => esc_html__( "2. When you're done translating, return here and run the menu synchronization again. This will use the strings that you translated to update the menus.", 'sitepress' ),
							)
						);
						break;
				}
				$handle = 'sitepress-' . $page_basename;
				wp_register_script( $handle, ICL_PLUGIN_URL . '/res/js/' . $page_basename . '.js', $dependencies, ICL_SITEPRESS_VERSION );
				if ( $localization ) {
					wp_localize_script( $handle, $localization[ 'object_name' ], $localization[ 'strings' ] );
				}
				if(in_array($color_picker_handler, $dependencies)) {
					wp_enqueue_style( $color_picker_handler );
				}
				wp_enqueue_script( $handle );
			}

			if ( $pagenow == 'edit.php' ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'language_filter' ) );
			}

			wp_enqueue_style( 'wpml-select-2', ICL_PLUGIN_URL . '/lib/select2/select2.css' );

		}
	}

	function front_end_js()
	{
		if ( defined( 'ICL_DONT_LOAD_LANGUAGES_JS' ) && ICL_DONT_LOAD_LANGUAGES_JS ) {
			return;
		}
		wp_register_script( 'sitepress', ICL_PLUGIN_URL . '/res/js/sitepress.js', false );
		wp_enqueue_script( 'sitepress' );

        $vars = array(
            'current_language'  => $this->this_lang,
            'icl_home'          => $this->language_url(),
            'ajax_url'          => $this->convert_url( admin_url('admin-ajax.php'), $this->this_lang ),
            'url_type'          => $this->settings['language_negotiation_type']
        );

		wp_localize_script( 'sitepress', 'icl_vars', $vars );
	}

	function rtl_fix()
	{
		global $wp_styles;
		if ( !empty( $wp_styles ) && $this->is_rtl() ) {
			$wp_styles->text_direction = 'rtl';
		}
	}

	function process_forms() {

		if ( isset( $_POST[ 'icl_post_action' ] ) ) {
			switch ( $_POST[ 'icl_post_action' ] ) {
				case 'save_theme_localization':
					$locales = array();
					foreach ( $_POST as $k => $v ) {
						if ( 0 !== strpos( $k, 'locale_file_name_' ) || !trim( $v ) ) {
							continue;
						}
						$locales[ str_replace( 'locale_file_name_', '', $k ) ] = $v;
					}
					if ( !empty( $locales ) ) {
						$this->set_locale_file_names( $locales );
					}
					break;
			}
			return;
		}

		if ( wp_verify_nonce(
			(string)filter_input( INPUT_POST, 'icl_initial_languagenonce', FILTER_SANITIZE_STRING ),
			'icl_initial_language'
		) ) {
			$setup_instance = wpml_get_setup_instance ();
			$first_lang = filter_input ( INPUT_POST, 'icl_initial_language_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$this->admin_language = $first_lang;
			$setup_instance->finish_step1($first_lang);
		} elseif ( wp_verify_nonce(
			(string)filter_input( INPUT_POST, 'icl_site_description_wizardnounce', FILTER_SANITIZE_STRING ),
			'icl_site_description_wizard'
		) ) {
			if ( isset( $_POST[ 'icl_content_trans_setup_back_2' ] ) ) {
				// back button.
				$this->settings[ 'content_translation_languages_setup' ]   = 0;
				$this->settings[ 'content_translation_setup_wizard_step' ] = 1;
				$this->save_settings();
			} elseif ( isset( $_POST[ 'icl_content_trans_setup_next_2' ] ) || isset( $_POST[ 'icl_content_trans_setup_next_2_enter' ] ) ) {
				// next button.
				$description = $_POST[ 'icl_description' ];
				if ( $description == "" ) {
					$_POST[ 'icl_form_errors' ] = __( 'Please provide a short description of the website so that translators know what background is required from them.', 'sitepress' );
				} else {
					$this->settings[ 'icl_site_description' ]                  = $description;
					$this->settings[ 'content_translation_setup_wizard_step' ] = 3;
					$this->save_settings();
				}
			}
		}
	}

	function post_edit_language_options() {
        /** @var TranslationManagement $iclTranslationManagement */
		global $post, $iclTranslationManagement, $post_new_file, $post_type_object;

		if ( ! isset( $post ) || ! $this->get_setting( 'setup_complete', false ) ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) ) {
			add_meta_box( 'icl_div_config', __( 'Multilingual Content Setup', 'sitepress' ), array( $this, 'meta_box_config' ), $post->post_type, 'normal', 'low' );
		}

		if ( filter_input(INPUT_POST, 'icl_action' ) === 'icl_mcs_inline' ) {
			$custom_post_type = filter_input(INPUT_POST, 'custom_post');
			if ( !in_array($custom_post_type, array( 'post', 'page' ) ) ) {
				$translate = (int)filter_input(INPUT_POST, 'translate');
				$iclsettings[ 'custom_posts_sync_option' ][ $custom_post_type ] = $translate;
				if ( $translate ) {
					$this->verify_post_translations( $custom_post_type );
				}
			}

			$custom_taxs_off = (array)filter_input(INPUT_POST, 'custom_taxs_off', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
			$custom_taxs_on = (array)filter_input(INPUT_POST, 'custom_taxs_on', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
			$tax_sync_options = array_merge(array_fill_keys($custom_taxs_on, 1), array_fill_keys($custom_taxs_off, 0));
			foreach ( $tax_sync_options as $key => $setting ) {
				$iclsettings[ 'taxonomies_sync_option' ][ $key ] = $setting;
				if($setting){
					$this->verify_taxonomy_translations( $key );
				}
			}

			$cf_names = (array)filter_input(INPUT_POST, 'cfnames', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
			$cf_vals = (array)filter_input(INPUT_POST, 'cfvals', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
			if ( in_array ( 1, $cf_vals ) === true ) {
				global $wpml_post_translations;
				$original_post_id = $wpml_post_translations->get_original_element ( $post->ID, true );
				$translations     = array_diff($wpml_post_translations->get_element_translations ( $original_post_id ), array($original_post_id));
			}
			foreach ( $cf_names as $k => $v ) {
				$custom_field_name    = base64_decode ( $v );
				$cf_translation_state = isset( $cf_vals[ $k ] ) ? (int) $cf_vals[ $k ] : 0;
				$iclTranslationManagement->settings[ 'custom_fields_translation' ][ $custom_field_name ] = $cf_translation_state;
				$iclTranslationManagement->save_settings();

				// sync the custom fields for the current post
				if ( 1 === $cf_translation_state ) {
					/**
					 * @var int[] $translations
					 * @var int $original_post_id
					 */
					foreach ( $translations as $translated_id ) {
						$this->_sync_custom_field($original_post_id, $translated_id, $custom_field_name);
					}
				}
			}
			if ( !empty( $iclsettings ) ) {
				$this->save_settings( $iclsettings );
			}
		}

		$post_types = array_keys( $this->get_translatable_documents() );
		if ( in_array( $post->post_type, $post_types ) ) {
			add_meta_box( 'icl_div', __( 'Language', 'sitepress' ), array( $this, 'meta_box' ), $post->post_type, 'side', 'high' );
		}

		//Fix the "Add new" button adding the language argument, so to create new content in the same language
		if(isset($post_new_file) && isset($post_type_object) && $this->is_translated_post_type($post_type_object->name)) {
			$post_language = $this->get_language_for_element($post->ID, 'post_' . $post_type_object->name);
			$post_new_file = add_query_arg(array('lang' => $post_language), $post_new_file);
		}
	}

	function set_element_language_details_action( $args ) {
		$element_id           = $args[ 'element_id' ];
		$element_type         = isset( $args[ 'element_type' ] ) ? $args[ 'element_type' ] : 'post_post';
		$trid                 = $args[ 'trid' ];
		$language_code        = $args[ 'language_code' ];
		$source_language_code = isset( $args[ 'source_language_code' ] ) ? $args[ 'source_language_code' ] : null;
		$check_duplicates     = isset( $args[ 'check_duplicates' ] ) ? $args[ 'check_duplicates' ] : true;
		$result = $this->set_element_language_details( $element_id, $element_type, $trid, $language_code, $source_language_code, $check_duplicates );
		$args[ 'result' ] = $result;
	}

	/**
	 * @param int         $el_id
	 * @param string      $el_type
	 * @param int         $trid
	 * @param string      $language_code
	 * @param null|string $src_language_code
	 * @param bool        $check_duplicates
	 *
	 * @return bool|int|null|string
	 */
	public function set_element_language_details(
		$el_id,
		$el_type = 'post_post',
		$trid,
		$language_code,
		$src_language_code = null,
		$check_duplicates = true
	) {
		if ( ! $this->language_setter ) {
			$this->language_setter = new WPML_Set_Language( $this,
				$this->wpdb,
				$this->post_translation,
				$this->term_translation );
		}

		return $this->language_setter->set(
			$el_id,
			$el_type,
			$trid,
			$language_code,
			$src_language_code,
			$check_duplicates
		);
	}

	function delete_element_translation( $trid, $element_type, $language_code = false ) {
		$result = false;

		if ( $trid !== false && is_numeric( $trid ) && $element_type !== false && is_string( $trid ) ) {
			$delete_where   = array( 'trid' => $trid, 'element_type' => $element_type );
			$delete_formats = array( '%d', '%s' );

			if ( $language_code ) {
				$delete_where[ 'language_code' ] = $language_code;
				$delete_formats[ ]               = '%s';
			}

			$context = explode( '_', $element_type );
			$update_args = array(
				'trid' => $trid,
				'element_type' => $element_type,
				'context' => $context[0]
			);

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

			$result = $this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', $delete_where, $delete_formats );

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );

			$this->get_translations_cache()->clear();
		}

		return $result;
	}

	function get_element_language_details( $el_id, $el_type = 'post_post' ) {
		$details = false;
		if ( $el_id ) {
			if ( strpos( $el_type, 'post_' ) === 0 ) {
				$details = $this->post_translation->get_element_language_details( $el_id, OBJECT );
			}
			if ( strpos( $el_type, 'tax_' ) === 0 ) {
				$details = $this->term_translation->get_element_language_details( $el_id, OBJECT );
			}
			if ( ! $details ) {
				$cache_key      = $el_id . ':' . $el_type;
				$cache_group    = 'element_language_details';
				$cached_details = wp_cache_get( $cache_key, $cache_group );
				if ( $cached_details ) {
					return $cached_details;
				}
				if ( $this->get_translations_cache()->has_key( $el_id . $el_type ) ) {
					return $this->get_translations_cache()->get( $el_id . $el_type );
				}
				$details_query = "
				SELECT trid, language_code, source_language_code
				FROM {$this->wpdb->prefix}icl_translations
				WHERE element_id=%d AND element_type=%s
				";
				$details_prepare = $this->wpdb->prepare( $details_query, array( $el_id, $el_type ) );
				$details              = $this->wpdb->get_row( $details_prepare );
				$this->get_translations_cache()->set( $el_id . $el_type, $details );

				wp_cache_add( $cache_key, $details, $cache_group );
			}
		}

		return $details;
	}

	public function _sync_custom_field( $post_id_from, $post_id_to, $meta_key ) {
		$sql         = "SELECT meta_value FROM {$this->wpdb->postmeta} WHERE post_id=%d AND meta_key=%s";
		$values_from = $this->wpdb->get_results( $this->wpdb->prepare( $sql, array( $post_id_from, $meta_key ) ), ARRAY_N );
		$values_to   = $this->wpdb->get_results( $this->wpdb->prepare( $sql, array( $post_id_to, $meta_key ) ), ARRAY_N );

		if ( ! empty( $values_from ) ) {
			$values_from = call_user_func_array( 'array_merge', $values_from );
		}

		if ( ! empty( $values_to ) ) {
			$values_to   = call_user_func_array( 'array_merge', $values_to );
		}

		$removed = array_diff( $values_to, $values_from );
		foreach ( $removed as $v ) {
			delete_post_meta( $post_id_to, $meta_key, maybe_unserialize( $v ) );
		}

		$added = array_diff( $values_from, $values_to );
		foreach ( $added as $v ) {
			$v = maybe_unserialize( $v );
			$v = wp_slash( $v );
			add_post_meta( $post_id_to, $meta_key, $v );
		}

		do_action( 'wpml_after_copy_custom_field', $post_id_from, $post_id_to, $meta_key );
	}

	function copy_custom_fields( $post_id_from, $post_id_to ) {
		$cf_copy = array();

		if ( isset( $this->settings[ 'translation-management' ][ 'custom_fields_translation' ] ) ) {
			foreach ( $this->settings[ 'translation-management' ][ 'custom_fields_translation' ] as $meta_key => $option ) {
				if ( $option == WPML_COPY_CUSTOM_FIELD ) {
					$cf_copy[ ] = $meta_key;
				}
			}
		}

		foreach ( $cf_copy as $meta_key ) {
			$meta_from = get_post_meta($post_id_from, $meta_key) ;
			$meta_to = get_post_meta($post_id_to, $meta_key) ;
			if($meta_from || $meta_to) {
				$this->_sync_custom_field( $post_id_from, $post_id_to, $meta_key );
			}
		}
	}

	/**
	 *This method does nothing and is only there as a placeholder for backward compatibility with old Types versions!
	 *
     * @deprecated Since WPML 3.1.9
	 *
	 * @param $meta_id
	 * @param $object_id
	 * @param $meta_key
	 * @param $_meta_value
	 */
	function update_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
		return;
	}

	/**
	 *This method does nothing and is only there as a placeholder for backward compatibility with old Types versions!
	 *
     * @deprecated Since WPML 3.1.9
     *
     * @param $meta_id
	 */
	function delete_post_meta( $meta_id ) {
		return;
	}

	/* Custom fields synchronization - END */

	function get_element_translations_filter( $value, $trid, $el_type = 'post_post', $skip_empty = false, $all_statuses = false, $skip_cache = false ) {
		return $this->get_element_translations( $trid, $el_type, $skip_empty, $all_statuses, $skip_cache );
	}


	public function get_original_element_id_filter($empty, $element_id, $element_type = 'post_post') {
		$original_element_id = $this->get_original_element_id($element_id, $element_type);

		return $original_element_id;
	}

	public function get_element_trid_filter($empty, $element_id, $element_type = 'post_post') {
		$trid = $this->get_element_trid($element_id, $element_type);

		return $trid;
	}

	function is_original_content_filter($default = false, $element_id, $element_type = 'post_post') {
		$is_original_content = $default;

		$trid = $this->get_element_trid($element_id, $element_type);

		$translations = $this->get_element_translations($trid, $element_type);

		if($translations) {
			foreach($translations as $language_code => $translation) {
				if($translation->element_id == $element_id) {
					$is_original_content = $translation->original;
					break;
				}
			}
		}

		return $is_original_content;
	}

	/**
	 * @param int    $trid
	 * @param string $el_type Use comment, post, page, {custom post time name}, nav_menu, nav_menu_item, category, post_tag, etc. (prefixed with 'post_', 'tax_', or nothing for 'comment')
	 * @param bool   $skip_empty
	 * @param bool   $all_statuses
	 * @param bool   $skip_cache
	 * @param bool   $skip_recursions
	 *
	 * @return array|bool|mixed
	 */
	function get_element_translations( $trid, $el_type = 'post_post', $skip_empty = false, $all_statuses = false, $skip_cache = false, $skip_recursions = false ) {
		$wpml_translations                  = new WPML_Translations( $this );
		$wpml_translations->skip_empty      = $skip_empty;
		$wpml_translations->all_statuses    = $all_statuses;
		$wpml_translations->skip_cache      = $skip_cache;
		$wpml_translations->skip_recursions = $skip_recursions;

		return $wpml_translations->get_translations( $trid, $el_type );
	}

	function clear_elements_cache( $ids, $taxonomy ) {
		$cache = new WPML_WP_Cache( WPML_ELEMENT_TRANSLATIONS_CACHE_GROUP );
		$cache->flush_group_cache();
	}

	static function get_original_element_id($element_id, $element_type = 'post_post', $skip_empty = false, $all_statuses = false, $skip_cache = false) {
		$cache_key_args = array_filter( array( $element_id, $element_type, $skip_empty, $all_statuses ) );
		$cache_key = md5(wp_json_encode( $cache_key_args ));
		$cache_group = 'original_element';

		$temp_elements = $skip_cache ? false : wp_cache_get($cache_key, $cache_group);
		if($temp_elements) return $temp_elements;

		global $sitepress;

		$original_element_id = false;

		$trid = $sitepress->get_element_trid($element_id, $element_type);
		if($trid) {
			$element_translations = $sitepress->get_element_translations($trid, $element_type,$skip_empty,$all_statuses,$skip_cache);

			foreach($element_translations as $element_translation) {
				if($element_translation->original) {
					$original_element_id = $element_translation->element_id;
					break;
				}
			}
		}

		if($original_element_id) {
			wp_cache_set($cache_key, $original_element_id, $cache_group);
		}
		return $original_element_id;
	}

	/**
	 * @param int    $element_id Use term_taxonomy_id for taxonomies, post_id for posts
	 * @param string $el_type    Use comment, post, page, {custom post time name}, nav_menu, nav_menu_item, category, post_tag, etc. (prefixed with 'post_', 'tax_', or nothing for 'comment')
	 *
	 * @return bool|mixed|null|string
	 */
	function get_element_trid( $element_id, $el_type = 'post_post' ) {
		if ( strpos ( $el_type, 'tax_' ) === 0 ) {
			/** @var WPML_Term_Translation $wpml_term_translations */
			global $wpml_term_translations;

			return $wpml_term_translations->get_element_trid ( $element_id );
		} elseif ( strpos ( $el_type, 'post_' ) === 0 ) {
			global $wpml_post_translations;

			return $wpml_post_translations->get_element_trid ( $element_id );
		} else {
			$cache_key = $element_id . ':' . $el_type;
			$cache_group = 'element_trid';
			$temp_trid = wp_cache_get( $cache_key, $cache_group );
			if ( (bool) $temp_trid === true ) {
				return $temp_trid;
			}

			$trid_prepared = $this->wpdb->prepare(
				"SELECT trid FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s",
				array( $element_id, $el_type )
			);

			$trid = $this->wpdb->get_var( $trid_prepared );

			if ( $trid ) {
				wp_cache_add( $cache_key, $trid, $cache_group );
			}
		}

		return $trid;
	}

	/**
	 * @param int $trid
	 *
	 * @return int|bool
	 */
	static function get_original_element_id_by_trid( $trid ) {
		global $wpdb;

		if ( (bool) $trid === true ) {
			$original_element_id_prepared = $wpdb->prepare( "SELECT element_id
															 FROM {$wpdb->prefix}icl_translations
															 WHERE trid=%d
															  AND source_language_code IS NULL
															 LIMIT 1",
															$trid );

			$element_id = $wpdb->get_var( $original_element_id_prepared );
		} else {
			$element_id = false;
		}

		return $element_id;
	}

	static function get_source_language_by_trid( $trid ) {
		global $wpdb;

		$source_language = null;
		if ( (bool) $trid === true ) {

			$cache = new WPML_WP_Cache( 'get_source_language_by_trid' );
			$found = false;
			$source_language = $cache->get( $trid, $found );
			if ( ! $found ) {
				$source_language_prepared = $wpdb->prepare( "
																SELECT language_code
																FROM {$wpdb->prefix}icl_translations
																WHERE trid=%d
																	AND source_language_code IS NULL
																LIMIT 1",
					$trid );

				$source_language = $wpdb->get_var( $source_language_prepared );

				$cache->set( $trid, $source_language );
			}
		}

		return $source_language;
	}

	public function get_element_translations_object( $element_type ) {
		global $wpml_post_translations, $wpml_term_translations, $wpml_cache_factory;

		$element_translations = null;
		if ( strpos ( $element_type, 'tax_' ) === 0 ) {
			$element_translations = $wpml_term_translations;
		} elseif ( strpos ( $element_type, 'post_' ) === 0 ) {
			$element_translations = $wpml_post_translations;
		} else {
			$element_translations = new WPML_Element_Type_Translation( $this->wpdb, $wpml_cache_factory, $element_type );
		}

		return $element_translations;
	}

	/**
	 * @param int    $element_id   Use term_taxonomy_id for taxonomies, post_id for posts
	 * @param string $element_type Use comment, post, page, {custom post time name}, nav_menu, nav_menu_item, category,
	 *                             post_tag, etc. (prefixed with 'post_', 'tax_', or nothing for 'comment')
	 *
	 * @return null|string
	 */
	function get_language_for_element( $element_id, $element_type = 'post_post' ) {
		$translation_object = $this->get_element_translations_object( $element_type );
		return $translation_object->get_element_lang_code( $element_id );
	}

	/**
	 * @param string $el_type     Use comment, post, page, {custom post time name}, nav_menu, nav_menu_item, category, post_tag, etc. (prefixed with 'post_', 'tax_', or nothing for 'comment')
	 * @param string $target_lang Target language code
	 * @param string $source_lang Source language code
	 *
	 * @return array
	 */
	function get_elements_without_translations( $el_type, $target_lang, $source_lang ) {
        $sql = $this->wpdb->prepare(
            "SELECT trid
             FROM {$this->wpdb->prefix}icl_translations
             WHERE language_code = %s
              AND element_type = %s",
            $target_lang,
            $el_type
        );

		$trids_for_target = $this->wpdb->get_col( $sql );
		if ( sizeof( $trids_for_target ) > 0 ) {
            $trids_for_target = wpml_prepare_in( $trids_for_target, '%d' );
			$not_trids        = 'AND trid NOT IN (' . $trids_for_target . ')';
		} else {
			$not_trids = '';
		}

		$join = $where = '';
		// exclude trashed posts
		if ( 0 === strpos( $el_type, 'post_' ) ) {
			$join .= " JOIN {$this->wpdb->posts} ON {$this->wpdb->posts}.ID = {$this->wpdb->prefix}icl_translations.element_id";
			$where .= " AND {$this->wpdb->posts}.post_status <> 'trash' AND {$this->wpdb->posts}.post_status <> 'auto-draft'";
		}

		// Now get all the elements that are in the source language that
		// are not already translated into the target language.
        $sql = $this->wpdb->prepare(
            "SELECT element_id
				FROM
					{$this->wpdb->prefix}icl_translations
					{$join}
			 WHERE language_code = %s
					{$not_trids}
                AND element_type= %s
					{$where}
				",
            $source_lang,
            $el_type
        );

		return $this->wpdb->get_col( $sql );
	}

	/**
	 * @param string $selected_language
	 * @param string $default_language
	 * @param string $post_type
	 *
	 * @used_by SitePress:meta_box
	 *
	 * @return array
	 */
	function get_posts_without_translations( $selected_language, $default_language, $post_type = 'post_post' ) {
		$untranslated_ids = $this->get_elements_without_translations( $post_type, $selected_language, $default_language );
		$untranslated = array();
		foreach ( $untranslated_ids as $id ) {
            $untranslated[ $id ] = $this->wpdb->get_var(
	            $this->wpdb->prepare( "SELECT post_title FROM {$this->wpdb->prefix}posts WHERE ID = %d", $id )
            );
		}

		return $untranslated;
	}

	public function get_orphan_translations( $trid, $post_type = 'post', $source_language ) {
		$results      = array();
		$translations = $this->get_element_translations( $trid, 'post_' . $post_type );
		if ( count( $translations ) === 1 ) {
			$sql                  = " SELECT trid, ";
			$language_codes       = array_keys( $this->get_active_languages() );
			$sql_languages        = array();
			$sql_languages_having = array();
			foreach ( $language_codes as $language_code ) {
				$sql_languages[] = "SUM(CASE language_code WHEN '" . esc_sql( $language_code ) . "' THEN 1 ELSE 0 END) AS `" . esc_sql( $language_code ) . '`';
				if ( $language_code == $source_language ) {
					$sql_languages_having[] = '`' . esc_sql( $language_code ) . '`= 0';
				}
			}
			$sql .= implode( ',', $sql_languages );
			$sql .= " 	FROM {$this->wpdb->prefix}icl_translations WHERE element_type = %s ";
			$sql .= 'GROUP BY trid ';
			$sql .= 'HAVING ' . implode( ' AND ', $sql_languages_having );
			$sql .= " ORDER BY trid;";
			$sql_prepared = $this->wpdb->prepare( $sql, array( 'post_' . $post_type ) );
			$trid_results = $this->wpdb->get_results( $sql_prepared, 'ARRAY_A' );
			$trid_list    = array_column( $trid_results, 'trid' );
			if ( $trid_list ) {
				$sql          = "SELECT trid AS value, CONCAT('[', t.language_code, '] ', (CASE p.post_title WHEN '' THEN CONCAT(LEFT(p.post_content, 30), '...') ELSE p.post_title END)) AS label
						FROM {$this->wpdb->posts} p
						INNER JOIN {$this->wpdb->prefix}icl_translations t
							ON p.ID = t.element_id
						WHERE t.element_type = %s
							AND t.language_code <> %s
							AND t.trid IN (" . wpml_prepare_in( $trid_list, '%d' ) . ") ";
				$sql_prepared = $this->wpdb->prepare( $sql, array( 'post_' . $post_type, $source_language ) );
				$results      = $this->wpdb->get_results( $sql_prepared );
			}
		}

		return $results;
	}

	/**
	 * @param WP_Post $post
	 */
	function meta_box( $post ) {

		do_action('wpml_post_edit_languages', $post);
	}

	function meta_box_config( $post ) {
		/** @var TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement,$wp_taxonomies, $wp_post_types, $sitepress_settings;
		if ( ! $this->settings[ 'setup_complete' ] ) {
			return false;
		}

		echo '<div class="icl_form_success" style="display:none">' . __( 'Settings saved', 'sitepress' ) . '</div>';

		$cp_editable = false;
		$checked     = false;
		if ( !in_array( $post->post_type, $this->get_always_translatable_post_types() ) ) {
			if ( !isset( $iclTranslationManagement->settings[ 'custom-types_readonly_config' ][ $post->post_type ] ) || $iclTranslationManagement->settings[ 'custom-types_readonly_config' ][ $post->post_type ] !== 0 ) {
				if ( in_array( $post->post_type, array_keys( $this->get_translatable_documents() ) ) ) {
					$checked   = ' checked="checked"';
					$radio_disabled = isset( $iclTranslationManagement->settings[ 'custom-types_readonly_config' ][ $post->post_type ] ) ? 'disabled="disabled"' : '';
				} else {
					$checked = $radio_disabled = '';
				}
				if ( !$radio_disabled ) {
					$cp_editable = true;
				}
				echo '<br style="line-height:8px;" /><label><input id="icl_make_translatable" type="checkbox" value="' . $post->post_type . '"' . $checked . $radio_disabled . '/>&nbsp;' . sprintf( __( "Make '%s' translatable", 'sitepress' ), $wp_post_types[ $post->post_type ]->labels->name ) . '</label><br style="line-height:8px;" />';
			}
		} else {
			echo '<input id="icl_make_translatable" type="checkbox" checked="checked" value="' . $post->post_type . '" style="display:none" />';
			$checked = true;
		}

		echo '<br clear="all" /><span id="icl_mcs_details">';
		if ( $checked ) {
			$custom_taxonomies = array_diff( get_object_taxonomies( $post->post_type ), array( 'post_tag', 'category', 'nav_menu', 'link_category', 'post_format' ) );
			if ( !empty( $custom_taxonomies ) ) {
				?>
				<table class="widefat">
					<thead>
					<tr>
						<th colspan="2"><?php _e( 'Custom taxonomies', 'sitepress' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $custom_taxonomies as $ctax ): ?>
						<?php
						$checked1 = ! empty( $sitepress_settings[ 'taxonomies_sync_option' ][ $ctax ] ) ? ' checked="checked"' : '';
						$checked0 = empty( $sitepress_settings[ 'taxonomies_sync_option' ][ $ctax ] ) ? ' checked="checked"' : '';
						$radio_disabled = isset( $iclTranslationManagement->settings[ 'taxonomies_readonly_config' ][ $ctax ] ) ? ' disabled="disabled"' : '';
						?>
						<tr>
							<td><?php echo $wp_taxonomies[ $ctax ]->labels->name ?></td>
							<td align="right">
								<label><input name="icl_mcs_custom_taxs_<?php echo $ctax ?>" class="icl_mcs_custom_taxs" type="radio"
											  value="<?php echo $ctax ?>" <?php echo $checked1; ?><?php echo $radio_disabled ?> />&nbsp;<?php _e( 'Translate', 'sitepress' ) ?></label>
								<label><input name="icl_mcs_custom_taxs_<?php echo $ctax ?>" type="radio" value="0" <?php echo $checked0; ?><?php echo $radio_disabled ?> />&nbsp;<?php _e( 'Do nothing', 'sitepress' ) ?></label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<br/>
			<?php
			}

			if ( $this->get_wp_api()->constant( 'WPML_TM_VERSION' ) ) {
				$settings_factory = $iclTranslationManagement->settings_factory();
				$settings_factory->show_system_fields = array_key_exists( 'show_system_fields', $_GET ) ? (bool) $_GET['show_system_fields'] : false;

				?>
				<p>
					<?php
					$toggle_system_fields= array(
						'url' => add_query_arg(array('show_system_fields' => !$settings_factory->show_system_fields)),
						'text' => $settings_factory->show_system_fields ? __('Hide system fields', 'wpml-translation-management') : __('Show system fields', 'wpml-translation-management'),
					);
					?>
					<a href="<?php echo $toggle_system_fields['url']?>"><?php echo $toggle_system_fields['text'];?></a>
				</p>
				<?php

				$settings_menu    = new WPML_TM_Post_Edit_Custom_Field_Settings_Menu( $this, $settings_factory, $post );
				echo $settings_menu->render();
				$custom_keys = $settings_menu->is_rendered();
			}

			if ( !empty( $custom_taxonomies ) || !empty( $custom_keys ) ) {
				echo '<small>' . __( 'Note: Custom taxonomies and custom fields are shared across different post types.', 'sitepress' ) . '</small>';
			}
		}
		echo '</span>';
		if ( $cp_editable || !empty( $custom_taxonomies ) || !empty( $custom_keys ) ) {
			echo '<p class="submit" style="margin:0;padding:0"><input class="button-secondary" id="icl_make_translatable_submit" type="button" value="' . __( 'Apply', 'sitepress' ) . '" /></p><br clear="all" />';
			wp_nonce_field( 'icl_mcs_inline_nonce', '_icl_nonce_imi' );
		} else {
			_e( 'Nothing to configure.', 'sitepress' );
		}
	}

	/**
	 * Filters the WP_Query in case of retrieving an ajax post list,
	 * e.g. links in the WYSIWYG post editor
	 *
	 * @param WP_Query $wpq
	 *
	 * @return WP_Query
	 */
	function pre_get_posts( $wpq ) {
		$post_action = isset( $_POST['action'] ) ? $_POST['action'] : null;
		if ( 'wp-link-ajax' === $post_action ) {
			/** @var WPML_Language_Resolution $wpml_language_resolution */
			global $wpml_language_resolution;
			$lang                                = $wpml_language_resolution->get_referrer_language_code();
			$this->this_lang                     = $lang;
			$wpq->query_vars['suppress_filters'] = false;
		}

		return $wpq;
	}

	function comment_feed_join( $join ) {
		global $wp_query;
		$type = isset($wp_query->query_vars['post_type']) && $wp_query->query_vars['post_type'] ? esc_sql( $wp_query->query_vars['post_type'] ) : 'post';

		$wp_query->query_vars['is_comment_feed'] = true;
		$join .= $this->wpdb->prepare( " JOIN {$this->wpdb->prefix}icl_translations t
                                    ON {$this->wpdb->comments}.comment_post_ID = t.element_id
                                        AND t.element_type = %s AND t.language_code = %s ",
		                               'post_' . $type,
		                               $this->this_lang );

		return $join;
	}

	/**
	 * @param string[] $clauses
	 * @param WP_Comment_Query $obj
	 *
	 * @return string[]
	 */
	function comments_clauses( $clauses, $obj ) {
		/** @var WPML_Query_Filter $wpml_query_filter */
		global $wpml_query_filter;

		return $wpml_query_filter->comments_clauses_filter ( $clauses, $obj );
	}

	function language_filter() {
		require ICL_PLUGIN_PATH . '/menu/post-menus/wpml-post-language-filter.class.php';
		$post_lang_filter = new WPML_Post_Language_Filter( $this->wpdb, $this );
		$post_lang_filter->register_scripts();

		return $post_lang_filter->post_language_filter();
	}

	/**
	 * @param array $arr Array of posts to filter
	 * @param array $get_page_arguments Arguments passed to the `get_pages` function
	 *
	 * @return array
	 */
	function exclude_other_language_pages2( $arr, $get_page_arguments ) {
		global $wpdb;
		$post_hooks = new WPML_Remove_Pages_Not_In_Current_Language( $wpdb, $this);

		return $post_hooks->filter_pages( $arr, $get_page_arguments );
	}

	function wp_dropdown_pages( $output ) {
		if ( isset( $_POST[ 'lang_switch' ] ) ) {
			$post_id = esc_sql( $_POST[ 'lang_switch' ] );
			$lang    = esc_sql( strip_tags( $_GET[ 'lang' ] ) );
			$parent  = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT post_parent FROM {$this->wpdb->posts} WHERE ID=%d", $post_id ) );
			if ( $parent ) {
				global $wpml_post_translations;
				$trid                 = $wpml_post_translations->get_element_trid($parent);
				$translated_parent_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT element_id
                                                                         FROM {$this->wpdb->prefix}icl_translations
                                                                         WHERE trid=%d
                                                                          AND element_type='post_page'
                                                                          AND language_code=%s", $trid, $lang) );
				if ( $translated_parent_id ) {
					$output = str_replace( 'selected="selected"', '', $output );
					$output = str_replace( 'value="' . $translated_parent_id . '"', 'value="' . $translated_parent_id . '" selected="selected"', $output );
				}
			}
		} elseif ( isset( $_GET[ 'lang' ] ) && isset( $_GET[ 'trid' ] ) ) {
			$lang                 = esc_sql( strip_tags( $_GET[ 'lang' ] ) );
			$trid                 = esc_sql( $_GET[ 'trid' ] );
			$post_type            = isset( $_GET[ 'post_type' ] ) ? $_GET[ 'post_type' ] : 'page';
			$elements_id          = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT element_id FROM {$this->wpdb->prefix}icl_translations
				 WHERE trid=%d AND element_type=%s AND element_id IS NOT NULL", $trid, 'post_' . $post_type ) );
			$translated_parent_id = 0;
			foreach ( $elements_id as $element_id ) {
				$parent               = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT post_parent FROM {$this->wpdb->posts} WHERE ID=%d", $element_id ) );
				$trid                 = $this->wpdb->get_var( $this->wpdb->prepare( "
					SELECT trid FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $parent, 'post_' . $post_type ) );
				$translated_parent_id = $this->wpdb->get_var( $this->wpdb->prepare( "
					SELECT element_id FROM {$this->wpdb->prefix}icl_translations
					WHERE trid=%d AND element_type=%s AND language_code=%s", $trid, 'post_' . $post_type, $lang ) );
				if ( $translated_parent_id ) {
					break;
				}
			}
			if ( $translated_parent_id ) {
				$output = str_replace( 'selected="selected"', '', $output );
				$output = str_replace( 'value="' . $translated_parent_id . '"', 'value="' . $translated_parent_id . '" selected="selected"', $output );
			}
		}
		if ( !$output ) {
			$output = '<select id="parent_id"><option value="">' . __( 'Main Page (no parent)', 'sitepress' ) . '</option></select>';
		}

		return $output;
	}

	function add_translate_options( $trid, $active_languages, $selected_language, $translations, $type ) {
		if ( $trid && $this->wp_api->is_term_edit_page() ):
			if(!$this->settings['setup_complete']){
				return false;
			}
	?>

		<div id="icl_translate_options">

			<?php
			// count number of translated and un-translated pages.
			$translations_found = 0;
			$untranslated_found = 0;
			foreach ( $active_languages as $lang ) {
				if ( $selected_language == $lang[ 'code' ] ) {
					continue;
				}
				if ( isset( $translations[ $lang[ 'code' ] ]->element_id ) ) {
					$translations_found += 1;
				} else {
					$untranslated_found += 1;
				}
			}
			?>

			<?php if ( $untranslated_found > 0 ): ?>

				<table cellspacing="1" class="icl_translations_table" style="min-width:200px;margin-top:10px;">
					<thead>
					<tr>
						<th colspan="2" style="padding:4px;background-color:#DFDFDF"><b><?php _e( 'Translate', 'sitepress' ); ?></b></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $active_languages as $lang ): if ( $selected_language == $lang[ 'code' ] ) {
						continue;
					} ?>
						<tr>
							<?php if ( !isset( $translations[ $lang[ 'code' ] ]->element_id ) ): ?>
								<td style="padding:4px;line-height:normal;"><?php echo $lang[ 'display_name' ] ?></td>
								<?php
								$taxonomy = $_GET[ 'taxonomy' ];
								$post_type_q = isset( $_GET[ 'post_type' ] ) ? '&amp;post_type=' . esc_html( $_GET[ 'post_type' ] ) : '';
								$add_link = admin_url( "edit-tags.php?taxonomy=" . esc_html( $taxonomy ) . "&amp;trid=" . $trid . "&amp;lang=" . $lang[ 'code' ] . "&amp;source_lang=" . $selected_language . $post_type_q );
								?>
								<td style="padding:4px;line-height:normal;"><a href="<?php echo $add_link ?>"><?php echo __( 'add', 'sitepress' ) ?></a></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( $translations_found > 0 ): ?>
				<p style="clear:both;margin:5px 0 5px 0">
					<b><?php _e( 'Translations', 'sitepress' ) ?></b>
					(<a class="icl_toggle_show_translations" href="#" <?php if (empty( $this->settings[ 'show_translations_flag' ] )): ?>style="display:none;"<?php endif; ?>><?php _e( 'hide', 'sitepress' ) ?></a><a
						class="icl_toggle_show_translations" href="#" <?php if (!empty( $this->settings[ 'show_translations_flag' ] )): ?>style="display:none;"<?php endif; ?>><?php _e( 'show', 'sitepress' ) ?></a>)

					<?php wp_nonce_field( 'toggle_show_translations_nonce', '_icl_nonce_tst' ) ?>
				<table cellspacing="1" width="100%" id="icl_translations_table" style="<?php if ( empty( $this->settings[ 'show_translations_flag' ] ) ): ?>display:none;<?php endif; ?>margin-left:0;">

					<?php foreach ( $active_languages as $lang ): if ( $selected_language === $lang[ 'code' ] )
						continue; ?>
						<tr>
							<?php if ( isset( $translations[ $lang[ 'code' ] ]->element_id ) ): ?>
								<td style="line-height:normal;"><?php echo $lang[ 'display_name' ] ?></td>
								<?php
								$taxonomy = $_GET[ 'taxonomy' ];
								$edit_link = get_edit_term_link( $translations[ $lang[ 'code' ] ]->term_id, $taxonomy, isset( $_GET[ 'post_type' ] ) ? $_GET[ 'post_type' ] : null );
								?>
								<td align="right" width="30%"
									style="line-height:normal;"><?php echo isset( $translations[ $lang[ 'code' ] ]->name ) ? '<a href="' . $edit_link . '" title="' . __( 'Edit', 'sitepress' ) . '">' . $translations[ $lang[ 'code' ] ]->name . '</a>' : __( 'n/a', 'sitepress' ) ?></td>

							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endif; ?>
			<br clear="all" style="line-height:1px;"/>
		</div>
	<?php
		endif;
	}

	/**
	 * @param string $name
	 *
	 * @deprecated deprecated since version 3.1.8
	 * @return array|mixed
	 */
	function the_category_name_filter( $name ) {
		if ( is_array( $name ) ) {
			foreach ( $name as $k => $v ) {
				$name[ $k ] = $this->the_category_name_filter( $v );
			}

			return $name;
		}
		if ( false === strpos( $name, '@' ) ) {
			return $name;
		}
		if ( false !== strpos( $name, '<a' ) ) {
			$int = preg_match_all( '|<a([^>]+)>([^<]+)</a>|i', $name, $matches );
			if ( $int && count( $matches[ 0 ] ) > 1 ) {
				$originals = $filtered = array();
				foreach ( $matches[ 0 ] as $m ) {
					$originals[ ] = $m;
					$filtered[ ]  = $this->the_category_name_filter( $m );
				}
				$name = str_replace( $originals, $filtered, $name );
			} else {
				$name_sh = strip_tags( $name );
				$exp     = explode( '@', $name_sh );
				$name    = str_replace( $name_sh, trim( $exp[ 0 ] ), $name );
			}
		} else {
			$name = preg_replace( '#(.*) @(.*)#i', '$1', $name );
		}

		return $name;
	}

	/**
	 * @param $terms
	 *
	 * @deprecated deprecated since version 3.1.8
	 * @return mixed
	 */
	function get_terms_filter( $terms ) {
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		foreach ( $terms as $k => $v ) {
			if ( isset( $terms[ $k ]->name ) ) {
				$terms[ $k ]->name = $this->the_category_name_filter( $terms[ $k ]->name );
			}
		}

		return $terms;
	}

	/**
	 * Wrapper for \WPML_Term_Actions::save_term_actions
	 *
	 * @param int    $cat_id
	 * @param int    $tt_id term taxonomy id of the new term
	 * @param string $taxonomy
	 *
	 * @uses \WPML_Term_Actions::save_term_actions to handle required actions
	 *                                               when creating a term
	 *
	 * @hook delete_term
	 */
	function create_term( $cat_id, $tt_id, $taxonomy ) {
		$term_actions = $this->get_term_actions_helper();
		$term_actions->save_term_actions( $tt_id, $taxonomy );
	}

	/**
	 * Wrapper for \WPML_Term_Actions::deleted_term_relationships
	 *
	 * @param int   $post_id
	 * @param array $delete_terms
	 *
	 * @uses \WPML_Term_Actions::deleted_term_relationships to handle required actions
	 *                                               when removing a term from a post
	 *
	 * @hook deleted_term_relationships
	 */
	function deleted_term_relationships( $post_id, $delete_terms ) {
		if ( $this->get_setting( 'sync_post_taxonomies' ) ) {
			$term_actions = $this->get_term_actions_helper();
			$term_actions->deleted_term_relationships( $post_id, $delete_terms );
		}
	}

	/**
	 * Wrapper for \WPML_Term_Actions::delete_term_actions
	 *
	 * @param mixed  $cat
	 * @param int    $tt_id term taxonomy id of the deleted term
	 * @param string $taxonomy
	 *
	 * @uses \WPML_Term_Actions::delete_term_actions to handle required actions
	 *                                               when deleting a term
	 *
	 * @hook delete_term
	 */
	function delete_term( $cat, $tt_id, $taxonomy ) {
		$term_actions = $this->get_term_actions_helper();
		$term_actions->delete_term_actions( $tt_id, $taxonomy );
	}

	/**
	 * @return WPML_Term_Actions
	 */
	public function get_term_actions_helper() {
		if ( ! isset( $this->term_actions ) ) {
			global $wpml_term_translations, $wpml_post_translations;
			$this->term_actions = new WPML_Term_Actions( $this,
			                                             $this->wpdb,
			                                             $wpml_post_translations,
			                                             $wpml_term_translations );
		}

		return $this->term_actions;
	}

	function get_terms_args_filter( $args, $taxonomies ) {
		$translated = false;
		foreach ( (array) $taxonomies as $tax ) {
			if ( is_taxonomy_translated( $tax ) ) {
				$translated = true;
				break;
			}
		}
		if ( $translated === false ) {
			return $args;
		}
		if ( isset( $args[ 'cache_domain' ] ) ) {
			$args[ 'cache_domain' ] .= '_' . $this->get_current_language();
		}
		$include_arr = $this->adjust_taxonomies_terms_ids( $args[ 'include' ] );
		if ( !empty( $include_arr ) ) {
			$args[ 'include' ] = $include_arr;
		}
		$exclude_arr = $this->adjust_taxonomies_terms_ids( $args[ 'exclude' ] );
		if ( !empty( $exclude_arr ) ) {
			$args[ 'exclude' ] = $exclude_arr;
		}
		$exclude_tree_arr = $this->adjust_taxonomies_terms_ids( $args[ 'exclude_tree' ] );
		if ( !empty( $exclude_tree_arr ) ) {
			$args['exclude_tree'] = $exclude_tree_arr;
		}
		$child_id_arr = isset( $args[ 'child_of' ] ) ? $this->adjust_taxonomies_terms_ids( $args[ 'child_of' ] ) : array();
		if ( !empty( $child_id_arr ) ) {
			$args[ 'child_of' ] = array_pop( $child_id_arr );
		}
		$parent_arr = isset( $args[ 'parent' ] ) ? $this->adjust_taxonomies_terms_ids( $args[ 'parent' ] ) : array();
		if ( !empty( $parent_arr ) ){
			$args[ 'parent' ] = array_pop( $parent_arr );
		}
		// special case for when term hierarchy is cached in wp_options
		$debug_backtrace = $this->get_backtrace( 5 );
		if ( isset( $debug_backtrace[ 4 ] ) && $debug_backtrace[ 4 ][ 'function' ] == '_get_term_hierarchy' ) {
			$args[ '_icl_show_all_langs' ] = true;
		}

		return $args;
	}

	function terms_clauses( $clauses, $taxonomies, $args ) {
		// special case for when term hierarchy is cached in wp_options
		$debug_backtrace = new WPML_Debug_BackTrace( phpversion(), 9 ); // Highest frame is #8
        if ( (bool) $taxonomies === false
             || $debug_backtrace->is_function_in_call_stack( '_get_term_hierarchy' )
             || $debug_backtrace->is_class_function_in_call_stack( 'WPML_Term_Translation_Utils', 'synchronize_terms' )
             || $debug_backtrace->is_function_in_call_stack( 'wp_get_object_terms' )
        ) {
            return $clauses;
        }

		$icl_taxonomies = array();
		foreach ( $taxonomies as $tax ) {
			if ( $this->is_translated_taxonomy( $tax ) ) {
				$icl_taxonomies[] = $tax;
			}
		}

		if ( (bool)$icl_taxonomies === false ){
			return $clauses;
		}

        $icl_taxonomies = "'tax_" . join( "','tax_",  esc_sql( $icl_taxonomies ) ) . "'";

		$lang = $this->get_current_language();
		$where_lang = $lang === 'all' ? '' : $this->wpdb->prepare( " AND icl_t.language_code = %s ", $lang );

		$clauses[ 'join' ] .= " LEFT JOIN {$this->wpdb->prefix}icl_translations icl_t
                                    ON icl_t.element_id = tt.term_taxonomy_id
                                        AND icl_t.element_type IN ({$icl_taxonomies})";
		$clauses[ 'where' ] .= " AND ( ( icl_t.element_type IN ({$icl_taxonomies}) {$where_lang} )
                                    OR icl_t.element_type NOT IN ({$icl_taxonomies}) OR icl_t.element_type IS NULL ) ";

		return $clauses;
	}

	/**
	 * Saves the current $wp_query to \SitePress::$wp_query
	 *
	 * @global WP_Query $wp_query
	 */
	public function set_wp_query() {
		global $wp_query;

		if ( 'wp' === $this->get_wp_api()->current_action() || ! $this->get_wp_api()->did_action( 'wp' ) ) {
			$this->wp_query = is_object( $wp_query ) ? clone $wp_query : null;
		}
	}

	/**
	 * @return WP_Query
	 */
	public function get_wp_query() {
		return $this->wp_query;
	}

	/**
	 * Converts WP generated url to language specific based on plugin settings
	 *
	 * @param string      $url
	 * @param null|string $code	(if null, fallback to default language for root page, or current language in all other cases)
	 *
	 * @return bool|string
	 */
	function convert_url( $url, $code = null ) {
		/* @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;

		return $wpml_url_converter->convert_url($url, $code);
	}

	/**
	 * @param string $url
	 * @param string $code
	 *
	 * @return string
	 */
	function convert_url_string( $url, $code ) {
		/* @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;

		return $wpml_url_converter->get_strategy()->convert_url_string( $url, $code );
	}

	function language_url( $code = null ) {
		global $wpml_url_converter;

		if ( is_null( $code ) ) {
			$code = $this->this_lang;
		}

		$abs_home = $wpml_url_converter->get_abs_home();

		if ( $this->settings[ 'language_negotiation_type' ] == 1 || $this->settings[ 'language_negotiation_type' ] == 2 ) {
			$url = trailingslashit( $this->convert_url( $abs_home, $code ) );
		} else {
			$url = $this->convert_url( $abs_home, $code );
		}

		return $url;
	}

	function post_type_archive_link_filter( $link, $post_type ) {
		/* @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;

		if ( isset( $this->settings[ 'custom_posts_sync_option' ][ $post_type ] ) && $this->settings[ 'custom_posts_sync_option' ][ $post_type ] ) {
			$link = $wpml_url_converter->convert_url( $link );
			$link = $this->adjust_cpt_in_url( $link, $post_type );
		}

		return $link;
	}

	public function adjust_cpt_in_url( $link, $post_type, $language_code = null ) {

		if ( $this->slug_translation_turned_on( $post_type ) ) {
			$url_cpt_converter = new WPML_URL_Converter_CPT();
			$link = $url_cpt_converter->adjust_cpt_slug_in_url( $link, $post_type, $language_code );
		}

		return $link;
	}

	/**
	 * Check if "Translate custom posts slugs (via WPML String Translation)."
	 * and slug translation for given $post_type are both checked
	 *
	 * @param string $post_type
	 * @return boolean
	 */
	private function slug_translation_turned_on($post_type) {
		return isset($this->settings['posts_slug_translation']['types'][$post_type])
					&& $this->settings['posts_slug_translation']['types'][$post_type]
					&& isset($this->settings['posts_slug_translation']['on'])
					&& $this->settings['posts_slug_translation']['on'];
	}

	function home_url($url){

		return $url;
	}

	function get_comment_link_filter( $link )
	{
		// decode html characters since they are already encoded in the template for some reason
		$link = html_entity_decode( $link );

		return $link;
	}

    function attachment_link_filter( $link, $id ) {
        /** @var WPML_Post_Translation $wpml_post_translations */
        global $wpml_post_translations;

	    $convert_url = $this->convert_url( $link, $wpml_post_translations->get_element_lang_code( $id ) );

        return $convert_url;
    }

	/**
	 * @return WPML_Query_Utils
	 */
	public function get_query_utils() {

		return new WPML_Query_Utils( $this->wpdb, $this->wp_api );
	}

	/**
	 * @return WPML_Root_Page_Actions
	 */
	public function get_root_page_utils() {

		return wpml_get_root_page_actions_obj();
	}

	/**
	 * @return WPML_WP_API
	 */
	public function get_wp_api() {
		$this->wp_api = $this->wp_api ? $this->wp_api : new WPML_WP_API();

		return $this->wp_api;
	}

	/**
	 * @return wpdb
	 */
	public function &wpdb() {

		return $this->wpdb;
	}

	/**
	 * @return TranslationManagement
	 */
	public function &core_tm() {
		global $iclTranslationManagement;

		return $iclTranslationManagement;
	}

	/**
	 * @return WPML_Term_Translation
	 */
	function &term_translations(){

		return $this->term_translation;
	}

	/**
	 * @return WPML_Post_Translation
	 */
	function &post_translations(){

		return $this->post_translation;
	}

	/**
	 * @return WPML_Records
	 */
	public function get_records() {
		$this->records = $this->records
			? $this->records : new WPML_Records( $this->wpdb );

		return $this->records;
	}

	/**
	 * @param WPML_WP_API $wp_api
	 */
	public function set_wp_api( $wp_api ) {
		$this->wp_api = $wp_api;
	}

	/**
	 * @return WPML_Helper
	 */
	public function get_wp_helper() {
		return $this->wpml_helper;
	}

	function get_ls_languages( $template_args = array() ) {

		/** @var $wp_query WP_Query */
		global $wp_query, $wpml_post_translations, $wpml_term_translations;

		$this->set_wp_query();

		$current_language = $this->get_current_language();
		$default_language = $this->get_default_language();

		$cache_key_args   = $template_args ? array_filter( $template_args ) : array( 'default' );
		$cache_key_args[] = $current_language;
		$cache_key_args[] = $default_language;
		if ( isset( $this->wp_query->request ) ) {
			$cache_key_args[] = $this->wp_query->request;
		}
		$cache_key_args   = array_filter( $cache_key_args );
		$cache_key        = md5( wp_json_encode( $cache_key_args ) );
		$cache_group      = 'ls_languages';
		$found            = false;

		$cache            = new WPML_WP_Cache( $cache_group );
		$ls_languages     = $cache->get( $cache_key, $found );
		if ( $found ) {
			return $ls_languages;
		}

		// use original wp_query for this
		// backup current $wp_query

		if ( ! isset( $wp_query ) ) {
			return apply_filters( 'wpml_active_languages_access', $this->get_active_languages(), array( 'action' => 'read' ) );
		}
		$_wp_query_back = clone $wp_query;
		// This part will reset the local value of $wp_query to it's global state.
		// `unset` function called on globals only removes local variable and then declaring it global again restores global state.
		unset( $wp_query );
		global $wp_query; // make it global again after unset
		if ( is_object( $this->wp_query ) ) {
			$wp_query = clone $this->wp_query;
		} else {
			// If `$this->wp_query` is not set at least uss native $wp_query
			$this->wp_query =  clone $wp_query;
		}

		$w_active_languages = apply_filters( 'wpml_active_languages_access', $this->get_active_languages(), array( 'action' => 'read' ) );

		if ( isset( $template_args[ 'skip_missing' ] ) ) {
			//override default setting
			$icl_lso_link_empty = !$template_args[ 'skip_missing' ];
		} else {
			$icl_lso_link_empty = $this->settings[ 'icl_lso_link_empty' ];
		}

		$link_empty_to = ! empty( $template_args[ 'link_empty_to' ] ) ? $template_args[ 'link_empty_to' ] : null;

		$languages_helper = new WPML_Languages( $wpml_term_translations, $this, $wpml_post_translations );
		list( $translations, $wp_query ) = $languages_helper->get_ls_translations( $wp_query,
		                                                                           $_wp_query_back,
		                                                                           $this->wp_query );

		// 2. determine url
		foreach ( $w_active_languages as $k => $lang ) {
			$skip_lang = false;
			if ( is_singular()
			     || ( isset( $_wp_query_back->query[ 'name' ] ) && isset( $_wp_query_back->query[ 'post_type' ] ) )
			     || ( ! empty( $this->wp_query->queried_object_id ) && $this->wp_query->queried_object_id == get_option( 'page_for_posts' ) )
			) {
				$this_lang_tmp = $this->this_lang;
				$this->switch_lang( $lang['code'] );
				$lang_page_on_front  = get_option( 'page_on_front' );
				$lang_page_for_posts = get_option( 'page_for_posts' );
				if($lang_page_on_front) {
					$lang_page_on_front = icl_object_id($lang_page_on_front, 'page', false, $lang[ 'code' ]);
				}
				if($lang_page_for_posts) {
					$lang_page_for_posts = icl_object_id($lang_page_for_posts, 'page', false, $lang[ 'code' ]);
				}
				if ( 'page' === get_option( 'show_on_front' ) && !empty( $translations[ $lang[ 'code' ] ] ) && $translations[ $lang[ 'code' ] ]->element_id == $lang_page_on_front ) {
					$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] );
				} elseif ( 'page' == get_option( 'show_on_front' ) && !empty( $translations[ $lang[ 'code' ] ] ) && $translations[ $lang[ 'code' ] ]->element_id && $translations[ $lang[ 'code' ] ]->element_id == $lang_page_for_posts ) {
					if ( $lang_page_for_posts ) {
						$lang[ 'translated_url' ] = get_permalink( $lang_page_for_posts );
					} else {
						$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] );
					}
				} else {
					if ( !empty( $translations[ $lang[ 'code' ] ] ) && isset( $translations[ $lang[ 'code' ] ]->post_title ) ) {
						$this->switch_lang( $lang['code'] );
						$lang[ 'translated_url' ] = get_permalink( $translations[ $lang[ 'code' ] ]->element_id );
						$lang[ 'missing' ]        = 0;
						$this->switch_lang( $current_language );
					} else {
						if ( $icl_lso_link_empty ) {
							if ( ! empty( $link_empty_to ) ) {
								$lang[ 'translated_url' ] = str_replace( '{%lang}', $lang[ 'code' ], $link_empty_to );
							} else {
								$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] );
							}

						} else {
							$skip_lang = true;
						}
						$lang[ 'missing' ] = 1;
					}
				}
				$this->this_lang = $this_lang_tmp;
			} elseif ( is_category() || is_tax() || is_tag() ) {
				global $icl_adjust_id_url_filter_off;

				$icl_adjust_id_url_filter_off = true;
				list( $lang, $skip_lang ) = $languages_helper->add_tax_url_to_ls_lang( $lang,
				                                                                       $translations,
				                                                                       $icl_lso_link_empty,
				                                                                       $skip_lang,
					                                                                   $link_empty_to );
				$icl_adjust_id_url_filter_off = false;
			} elseif ( is_author() ) {
				global $authordata;
				if ( empty( $authordata ) ) {
					$authordata = get_userdata( get_query_var( 'author' ) );
				}
				remove_filter( 'home_url', array( $this, 'home_url' ), 1 );
				remove_filter( 'author_link', array( $this, 'author_link' ) );
				list( $lang, $skip_lang ) = $languages_helper->add_author_url_to_ls_lang( $lang,
				                                                                          $authordata,
				                                                                          $icl_lso_link_empty,
				                                                                          $skip_lang,
					                                                                      $link_empty_to );
				add_filter( 'home_url', array( $this, 'home_url' ), 1, 4 );
				add_filter( 'author_link', array( $this, 'author_link' ) );
			} elseif ( is_archive() && !is_tag() ) {
				global $icl_archive_url_filter_off;
				$icl_archive_url_filter_off = true;
				remove_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link_filter' ), 10 );
				list( $lang, $skip_lang ) = $languages_helper->add_date_or_cpt_url_to_ls_lang( $lang,
				                                                                               $this->wp_query,
				                                                                               $icl_lso_link_empty,
				                                                                               $skip_lang,
					                                                                           $link_empty_to );
				add_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link_filter' ), 10, 2 );
				$icl_archive_url_filter_off = false;
			} elseif ( is_search() ) {
				$url_glue                 = strpos( $this->language_url( $lang[ 'code' ] ), '?' ) === false ? '?' : '&';
				$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] ) . $url_glue . 's=' . urlencode( $wp_query->query[ 's' ] );
			} else {
				if ( $icl_lso_link_empty || is_home() || is_404() || ( 'page' === get_option( 'show_on_front' ) && ( $this->wp_query->queried_object_id == get_option( 'page_on_front' ) || $this->wp_query->queried_object_id == get_option( 'page_for_posts' ) ) ) || WPML_Root_Page::is_current_request_root() ) {
					$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] );
					$skip_lang                = false;
				} else {
					$skip_lang = true;
					unset( $w_active_languages[ $k ] );
				}
			}
			if ( !$skip_lang ) {
				$w_active_languages[ $k ] = $lang;
			} else {
				unset( $w_active_languages[ $k ] );
			}
		}

		// 3.
		foreach ( $w_active_languages as $k => $v ) {
			$w_active_languages[ $k ] = $languages_helper->get_ls_language (
				$k,
				$current_language,
				$w_active_languages[ $k ]
			);
		}

		// 4. pass GET parameters
		$parameters_copied = apply_filters( 'icl_lang_sel_copy_parameters',
											array_map( 'trim',
													   explode( ',',
																wpml_get_setting_filter('',
																						'icl_lang_sel_copy_parameters') ) ) );
		if ( $parameters_copied ) {
			foreach ( $_GET as $k => $v ) {
				if ( in_array( $k, $parameters_copied ) ) {
					$gets_passed[ $k ] = $v;
				}
			}
		}
		if ( ! empty( $gets_passed ) ) {
			foreach ( $w_active_languages as $code => $al ) {
				if ( empty( $al[ 'missing' ] ) ) {
					$w_active_languages[ $code ][ 'url' ] = add_query_arg( $gets_passed, $w_active_languages[ $code ][ 'url' ] );
				}
			}
		}

		// restore current $wp_query
		unset( $wp_query );
		global $wp_query; // make it global again after unset
		$wp_query = clone $_wp_query_back;
		unset( $_wp_query_back );

		$w_active_languages = apply_filters( 'icl_ls_languages', $w_active_languages );

		$w_active_languages = $languages_helper->sort_ls_languages( $w_active_languages, $template_args );

		// Change the url, in case languages in subdomains are set.
		if ( $this->get_setting( 'language_negotiation_type' ) == WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN ) {
			foreach ( $w_active_languages as $lang => $element ) {
				$w_active_languages[ $lang ][ 'url' ] = $this->convert_url( $element[ 'url' ], $lang );
			}
		}

		if ( isset( $this->wp_query->query_vars['post_type'] ) &&
		  ! is_array( $this->wp_query->query_vars['post_type'] ) &&
		  ! empty( $this->wp_query->query_vars['post_type'] ) &&
		  ! $this->is_translated_post_type( $this->wp_query->query_vars['post_type'] )
		) {
			foreach ( $w_active_languages as $lang => $element ) {
				unset( $w_active_languages[ $lang ] );
			}
		}

		wp_reset_query();

		$cache->set( $cache_key, $w_active_languages );
		return $w_active_languages;
	}

	function get_display_single_language_name_filter( $empty, $args ) {
		$language_code = $args['language_code'];
		$display_code = isset($args['display_code']) ? $args['display_code'] : null;
		return $this->get_display_language_name($language_code, $display_code);
	}

	function get_display_language_name( $lang_code, $display_code = null ) {
		$display_code    = $display_code ? $display_code : $this->get_current_language();
		$translated_name = $this->get_language_name_cache()->get( $lang_code . $display_code );
		if ( !$translated_name ) {
			$display_code    = $display_code === 'all' ? $this->get_admin_language() : $display_code;
			$translated_name = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"  SELECT name
                       FROM {$this->wpdb->prefix}icl_languages_translations
                       WHERE language_code=%s
                        AND display_language_code=%s",
					$lang_code,
					$display_code
				)
			);
			$this->get_language_name_cache()->set( $lang_code . $display_code, $translated_name );
		}

		return $translated_name;
	}

	function get_flag( $lang_code ) {
		return $this->flags->get_flag( $lang_code );
	}

	function get_flag_url( $code ) {
		return $this->flags->get_flag_url( $code );
	}

	function get_flag_img( $code ) {
		return '<img src="' . $this->flags->get_flag_url( $code ) . '">';
	}

	function clear_flags_cache() {
		$this->flags->clear();
	}

	/**
	 * @deprecated
	 *
	 * @return string
	 */
	function get_desktop_language_selector() {
		return $this->get_language_selector();
	}

	/**
	 * @deprecated
	 *
	 * @return string
	 */
	function get_mobile_language_selector() {
		return $this->get_language_selector();
	}

	/**
	 * @deprecated
	 *
	 * @return string
	 */
	function get_language_selector() {
		ob_start();
		do_action( 'wpml_add_language_selector' );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * @deprecated
	 */
	function language_selector() {
		do_action( 'wpml_add_language_selector' );
	}

	public function add_extra_debug_info( $extra_debug ) {
		$extra_debug[ 'WMPL' ] = $this->get_settings();

		return $extra_debug;
	}

	function set_default_categories( $def_cat )
	{
		$this->settings[ 'default_categories' ] = $def_cat;
		$this->save_settings();
	}

	function pre_option_default_category( $setting ) {
		$lang = filter_input ( INPUT_POST, 'icl_post_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$lang = $lang ? $lang : filter_input ( INPUT_GET, 'lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$lang = $lang ? $lang : $this->get_current_language();

		$lang = $lang === 'all' ? $this->get_default_language () : $lang;
		$ttid = isset( $this->settings[ 'default_categories' ][ $lang ] ) ? (int) $this->settings['default_categories'][ $lang ] : 0;

		return $ttid === 0
			? null : $this->wpdb->get_var (
				$this->wpdb->prepare (
							"SELECT term_id
		                     FROM {$this->wpdb->term_taxonomy}
		                     WHERE term_taxonomy_id= %d
		                     AND taxonomy='category'",
							$ttid
						)
					);
	}

	function update_option_default_category( $oldvalue, $new_value ) {
		$new_value     = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy} WHERE taxonomy='category' AND term_id=%d", $new_value ) );
		$translations = $this->get_element_translations( $this->get_element_trid( $new_value, 'tax_category' ) );
		if ( !empty( $translations ) ) {
			foreach ( $translations as $t ) {
				$icl_settings[ 'default_categories' ][ $t->language_code ] = $t->element_id;
			}
			if ( isset( $icl_settings ) ) {
				$this->save_settings( $icl_settings );
			}
		}
	}


	function get_term_adjust_id( $term ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		/** @var WPML_Post_Translation $wpml_post_translations */
		global $icl_adjust_id_url_filter_off, $wpml_term_translations, $wpml_post_translations;
		if ( $icl_adjust_id_url_filter_off || ! $this->get_setting( 'auto_adjust_ids' ) ) {
			return $term;
		} // special cases when we need the category in a different language

		// exception: don't filter when called from get_permalink. When category parents are determined
		$debug_backtrace = $this->get_backtrace( 7 ); //Limit to first 7 stack frames, since 6 is the highest index we use
		if ( ( isset( $debug_backtrace[5]['function'] )
		       && $debug_backtrace[5]['function'] === 'get_category_parents' )
		     || ( isset( $debug_backtrace[6]['function'] )
		          && $debug_backtrace[6]['function'] === 'get_permalink' )
		     || ( isset( $debug_backtrace[4]['function'] )
		          && $debug_backtrace[4]['function'] === 'get_permalink' ) // WP 3.5
		) {
			return $term;
		}

		$translated_id = $wpml_term_translations->element_id_in($term->term_taxonomy_id, $this->this_lang);

		if ( $translated_id && (int)$translated_id !== (int)$term->term_taxonomy_id ) {
			$object_id = isset( $term->object_id ) ? $term->object_id : false;
			$term = get_term_by( 'term_taxonomy_id', $translated_id, $term->taxonomy );
			if ( $object_id ) {
				$translated_object_id = $wpml_post_translations->element_id_in( $object_id, $this->this_lang );
				if ( $translated_object_id ) {
					$term->object_id = $translated_object_id;
				}
			}
		}

		return $term;
	}

	function get_pages_adjust_ids( $pages, $args ) {
		if ( $pages && $this->get_current_language () !== $this->get_default_language () ) {
			$args_hash      = md5 ( wp_json_encode ( $args ) );
			$cache_key_args = md5 ( wp_json_encode ( wp_list_pluck ( $pages, 'ID' ) ) );
			$cache_key_args .= ":";
			$cache_key_args .= $args_hash;

			$cache_key     = $cache_key_args;
			$cache_group   = 'get_pages_adjust_ids';
			$found         = false;
			$cached_result = wp_cache_get ( $cache_key, $cache_group, false, $found );

			if ( !$found ) {
				if ( $args[ 'include' ] ) {
					$args = $this->translate_csv_page_ids ( $args, 'include' );
				}
				if ( $args[ 'exclude' ] ) {
					$args = $this->translate_csv_page_ids ( $args, 'exclude' );
				}
				if ( $args[ 'child_of' ] ) {
					$args[ 'child_of' ] = icl_object_id ( $args[ 'child_of' ], 'page', true );
				}
				if ( md5 ( wp_json_encode ( $args ) ) !== $args_hash ) {
					remove_filter ( 'get_pages', array( $this, 'get_pages_adjust_ids' ), 1 );
					$pages = get_pages ( $args );
					add_filter ( 'get_pages', array( $this, 'get_pages_adjust_ids' ), 1, 2 );
				}
				wp_cache_set ( $cache_key, $pages, $cache_group );
			} else {
				$pages = $cached_result;
			}
		}

		return $pages;
	}

	private function translate_csv_page_ids( $args, $index ) {
		$original_ids   = array_map ( 'trim', explode ( ',', $args[ $index ] ) );
		$translated_ids = array();
		foreach ( $original_ids as $i ) {
			$t = icl_object_id ( $i, 'page', true );
			if ( $t ) {
				$translated_ids[ ] = $t;
			}
		}
		$args[ $index ] = join ( ',', $translated_ids );

		return $args;
	}

	function category_link_adjust_id( $catlink, $cat_id ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $icl_adjust_id_url_filter_off, $wpml_term_translations;
		if ( $icl_adjust_id_url_filter_off )
			return $catlink; // special cases when we need the category in a different language

		$translated_id = $wpml_term_translations->term_id_in($cat_id, $this->this_lang);
		if ( $translated_id && $translated_id != $cat_id ) {
			remove_filter( 'category_link', array( $this, 'category_link_adjust_id' ), 1 );
			$catlink = get_category_link( $translated_id );
			add_filter( 'category_link', array( $this, 'category_link_adjust_id' ), 1, 2 );
		}

		return $catlink;
	}

	// adjacent posts links
	function get_adjacent_post_join( $join ) {
		$post_type = get_query_var( 'post_type' );
		$cache_key = md5( wp_json_encode( array( $post_type, $join ) ) );
		$cache_group = 'adjacent_post_join';

		$temp_join = wp_cache_get( $cache_key, $cache_group );
		if ( $temp_join ) {
			return $temp_join;
		}

		if ( !$post_type ) {
			$post_type = 'post';
		}
		if ( $this->is_translated_post_type( $post_type ) ) {
			$join .= $this->wpdb->prepare(" JOIN {$this->wpdb->prefix}icl_translations t
                                        ON t.element_id = p.ID AND t.element_type = %s",
                                    'post_' . $post_type );
		}
		wp_cache_set( $cache_key, $join, $cache_group );

		return $join;
	}

	function get_adjacent_post_where( $where ) {
		$post_type = get_query_var( 'post_type' );
		$cache_key   = md5(wp_json_encode( array( $post_type, $where ) ) );
		$cache_group = 'adjacent_post_where';
		$temp_where = wp_cache_get( $cache_key, $cache_group );
		if ( $temp_where ) {
			return $temp_where;
		}
		if ( !$post_type ) {
			$post_type = 'post';
		}
		if ( $this->is_translated_post_type( $post_type ) ) {
			$where .= " AND language_code = '" . esc_sql( $this->this_lang ) . "'";
		}
		wp_cache_set( $cache_key, $where, $cache_group );

		return $where;

	}

	// feeds links
	function feed_link( $out ) {
		return $this->convert_url( $out );
	}

	// commenting links
	function post_comments_feed_link( $out ) {
		if ( $this->settings[ 'language_negotiation_type' ] == 3 ) {
			$out = preg_replace( '@(\?|&)lang=([^/]+)/feed/@i', 'feed/$1lang=$2', $out );
		}

		return $out;
	}

	function trackback_url( $out ) {
		return $this->convert_url( $out );
	}

	function user_trailingslashit( $string, $type_of_url )
	{
		// fixes comment link for when the comments list pagination is enabled
		if ( $type_of_url == 'comment' ) {
			$string = preg_replace( '@(.*)/\?lang=([a-z-]+)/(.*)@is', '$1/$3?lang=$2', $string );
		}

		return $string;
	}

	// archives links
	function getarchives_join( $join, $args ) {

		$post_type = array_key_exists('post_type', $args) ? $args['post_type'] : 'post';
		$post_type = esc_sql($post_type);
		return $join . " JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = {$this->wpdb->posts}.ID AND t.element_type='post_" . $post_type . "'" ;
	}

	function getarchives_where( $where ) {

		return $where . " AND language_code = '" . esc_sql( $this->this_lang ) . "'";
	}

	/**
	 * Fixes double dashes as well as broken author links that contain repeated question marks as a result of
	 * WPML filtering the home url to contain a question mark already.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	function author_link( $url ) {
		$url = preg_replace( '#^http://(.+)//(.+)$#', 'http://$1/$2', $this->convert_url( $url ) );

		return preg_replace( '#(\?.*)(\?)#', '$1&', $url );
	}

	function pre_option_home( $setting = false ) {
		if ( ! defined( 'TEMPLATEPATH' ) ) {
			return $setting;
		}

		if ( null === $this->template_real_path ) {
			$this->template_real_path = realpath( TEMPLATEPATH );
		}
		$debug_backtrace = $this->get_backtrace( 7 ); //Ignore objects and limit to first 7 stack frames, since 6 is the highest index we use
		$function = isset( $debug_backtrace[ 4 ] ) && isset( $debug_backtrace[ 4 ][ 'function' ] ) ? $debug_backtrace[ 4 ][ 'function' ] : null;
		$previous_function = isset( $debug_backtrace[ 5 ] ) && isset( $debug_backtrace[ 5 ][ 'function' ] ) ? $debug_backtrace[ 5 ][ 'function' ] : null;
		$inc_methods = array( 'include', 'include_once', 'require', 'require_once' );

		if ( $function === 'get_bloginfo' && $previous_function === 'bloginfo' ) {
			// case of bloginfo
			$is_template_file = false !== strpos( $debug_backtrace[ 5 ][ 'file' ], $this->template_real_path );
			$is_direct_call   = in_array( $debug_backtrace[ 6 ][ 'function' ], $inc_methods ) || ( false !== strpos( $debug_backtrace[ 6 ][ 'file' ], $this->template_real_path ) );
		} elseif ( in_array ( $function, array( 'get_bloginfo', 'get_settings' ), true ) ) {
			// case of get_bloginfo or get_settings
			$is_template_file = false !== strpos( $debug_backtrace[ 4 ][ 'file' ], $this->template_real_path );
			$is_direct_call   = in_array( $previous_function, $inc_methods ) || ( false !== strpos( $debug_backtrace[ 5 ][ 'file' ], $this->template_real_path ) );
		} else {
			// case of get_option
			$is_template_file = isset( $debug_backtrace[ 3 ][ 'file' ] ) && ( false !== strpos( $debug_backtrace[ 3 ][ 'file' ], $this->template_real_path ) );
			$is_direct_call   = in_array( $function, $inc_methods ) || ( isset( $debug_backtrace[ 4 ][ 'file' ] ) && false !== strpos( $debug_backtrace[ 4 ][ 'file' ], $this->template_real_path ) );
		}

		$home_url = $is_template_file && $is_direct_call ? $this->language_url( $this->this_lang ) : $setting;

		return $home_url;
	}

	/**
	 *
	 *
	 * @param array $public_query_vars
	 *
	 * @return array with added 'lang' index
	 */
	function query_vars( $public_query_vars ) {
		global $wp_query;

		$public_query_vars[] = 'lang';
		$wp_query->query_vars['lang'] = $this->this_lang;

		return $public_query_vars;
	}

	function parse_query( $q ) {
		global $wpml_query_filter;

		$query_parser = new WPML_Query_Parser( $this, $wpml_query_filter );

		return $query_parser->parse_query( $q );
	}

	function adjust_wp_list_pages_excludes( $pages ) {
		foreach ( $pages as $k => $v ) {
			$pages[ $k ] = icl_object_id( $v, 'page', true );
		}

		return $pages;
	}

	function language_attributes( $output ) {
		if ( preg_match( '#lang="[a-z-]+"#i', $output ) ) {
			$output = preg_replace( '#lang="([a-z-]+)"#i', 'lang="' . $this->this_lang . '"', $output );
		} else {
			$output .= ' lang="' . $this->this_lang . '"';
		}

		return $output;
	}

	// Localization
	function plugin_localization() {
		load_plugin_textdomain( 'sitepress', false, ICL_PLUGIN_FOLDER . '/locale' );
	}

	/**
	 * @see \Test_Admin_Settings::test_locale
	 * @fixme
	 * Due to the way these tests work (global state issues) I had to create this method
	 * to ensure we have full coverage of the code.
	 * This method shouldn't be used anywhere else and should be removed once tests are migrated
	 * to the new tests framework.
	 */
	function reset_locale_utils_cache() {
		if($this->locale_utils) {
			$this->locale_utils->reset_cached_data();
		}
	}

	function locale_filter( $default ) {

		if ( ! $this->get_settings() ) {
			return $default;
		}

		return $this->locale_utils->locale();
	}

	function get_language_tag( $code ) {
		if ( is_null ( $code ) ) {
			return false;
		}
		$found = false;
		$tags  = wp_cache_get ( 'icl_language_tags', '', false, $found );
		if ( $found === true ) {
			if ( isset( $tags[ $code ] ) ) {
				return $tags[ $code ];
			}
		}

		$all_tags = array();
		$all_tags_data = $this->wpdb->get_results( "SELECT code, tag FROM {$this->wpdb->prefix}icl_languages" );
		foreach($all_tags_data as $tag_data) {
			$all_tags[$tag_data->code] = $tag_data->tag;
		}

		$tag = isset($all_tags[$code]) ? $all_tags[$code] : false;
		wp_cache_set( 'icl_language_tags', $all_tags);

		return $tag ? $tag : $this->get_locale( $code );
	}

	function get_locale( $code ) {

		return $this->locale_utils->get_locale( $code );
	}

	function switch_locale( $lang_code = false ) {
		$this->locale_utils->switch_locale( $lang_code );
	}

	function get_locale_file_names() {

		return $this->locale_utils->get_locale_file_names();
	}

	function set_locale_file_names( $locale_file_names_pairs ) {

		return $this->locale_utils->set_locale_file_names( $locale_file_names_pairs );
	}

	function pre_option_page_on_front() {
		global $switched;

		$pre_option_page = new WPML_Pre_Option_Page( $this->wpdb, $this, $switched, $this->this_lang );
		return $pre_option_page->get( 'page_on_front' );
	}

	function pre_option_page_for_posts() {

		global $switched;

		$pre_option_page = new WPML_Pre_Option_Page( $this->wpdb, $this, $switched, $this->this_lang );
		return $pre_option_page->get( 'page_for_posts' );
	}

	function fix_trashed_front_or_posts_page_settings( $post_id ) {
		global $switched;

		$pre_option_page_current = new WPML_Pre_Option_Page( $this->wpdb, $this, $switched, $this->this_lang );
		$pre_option_page_current->fix_trashed_front_or_posts_page_settings( $post_id );
	}

	// adds the language parameter to the admin post filtering/search
	function restrict_manage_posts() {
		echo '<input type="hidden" name="lang" value="' . $this->this_lang . '" />';
	}

	function get_edit_term_link( $link, $term_id, $taxonomy, $object_type ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations;
		$default_language = $this->get_default_language();
		$current_language = $this->get_current_language();
		$lang = $wpml_term_translations->lang_code_by_termid($term_id);
		$lang = $lang ? $lang : $default_language;

		if ( $lang !== $default_language || $current_language !== $default_language ) {
			$link .= '&lang=' . $lang;
		}

		return $link;
	}

	/**
	 * Wrapper for \WPML_Post_Translation::pre_option_sticky_posts_filter
	 *
	 * @param int[] $posts
	 *
	 * @return int[]
	 *
	 * @uses \WPML_Post_Translation::pre_option_sticky_posts_filter to filter the sticky posts
	 *
	 * @hook pre_option_sticky_posts
	 */
	function option_sticky_posts( $posts ) {
		/** @var WPML_Post_Translation $wpml_post_translations */
		global $wpml_post_translations;

		remove_filter( 'pre_option_sticky_posts', array( $this, 'option_sticky_posts' ) );
		$posts = $wpml_post_translations->pre_option_sticky_posts_filter($posts, $this);
		add_filter( 'pre_option_sticky_posts', array( $this, 'option_sticky_posts' ), 10, 2 );

		return $posts;
	}

	function noscript_notice()
	{
		?>
		<noscript>
		<div class="error"><?php echo __( 'WPML admin screens require JavaScript in order to display. JavaScript is currently off in your browser.', 'sitepress' ) ?></div></noscript><?php
	}

	function get_inactive_content() {
		$inactive         = array();
		$current_language = $this->get_current_language();
		$res_p_prepared   = $this->wpdb->prepare( "
				   SELECT COUNT(p.ID) AS c, p.post_type, lt.name AS language FROM {$this->wpdb->prefix}icl_translations t
					JOIN {$this->wpdb->posts} p ON t.element_id=p.ID AND t.element_type LIKE %s
					JOIN {$this->wpdb->prefix}icl_languages l ON t.language_code = l.code AND l.active = 0
					JOIN {$this->wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code  AND lt.display_language_code=%s
					GROUP BY p.post_type, t.language_code
				", array( wpml_like_escape('post_') . '%', $current_language) );
		$res_p            = $this->wpdb->get_results( $res_p_prepared );
		if ($res_p) {
			foreach ( $res_p as $r ) {
				$inactive[ $r->language ][ $r->post_type ] = $r->c;
			}
		}
		$res_t_query = "
		   SELECT COUNT(p.term_taxonomy_id) AS c, p.taxonomy, lt.name AS language FROM {$this->wpdb->prefix}icl_translations t
			JOIN {$this->wpdb->term_taxonomy} p ON t.element_id=p.term_taxonomy_id
			JOIN {$this->wpdb->prefix}icl_languages l ON t.language_code = l.code AND l.active = 0
			JOIN {$this->wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code  AND lt.display_language_code=%s
			WHERE t.element_type LIKE %s
			GROUP BY p.taxonomy, t.language_code
		";
		$res_t_query_prepared = $this->wpdb->prepare($res_t_query, $current_language, wpml_like_escape('tax_') . '%');
		$res_t = $this->wpdb->get_results( $res_t_query_prepared );
		if ($res_t) {
			foreach ( $res_t as $r ) {
				if ( $r->taxonomy === 'category' && $r->c == 1 ) {
					continue; //ignore the case of just the default category that gets automatically created for a new language
				}
				$inactive[ $r->language ][ $r->taxonomy ] = $r->c;
			}
		}

		return $inactive;
	}

	function  save_user_options() {
		$user_id = $_POST[ 'user_id' ];
		if ( $user_id ) {
			if ( $this->get_wp_api()->is_admin() ) {
				update_user_meta( $user_id, 'icl_admin_language', $_POST['icl_user_admin_language'] );
			}
			update_user_meta( $user_id, 'icl_show_hidden_languages', isset( $_POST['icl_show_hidden_languages'] ) ? (int) $_POST['icl_show_hidden_languages'] : 0 );
			update_user_meta( $user_id, 'icl_admin_language_for_edit', isset( $_POST['icl_admin_language_for_edit'] ) ? (int) $_POST['icl_admin_language_for_edit'] : 0 );
			$this->reset_admin_language_cookie();
		}
	}

	function help_admin_notice() {
		$args = array(
			'name' => 'wpml-intro',
			'iso'  => defined( 'WPLANG' ) ? WPLANG : ''
		);
		$q = http_build_query( $args );
		?>
		<br clear="all"/>
		<div id="message" class="updated message fade otgs-is-dismissible">
			<p>
				<?php _e( 'You need to configure WPML before you can start translating.', 'sitepress' ); ?>
			</p>
			<p>
				<input type="hidden" id="icl_dismiss_help_nonce" value="<?php echo $icl_dhn = wp_create_nonce( 'dismiss_help_nonce' ) ?>"/>
				<a href="admin.php?page=<?php echo basename( ICL_PLUGIN_PATH ) . '/menu/languages.php' ?>" class="button-primary"><?php _e( 'Configure WPML', 'sitepress' ) ?></a>&nbsp;
				<a href="https://wpml.org/documentation/getting-started-guide/?utm_source=wpmlplugin&utm_medium=wpadmin&utm_term=getting-started&utm_content=getting-started&utm_campaign=wpml" target="_blank">
					<?php _e( 'Getting started guide', 'sitepress' ) ?>
				</a>
			</p>
			<span title="<?php _e( 'Stop showing this message', 'sitepress' ) ?>" id="icl_dismiss_help" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss', 'sitepress' ) ?></span></span>
		</div>
	<?php
	}

	function upgrade_notice() {
		include ICL_PLUGIN_PATH . '/menu/upgrade_notice.php';
	}

	function display_wpml_footer() {
		if ( $this->settings[ 'promote_wpml' ] ) {
			$wpml_site_languages = array( 'es', 'de', 'fr', 'pt-br', 'ja', 'ru', 'zh-hans', 'it', 'he', 'ar' );
			$url_language_code   = in_array( ICL_LANGUAGE_CODE, $wpml_site_languages ) ? ICL_LANGUAGE_CODE . '/' : '';

			$part_one = _x( 'Multilingual WordPress', 'Multilingual WordPress with WPML: first part', 'sitepress' );
			$part_two = _x( 'with WPML', 'Multilingual WordPress with WPML: second part', 'sitepress' );

			echo '<p id="wpml_credit_footer"><a href="https://wpml.org/' . $url_language_code . '" rel="nofollow" >' . $part_one . '</a> ' . $part_two . '</p>';
		}
	}

	function xmlrpc_methods( $methods )
	{
		//Translation proxy XMLRPC calls
		/**
		 * @deprecated Use `wpml.get_languages` XMLRPC call
		 * @since 3.5.0
		 */
		$methods[ 'translationproxy.get_languages_list' ] = array( $this, 'xmlrpc_get_languages_list' );

		return $methods;
	}

	function xmlrpc_call_actions( $action ) {
		$params = icl_xml2array ( print_r ( file_get_contents( 'php://input' ), true ) );
		add_filter( 'is_protected_meta', array( $this, 'xml_unprotect_wpml_meta' ), 10, 3 );
		switch ( $action ) {
			case 'wp.getPage':
			case 'blogger.getPost': // yet this doesn't return custom fields
				if ( isset( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 1 ][ 'value' ][ 'int' ][ 'value' ] ) ) {
					$page_id         = $params[ 'methodCall' ][ 'params' ][ 'param' ][ 1 ][ 'value' ][ 'int' ][ 'value' ];
					$lang_details    = $this->get_element_language_details( $page_id, 'post_' . get_post_type( $page_id ) );
					$this->this_lang = $lang_details->language_code; // set the current language to the posts language
					update_post_meta( $page_id, '_wpml_language', $lang_details->language_code );
					update_post_meta( $page_id, '_wpml_trid', $lang_details->trid );
					$active_languages = $this->get_active_languages();
					$res              = $this->get_element_translations( $lang_details->trid );
					$translations     = array();
					foreach ( $active_languages as $k => $v ) {
						if ( $page_id != $res[ $k ]->element_id ) {
							$translations[ $k ] = isset( $res[ $k ]->element_id ) ? $res[ $k ]->element_id : 0;
						}
					}
					update_post_meta( $page_id, '_wpml_translations', wp_json_encode( $translations ) );
				}
				break;
			case 'metaWeblog.getPost':
				if ( isset( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 0 ][ 'value' ][ 'int' ][ 'value' ] ) ) {
					$page_id         = $params[ 'methodCall' ][ 'params' ][ 'param' ][ 0 ][ 'value' ][ 'int' ][ 'value' ];
					$lang_details    = $this->get_element_language_details( $page_id, 'post_' . get_post_type( $page_id ) );
					$this->this_lang = $lang_details->language_code; // set the current language to the posts language
					update_post_meta( $page_id, '_wpml_language', $lang_details->language_code );
					update_post_meta( $page_id, '_wpml_trid', $lang_details->trid );
					$active_languages = $this->get_active_languages();
					$res              = $this->get_element_translations( $lang_details->trid );
					$translations     = array();
					foreach ( $active_languages as $k => $v ) {
						if ( isset( $res[ $k ] ) && $page_id != $res[ $k ]->element_id ) {
							$translations[ $k ] = isset( $res[ $k ]->element_id ) ? $res[ $k ]->element_id : 0;
						}
					}
					update_post_meta( $page_id, '_wpml_translations', wp_json_encode( $translations ) );
				}
				break;
			case 'metaWeblog.getRecentPosts':
				if ( isset( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 3 ][ 'value' ][ 'int' ][ 'value' ] ) ) {
					$num_posts = (int) $params['methodCall']['params']['param'][3]['value']['int']['value'];
					if ( $num_posts ) {
						$posts = get_posts( 'suppress_filters=false&numberposts=' . $num_posts );
						foreach ( $posts as $p ) {
							$lang_details = $this->get_element_language_details( $p->ID, 'post_post' );
							update_post_meta( $p->ID, '_wpml_language', $lang_details->language_code );
							update_post_meta( $p->ID, '_wpml_trid', $lang_details->trid );
							$active_languages = $this->get_active_languages();
							$res              = $this->get_element_translations( $lang_details->trid );
							$translations     = array();
							foreach ( $active_languages as $k => $v ) {
								if ( $p->ID != $res[ $k ]->element_id ) {
									$translations[ $k ] = isset( $res[ $k ]->element_id ) ? $res[ $k ]->element_id : 0;
								}
							}
							update_post_meta( $p->ID, '_wpml_translations', wp_json_encode( $translations ) );
						}
					}
				}
				break;
		}
	}

	/**
	 * @deprecated Use `wpml.get_languages` XMLRPC call
	 * @since 3.5.0
	 * @param $lang
	 *
	 * @return array|bool|mixed|null
	 */
	function xmlrpc_get_languages_list( $lang ) {
		if ( !is_null( $lang ) ) {
			if ( !$this->wpdb->get_var( $this->wpdb->prepare("SELECT code FROM {$this->wpdb->prefix}icl_languages WHERE code=%s", $lang) ) ) {
				$IXR_Error = new IXR_Error( 401, __( 'Invalid language code', 'sitepress' ) );
				echo $IXR_Error->getXml();
				exit( 1 );
			}
			$this->admin_language = $lang;
		}
		define( 'WP_ADMIN', true ); // hack - allow to force display language
		$active_languages = $this->get_active_languages( true );

		return $active_languages;
	}

	function xml_unprotect_wpml_meta( $protected, $meta_key, $meta_type )
	{
		$metas_list = array( '_wpml_trid', '_wpml_translations', '_wpml_language' );
		if ( in_array( $meta_key, $metas_list, true ) ) {
			$protected = false;
		}

		return $protected;
	}

	function meta_generator_tag()
	{
		$lids = array();
		$active_languages = $this->get_active_languages();
		if($active_languages) {
			foreach ( $active_languages as $l ) {
				$lids[ ] = $l[ 'id' ];
			}
			$stt = join( ",", $lids );
			$stt .= ";";
			printf( '<meta name="generator" content="WPML ver:%s stt:%s" />' . PHP_EOL, ICL_SITEPRESS_VERSION, $stt );
		}
	}

	function update_language_cookie($language_code) {
		$_COOKIE[ '_icl_current_language' ] = $language_code;
	}

	function get_language_cookie() {
		global $wpml_request_handler;

		return $wpml_request_handler->get_cookie_lang();
	}

	// _icl_current_language will have to be replaced with _icl_current_language
	function set_admin_language_cookie( $lang = false ) {
		if ( is_admin() ) {
			global $wpml_request_handler;

			$wpml_request_handler->set_language_cookie( $lang ? $lang : $this->get_default_language() );
		}
	}

	function get_admin_language_cookie() {
		global $wpml_request_handler;

		return is_admin() ? $wpml_request_handler->get_cookie_lang() : null;
	}

	function reset_admin_language_cookie() {
		$this->set_admin_language_cookie( $this->get_default_language() );
	}

	function rewrite_rules_filter( $value ) {
		global $wpml_language_resolution;

		$active_language_codes = $wpml_language_resolution->get_active_language_codes();
		$filter = new WPML_Rewrite_Rules_Filter( $active_language_codes );

		return $filter->rid_of_language_param ( $value );
	}

	function is_rtl( $lang = false )
	{
		if ( is_admin() ) {
			if ( empty( $lang ) )
				$lang = $this->get_admin_language();

		} else {
			if ( empty( $lang ) )
				$lang = $this->get_current_language();
		}

		$rtl_languages_codes = apply_filters('wpml_rtl_languages_codes', array( 'ar', 'he', 'fa', 'ku', 'ur' ));

		return in_array( $lang, $rtl_languages_codes );
	}

	/**
	 * Returns an array of post types that are set to be translatable
	 * @param array $default  Set the default value, in case no posts are set to be translatable (default: array())
	 *
	 * @return array
	 */
	function get_translatable_documents_filter( $default = array() ) {
		$post_types = $this->get_translatable_documents(false);
		if(!$post_types) {
			$post_types = $default;
		}

		return $post_types;
	}

	function get_translatable_documents( $include_not_synced = false )
	{
		global $wp_post_types;
		$icl_post_types = array();
		//TODO: [WPML 3.3] Keep attachments as exception, and handle it from WPML-Media by filtering get_translatable_documents
		$attachment_is_translatable = $this->is_translated_post_type( 'attachment' );
		$exceptions = array( 'revision', 'nav_menu_item' );
		if(!$attachment_is_translatable) {
			$exceptions[] = 'attachment';
		}

		foreach ( $wp_post_types as $k => $v ) {
			if ( !in_array( $k, $exceptions ) ) {
				if ( !$include_not_synced && ( empty( $this->settings[ 'custom_posts_sync_option' ][ $k ] ) || $this->settings[ 'custom_posts_sync_option' ][ $k ] != 1 ) && !in_array( $k, array( 'post', 'page' ) ) )
					continue;
				$icl_post_types[ $k ] = $v;
			}
		}
		$icl_post_types = apply_filters( 'get_translatable_documents', $icl_post_types );

		return $icl_post_types;
	}

	function get_translatable_taxonomies( $include_not_synced = false, $object_type = 'post' )
	{
		global $wp_taxonomies;
		$t_taxonomies = array();
		if ( $include_not_synced ) {
			if ( in_array( $object_type, $wp_taxonomies[ 'post_tag' ]->object_type ) )
				$t_taxonomies[ ] = 'post_tag';
			if ( in_array( $object_type, $wp_taxonomies[ 'category' ]->object_type ) )
				$t_taxonomies[ ] = 'category';
		}
		foreach ( $wp_taxonomies as $taxonomy_name => $taxonomy ) {
			// exceptions
			if ( 'post_format' == $taxonomy_name )
				continue;
			if ( in_array( $object_type, $taxonomy->object_type ) && !empty( $this->settings[ 'taxonomies_sync_option' ][ $taxonomy_name ] ) ) {
				$t_taxonomies[ ] = $taxonomy_name;
			}
		}

		if ( has_filter( 'get_translatable_taxonomies' ) ) {
			$filtered     = apply_filters( 'get_translatable_taxonomies', array( 'taxs' => $t_taxonomies, 'object_type' => $object_type ) );
			$t_taxonomies = $filtered[ 'taxs' ];
			if ( empty( $t_taxonomies ) )
				$t_taxonomies = array();
		}

		return $t_taxonomies;
	}

	/**
	 * @param string $tax 
	 * @return bool
	 */
	function is_translated_taxonomy( $tax ) {
		$option_key          = 'taxonomies_sync_option';
		$readonly_config_key = 'taxonomies_readonly_config';

		$translated = apply_filters( 'pre_wpml_is_translated_taxonomy', null, $tax );

		return $translated !== null ? $translated : $this->is_translated_element( $tax,
																		  $option_key,
																		  $readonly_config_key,
																		  $this->get_always_translatable_taxonomies() );
	}

	public function is_translated_post_type_filter($value, $post_type ) {
		return $this->is_translated_post_type( $post_type );
	}

	function is_translated_post_type( $type ) {

		$translated = apply_filters( 'pre_wpml_is_translated_post_type', $type === 'attachment' ? false : null, $type );

		return $translated !== null ? $translated : $this->is_translated_element( $type,
		                                                                          'custom_posts_sync_option',
		                                                                          'custom-types_readonly_config',
		                                                                          $this->get_always_translatable_post_types() );
	}

	/**
	 * @param null $value 
	 * @param string $taxonomy 
	 * @return int
	 */
	public function is_translated_taxonomy_filter( $value, $taxonomy ) {
		return $this->is_translated_taxonomy( $taxonomy );
	}

	function verify_post_translations_action( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = (array) $post_types;
		}
		foreach ( $post_types as $post_type => $translate ) {
			if ( $translate && ! is_numeric( $post_type ) ) {
				$this->verify_post_translations( $post_type );
			}
		}
	}

	/**
	 * Sets the default language for all posts in a given post type that do not have any language set
	 *
	 * @param string $post_type
	 */
	public function verify_post_translations( $post_type ) {
		$sql          = "
					SELECT p.ID
					FROM {$this->wpdb->posts} p
					LEFT OUTER JOIN {$this->wpdb->prefix}icl_translations t
						ON t.element_id = p.ID AND t.element_type = CONCAT('post_', p.post_type)
					WHERE p.post_type = %s AND t.translation_id IS NULL
				";
		$sql_prepared = $this->wpdb->prepare( $sql, array( $post_type ) );
		$results      = $this->wpdb->get_col( $sql_prepared );

		$def_language = $this->get_default_language();
		foreach ( $results as $id ) {
			$this->set_element_language_details( $id, 'post_' . $post_type, false, $def_language );
		}
	}

	/**
	 * This function is to be used on setting a taxonomy from untranslated to being translated.
	 * It creates potentially missing translations and reassigns posts to the then created terms in the correct language.
	 * This function affects all terms in a taxonomy and therefore, depending on the database size results in
	 * heavy resource demand. It should not be used to fix term and post assignment problems other than those
	 * resulting from the action of turning a translated taxonomy into an untranslated one.
	 *
	 * An exception is being made for the installation process assigning all existing terms the default language,
	 * given no prior language information is saved about them in the database.
	 *
	 * @param string $taxonomy
	 */
	function verify_taxonomy_translations( $taxonomy ) {
		$term_utils = new WPML_Terms_Translations();
		$tax_sync   = new WPML_Term_Language_Synchronization( $this,
			$term_utils, $taxonomy );
		if ( $this->get_setting( 'setup_complete' ) ) {
			$tax_sync->set_translated();
		} else {
			$tax_sync->set_initial_term_language();
		}
		delete_option( $taxonomy . '_children', array() );
	}

	function wp_upgrade_locale( $locale ) {
		$default_language = $this->get_default_language();
		$default_locale = $this->get_locale_from_language_code($default_language);
		return defined( 'WPLANG' ) && WPLANG ? WPLANG : $default_locale;
	}

	function admin_language_switcher() {
		require_once ICL_PLUGIN_PATH . '/menu/wpml-admin-lang-switcher.class.php';
		$admin_lang_switcher = new WPML_Admin_Language_Switcher();
		$admin_lang_switcher->render();
	}

	function admin_notices( $message, $class = "updated" )
	{
		static $hook_added = 0;
		$this->_admin_notices[ ] = array( 'class' => $class, 'message' => $message );

		if ( !$hook_added )
			add_action( 'admin_notices', array( $this, '_admin_notices_hook' ) );

		$hook_added = 1;
	}

	function _admin_notices_hook()
	{
		if ( !empty( $this->_admin_notices ) )
			foreach ( $this->_admin_notices as $n ) {
				echo '<div class="' . $n[ 'class' ] . '">';
				echo '<p>' . $n[ 'message' ] . '</p>';
				echo '</div>';
			}
	}

	function allowed_redirect_hosts( $hosts ) {
		if ( $this->settings[ 'language_negotiation_type' ] == 2 ) {
			$allowed_redirect_hosts = new WPML_Allowed_Redirect_Hosts( $this );
			$hosts = $allowed_redirect_hosts->get_hosts( $hosts );
		}

		return $hosts;
	}

	public static function get_installed_plugins() {
		if(!function_exists('get_plugins')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		$wp_plugins        = get_plugins();
		$wpml_plugins_list = array(
			'WPML Multilingual CMS'       => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'sitepress-multilingual-cms' ),
			'WPML CMS Nav'                => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-cms-nav' ),
			'WPML String Translation'     => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-string-translation' ),
			'WPML Sticky Links'           => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-sticky-links' ),
			'WPML Translation Management' => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-translation-management' ),
			'WPML Media'                  => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-media' ),
			'WooCommerce Multilingual'    => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'woocommerce-multilingual' ),
			'Gravity Forms Multilingual'  => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'gravityforms-multilingual' ),
		);

		foreach ( $wpml_plugins_list as $wpml_plugin_name => $v ) {
			foreach ( $wp_plugins as $file => $plugin ) {
				$plugin_name = $plugin[ 'Name' ];
				if ( $plugin_name == $wpml_plugin_name ) {
					$wpml_plugins_list[ $plugin_name ][ 'installed' ] = true;
					$wpml_plugins_list[ $plugin_name ][ 'plugin' ]    = $plugin;
					$wpml_plugins_list[ $plugin_name ][ 'file' ]      = $file;
				}
			}
		}

		return $wpml_plugins_list;
	}

	/**
	 * @param int  $limit
	 * @param bool $provide_object
	 * @param bool $ignore_args
	 *
	 * @return array
	 */
	public function get_backtrace($limit = 0, $provide_object = false, $ignore_args = true) {
		$options = false;

		if ( version_compare( $this->wp_api->phpversion(), '5.3.6' ) < 0 ) {
			// Before 5.3.6, the only values recognized are TRUE or FALSE,
			// which are the same as setting or not setting the DEBUG_BACKTRACE_PROVIDE_OBJECT option respectively.
			$options = $provide_object;
		} else {
			// As of 5.3.6, 'options' parameter is a bitmask for the following options:
			if ( $provide_object )
				$options |= DEBUG_BACKTRACE_PROVIDE_OBJECT;
			if ( $ignore_args )
				$options |= DEBUG_BACKTRACE_IGNORE_ARGS;
		}
		if ( version_compare( $this->wp_api->phpversion(), '5.4.0' ) >= 0 ) {
			$actual_limit    = $limit == 0 ? 0 : $limit + 1;
			$debug_backtrace = debug_backtrace( $options, $actual_limit ); //add one item to include the current frame
		} elseif ( version_compare( $this->wp_api->phpversion(), '5.2.4' ) >= 0 ) {
			//@link https://core.trac.wordpress.org/ticket/20953
			$debug_backtrace = debug_backtrace();
		} else {
			$debug_backtrace = debug_backtrace( $options );
		}

		//Remove the current frame
		if($debug_backtrace) {
			array_shift($debug_backtrace);
		}
		return $debug_backtrace;
	}

	/**
	 * Used as filter for wordpress core function url_to_postid()
	 *
	 * @global AbsoluteLinks $absolute_links_object
	 * @param string $url URL to filter
	 * @return string URL changed into format ...?p={ID} or original
	 */
	function url_to_postid($url) {

		if (strpos($url, 'wp-login.php') !== false
		    || strpos($url, '/wp-admin/') !== false
		    || strpos($url, '/wp-content/') !== false ) {
			return $url;
		}

		$is_language_in_domain = false; // if language negotiation type as lang. in domain
		$is_translated_domain = false; // if this url is in secondary language domain

		// for 'different domain per language' we need to switch_lang according to domain of parsed $url
		if (2 == $this->settings['language_negotiation_type'] && isset($this->settings['language_domains'])) {
			$is_language_in_domain = true;
			// if url domain fits to one of secondary language domains
			// switch sitepress language to this
			// but save current language context in $current_language, we will have to switch to this back
			$domains = array_filter( $this->get_setting( 'language_domains' ) );
			foreach ( $domains as $code => $domain ) {
				if ( strpos( $url, $domain ) === 0 ) {
					$is_translated_domain = true;
					$current_language     = $this->get_current_language();
					$this->switch_lang( $code );
					$url = str_replace( $domain, site_url(), $url );
					break;
				}
			}

			// if it is url in original domain
			// switch sitepress language to default language
			// but save current language context in $current_language, we will have to switch to this back
			if (!$is_translated_domain) {
				$current_language = $this->get_current_language();
				$default_language = $this->get_default_language();
				$this->switch_lang($default_language);
			}
		}

		// we will use AbsoluteLinks::_process_generic_text, so make sure that
		// we have this object here
		global $absolute_links_object;
		if (!isset($absolute_links_object) || !is_a($absolute_links_object, 'AbsoluteLinks') || $is_language_in_domain ) {
			require_once ICL_PLUGIN_PATH . '/inc/absolute-links/absolute-links.class.php';
			$absolute_links_object = new AbsoluteLinks();
		}

		// in next steps we will have to compare processed url with original,
		// so we need to save original
		$original_url = $url;
		// we also need site_url for comparisions
		$site_url = site_url();

		// _process_generic_text will change slug urls into ?p=1 or ?cpt-slug=cpt-title
		// but this function operates not on clean url but on html <a> element
		// we need to change temporary url into html, pass to this function and
		// extract url from returned html
		$html = '<a href="'.$url.'">removeit</a>';
		$alp_broken_links = array();
		remove_filter('url_to_postid', array($this, 'url_to_postid'));
		$html = $absolute_links_object->_process_generic_text($html, $alp_broken_links);
		add_filter('url_to_postid', array($this, 'url_to_postid'));
		$url = str_replace(array('<a href="', '">removeit</a>'), array('', ''), $html);

		// for 'different domain per language', switch language back. now we can do this
		if ($is_language_in_domain && isset($current_language)) {
			$this->switch_lang($current_language);
		}

		// if this is not url to external site
		if ( 0 === strpos($original_url, $site_url)) {

			// if this is url like ...?cpt-rewrite-slug=cpt-title
			// change it into ...?p=11
			$url2 = $this->cpt_url_to_id_url($url, $original_url);

			if ($url2 == $url && $original_url != $url) { // if it was not a case with ?cpt-slug=cpt-title
				// if this is translated post and it has the same slug as original,
				// _process_generic_text returns the same ID for both
				// lets check if it is this case and replace ID in returned url
				$url = $this->maybe_adjust_url($url, $original_url);
			} else { // yes! it was not a case with ?cpt-slug=cpt-title
				$url = $url2;
			}
		}

		return $url;
	}

	/**
	 * Check if $url is in format ...?cpt-slug=cpt-title and change into ...?p={ID}
	 *
	 *
	 * @param string $url URL, probably in format ?cpt-slug=cpt-title
	 * @param string $original_url URL in original format (probably with permalink)
	 * @return string URL, if $url was in expected format ?cpt-slug format, url is now changed into ?p={ID}, otherwise, returns $url as it was passed in parameter
	 */
	function cpt_url_to_id_url($url, $original_url) {

		$parsed_url = wpml_parse_url($url);

		if (!isset($parsed_url['query'])) {
			return $url;
		}

		$query = $parsed_url['query'];


		parse_str($query, $vars);


		$args = array(
				'public'   => true,
				'_builtin' => false
		);

		$post_types = get_post_types($args, 'objects');

		foreach ($post_types as $name => $attrs) {
			if ( isset( $vars[ $attrs->rewrite['slug'] ] ) ) {
				$post_type = $name;
				$post_slug = $vars[trim($attrs->rewrite['slug'],'/')];
				break;
			}
		}

		if (!isset($post_type, $post_slug)) {
			return $url;
		}

		$args = array(
				'name' => $post_slug,
				'post_type' => $post_type
		);

		$post = new WP_Query($args);

		if (!isset($post->post)) {
			return $url;
		}

		$id = $post->post->ID;

		$post_language = $this->get_language_for_element($id, 'post_' . $post_type);

		$url_language = $this->get_language_from_url($original_url);

		$new_vars = array();
		if ($post_language != $url_language) {

			$trid         = $this->get_element_trid( $id, 'post_' . $post_type );
			$translations = $this->get_element_translations( $trid, 'post_' . $post_type );

			if (isset($translations[$url_language])) {
				$translation = $translations[$url_language];
				if (isset($translation->element_id)) {
					$new_vars['p'] = $translation->element_id;
				}

			}

		} else {
			$new_vars['p'] = $id;
		}

		$new_query = http_build_query($new_vars);

		$url = str_replace($query, $new_query, $url);

		return $url;
	}

	/**
	 * Fix sticky link url to have ID of translated post (used in case both translations have same slug)
	 *
	 * @param string $url - url in sticky link form
	 * @param string $original_url - url in permalink form
	 * @return string  - url in sticky link form to correct translation
	 */
	private function maybe_adjust_url( $url, $original_url ) {
		$parsed_url = wpml_parse_url ( $url );
		$query      = isset( $parsed_url[ 'query' ] ) ? $parsed_url[ 'query' ] : "";

		parse_str ( $query, $vars );
		$inurl   = isset( $vars[ 'page_id' ] ) ? 'page_id' : null;
		$inurl   = $inurl === null && isset( $vars[ 'p' ] ) ? 'p' : null;
		$post_id = $inurl !== null ? $vars[ $inurl ] : null;

		if ( isset( $post_id ) ) {
			$post_type     = get_post_type ( $post_id );
			$post_language = $this->get_language_for_element ( $post_id, 'post_' . $post_type );
			$url_language  = $this->get_language_from_url ( $original_url );
			if ( $post_language !== $url_language ) {
				$trid         = $this->get_element_trid ( $post_id, 'post_' . $post_type );
				$translations = $this->get_element_translations ( $trid, 'post_' . $post_type );
				if ( isset( $translations[ $url_language ] ) ) {
					$translation = $translations[ $url_language ];
					if ( isset( $translation->element_id ) ) {
						$vars[ $inurl ] = $translation->element_id;
						$new_query      = http_build_query ( $vars );
						$url            = str_replace ( $query, $new_query, $url );
					}
				}
			}
		}

		return $url;
	}

	/**
	 * Find language of document based on given permalink
	 *
	 * @param string $url Local url in permalink form
	 * @return string language code
	 */
	function get_language_from_url( $url ) {
		/* @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;

		return $wpml_url_converter->get_language_from_url ( $url );
	}

	function update_index_screen(){
		return include ICL_PLUGIN_PATH . '/menu/theme-plugins-compatibility.php';
	}

	/**
	 * Filter to add language field to WordPress search form
	 *
	 * @param string $form HTML code of search for before filtering
	 * @return string HTML code of search form
	 */
	function get_search_form_filter($form) {
		if ( strpos($form, wpml_get_language_input_field() ) === false
			 && WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER === (int) $this->get_setting( 'language_negotiation_type' )
		) {
			$form = str_replace("</form>", wpml_get_language_input_field() . "</form>", $form);
		}
		return $form;
	}

	/**
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
	public function get_string_translation_settings($key = '') {
		$setting = $this->get_setting( 'st' );

		if( $this->setting_array_is_set_or_has_key( $setting, $key ) ) {
			$setting = $setting[$key];
		}
		return $setting;
	}

	/**
	 * @param $setting
	 * @param $key
	 *
	 * @return bool
	 */
	private function setting_array_is_set_or_has_key( $setting, $key ) {
		return $key != '' && $setting && isset( $setting[ $key ] );
	}

	/**
	 * @param string|array $source
	 *
	 * @return array
	 */
	private function explode_and_trim( $source ) {
		if ( ! is_array( $source ) ) {
			$source = array_map( 'trim', explode( ',', $source ) );
		}

		return $source;
	}

	/**
	 * @param string|array $terms_ids
	 *
	 * @return array
	 */
	private function adjust_taxonomies_terms_ids( $terms_ids ) {
		/** @var  WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations;

		$terms_ids = array_filter( array_unique( $this->explode_and_trim( $terms_ids ) ) );

		$translated_ids = array();
		foreach ( $terms_ids as $term_id ) {
			$translated_ids[ ] = $wpml_term_translations->term_id_in( $term_id, $this->this_lang );
		}

		return array_filter( $translated_ids );
	}

	/**
	 * @param $element_type
	 * @param $option_key
	 * @param $readonly_config_key
	 * @param $always_true_types
	 *
	 * @return bool
	 */
	private function is_translated_element( $element_type, $option_key, $readonly_config_key, $always_true_types ) {
		$ret = false;

		if ( is_scalar( $element_type ) ) {
			$ret = in_array( $element_type, $always_true_types, true );
			if ( ! $ret ) {
				$translation_management_options = $this->get_setting( 'translation-management' );
				if ( 'any' === $element_type ) {
					$ret = count( $always_true_types ) > 0 || count( $translation_management_options[ $readonly_config_key ] ) > 0;
				} else {
					$ret = icl_get_sub_setting( $option_key, $element_type );
					if ( ! $ret ) {
						if ( isset( $translation_management_options[ $readonly_config_key ][ $element_type ] ) && $translation_management_options[ $readonly_config_key ][ $element_type ] == 1 ) {
							$ret = true;
						} else {
							$ret = false;
						}
					}
				}
			}
		}

		return (bool) $ret;
	}

	/**
	 * @return array
	 */
	public function get_always_translatable_post_types() {
		return $this->always_translatable_post_types;
	}

	/**
	 * @return array
	 */
	private function get_always_translatable_taxonomies() {
		return $this->always_translatable_taxonomies;
	}

	/**
     * @param Integer $master_post_id The original post id for which duplicate posts are to be retrieved
     * @return Integer[] An associative array with language codes as indexes and post_ids as values
     */
    function get_duplicates( $master_post_id ) {
        $this->post_duplication = $this->post_duplication === null ? new WPML_Post_Duplication( $this->wpdb, $this )
            : $this->post_duplication;

        return $this->post_duplication->get_duplicates ( $master_post_id );
    }

    /**
     * @param Integer $master_post_id ID of the to be duplicated post
     * @param String $lang Language code to which the post is to be duplicated
     * @return bool|int|WP_Error
     */function make_duplicate($master_post_id, $lang){
        $this->post_duplication = $this->post_duplication === null ? new WPML_Post_Duplication( $this->wpdb, $this )
            : $this->post_duplication;

        return $this->post_duplication->make_duplicate($master_post_id, $lang);
    }

	function get_new_post_source_id ( $post_id ) {
		global $pagenow;

		if ( $pagenow == 'post-new.php' && isset( $_GET['trid'] ) && isset( $_GET['source_lang'] ) ) {
			// Get the template from the source post.
			$translations = $this->get_element_translations( $_GET['trid'] );

			if (isset($translations[$_GET['source_lang']])) {
				$post_id = $translations[$_GET['source_lang']]->element_id;
			}
		}

		return $post_id;
	}

	/**
	 * @param int           $element_id
	 * @param string     $element_type
	 * @param bool|false $return_original_if_missing
	 * @param null       $language_code
	 *
	 * @return int|null
	 */
	function get_object_id( $element_id, $element_type = 'post', $return_original_if_missing = false, $language_code = null ) {
		global $wp_post_types, $wp_taxonomies;

		$ret_element_id = null;

		if ( $element_id ) {
			$language_code = $language_code ? $language_code : $this->get_current_language();

			if ( $element_type == 'any' ) {
				$post_type = get_post_type( $element_id );
				if ( $post_type ) {
					$element_type = $post_type;
				} else {
					$element_type = null;
				}
			}

			if ( $element_type ) {
				if ( isset( $wp_taxonomies[ $element_type ] ) ) {
					/** @var WPML_Term_Translation $wpml_term_translations */
					global $wpml_term_translations;
					$ret_element_id = is_taxonomy_translated( $element_type ) ? $wpml_term_translations->term_id_in( $element_id, $language_code ) : $element_id;
				} elseif ( isset( $wp_post_types[ $element_type ] ) ) {
					/** @var WPML_Post_Translation $wpml_post_translations */
					global $wpml_post_translations;
					$ret_element_id = is_post_type_translated( $element_type ) ? $wpml_post_translations->element_id_in( $element_id, $language_code ) : $element_id;
				} else {
					$ret_element_id = null;
				}

				$ret_element_id = $ret_element_id ? (int) $ret_element_id : ( $return_original_if_missing && ! $ret_element_id ? $element_id : null );
			}
		}

		return $ret_element_id;
	}

	private function is_troubleshooting_page() {
		return isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php';
	}

	public function handle_head_hreflang() {
		$wpml_seo_headlangs = new WPML_SEO_HeadLangs($this);
		$wpml_seo_headlangs->init_hooks();
	}

	/**
	 * Get previously set data for the current request.
	 *
	 * @param $key
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	public function get_current_request_data( $key, $default = null ) {
		return isset( $this->current_request_data[ $key ] ) ? $this->current_request_data[ $key ] : $default;
	}

	/**
	 * Set temporary data for the current request that can be recalled later
	 *
	 * @param $key
	 * @param $data
	 */
	public function set_current_request_data( $key, $data ) {
		$this->current_request_data[ $key ] = $data;
	}

	/**
	 * Clear the data for the current request
	 *
	 * @param $key
	 */
	public function clear_current_request_data( $key ) {
		unset( $this->current_request_data[ $key ] );
	}

	/**
	 * Load \TranslationManagement class.
	 */
	public function load_core_tm() {
		$iclTranslationManagement = wpml_load_core_tm();
	}
}
