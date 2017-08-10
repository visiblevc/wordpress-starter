<?php
class WPML_ACF_Worker {
	private $duplicated_post;

	public function __construct($duplicated_post) {
		$this->register_hooks();
		$this->duplicated_post = $duplicated_post;
	}

	public function register_hooks() {
		add_filter('wpml_duplicate_generic_string', array($this, 'duplicate_post_meta'), 10, 3);
		add_filter('wpml_sync_parent_for_post_type', array($this, 'sync_parent_for_post_type'), 10, 2);
	}
	
	public function duplicate_post_meta($meta_value, $target_lang, $meta_data) {

		$processed_data = new WPML_ACF_Processed_Data($meta_value, $target_lang, $meta_data);

		$field = $this->duplicated_post->resolve_field($processed_data);
		
		$meta_value_converted = $field->convert_ids();
		
		return $meta_value_converted;
	}

	public function sync_parent_for_post_type($sync, $post_type) {
		if ("acf-field" == $post_type || "acf-field-group" == $post_type) {
			$sync = false;
		}

		return $sync;
	}
	
}
