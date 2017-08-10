<?php

class WPML_Editor_UI_Field_Section extends WPML_Editor_UI_Fields {

	private $title;
	private $sub_title;

	function __construct( $title = '', $sub_title = '' ) {
		$this->title     = $title;
		$this->sub_title = $sub_title;
	}

	public function get_layout() {
		$data = array(
			'empty_message' => '',
			'empty'         => false,
			'title'         => $this->title,
			'sub_title'     => $this->sub_title,
			'field_type'    => 'tm-section',
		);

		$data['fields'] = parent::get_layout();

		return $data;
	}

}

