<?php

class WPML_ACF_Duplicated_Post {

	protected $wpdb;

	public function __construct( $wpdb )
	{
		$this->wpdb = $wpdb;
	}

	public function resolve_field($processed_data) {
		$field = new WPML_ACF_Void_Field($processed_data);
		
		if (isset($processed_data->meta_data['context']) && 'custom_field' == $processed_data->meta_data['context']) {

			$related_acf_field_name = maybe_unserialize(
					get_post_meta($processed_data->meta_data['master_post_id'], "_".$processed_data->meta_data['key'], true)
					);

			if (is_string($related_acf_field_name) && strpos($related_acf_field_name, "field_") === 0) {

				if ($key_parts = $this->check_repeater_field($processed_data->meta_data['key'])) {
					$WPML_ACF_Repeater_Field = new WPML_ACF_Repeater_Field($this, $this->wpdb);
					$field = $WPML_ACF_Repeater_Field->resolve_repeater_subfield($processed_data, $key_parts, $field);
				} else {
					$related_acf_field_value = $this->get_related_acf_field_value($related_acf_field_name);

					if (isset($related_acf_field_value['key']) && $related_acf_field_value['key'] == $related_acf_field_name) { // acf free

						if (isset($related_acf_field_value['type'])) {
							$processed_data->related_acf_field_value = $related_acf_field_value;
							$field = $this->get_field_object($processed_data, $field);
						}

					} else { // acf pro
						$related_acf_pro_field_value = $this->get_related_acf_pro_field_value($related_acf_field_name);
						if (isset($related_acf_pro_field_value['type'])) {
							$processed_data->related_acf_field_value = $related_acf_pro_field_value;
							$field = $this->get_field_object($processed_data, $field);
						}
					}
				}


			}
			
		}
		
		return $field;
	}
	
	public function get_related_acf_field_value($field_name) {

		return $value = maybe_unserialize($this->wpdb->get_var( $this->wpdb->prepare("SELECT meta_value FROM {$this->wpdb->postmeta} WHERE meta_key = %s LIMIT 1" , $field_name) ));
	}

	public function get_related_acf_pro_field_value($field_name) {
		return $value = maybe_unserialize($this->wpdb->get_var( $this->wpdb->prepare("SELECT post_content FROM {$this->wpdb->posts} WHERE post_name = %s AND post_type = 'acf-field' LIMIT 1", $field_name) ));

	}

	private function check_repeater_field($key) {
		$re = "/([a-z_]+)_(\\d)_(\\S+)/";

		$matches = false;

		preg_match($re, $key, $matches);

		return $matches;
	}



	public function get_field_object($processed_data, $field) {
		if ('post_object' ==  $processed_data->related_acf_field_value['type']) {
			$ids_object = new WPML_ACF_Post_Ids();
			$field = new WPML_ACF_Post_Object_Field($processed_data, $ids_object);
		} else if ('page_link' == $processed_data->related_acf_field_value['type']) {
			$ids_object = new WPML_ACF_Post_Ids();
			$field = new WPML_ACF_Page_Link_Field($processed_data, $ids_object);
		} else if ('relationship' == $processed_data->related_acf_field_value['type']) {
			$ids_object = new WPML_ACF_Post_Ids();
			$field = new WPML_ACF_Relationship_Field($processed_data, $ids_object);
		} else if ('taxonomy' == $processed_data->related_acf_field_value['type']) {
			$ids_object = new WPML_ACF_Term_Ids();
			$field = new WPML_ACF_Taxonomy_Field($processed_data, $ids_object);
		} else if ('gallery' == $processed_data->related_acf_field_value['type']) {
			$ids_object = new WPML_ACF_Post_Ids();
			$field = new WPML_ACF_Post_Object_Field($processed_data, $ids_object);
		}

		return $field;

	}
}
