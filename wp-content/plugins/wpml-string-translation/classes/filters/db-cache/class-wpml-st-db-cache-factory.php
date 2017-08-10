<?php

class WPML_ST_DB_Cache_Factory {
	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var sitepress
	 */
	private $sitepress;

	/**
	 * @var WPML_ST_WP_Wrapper
	 */
	private $wp;

	/**
	 * @param wpdb $wpdb
	 * @param $sitepress sitepress
	 * @param WP $wp
	 */
	public function __construct( $wpdb = null, $sitepress = null, $wp = null ) {
		if ( ! $wpdb ) {
			global $wpdb;
		}
		$this->wpdb = $wpdb;

		if ( ! $sitepress ) {
			global $sitepress;
		}
		$this->sitepress = $sitepress;

		if ( ! $wp ) {
			global $wp;
			if ( ! $wp ) {
				$GLOBALS['wp_rewrite'] = new WP_Rewrite();
				$wp = $GLOBALS['wp'] = new WP();
			}
		}
		$this->wp = new WPML_ST_WP_Wrapper( $wp );
	}

	/**
	 * @param string $language
	 *
	 * @return WPML_ST_DB_Cache
	 */
	public function create( $language ) {
		$persist = $this->create_persist();

		$retriever = new WPML_ST_DB_Translation_Retrieve( $this->wpdb );
		$url_preprocessor = new WPML_ST_Page_URL_Preprocessor( $this->wp );

		return new WPML_ST_DB_Cache( $language, $persist, $retriever, $url_preprocessor );
	}

	/**
	 * @return IWPML_ST_Page_Translations_Persist
	 */
	public function create_persist() {
		$db_persist = new WPML_ST_Page_Translations_Persist( $this->wpdb );
		$cache = new WPML_WP_Cache( WPML_ST_Page_Translations_Cached_Persist::CACHE_GROUP );
		$cached_persist = new WPML_ST_Page_Translations_Cached_Persist( $db_persist, $cache );

		return $cached_persist;
	}
}
