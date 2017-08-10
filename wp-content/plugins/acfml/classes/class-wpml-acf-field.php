<?php

abstract class WPML_ACF_Field {
	public $meta_value;
	public $target_lang;
	public $meta_data;
	public $ids_object;
	public $related_acf_field_value;


	public function __construct($processed_data, $ids = null) {
		$this->meta_value = $processed_data->meta_value;
		$this->target_lang = $processed_data->target_lang;
		$this->meta_data = $processed_data->meta_data;
		$this->related_acf_field_value = $processed_data->related_acf_field_value;

		$this->ids_object = $ids;

	}
	
	public function convert_ids() {
		return $this->ids_object->convert($this);
	}
}
