<?php

class WPML_LS_Footer_Slot extends WPML_LS_Slot {

	/**
	 * @return array
	 */
	protected function get_allowed_properties() {
		$allowed_properties = array(
			'template'   => array( 'type' => 'string', 'force_missing_to' => $this->get_core_template( 'list-horizontal' ) ),
			'slot_group' => array( 'type' => 'string', 'force_missing_to' => 'statics' ),
			'slot_slug'  => array( 'type' => 'string', 'force_missing_to' => 'footer' ),
		);

		return array_merge( parent::get_allowed_properties(), $allowed_properties );
	}
}