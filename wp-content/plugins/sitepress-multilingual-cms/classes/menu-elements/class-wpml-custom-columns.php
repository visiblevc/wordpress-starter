<?php

/**
 * Class WPML_Custom_Columns
 */
class WPML_Custom_Columns extends WPML_WPDB_And_SP_User {

	/**
	 * @var WPML_Post_Status_Display
	 */
	public $post_status_display;

	/**
	 * @param $columns
	 *
	 * @return array
	 */
	public 	function add_posts_management_column( $columns ) {
		$new_columns = $columns;
		$active_languages = $this->get_filtered_active_lanugages();
		if ( count( $active_languages ) <= 1 || 'trash' === get_query_var( 'post_status' ) ) {
			return $columns;
		}

		$current_language = $this->sitepress->get_current_language();
		unset( $active_languages[ $current_language ] );

		if ( count( $active_languages ) > 0 ) {
			$flags_column = '';
			foreach ( $active_languages as $language_data ) {
				$flags_column .= '<img src="' . $this->sitepress->get_flag_url( $language_data['code'] ). '" width="18" height="12" alt="' . $language_data['display_name'] . '" title="' . $language_data['display_name'] . '" style="margin:2px" />';
			}

			$new_columns = array();
			foreach ( $columns as $column_key => $column_content ) {
				$new_columns[ $column_key ] = $column_content;
				if ( ( 'title' === $column_key || 'name' === $column_key ) && ! isset( $new_columns['icl_translations'] ) ) {
					$new_columns['icl_translations'] = $flags_column;
				}
			}
		}

		return $new_columns;
	}

	/**
	 * Add posts management column.
	 *
	 * @param $column_name
	 */
	public function add_content_for_posts_management_column( $column_name ) {
		global $post;

		if ( 'icl_translations' !== $column_name ) {
			return;
		}

		$active_languages = $this->get_filtered_active_lanugages();
		if ( is_null( $this->post_status_display ) ) {
			$this->post_status_display = new WPML_Post_Status_Display( $active_languages );
		}
		unset( $active_languages[ $this->sitepress->get_current_language() ] );
		foreach ( $active_languages as $language_data ) {
			$icon_html = $this->post_status_display->get_status_html( $post->ID, $language_data['code'] );
			echo $icon_html;
		}
	}

	/**
	 * Check translation management column screen option.
	 *
	 * @param string $post_type Current post type.
	 *
	 * @return bool
	 */
	public function show_management_column_content( $post_type ) {
		$user = get_current_user_id();
		$hidden_columns = get_user_meta( $user, 'manageedit-' . $post_type . 'columnshidden', true );
		if ( '' === $hidden_columns ) {
			$is_visible = (bool) apply_filters( 'wpml_hide_management_column', true, $post_type );
			if ( false === $is_visible ) {
				update_user_meta( $user, 'manageedit-' . $post_type . 'columnshidden', array( 'icl_translations' ) );
			}
			return $is_visible;
		}

		return ! is_array( $hidden_columns ) || ! in_array( 'icl_translations', $hidden_columns, true );
	}

	/**
	 * Get list of active languages.
	 *
	 * @return array
	 */
	private function get_filtered_active_lanugages() {
		$active_languages = $this->sitepress->get_active_languages();
		return apply_filters( 'wpml_active_languages_access', $active_languages, array( 'action' => 'edit' ) );
	}
}
