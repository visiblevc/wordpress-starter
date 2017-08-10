<?php

class WPML_Editor_UI_Field {

	protected $id;
	protected $title;
	protected $original;
	protected $translation;
	private $requires_complete;
	protected $is_complete;

	function __construct( $id, $title, $data, $requires_complete = false ) {

		$this->id                = $id;
		$this->title             = $title ? $title : '';
		$this->original          = $data[ $id ]['original'];
		$this->translation       = isset( $data[ $id ]['translation'] ) ? $data[ $id ]['translation'] : '';
		$this->requires_complete = $requires_complete;
		$this->is_complete       = isset( $data[ $id ]['is_complete'] ) ? $data[ $id ]['is_complete'] : false;

	}

	public function get_fields() {
		$field                          = array();
		$field['field_type']            = $this->id;
		$field['field_data']            = $this->original;
		$field['field_data_translated'] = $this->translation;
		$field['title']                 = $this->title;
		$field['field_finished']        = $this->is_complete ? '1' : '0';
		$field['tid']                   = '0';

		return $field;
	}

	public function get_layout() {
		// This is a field with no sub fields so just return the id
		return $this->id;
	}


}

