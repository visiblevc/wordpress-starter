<?php

class WPML_Translation_Editor_Header {

	private $job_instance;

	public function __construct( $job_instance ) {
		$this->job_instance = $job_instance;
	}

	public function get_model() {
		$type_title        = esc_html( $this->job_instance->get_type_title() );
		$title             = esc_html( $this->job_instance->get_title() );
		$data              = array();
		$data['title']     = sprintf( __( '%1$s translation: %2$s', 'wpml-translation-management' ), $type_title, '<strong>' . $title . '</strong>' );
		$data['link_url']  = $this->job_instance->get_url( true );
		$data['link_text'] = $this->job_instance instanceof WPML_External_Translation_Job ? '' : sprintf( __( 'View %s', 'wpml-translation-management' ), $type_title );

		return $data;
	}
}

