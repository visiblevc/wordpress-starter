<?php

class WPML_ACF_Repeater_Field {
	private $duplicated_post_object;

	protected $wpdb;

	public function __construct(&$duplicated_post_object, $wpdb)
	{
		$this->duplicated_post_object = $duplicated_post_object;
		$this->wpdb = $wpdb;
	}

	public function resolve_repeater_subfield($processed_data, $key_parts, $field) {
		$repeater_field = get_post_meta($processed_data->meta_data['master_post_id'], "_" . $key_parts[1], true);

		if ($repeater_field) {
			$value = $this->duplicated_post_object->get_related_acf_field_value($repeater_field);

			if (isset($value['type']) && 'repeater' == $value['type'] && isset($value['sub_fields'])) { // acf free
				foreach ($value['sub_fields'] as $key => $sub_field) {
					if (isset($sub_field['name']) && strpos($key_parts[3], $sub_field['name']) === 0 && isset($sub_field['type'])) {
						$processed_data->related_acf_field_value['type'] = $sub_field['type'];
						if ('repeater' == $sub_field['type']) {
							$WPML_ACF_Repeater_Field = new WPML_ACF_Repeater_Field($this->duplicated_post_object, $this->wpdb);
							$field = $WPML_ACF_Repeater_Field->resolve_repeater_subfield($processed_data, $key_parts, $field);
						} else {
							$field = $this->duplicated_post_object->get_field_object($processed_data, $field);
						}
						break;
					}
				}
			} else { // acf pro
				$value = $this->duplicated_post_object->get_related_acf_pro_field_value($repeater_field);
				if (isset($value['type']) && 'repeater' == $value['type']) {
					$sub_field_name = get_post_meta($processed_data->meta_data['master_post_id'], "_" . $processed_data->meta_data['key'], true);
					$sub_field = $this->get_sub_acf_pro_repeater_field($sub_field_name);
					if (isset($sub_field['type'])) {
						$processed_data->related_acf_field_value['type'] = $sub_field['type'];
						if ('repeater' == $sub_field['type']) {
							$WPML_ACF_Repeater_Field = new WPML_ACF_Repeater_Field($this->duplicated_post_object, $this->wpdb);
							$field = $WPML_ACF_Repeater_Field->resolve_repeater_subfield($processed_data, $key_parts, $field);
						} else {
							$field = $this->duplicated_post_object->get_field_object($processed_data, $field);
						}
					}


				}
			}
		}

		return $field;
	}

	private function get_sub_acf_pro_repeater_field($sub_field_name) {
		return $value = maybe_unserialize(
			$this->wpdb->get_var(
				$this->wpdb->prepare("SELECT post_content FROM {$this->wpdb->posts} WHERE post_name = %s AND post_type = 'acf-field' LIMIT 1", $sub_field_name) ));

	}
}