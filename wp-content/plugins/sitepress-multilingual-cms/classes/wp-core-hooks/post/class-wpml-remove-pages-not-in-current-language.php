<?php

class WPML_Remove_Pages_Not_In_Current_Language extends WPML_WPDB_And_SP_User {
	private $posts_from_ids = array();
	private $posts_in_other_languages = array();

	/**
	 * @param array $arr Array of posts to filter
	 * @param array $get_page_arguments Arguments passed to the `get_pages` function
	 *
	 * @return array
	 */
	function filter_pages( $arr, $get_page_arguments ) {
		$new_arr          = $arr;
		$current_language = $this->sitepress->get_current_language();

		if ( 'all' !== $current_language && 0 !== count( $new_arr ) ) {
			$cache_key = md5( serialize( $new_arr ) );
			if ( $this->is_cached( $cache_key ) ) {
				$new_arr = $this->posts_from_ids[ $current_language ][ $cache_key ];
			} else {
				$post_type = $this->find_post_type( $get_page_arguments, $new_arr );

				$filtered_pages = array();
				// grab list of pages NOT in the current language
				$excl_pages = $this->get_posts_in_other_languages( $post_type, $current_language );
				foreach ( $new_arr as $page ) {
					$post_id = false;
					if ( is_numeric( $page ) ) {
						$post_id = $page;
					} elseif ( isset( $page->ID ) ) {
						$post_id = $page->ID;
					} elseif ( isset( $page['ID'] ) ) {
						$post_id = $page['ID'];
					}

					if ( false !== $post_id && ! in_array( $post_id, $excl_pages, false ) ) {
						$filtered_pages[] = $page;
					}
				}
				$new_arr                                                 = $filtered_pages;
				$this->posts_from_ids[ $current_language ][ $cache_key ] = $new_arr;
			}
		}

		return $new_arr;
	}


	/**
	 * @param $post_type
	 * @param $current_language
	 *
	 * @return array
	 */
	private function get_posts_in_other_languages( $post_type, $current_language ) {
		if ( isset( $this->posts_in_other_languages[ $current_language ][ $post_type ] ) ) {
			$excl_pages = $this->posts_in_other_languages[ $current_language ][ $post_type ];
		} else {
			$excl_pages_query = "
								SELECT p.ID FROM {$this->wpdb->posts} p
								JOIN {$this->wpdb->prefix}icl_translations t ON p.ID = t.element_id
								WHERE t.element_type=%s AND p.post_type=%s AND t.language_code <> %s
								";

			$excl_pages_args                                                   = array(
				'post_' . $post_type,
				$post_type,
				$current_language
			);
			$excl_pages_prepare                                                = $this->wpdb->prepare( $excl_pages_query, $excl_pages_args );
			$excl_pages                                                        = $this->wpdb->get_col( $excl_pages_prepare );
			$this->posts_in_other_languages[ $current_language ][ $post_type ] = $excl_pages;
		}

		return $excl_pages;
	}

	/**
	 * @param $get_page_arguments
	 * @param $new_arr
	 *
	 * @return false|string
	 */
	private function find_post_type( $get_page_arguments, $new_arr ) {
		$post_type = 'page';
		if ( array_key_exists( 'post_type', $get_page_arguments ) ) {
			$post_type = $get_page_arguments['post_type'];

			return $post_type;
		} else {
			$temp_items = array_values( $new_arr );
			$first_item = $temp_items[0];
			if ( is_object( $first_item ) ) {
				$first_item = object_to_array( $first_item );
			}
			if ( is_array( $first_item ) ) {
				if ( array_key_exists( 'post_type', $first_item ) ) {
					$post_type = $first_item['post_type'];

					return $post_type;
				} elseif ( array_key_exists( 'ID', $first_item ) ) {
					$post_type = $this->sitepress->get_wp_api()->get_post_type( $first_item['ID'] );

					return $post_type;
				}

				return $post_type;
			} elseif ( is_numeric( $first_item ) ) {
				$post_type = $this->sitepress->get_wp_api()->get_post_type( $first_item );

				return $post_type;
			}

			return $post_type;
		}
	}

	/**
	 * @param $cache_key
	 *
	 * @return bool
	 */
	private function is_cached( $cache_key ) {
		$current_language = $this->sitepress->get_current_language();
		return array_key_exists( $current_language, $this->posts_from_ids ) && array_key_exists( $cache_key, $this->posts_from_ids[ $current_language ] ) && 0 !== count( $this->posts_from_ids[ $current_language ] );
}

}