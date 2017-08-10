<?php

class WPML_Editor_UI_WYSIWYG_Field extends WPML_Editor_UI_Field {

	private $include_copy_button;

	function __construct( $id, $title, $data, $include_copy_button, $requires_complete = false ) {
		parent::__construct( $id, $title, $data, $requires_complete );

		$this->include_copy_button = $include_copy_button;
	}

	public function get_fields() {
		$field                = parent::get_fields();
		$field['field_style'] = '2';

		return array( $field );
	}


}

