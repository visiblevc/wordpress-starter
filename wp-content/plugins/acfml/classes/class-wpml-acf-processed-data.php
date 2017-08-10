<?php

class WPML_ACF_Processed_Data {
	public $meta_value;
	public $target_lang;
	public $meta_data;
	public $related_acf_field_value;

	public function __construct($meta_value, $target_lang, $meta_data, $related_acf_field_value = null)
	{
		$this->meta_value = $meta_value;
		$this->target_lang = $target_lang;
		$this->meta_data = $meta_data;
		$this->related_acf_field_value = $related_acf_field_value;
	}
}