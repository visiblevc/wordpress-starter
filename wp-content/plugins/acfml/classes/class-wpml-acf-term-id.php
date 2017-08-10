<?php
class WPML_ACF_Term_Id {
	public $id;
	public $WPML_ACF_Field;

	public function __construct($id, $WPML_ACF_Field) {
		$this->id = $id;
		$this->WPML_ACF_Field = maybe_unserialize($WPML_ACF_Field);
	}

	public function convert() {

		$taxonomy = $this->WPML_ACF_Field->related_acf_field_value['taxonomy'];

		$translated_id = apply_filters('wpml_object_id', $this->id, $taxonomy, true, $this->WPML_ACF_Field->target_lang);

		return new WPML_ACF_Term_Id($translated_id, $this->WPML_ACF_Field);

	}
}
