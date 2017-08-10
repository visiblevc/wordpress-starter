<?php

class WPML_TM_Settings_Post_Process extends WPML_TM_User {

	/**
	 * Saves TM settings to the database in case they have changed after reading a config file.
	 */
	public function run() {
		$changed  = false;
		$settings = &$this->tm_instance->settings;
		foreach (
			array(
				WPML_POST_META_READONLY_SETTING_INDEX,
				WPML_TERM_META_READONLY_SETTING_INDEX,
				WPML_POST_TYPE_READONLY_SETTING_INDEX
			) as $index
		) {
			$prev_index = $this->prev_index( $index );
			if ( isset( $settings[ $index ] ) && isset( $settings[ $prev_index ] ) ) {
				foreach (
					array(
						$index      => $prev_index,
						$prev_index => $index
					) as $left_index => $right_index
				) {
					foreach ( $settings[ $right_index ] as $cf ) {
						if ( ! in_array( $cf,
							$settings[ $left_index ] )
						) {
							$changed = true;
							break;
						}
					}
				}
			}
		}
		if ( $changed ) {
			$this->tm_instance->save_settings();
		}
	}

	private function prev_index( $index ) {

		return '__' . $index . '_prev';
	}
}