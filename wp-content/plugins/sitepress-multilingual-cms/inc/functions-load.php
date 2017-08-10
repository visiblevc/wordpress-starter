<?php
/**
 * @global WPML_Term_Translation $wpml_term_translations
 * @global WPML_Slug_Filter $wpml_slug_filter
 */

/**
 * Loads global variables providing functionality that is used throughout the plugin.
 *
 * @param null|book              $is_admin If set to `null` it will read from `is_admin()`
 *
 * @global                       $wpml_language_resolution
 * @global $wpml_slug_filter
 * @global WPML_Term_Translation $wpml_term_translations
 */
function load_essential_globals( $is_admin = null ) {
	global $wpml_language_resolution, $wpml_term_translations, $wpdb;

	$settings       = get_option( 'icl_sitepress_settings' );
	if ( (bool) $settings === false ) {
		icl_sitepress_activate();
	} else {
		
		if ( isset( $settings[ 'setup_complete' ] ) && $settings[ 'setup_complete' ] ) {
			$active_plugins = get_option( 'active_plugins' );
			$wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
			
			if ( in_array( trailingslashit( ICL_PLUGIN_FOLDER ) . 'sitepress.php',
													 $active_plugins,
													 true ) === false &&
				 in_array( trailingslashit( ICL_PLUGIN_FOLDER ) . 'sitepress.php',
													 array_keys( $wpmu_sitewide_plugins ),
													 true ) === false
				) {
				
				// The plugin has just be reactivated.
				
				// reset ajx_health_flag
				// set the just_reactivated flag so any posts created while
				// WPML was not activate will get the default language
				// https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1924
				$settings[ 'ajx_health_checked' ] = 0;
				$settings[ 'just_reactivated' ]   = 1;
				update_option( 'icl_sitepress_settings', $settings );
			}
		}
	}
	
	
	$active_language_codes                = isset( $settings[ 'active_languages' ] ) ? $settings[ 'active_languages' ]
		: array();
	$active_language_codes                = (bool) $active_language_codes === true
		? $active_language_codes : wpml_reload_active_languages_setting ();
	$default_lang_code                    = isset( $settings[ 'default_language' ] ) ? $settings[ 'default_language' ]
		: false;
	$wpml_language_resolution             = new WPML_Language_Resolution( $active_language_codes, $default_lang_code );
	$admin                                = $is_admin === null ? is_admin() : $is_admin;

	wpml_load_post_translation( $admin, $settings );
	$wpml_term_translations = new WPML_Term_Translation( $wpdb );
	$domain_validation      = filter_input( INPUT_GET, '____icl_validate_domain' ) ? 1 : false;
	$domain_validation      = filter_input( INPUT_GET, '____icl_validate_directory' ) ? 2 : $domain_validation;
	$url_converter          = load_wpml_url_converter( $settings, $domain_validation, $default_lang_code );
	$directory = $domain_validation === 2 || ( is_multisite() && ! is_subdomain_install() );
	if ( $domain_validation ) {
		echo wpml_validate_host( $_SERVER['REQUEST_URI'], $url_converter, $directory );
		exit;
	}
	if ( $admin ) {
		wpml_load_admin_files();
	}
}

function wpml_load_post_translation( $is_admin, $settings ) {
	global $wpml_post_translations, $wpdb;

	if ( $is_admin === true ) {
		$wpml_post_translations = new WPML_Admin_Post_Actions( $settings, $wpdb );
	} else {
		$wpml_post_translations = new WPML_Frontend_Post_Actions( $settings, $wpdb );
		wpml_load_frontend_tax_filters ();
	}

	$wpml_post_translations->init ();
}

function wpml_load_request_handler( $is_admin, $active_language_codes, $default_language ) {
	global $wpml_request_handler, $wpml_url_converter;

	if ( ! isset( $wpml_request_handler ) ) {
		require ICL_PLUGIN_PATH . '/inc/request-handling/wpml-request.class.php';
		require ICL_PLUGIN_PATH . '/inc/request-handling/wpml-backend-request.class.php';
	}

	$wpml_cookie = new WPML_Cookie();
	$wp_api      = new WPML_WP_API();

	if ( $is_admin === true ) {
		$wpml_request_handler = new WPML_Backend_Request(
			$wpml_url_converter,
			$active_language_codes,
			$default_language, $wpml_cookie,
			$wp_api );
	} else {
		$wpml_request_handler = new WPML_Frontend_Request(
			$wpml_url_converter,
			$active_language_codes,
			$default_language, $wpml_cookie,
			$wp_api );
	}

	return $wpml_request_handler;
}

function wpml_load_query_filter( $installed ) {
	global $wpml_query_filter, $sitepress, $wpdb, $wpml_post_translations, $wpml_term_translations;

	$wpml_query_filter = $wpml_query_filter ? $wpml_query_filter : new WPML_Query_Filter( $sitepress, $wpdb, $wpml_post_translations, $wpml_term_translations );
	if ( $installed ) {
		if ( ! has_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ) ) ) {
			add_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10, 2 );
			add_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10, 2 );
		}
	}
}

function load_wpml_url_converter( $settings, $domain_validation, $default_lang_code ) {
	/**
	 * @var WPML_URL_Converter $wpml_url_converter
	 * @var WPML_Language_Resolution $wpml_language_resolution
	 */
	global $wpml_url_converter, $wpml_language_resolution;

	$url_type     = isset( $settings['language_negotiation_type'] ) ? $settings['language_negotiation_type'] : false;
	$url_type     = $domain_validation ? $domain_validation : $url_type;
	$active_language_codes = $wpml_language_resolution->get_active_language_codes();

	$factory            = new WPML_URL_Converter_Factory( $settings, $default_lang_code, $active_language_codes );
	$wpml_url_converter = $factory->create( (int) $url_type );

	return $wpml_url_converter;
}

/**
 * @param string             $req_uri
 * @param WPML_URL_Converter $wpml_url_converter
 * @param bool               $directory
 *
 * @return string
 */
function wpml_validate_host( $req_uri, $wpml_url_converter, $directory = true ) {
	if ( $directory === true ) {
		$req_uri_parts = array_filter ( explode ( '/', $req_uri ) );
		$lang_slug     = array_pop ( $req_uri_parts );
		if ( strpos ( $lang_slug, '?' ) === 0 ) {
			$lang_slug = array_pop ( $req_uri_parts );
		} elseif ( strpos ( $lang_slug, '?' ) !== false ) {
			$parts     = explode ( '?', $lang_slug );
			$lang_slug = array_shift ( $parts );
		}
	} else {
		$lang_slug = '';
	}

	return '<!--' . esc_url( untrailingslashit ( trailingslashit ( $wpml_url_converter->get_abs_home () ) . $lang_slug ) ) . '-->';
}

/**
 * Checks if a given taxonomy is currently translated
 *
 * @param string $taxonomy name/slug of a taxonomy
 * @return bool true if the taxonomy is currently set to being translatable in WPML
 */
function is_taxonomy_translated( $taxonomy ) {

	return in_array( $taxonomy, array( 'category', 'post_tag', 'nav_menu' ), true )
	       || in_array(
		       $taxonomy,
		       array_keys( array_filter( icl_get_setting( 'taxonomies_sync_option', array() ) ) )
	       );
}

/**
 * Checks if a given post_type is currently translated
 *
 * @param string $post_type name/slug of a post_type
 * @return bool true if the post_type is currently set to being translatable in WPML
 */
function is_post_type_translated( $post_type ) {

	return in_array( $post_type, array( 'post', 'page', 'nav_menu_item' ), true )
	       || in_array(
		       $post_type,
		       array_keys( array_filter( icl_get_setting( 'custom_posts_sync_option', array() ) ) )
	       );
}

function setup_admin_menus() {
	global $pagenow;

	if ( $pagenow === 'edit-tags.php' || $pagenow === 'term.php' ) {
		maybe_load_translated_tax_screen ();
	}
}

function maybe_load_translated_tax_screen() {
	$taxonomy_get = (string) filter_input( INPUT_GET, 'taxonomy' );
	$taxonomy_get = $taxonomy_get ? $taxonomy_get : 'post_tag';
	if ( is_taxonomy_translated( $taxonomy_get ) ) {
		global $wpdb, $sitepress;
		require ICL_PLUGIN_PATH . '/menu/term-taxonomy-menus/wpml-tax-menu-loader.class.php';
		new WPML_Tax_Menu_Loader( $wpdb, $sitepress, $taxonomy_get );
	}
}

function wpml_reload_active_languages_setting( $override = false ) {
	global $wpdb, $sitepress_settings;

	if ( true === (bool) $sitepress_settings
	     && ( $override || wpml_get_setting_filter( false, 'setup_complete' ) )
	) {
		if ( $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}icl_languages'") ) {
			$active_languages                       = $wpdb->get_col( "	SELECT code
																	FROM {$wpdb->prefix}icl_languages
																	WHERE active = 1" );
		} else {
			$active_languages = array();
		}
		$sitepress_settings['active_languages'] = $active_languages;
		icl_set_setting( 'active_languages', $active_languages, true );
	} else {
		$active_languages = array();
	}

	return (array) $active_languages;
}

/**
 * Returns and if necessary instantiates an instance of the WPML_Installation Class
 *
 * @return \WPML_Installation
 */
function wpml_get_setup_instance() {
	global $wpml_installation, $wpdb, $sitepress;

	if ( ! isset( $wpml_installation ) ) {
		require ICL_PLUGIN_PATH . '/inc/setup/wpml-installation.class.php';
		$wpml_installation = new WPML_Installation( $wpdb, $sitepress );
	}

	return $wpml_installation;
}

function wpml_load_admin_files() {
	require_once ICL_PLUGIN_PATH . '/menu/wpml-troubleshooting-terms-menu.class.php';
	require_once ICL_PLUGIN_PATH . '/inc/wpml-post-edit-ajax.class.php';
	require_once ICL_PLUGIN_PATH . '/menu/wpml-post-status-display.class.php';
	require_once ICL_PLUGIN_PATH . '/inc/utilities/wpml-color-picker.class.php';
}

function wpml_get_post_status_helper() {
	global $wpml_post_status, $wpdb, $sitepress;

	if ( ! isset( $wpml_post_status ) ) {
		$wpml_post_status = new WPML_Post_Status( $wpdb, $sitepress->get_wp_api() );
	}

	return $wpml_post_status;
}

function wpml_get_create_post_helper() {
	global $wpml_create_post_helper, $sitepress;

	if ( ! isset( $wpml_create_post_helper ) ) {
		require ICL_PLUGIN_PATH . '/inc/post-translation/wpml-create-post-helper.class.php';
		$wpml_create_post_helper = new WPML_Create_Post_Helper( $sitepress );
	}

	return $wpml_create_post_helper;
}

/**
 * @return \TranslationManagement
 */
function wpml_load_core_tm() {
	global $iclTranslationManagement;

	if ( !isset( $iclTranslationManagement ) ) {
		require_once ICL_PLUGIN_PATH . '/inc/translation-management/translation-management.class.php';
		$iclTranslationManagement = new TranslationManagement();
	}

	return $iclTranslationManagement;
}

function wpml_get_langs_in_dirs_val( $http_client, $wpml_url_converter, $posted_url = false ) {
	global $sitepress;

	require_once ICL_PLUGIN_PATH . '/inc/url-handling/wpml-lang-url-validator.class.php';
	$posted_url = $posted_url ? $posted_url : (string) filter_input ( INPUT_POST, 'url' );

	return new WPML_Lang_URL_Validator( $http_client, $wpml_url_converter, $posted_url, $sitepress );
}

function wpml_get_root_page_actions_obj() {
	global $wpml_root_page_actions, $sitepress_settings;

	if ( !isset( $wpml_root_page_actions ) ) {
		require_once ICL_PLUGIN_PATH . '/inc/post-translation/wpml-root-page-actions.class.php';
		$wpml_root_page_actions = new WPML_Root_Page_Actions( $sitepress_settings );
	}

	return $wpml_root_page_actions;
}

function wpml_get_hierarchy_sync_helper( $type = 'post' ) {
	global $wpdb;

	if ( $type === 'post' ) {
		require_once ICL_PLUGIN_PATH . '/inc/post-translation/wpml-post-hierarchy-sync.class.php';
		$hierarchy_helper = new WPML_Post_Hierarchy_Sync( $wpdb );
	} elseif ( $type === 'term' ) {
		require_once ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-hierarchy-sync.class.php';
		$hierarchy_helper = new WPML_Term_Hierarchy_Sync( $wpdb );
	} else {
		$hierarchy_helper = false;
	}

	return $hierarchy_helper;
}

function wpml_maybe_setup_post_edit() {
	global $pagenow, $sitepress, $post_edit_screen;

	if ( in_array( $pagenow, array( 'post.php', 'post-new.php', 'edit.php' ), true ) || defined( 'DOING_AJAX' )
	) {
		require ICL_PLUGIN_PATH . '/menu/post-menus/post-edit-screen/wpml-post-edit-screen.class.php';
		$post_edit_screen = new WPML_Post_Edit_Screen( $sitepress );
		add_action( 'admin_head', array( $sitepress, 'post_edit_language_options' ) );
	}
}

/**
 * @return \WPML_Frontend_Tax_Filters
 */
function wpml_load_frontend_tax_filters() {
	global $wpml_term_filters;

	if ( !isset( $wpml_term_filters ) ) {
		require ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-frontend-tax-filters.class.php';
		$wpml_term_filters = new WPML_Frontend_Tax_Filters();
	}

	return $wpml_term_filters;
}

/**
 * @return \WPML_Settings_Helper
 */
function wpml_load_settings_helper() {
	global $wpml_settings_helper, $sitepress, $wpml_post_translations;

	if ( ! isset( $wpml_settings_helper ) ) {
		require_once ICL_PLUGIN_PATH . '/inc/setup/wpml-settings-helper.class.php';
		$wpml_settings_helper = new WPML_Settings_Helper( $wpml_post_translations, $sitepress );
	}

	return $wpml_settings_helper;
}

function wpml_get_term_translation_util() {
	global $sitepress;
	require_once ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-translation-utils.class.php';

	return new WPML_Term_Translation_Utils( $sitepress );
}

/**
 * @return \WPML_Term_Filters
 */
function wpml_load_term_filters() {
	global $wpml_term_filters_general, $sitepress, $wpdb;

	if ( ! isset( $wpml_term_filters_general ) ) {
		require ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-filters.class.php';
		$wpml_term_filters_general = new WPML_Term_Filters( $wpdb, $sitepress );
		$wpml_term_filters_general->init();
	}

	return $wpml_term_filters_general;
}

function wpml_show_user_options() {
	global $sitepress, $current_user, $user_id, $pagenow;

	if ( ! isset( $user_id ) && 'profile.php' === $pagenow ) {
		$user_id = $current_user->ID;
	}

	$user = new WP_User( $user_id );
	$user_options_menu = new WPML_User_Options_Menu( $sitepress, $user );
	echo $user_options_menu->render();
}

if ( is_admin() ) {
	add_action( 'personal_options', 'wpml_show_user_options' );
}