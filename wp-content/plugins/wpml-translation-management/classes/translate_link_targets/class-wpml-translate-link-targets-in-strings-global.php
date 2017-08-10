<?php

class WPML_Translate_Link_Targets_In_Strings_Global extends WPML_Translate_Link_Targets_In_Strings {

	protected function get_contents_with_links_needing_fix( $start_id = 0, $count = 0 ) {

		$limit = '';
		if ( $count > 0 ) {
			$limit = " LIMIT " . $count;
		}

		$this->content_to_fix = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT id as element_id, language as language_code FROM {$this->wpdb->prefix}icl_string_translations WHERE id >= %d ORDER BY id " . $limit,
			$start_id
		) );
	}

	public function get_number_to_be_fixed( $start_id = 0 ) {
		return $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT COUNT(id) FROM {$this->wpdb->prefix}icl_string_translations WHERE id >= %d ORDER BY id ",
			$start_id
		) );
	}

}

