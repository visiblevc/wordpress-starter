<?php

class WPML_WP_Taxonomy_Query  {
	private $taxonomies_query_vars;
	
	public function __construct( $wp_api ) {
		
		$wp_taxonomies = $wp_api->get_wp_taxonomies();
		
		$this->taxonomies_query_vars = array();
		
		foreach ( $wp_taxonomies as $k => $v ) {
			if ( $k === 'category' ) {
				continue;
			}
			if ( $k == 'post_tag' && !$v->query_var ) {
				$tag_base     = $wp_api->get_option( 'tag_base', 'tag' );
				$v->query_var = $tag_base;
			}
			if ( $v->query_var ) {
				$this->taxonomies_query_vars[ $k ] = $v->query_var;
			}
		}
	}
	
	public function get_query_vars() {
		return $this->taxonomies_query_vars;
	}
	
	public function find( $taxonomy ) {
		$tax = false;
		if ( isset( $this->taxonomies_query_vars ) && is_array( $this->taxonomies_query_vars ) ) {
			$tax = array_search( $taxonomy, $this->taxonomies_query_vars );
		}
		return $tax;
	}
}