<?php

class WPML_PB_Integration_Rescan {
	/**
	 * @var WPML_PB_Integration
	 */
	private $integrator;

	/**
	 * @param WPML_PB_Integration $integrator
	 */
	public function __construct( WPML_PB_Integration $integrator ) {
		$this->integrator = $integrator;
	}

	/**
	 * Rescan post content if it does not contain packages
	 *
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-958
	 * @param array $translation_package
	 * @param $post
	 *
	 * @return array
	 */
	public function rescan( array $translation_package, $post ) {
		$string_packages = apply_filters( 'wpml_st_get_post_string_packages', false, $post->ID );
		if ( ! $string_packages ) {
			$this->integrator->register_all_strings_for_translation( $post );
		}

		return $translation_package;
	}
}