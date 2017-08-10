<?php

class WPML_Translation_Job_Helper {

	public function encode_field_data( $data, $format ) {

		return base64_encode( $data );
	}

	public function decode_field_data( $data, $format ) {
		return $this->get_core_translation_management()->decode_field_data( $data, $format );
	}

	protected function get_tm_setting( $indexes ) {
		if ( empty( $this->get_core_translation_management()->settings ) ) {
			$this->get_core_translation_management()->init();
		}

		$settings = $this->get_core_translation_management()->get_settings();

		foreach ( $indexes as $index ) {
			$settings = isset( $settings[ $index ] ) ? $settings[ $index ] : null;
			if ( ! isset( $settings ) ) {
				break;
			}
		}

		return $settings;
	}

	/**
	 * @return TranslationManagement
	 */
	private function get_core_translation_management() {
		/** TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement;

		return $iclTranslationManagement;
	}
}