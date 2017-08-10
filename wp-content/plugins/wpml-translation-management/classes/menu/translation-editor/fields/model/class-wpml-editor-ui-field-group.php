<?php

class WPML_Editor_UI_Field_Group extends WPML_Editor_UI_Fields {

	private $title;
	private $divider;

	function __construct( $title = '', $divider = true ) {
		$this->title   = $title;
		$this->divider = $divider;

	}

	public function get_layout() {
		$data = array(
			'title'      => $this->title,
			'divider'    => $this->divider,
			'field_type' => 'tm-group',
		);

		$data['fields'] = parent::get_layout();

		return $data;
	}

}

