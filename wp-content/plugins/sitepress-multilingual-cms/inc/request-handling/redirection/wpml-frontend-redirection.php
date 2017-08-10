<?php
require dirname( __FILE__ ) . '/wpml-redirection.class.php';
require dirname( __FILE__ ) . '/wpml-redirect-by-param.class.php';

/**
 *
 * @return  WPML_Redirection
 *
 */
function _wpml_get_redirect_helper() {
	global $wpml_url_converter, $wpml_request_handler, $wpml_language_resolution, $sitepress;

	$lang_neg_type = wpml_get_setting_filter( false, 'language_negotiation_type' );
	switch ( $lang_neg_type ) {
		case 1:
			global $wpml_url_filters;
			if ( $wpml_url_filters->frontend_uses_root() !== false ) {
				require_once ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-rootpage-redirect-by-subdir.class.php';
				$redirect_helper = new WPML_RootPage_Redirect_By_Subdir(
						wpml_get_setting_filter( array(), 'urls' ),
						$wpml_request_handler,
						$wpml_url_converter,
						$wpml_language_resolution
				);
			} else {
				require_once ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-redirect-by-subdir.class.php';
				$redirect_helper = new WPML_Redirect_By_Subdir(
						$wpml_url_converter,
						$wpml_request_handler,
						$wpml_language_resolution
				);
			}
			break;
		case 2:
			require_once ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-redirect-by-domain.class.php';
			$wp_api = new WPML_WP_API();
			$redirect_helper = new WPML_Redirect_By_Domain(
					icl_get_setting( 'language_domains' ),
					$wp_api,
					$wpml_request_handler,
					$wpml_url_converter,
					$wpml_language_resolution
			);
			break;
		case 3:
		default:
			$redirect_helper = new WPML_Redirect_By_Param(
					icl_get_setting( 'taxonomies_sync_option', array() ),
					$wpml_url_converter,
					$wpml_request_handler,
					$wpml_language_resolution,
					$sitepress
			);
			$redirect_helper->init_hooks();
	}

	return $redirect_helper;
}