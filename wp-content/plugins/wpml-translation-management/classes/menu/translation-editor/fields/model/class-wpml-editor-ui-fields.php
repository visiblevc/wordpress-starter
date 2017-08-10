<?php

class WPML_Editor_UI_Fields {

	private $fields = array();

	public function add_field( $field ) {
		$this->fields[] = $field;
	}

	public function get_fields() {
		$fields = array();
		/** @var WPML_Editor_UI_Field $field */
		foreach ( $this->fields as $field ) {
			$child_fields = $field->get_fields();
			foreach ( $child_fields as $child_field ) {
				$fields[] = $child_field;
			}
		}

		return $fields;
	}

	public function get_layout() {
		$layout = array();
		/** @var WPML_Editor_UI_Field $field */
		foreach ( $this->fields as $field ) {
			$layout[] = $field->get_layout();
		}

		return $layout;
	}


}

