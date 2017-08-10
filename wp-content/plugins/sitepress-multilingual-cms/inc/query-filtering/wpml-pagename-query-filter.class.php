<?php

/**
 * Class WPML_Page_Name_Query_Filter
 *
 * @package    wpml-core
 * @subpackage post-translation
 *
 * @since      3.2
 */
class WPML_Page_Name_Query_Filter extends WPML_Name_Query_Filter_Translated {

	protected $id_index = 'page_id';

	/**
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translations
	 * @param wpdb                  $wpdb
	 */
	public function __construct( &$sitepress, &$post_translations, &$wpdb ) {
		parent::__construct( 'page', $sitepress, $post_translations, $wpdb );
		$this->indexes = array( 'name', 'pagename' );
	}

	/**
	 * @param WP_Query $page_query
	 * @param int      $pid
	 * @param string   $index
	 *
	 * @return WP_Query
	 */
	protected function maybe_adjust_query_by_pid( $page_query, $pid, $index ) {
		$page_query = parent::maybe_adjust_query_by_pid( $page_query, $pid, $index );
		if ( (int) $pid === (int) get_option( 'page_for_posts' ) ) {
			$page_query->query_vars['post_status'] = 'publish';
			$page_query->query_vars['name']        = '';
			$page_query->post_status               = 'publish';
			$page_query->is_page                   = false;
			$page_query->is_singular               = false;
			$page_query->is_home                   = true;
			$page_query->is_posts_page             = true;
		}

		return $page_query;
	}

	/**
	 * Called when the post id is being adjusted. Can be overridden.
	 * 
	 * @param WP_Query $page_query
	 *
	 * @return WP_Query
	 */
	
	protected function adjusting_id( $page_query ) {
		$page_query->is_page = true;
		
		return $page_query;
	}
}