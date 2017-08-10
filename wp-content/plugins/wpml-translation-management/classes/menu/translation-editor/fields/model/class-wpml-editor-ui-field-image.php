<?php

class WPML_Editor_UI_Field_Image extends WPML_Editor_UI_Fields {

	private $image_id;
	private $divider;
	private $group;

	function __construct( $id, $image_id, $data, $divider = true ) {

		$this->image_id = $image_id;
		$this->divider  = $divider;
		$this->group    = new WPML_Editor_UI_Field_Group( '', false );

		$this->group->add_field( new WPML_Editor_UI_Single_Line_Field( $id . '-title', __( 'Title', 'wpml-translation-management' ), $data, false ) );
		$this->group->add_field( new WPML_Editor_UI_Single_Line_Field( $id . '-caption', __('Caption', 'wpml-translation-management' ), $data, false ) );
		$this->group->add_field( new WPML_Editor_UI_Single_Line_Field( $id . '-alt-text', __('Alt Text', 'wpml-translation-management' ), $data, false ) );
		$this->group->add_field( new WPML_Editor_UI_Single_Line_Field( $id . '-description', __('Description', 'wpml-translation-management' ), $data, false ) );

		$this->add_field( $this->group );
	}

	public function get_layout() {
		$image = wp_get_attachment_image_src( $this->image_id, array( 100, 100 ) );
		$data  = array(
			'field_type' => 'wcml-image',
			'divider'    => $this->divider,
			'image_src'  => $image[0],
		);

		$data['fields'] = parent::get_layout();

		return $data;
	}


}

