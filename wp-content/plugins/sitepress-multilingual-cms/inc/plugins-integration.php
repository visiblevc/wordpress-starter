<?php

add_action('plugins_loaded', 'wpml_plugins_integration_setup', 10);

//Todo: do not include files: move to autoloaded classes
function wpml_plugins_integration_setup(){
	/** @var WPML_URL_Converter $wpml_url_converter */
    global $sitepress, $wpml_url_converter;
    // WPSEO integration
    if ( defined( 'WPSEO_VERSION' ) && version_compare( WPSEO_VERSION, '1.0.3', '>=' ) ){
        $wpml_wpseo_xml_sitemap_filters = new WPML_WPSEO_XML_Sitemaps_Filter( $sitepress, $wpml_url_converter );
		$wpml_wpseo_xml_sitemap_filters->init_hooks();
	    $canonical     = new WPML_Canonicals( $sitepress );
        $wpseo_filters = new WPML_WPSEO_Filters( $canonical );
        $wpseo_filters->init_hooks();
    }
	if ( class_exists( 'bbPress' ) ) {
		$wpml_bbpress_api = new WPML_BBPress_API();
		$wpml_bbpress_filters = new WPML_BBPress_Filters( $wpml_bbpress_api, $sitepress, $wpml_url_converter );
		$wpml_bbpress_filters->add_hooks();
	}

    // NextGen Gallery
    if ( defined( 'NEXTGEN_GALLERY_PLUGIN_VERSION' ) ){
        require_once ICL_PLUGIN_PATH . '/inc/plugin-integration-nextgen.php';
    }
}

add_action( 'after_setup_theme', 'wpml_themes_integration_setup' );

function wpml_themes_integration_setup() {
	if ( function_exists( 'twentyseventeen_panel_count' ) && ! function_exists( 'twentyseventeen_translate_panel_id' ) ) {
		$wpml_twentyseventeen = new WPML_Compatibility_2017();
		$wpml_twentyseventeen->init_hooks();
	}

	if ( function_exists( 'avia_lang_setup' ) ) {
		$enfold = new WPML_Compatibility_Theme_Enfold();
		$enfold->init_hooks();
	}
}