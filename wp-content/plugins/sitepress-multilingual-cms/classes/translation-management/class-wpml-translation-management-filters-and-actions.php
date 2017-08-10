<?php

class WPML_Translation_Management_Filters_And_Actions extends WPML_SP_User {

	/** @param TranslationManagement tm_instance */
	public function __construct( &$tm_instance, &$sitepress ) {
		parent::__construct( $sitepress );

		if ( ! is_admin() ) {
			$this->add_filters_for_translating_link_targets( $tm_instance );
		}

	}
	
	private function add_filters_for_translating_link_targets( &$tm_instance ) {
		require_once ICL_PLUGIN_PATH . '/inc/absolute-links/absolute-links.class.php';
		$this->absolute_links = new AbsoluteLinks();
		$wp_api = $this->sitepress->get_wp_api();
		$this->permalinks_converter = new WPML_Absolute_To_Permalinks( $this->sitepress );
		$this->translate_links_in_custom_fields = new WPML_Translate_Link_Targets_In_Custom_Fields( $tm_instance,
																			 $wp_api,
																			 $this->absolute_links,
																			 $this->permalinks_converter
																			 );
		$this->translate_links_in_custom_fields_hooks = new WPML_Translate_Link_Targets_In_Custom_Fields_Hooks( $this->translate_links_in_custom_fields,
																			 $wp_api
																			 );
		
		$this->translate_link_target = new WPML_Translate_Link_Targets( $this->absolute_links, $this->permalinks_converter );
		$this->translate_link_target_hooks = new WPML_Translate_Link_Targets_Hooks( $this->translate_link_target, $wp_api );
	}
}