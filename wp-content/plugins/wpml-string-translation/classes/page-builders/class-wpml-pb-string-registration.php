<?php

/**
 * Class WPML_PB_String_Registration
 */
class WPML_PB_String_Registration {

	private $strategy;

	public function __construct( IWPML_PB_Strategy $strategy ) {
		$this->strategy = $strategy;
	}

	/**
	 * @param int $post_id
	 * @param string $content
	 *
	 * @return null|int
	 */
	public function get_string_id_from_package( $post_id, $content ) {
		$package_data = $this->strategy->get_package_key( $post_id );
		$package      = new WPML_Package( $package_data );
		$string_name  = md5( $content );
		$string_name  = $package->sanitize_string_name( $string_name );
		$string_value = $content;

		return apply_filters( 'wpml_string_id_from_package', null, $package, $string_name, $string_value );
	}

	public function get_string_title( $string_id ) {
		return apply_filters('wpml_string_title_from_id', null, $string_id);
	}

	/**
	 * @param int    $post_id
	 * @param string $content
	 * @param string $type
	 * @param string $title
	 */
	public function register_string( $post_id, $content = '', $type = 'LINE', $title = '' ) {
		if ( trim( $content ) ) {
			$string_value = $content;
			$string_name  = md5( $content );
			$package      = $this->strategy->get_package_key( $post_id );
			$string_title = $title ? $title : $string_value;
			do_action( 'wpml_register_string', $string_value, $string_name, $package, $string_title, $type );
		}
	}
}
