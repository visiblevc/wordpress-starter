<?php

class WPML_TM_Field_Type_Sanitizer {


	/**
	 * Get elements custom field `field_type`.
	 * Removes last character if it's number.
	 * ex. field-custom_field-0 => field-custom_field
	 *
	 * @param $element
	 *
	 * @return string
	 */
	public static function sanitize( $custom_field_type ) {
		$element_field_type_parts = explode( '-', $custom_field_type );
		$last_part                = array_pop( $element_field_type_parts );

		if ( empty( $element_field_type_parts ) ) {
			return $custom_field_type;
		}

		// Re-create field.
		$field_type = implode( '-', $element_field_type_parts );
		if ( is_numeric( $last_part ) ) {
			return $field_type;
		} else {
			return $custom_field_type;
		}
	}
}

