<?php

class WPML_LS_Sidebar_Slot extends WPML_LS_Slot {

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
			'widget_title' => array( 'type' => 'string', 'stripslashes' => true ),
			'show'         => array( 'type' => 'int', 'force_missing_to' => 1 ),
			'template'     => array( 'type' => 'string', 'force_missing_to' => $this->get_core_template( 'dropdown' ) ),
			'slot_group'   => array( 'type' => 'string', 'force_missing_to' => 'sidebars' ),
		);

		return array_merge( parent::get_allowed_properties(), $allowed_properties );
	}
}