<?php

class WPML_LS_Post_Translations_Slot extends WPML_LS_Slot {

	/**
	 * @return array
	 */
	protected function get_allowed_properties() {
		$allowed_properties = array(
			'display_before_content' => array( 'type' => 'int', 'force_missing_to' => 0 ),
			'display_after_content'  => array( 'type' => 'int', 'force_missing_to' => 0 ),
			'availability_text'      => array( 'type' => 'string', 'stripslashes' => true ),
			'template'               => array( 'type' => 'string', 'force_missing_to' => $this->get_core_template( 'post-translations' ) ),
			'slot_group'             => array( 'type' => 'string', 'force_missing_to' => 'statics' ),
			'slot_slug'              => array( 'type' => 'string', 'force_missing_to' => 'post_translations' ),
		);

		return array_merge( parent::get_allowed_properties(), $allowed_properties );
	}
}