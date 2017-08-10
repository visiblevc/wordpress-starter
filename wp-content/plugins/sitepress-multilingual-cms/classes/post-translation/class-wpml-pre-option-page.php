<?php

class WPML_Pre_Option_Page extends WPML_WPDB_And_SP_User {
	
	const CACHE_GROUP = 'wpml_pre_option_page';
	
	private $switched;
	private $lang;
	
	public function __construct( &$wpdb, &$sitepress, $switched, $lang ) {
		parent::__construct( $wpdb, $sitepress );
		
		$this->switched = $switched;
		$this->lang     = $lang;
	}
	
	public function get( $type, $from_language = null ) {

		$cache_key   = $type;
		$cache_found = false;
		
		$cache       = new WPML_WP_Cache( self::CACHE_GROUP );
		$results     = $cache->get( $cache_key, $cache_found );

		if ( ( ( ! $cache_found || ! isset ( $results[ $type ] ) ) && ! $this->switched )
		     || ( $this->switched && $this->sitepress->get_setting( 'setup_complete' ) )
		) {
			$results[ $type ] = array();
			// Fetch for all languages and cache them.
			$values = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"	SELECT element_id, language_code
						FROM {$this->wpdb->prefix}icl_translations
						WHERE trid =
							(SELECT trid
							 FROM {$this->wpdb->prefix}icl_translations
							 WHERE element_type = 'post_page'
							 AND element_id = (SELECT option_value
											   FROM {$this->wpdb->options}
											   WHERE option_name=%s
											   LIMIT 1))
						",
					$type
				)
			);

			if ( count( $values ) ) {
				foreach ( $values as $lang_result ) {
					$results [ $type ] [ $lang_result->language_code ] = $lang_result->element_id;
				}
			}

			$cache->set( $cache_key, $results );
		}

		$target_language = $from_language ? $from_language : $this->lang;

		return isset( $results[ $type ][ $target_language ] ) ? $results[ $type ][ $target_language ] : false;
	}

	public function clear_cache() {
		$cache = new WPML_WP_Cache( self::CACHE_GROUP );
		$cache->flush_group_cache();
	}

	function fix_trashed_front_or_posts_page_settings( $post_id ) {
		$post_id = (int) $post_id;
		$page_on_front_current  = (int) $this->get( 'page_on_front' );
		$page_for_posts_current = (int) $this->get( 'page_for_posts' );

		$page_on_front_default  = (int) $this->get( 'page_on_front', $this->sitepress->get_default_language() );
		$page_for_posts_default = (int) $this->get( 'page_for_posts', $this->sitepress->get_default_language() );

		if ( $page_on_front_current === $post_id && $page_on_front_current !== $page_on_front_default ) {
			remove_filter( 'pre_option_page_on_front', array( $this->sitepress, 'pre_option_page_on_front' ) );
			update_option( 'page_on_front', $page_on_front_default );
			add_filter( 'pre_option_page_on_front', array( $this->sitepress, 'pre_option_page_on_front' ) );
		}
		if ( $page_for_posts_current === $post_id && $page_for_posts_current !== $page_for_posts_default ) {
			remove_filter( 'pre_option_page_for_posts', array( $this->sitepress, 'pre_option_page_for_posts' ) );
			update_option( 'page_for_posts', $page_for_posts_default );
			add_filter( 'pre_option_page_for_posts', array( $this->sitepress, 'pre_option_page_for_posts' ) );
		}
	}

}