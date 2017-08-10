<?php
class WPML_ACF_Post_Id {
	public $id;
	public $WPML_ACF_Field;
	
	public function __construct($id, $WPML_ACF_Field) {
		$this->id = $id;
		$this->WPML_ACF_Field = maybe_unserialize($WPML_ACF_Field);
	}
	
	public function convert() {
		$post_type = get_post_type($this->id);
		
		$translated_id = apply_filters('wpml_object_id', $this->id, $post_type, true, $this->WPML_ACF_Field->target_lang);
		
		return new WPML_ACF_Post_Id($translated_id, $this->WPML_ACF_Field);
		
	}
}
