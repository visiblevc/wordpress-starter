<?php

class WPML_WP_Query_API {

	private $wp_query;
	
	public function __construct( &$wp_query ) {
		$this->wp_query = $wp_query;
	}
	
	public function get_post_type_if_single() {
		$post_type = null;
		if ( isset ( $this->wp_query->query_vars[ 'post_type' ] ) ) {
			$post_type = $this->wp_query->query_vars[ 'post_type' ];
			if ( is_array( $post_type ) ) {
				if ( 1 === count( $post_type ) ) {
					$post_type = $post_type[0];
				} else {
					$post_type = null;
				}
			}
		}
		return $post_type;
	}
}