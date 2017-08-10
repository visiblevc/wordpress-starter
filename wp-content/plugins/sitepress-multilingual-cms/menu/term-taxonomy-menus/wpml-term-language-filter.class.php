<?php
require_once ICL_PLUGIN_PATH . '/menu/wpml-language-filter-bar.class.php';

class WPML_Term_Language_Filter extends WPML_Language_Filter_Bar {

	function terms_language_filter( $echo = true ) {
		$this->init();
		$requested_data  = $this->sanitize_request();
		$taxonomy        = $requested_data['req_tax'] !== '' ? $requested_data['req_tax'] : 'post_tag';
		$post_type       = $requested_data['req_ptype'] !== '' ? $requested_data['req_ptype'] : '';
		$languages       = $this->get_counts( $taxonomy );
		$languages_links = array();
		foreach ( $this->active_languages as $code => $lang ) {
			$languages_links[] = $this->lang_element( $languages, $code, $taxonomy, $post_type );
		}
		$all_languages_links = join( ' | ', $languages_links );

		$html = '<span id="icl_subsubsub" style="display: none;">' . $all_languages_links . '</span>';
		if ( $echo !== false ) {
			echo $html;
		}

		return $html;
	}

	private function lang_element( $languages, $code, $taxonomy, $post_type ) {
		$count = isset( $languages[ $code ] ) ? $languages[ $code ] : 0;
		if ( $code === $this->current_language ) {
			list($px, $sx) = $this->strong_lang_span_cover($code, $count);
		} else {
			$px = '<a href="?taxonomy=' . $taxonomy . '&amp;lang=' . $code;
			$px .= $post_type !== '' ? '&amp;post_type=' . $post_type : '';
			$px .= '">';
			$sx = '</a>' . $this->lang_span( $code, $count );
		}

		return $px . $this->active_languages[ $code ][ 'display_name' ] . $sx;
	}

	protected function get_count_data( $taxonomy ) {
		$res_query = "	SELECT language_code, COUNT(tm.term_id) AS c
						FROM {$this->wpdb->prefix}icl_translations t
						JOIN {$this->wpdb->term_taxonomy} tt
							ON t.element_id = tt.term_taxonomy_id
							AND t.element_type = CONCAT('tax_', tt.taxonomy)
						JOIN {$this->wpdb->terms} tm
							ON tt.term_id = tm.term_id
						WHERE tt.taxonomy = %s
						" . $this->extra_conditions_snippet();

		return $this->wpdb->get_results ( $this->wpdb->prepare ( $res_query, $taxonomy ) );
	}
}
