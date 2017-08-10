<?php

class WPML_Update_Term_Count {

	/** @var  WPML_WP_API $wp_api */
	private $wp_api;

	/**
	 * WPML_Update_Term_Count constructor.
	 *
	 * @param WPML_WP_API $wp_api
	 */
	public function __construct( &$wp_api ) {
		$this->wp_api = $wp_api;
	}

	/**
	 * Triggers an update to the term count of all terms associated with the
	 * input post_id
	 *
	 * @param int $post_id
	 */
	public function update_for_post( $post_id ) {
		$taxonomies = $this->wp_api->get_taxonomies();
		foreach ( $taxonomies as $taxonomy ) {
			$terms_for_post = $this->wp_api->wp_get_post_terms( $post_id,
				$taxonomy );
			foreach ( $terms_for_post as $term ) {
				if ( isset( $term->term_taxonomy_id ) ) {
					$this->wp_api->wp_update_term_count( $term->term_taxonomy_id, $taxonomy );
				}
			}
		}
	}
}
