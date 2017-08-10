<?php

class WPML_Resolve_Object_Url_Helper_Factory {
	/**
	 * @return WPML_Resolve_Object_Url_Helper
	 */
	public function create() {
		global $sitepress, $wp_query, $wpml_term_translations, $wpml_post_translations;
		$helper = new WPML_Resolve_Object_Url_Helper( $sitepress, $wp_query, $wpml_term_translations, $wpml_post_translations );

		return $helper;
	}
}