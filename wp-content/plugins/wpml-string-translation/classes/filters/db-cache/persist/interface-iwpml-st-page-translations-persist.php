<?php

interface IWPML_ST_Page_Translations_Persist {
	/**
	 * @param $language
	 * @param $page_url
	 *
	 * @return WPML_ST_Page_Translations
	 */
	public function get_translations_for_page( $language, $page_url );

	/**
	 * @param string $language
	 * @param string $page_url
	 * @param WPML_ST_Page_Translation[] $translations
	 */
	public function store_new_translations( $language, $page_url, $translations );
	
	public function clear_cache();
}