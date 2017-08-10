<?php

class WPML_LS_Shortcode_Actions_Slot extends WPML_LS_Slot {

	/**
	 * @return bool
	 */
	public function is_enabled() {
		return true;
	}

	/**
	 * @return array
	 */
	protected function get_allowed_properties() {
		$allowed_properties = array(
			'show'       => array( 'type' => 'int', 'force_missing_to' => 1 ),
			'template'   => array( 'type' => 'string', 'force_missing_to' => $this->get_core_template( 'list-horizontal' ) ),
			'slot_group' => array( 'type' => 'string', 'force_missing_to' => 'statics' ),
			'slot_slug'  => array( 'type' => 'string', 'force_missing_to' => 'shortcode_actions' ),
		);

		return array_merge( parent::get_allowed_properties(), $allowed_properties );
	}
}