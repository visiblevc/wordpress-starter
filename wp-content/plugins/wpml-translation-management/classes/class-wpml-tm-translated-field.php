<?php

class WPML_TM_Translated_Field {

	private $field_name;
	private $original;
	private $translation;
	private $finished_state;

	/**
	 * WPML_TM_API constructor.
	 *
	 * @param string $field_name
	 * @param string $original
	 * @param string $translation
	 * @param bool   $finished_state
	 */
	
	public function __construct( $field_name, $original, $translation, $finished_state ) {
		$this->field_name     = $field_name;
		$this->original       = $original;
		$this->translation    = $translation;
		$this->finished_state = $finished_state;
	}
	
	public function get_translation() {
		return $this->translation;
	}
	
	public function is_finished( $original ) {
		if ( $original == $this->original ) {
			return $this->finished_state;
		} else {
			return false;
		}
	}
}