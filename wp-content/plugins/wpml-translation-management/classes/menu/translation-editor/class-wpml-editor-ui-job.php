<?php

class WPML_Editor_UI_Job {

	private $fields = array();
	protected $job_id;
	private $job_type;
	private $job_type_title;
	private $title;
	private $view_link;
	private $source_lang;
	private $target_lang;
	private $translation_complete;
	private $duplicate;
	private $note = '';

	function __construct(
		$job_id,
		$job_type,
		$job_type_title,
		$title,
		$view_link,
		$source_lang,
		$target_lang,
		$translation_complete,
		$duplicate
	) {

		$this->job_id               = $job_id;
		$this->job_type             = $job_type;
		$this->job_type_title       = $job_type_title;
		$this->title                = $title;
		$this->view_link            = $view_link;
		$this->source_lang          = $source_lang;
		$this->target_lang          = $target_lang;
		$this->translation_complete = $translation_complete;
		$this->duplicate            = $duplicate;

	}

	public function add_field( $field ) {
		$this->fields[] = $field;
	}

	public function add_note( $note ) {
		$this->note = $note;
	}

	public function get_all_fields() {
		$fields = array();
		/** @var WPML_Editor_UI_Field $field */
		foreach ( $this->fields as $field ) {
			$child_fields = $field->get_fields();
			/** @var WPML_Editor_UI_Field $child_field */
			foreach ( $child_fields as $child_field ) {
				$fields[] = $child_field;
			}
		}

		return $fields;
	}

	public function get_layout_of_fields() {
		$layout = array();
		/** @var WPML_Editor_UI_Field $field */
		foreach ( $this->fields as $field ) {
			$layout[] = $field->get_layout();
		}

		return $layout;
	}

	public function get_target_language() {
		return $this->target_lang;
	}

	public function is_translation_complete() {
		return $this->translation_complete;
	}
	
	public function save( $data ) {
		$translations = array();

		foreach ( $data['fields'] as $id => $field ) {
			$translations[ $this->convert_id_to_translation_key( $id ) ] = $field['data'];
		}

		try {
			$this->save_translations( $translations );

			return new WPML_Ajax_Response( true, true );
		} catch ( Exception $e ) {
			return new WPML_Ajax_Response( false, 0 );
		}
	}

	private function convert_id_to_translation_key( $id ) {
		// This is to support the old api for saving translations.
		return md5( $id );
	}

	public function requires_translation_complete_for_each_field() {
		return true;
	}

	public function is_hide_empty_fields() {
		return true;
	}
	
	public function save_translations( $translations ) {
		
	}

}
