<?php

/**
 * Class WPML_Translate_Link_Targets_In_Content
 *
 * @package wpml-tm
 */
abstract class WPML_Translate_Link_Targets_In_Content extends WPML_WPDB_User {

	protected $scanning_in_progress = false;
	protected $content_to_fix;
	protected $number_of_links_fixed;
	/* var WPML_Pro_Translation	$pro_translation */
	protected $pro_translation;
	/** @var  WPML_Translate_Link_Target_Global_State $translate_link_target_global_state */
	private $translate_link_target_global_state;

	const MAX_TO_FIX_FOR_NEW_CONTENT = 3;

	public function __construct( WPML_Translate_Link_Target_Global_State $translate_link_target_global_state,
		&$wpdb, 
		$pro_translation ) 
	{
		parent::__construct( $wpdb );
		$this->pro_translation                    = $pro_translation;
		$this->translate_link_target_global_state = $translate_link_target_global_state;
	}

	public function new_content() {
		if ( $this->translate_link_target_global_state->should_fix_content() ) {
			if ( ! $this->do_new_content() ) {
				$this->translate_link_target_global_state->set_rescan_required();
			}
		}
	}

	private function do_new_content() {

		if ( $this->pro_translation && ! $this->scanning_in_progress ) {
			$number_needing_to_be_fixed = $this->get_number_to_be_fixed();
			$this->fix( 0, self::MAX_TO_FIX_FOR_NEW_CONTENT );
			return $number_needing_to_be_fixed <= self::MAX_TO_FIX_FOR_NEW_CONTENT;
		} else {
			return true;
		}
	}

	public function get_number_of_links_that_were_fixed() {
		return $this->number_of_links_fixed;
	}
	
	
	public function fix( $start = 0, $count = 0 ) {
		$this->scanning_in_progress = true;
		$this->get_contents_with_links_needing_fix( $start, $count );
		$last_content_processed = 0;
		$this->number_of_links_fixed = 0;

		foreach( $this->content_to_fix as $content ) {

			$this->number_of_links_fixed += $this->pro_translation->fix_links_to_translated_content( $content->element_id, $content->language_code, $this->get_content_type() );
			$last_content_processed = $content->element_id;
		}

		$this->scanning_in_progress = false;

		return $last_content_processed;
	}

	abstract protected function get_contents_with_links_needing_fix( $start = 0, $count = 0 );
	abstract protected function get_content_type();
	abstract public function get_number_to_be_fixed( $start_id = 0 );
	
}