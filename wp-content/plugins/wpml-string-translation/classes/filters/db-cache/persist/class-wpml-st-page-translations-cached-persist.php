<?php

class WPML_ST_Page_Translations_Cached_Persist implements IWPML_ST_Page_Translations_Persist {
	const CACHE_GROUP = 'wpml_display_filter';
	const EXPIRE_TIME = 600;

	/**
	 * @var IWPML_ST_Page_Translations_Persist
	 */
	private $wrapped_persist;

	/**
	 * @var WPML_WP_Cache
	 */
	private $cache;

	/**
	 * @param IWPML_ST_Page_Translations_Persist $wrapped_persist
	 * @param WPML_WP_Cache $cache
	 */
	public function __construct( IWPML_ST_Page_Translations_Persist $wrapped_persist, WPML_WP_Cache $cache ) {
		$this->wrapped_persist = $wrapped_persist;
		$this->cache = $cache;
	}

	/**
	 * @param $language
	 * @param $page_url
	 *
	 * @return WPML_ST_Page_Translations
	 */
	public function get_translations_for_page( $language, $page_url ) {
		$key = $this->get_cache_key( $language, $page_url );
		$found = false;
		$result = $this->cache->get( $key, $found );

		if ( ! $result instanceof WPML_ST_Page_Translations ) {
			$result = $this->wrapped_persist->get_translations_for_page( $language, $page_url );
			$this->cache->set( $key, $result, self::EXPIRE_TIME );
		}

		return $result;
	}

	/**
	 * @param string $language
	 * @param string $page_url
	 * @param WPML_ST_Page_Translation[] $translations
	 */
	public function store_new_translations( $language, $page_url, $translations ) {
		$this->wrapped_persist->store_new_translations( $language, $page_url, $translations );

		$key = $this->get_cache_key( $language, $page_url );
		$result = $this->wrapped_persist->get_translations_for_page( $language, $page_url );
		$this->cache->set( $key, $result, self::EXPIRE_TIME );
	}

	public function clear_cache() {
		$this->wrapped_persist->clear_cache();
		$this->cache->flush_group_cache();
	}

	/**
	 * @param $language
	 * @param $page_url
	 *
	 * @return string
	 */
	private function get_cache_key( $language, $page_url ) {
		return md5( $language . '_' . $page_url );
	}
}