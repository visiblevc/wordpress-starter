<?php

class WPML_PB_Loader {

	public function __construct(
		SitePress $sitepress,
		WPDB $wpdb,
		WPML_ST_Settings $st_settings,
		$pb_integration = null // Only needed for testing
	) {

		do_action( 'wpml_load_page_builders_integration' );

		$page_builder_strategies = array();

		$page_builder_config_import = new WPML_PB_Config_Import_Shortcode( $st_settings );
		$page_builder_config_import->add_hooks();
		if ( $page_builder_config_import->has_settings() ) {
			$strategy = new WPML_PB_Shortcode_Strategy();
			$strategy->add_shortcodes( $page_builder_config_import->get_settings() );
			$page_builder_strategies[] = $strategy;
		}

		$required = apply_filters( 'wpml_page_builder_support_required', array() );
		foreach ( $required as $plugin ) {
			$page_builder_strategies[] = new WPML_PB_API_Hooks_Strategy( $plugin );
		}

		if ( $page_builder_strategies ) {
			if ( $pb_integration ) {
				$factory = $pb_integration->get_factory();
			} else {
				$factory        = new WPML_PB_Factory( $wpdb, $sitepress );
				$pb_integration = new WPML_PB_Integration( $sitepress, $factory );
			}
			$pb_integration->add_hooks();
			foreach ( $page_builder_strategies as $strategy ) {
				$strategy->set_factory( $factory );
				$pb_integration->add_strategy( $strategy );
			}
		}

	}
}