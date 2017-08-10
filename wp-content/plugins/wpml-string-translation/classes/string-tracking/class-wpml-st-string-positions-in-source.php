<?php

/**
 * Class WPML_ST_String_Positions_In_Source
 */
class WPML_ST_String_Positions_In_Source extends WPML_ST_String_Positions {

	const KIND = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE;
	const TEMPLATE = 'positions-in-source.twig';

	/**
	 * @param int $string_id
	 */
	public function dialog_render( $string_id ) {
		$positions       = $this->get_positions( $string_id );
		$st_settings     = $this->sitepress->get_setting( 'st' );
		$highlight_color = '#FFFF00';

		if ( array_key_exists( 'hl_color', $st_settings ) ) {
			$highlight_color = $st_settings['hl_color'];
		}

		$model = array(
			'positions' => $positions,
			'no_results_label' => __( 'No records found', 'wpml-string-translation' ),
			'highlight_color' => $highlight_color,
		);

		$this->render( $model, self::TEMPLATE );
	}

	/**
	 * @param $string_id
	 *
	 * @return array
	 */
	private function get_positions( $string_id ) {
		$positions = array();
		$paths     = $this->get_mapper()->get_positions_by_string_and_kind( $string_id, self::KIND );

		foreach ( $paths as $path ) {
			$position = explode( '::', $path );

			$path = isset( $position[0] ) ? $position[0] : null;

			if( ! $this->get_filesystem()->exists( $path ) ) {
				$path = $this->maybe_transform_from_relative_path_to_absolute_path( $path );
			}

			if ( $path && $this->get_filesystem()->is_readable( $path ) ) {
				$positions[] = array(
					'path' => $path,
					'line' => isset( $position[1] ) ? $position[1] : null,
					'content' => $this->get_filesystem()->get_contents_array( $path ),
				);
			}
		}

		return $positions;
	}

	/**
	 * @param string $path
	 *
	 * @return string|false
	 */
	private function maybe_transform_from_relative_path_to_absolute_path( $path ) {
		$path = $this->get_filename_converter()->transform_reference_to_realpath( $path );

		if ( $this->get_filesystem()->exists( $path ) ) {
			return $path;
		}

		return false;
	}
}