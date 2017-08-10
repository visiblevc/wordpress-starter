<?php

abstract class WPML_Language_Filter_Bar extends WPML_WPDB_And_SP_User {

	protected $active_languages;
	protected $current_language;

	protected function init() {
		if ( !isset( $this->active_languages[ 'all' ] ) ) {
			$this->current_language          = $this->sitepress->get_current_language ();
			$this->active_languages          = $this->sitepress->get_active_languages ();
			$this->active_languages[ 'all' ] = array( 'display_name' => __ ( 'All languages', 'sitepress' ) );
		}
	}

	protected function lang_span( $lang_code, $count ) {

		return ' (<span class="' . $lang_code . '">' . $count . '</span>)';
	}

	protected function strong_lang_span_cover($lang_code, $count){
		$px = '<strong>';
		$sx = $this->lang_span( $lang_code, $count ) . '</strong>';

		return array($px, $sx);
	}

	private function sanitize_get_input( $index, $parent_array ) {
		$value = isset( $_GET[ $index ] ) ? $_GET[ $index ] : false;

		return isset( $parent_array[ $value ] ) ? $value : '';
	}

	protected function sanitize_request() {
		global $wp_post_types, $wp_taxonomies;
		$taxonomy  = $this->sanitize_get_input( 'taxonomy', $wp_taxonomies );
		$post_type = $this->sanitize_get_input( 'post_type', $wp_post_types );

		return array( 'req_tax' => $taxonomy, 'req_ptype' => $post_type );
	}

	protected abstract function get_count_data( $element_type );

	protected function extra_conditions_snippet() {

		$sql = " AND t.language_code IN (" . wpml_prepare_in( array_keys( $this->active_languages ) ) . ")
				 GROUP BY language_code";

		return apply_filters( 'wpml_language_filter_extra_conditions_snippet', $sql );
	}

	protected function get_counts( $element_type ) {
		$counts = $this->get_count_data( $element_type );
		$counts = (bool) $counts === true ? $counts : array();

		return $this->generate_counts_array( $counts);
	}

	private function generate_counts_array( array $data ) {
		$languages = array( 'all' => 0 );
		foreach ( $data as $language_count ) {
			$languages[ $language_count->language_code ] = $language_count->c;
			$languages['all'] += $language_count->c;
		}

		return $languages;
	}
}