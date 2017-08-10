<?php

/**
 * Class WPML_Translate_Link_Targets_In_Strings
 *
 * @package wpml-tm
 */
class WPML_Translate_Link_Targets_In_Strings extends WPML_Translate_Link_Targets_In_Content {

	private $option_name = 'wpml_strings_need_links_fixed';

	/* var WPML_WP_API $wp_api */
	private $wp_api;

	public function __construct( WPML_Translate_Link_Target_Global_State $translate_link_target_global_state, &$wpdb, $wp_api, $pro_translation ) {
		parent::__construct( $translate_link_target_global_state, $wpdb, $pro_translation );
		$this->wp_api          = $wp_api;
	}
	
	protected function get_contents_with_links_needing_fix( $start = 0, $count = 0 ) {
		$strings_to_fix = $this->wp_api->get_option( $this->option_name, array() );
		sort( $strings_to_fix, SORT_NUMERIC );
		$strings_to_fix_part = array();
		$include_all = $count == 0 ? true : false;
		foreach ( $strings_to_fix as $string_id ) {
			if ( $string_id >= $start ) {
				$strings_to_fix_part[] = $string_id;
			}
			if ( !$include_all ) {
				$count--;
				if ( $count == 0 ) {
					break;
				}
			}
		}

		$this->content_to_fix = array();

		if ( sizeof( $strings_to_fix_part ) ) {
			$strings_to_fix_part  = implode( ',', $strings_to_fix_part );
			$this->content_to_fix = $this->wpdb->get_results(
				"SELECT id as element_id, language as language_code 
					FROM {$this->wpdb->prefix}icl_string_translations
					WHERE id in ( {$strings_to_fix_part} )"
			);
		}
	}

	protected function get_content_type() {
		return 'string';
	}

	public function get_number_to_be_fixed( $start_id = 0 ) {
		$this->get_contents_with_links_needing_fix( $start_id );
		return sizeof( $this->content_to_fix );
	}

}