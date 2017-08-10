<?php

class WPML_Translator {
	var $ID;
	var $display_name;
	var $user_login;
	var $language_pairs;

	/** @noinspection PhpInconsistentReturnPointsInspection
	 * @param string $property
	 *
	 * @return
	 */
	public function __get( $property ) {
		if ( $property == 'translator_id' ) {
			return $this->ID;
		}
	}

	public function __set( $property, $value ) {
		if ( $property == 'translator_id' ) {
			$this->ID = $value;
		}
	}
}
