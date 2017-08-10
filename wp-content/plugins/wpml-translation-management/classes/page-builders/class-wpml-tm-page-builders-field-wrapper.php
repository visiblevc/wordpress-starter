<?php

class WPML_TM_Page_Builders_Field_Wrapper {
	const SLUG_BASE = 'package-string-';

	/**
	 * @var string
	 */
	private $field_slug;

	/**
	 * @var int
	 */
	private $package_id;

	/**
	 * @var int
	 */
	private $string_id;

	/**
	 * @param string $field_slug
	 */
	public function __construct( $field_slug ) {
		$this->field_slug = $field_slug;
	}

	/**
	 * @param bool $package_must_exist
	 *
	 * @return bool
	 */
	public function is_valid( $package_must_exist = false ) {
		$result = $this->get_package_id() && $this->get_string_id();
		if ( $result && $package_must_exist ) {
			$result = $this->get_package() !== null;
		}

		return $result;
	}

	/**
	 * @return false|int
	 */
	public function get_package_id() {
		if ( null === $this->package_id ) {
			$this->package_id = $this->extract_string_package_id( $this->field_slug );
		}

		return $this->package_id;
	}

	/**
	 * @return WPML_Package|null
	 */
	public function get_package() {
		if ( ! $this->get_package_id() ) {
			return null;
		}

		return apply_filters( 'wpml_st_get_string_package', false, $this->get_package_id() );
	}

	/**
	 * @return false|int
	 */
	public function get_string_id() {
		if ( null === $this->string_id ) {
			$this->string_id = $this->extract_string_id( $this->field_slug );
		}

		return $this->string_id;
	}

	/**
	 * @return string
	 */
	public function get_field_slug() {
		return $this->field_slug;
	}

	/**
	 * @return false|string
	 */
	public function get_string_type() {
		$result = false;
		if ( $this->is_valid( true ) ) {
			$package_strings = $this->get_package()->get_package_strings();
			$package_strings = wp_list_pluck( $package_strings, 'type', 'id' );
			$result          = $package_strings[ $this->get_string_id() ];
		}

		return $result;
	}

	/**
	 * @param int $package_id
	 * @param int $string_id
	 *
	 * @return string
	 */
	public static function generate_field_slug( $package_id, $string_id ) {
		return self::SLUG_BASE . $package_id . '-' . $string_id;
	}

	/**
	 * @param string $field_slug
	 *
	 * @return int|false
	 */
	private function extract_string_id( $field_slug ) {
		$result = false;

		if ( is_string( $field_slug ) && preg_match( '#^' . self::SLUG_BASE . '#', $field_slug ) ) {
			$result = preg_replace( '#^' . self::SLUG_BASE . '([0-9]+)-([0-9]+)$#', '$2', $field_slug, 1 );
		}

		return is_numeric( $result ) ? $result : false;
	}

	/**
	 * @param string $field_slug
	 *
	 * @return int|false
	 */
	private function extract_string_package_id( $field_slug ) {
		$result = false;

		if ( is_string( $field_slug ) && preg_match( '#^' . self::SLUG_BASE . '#', $field_slug ) ) {
			$result = preg_replace( '#^' . self::SLUG_BASE . '([0-9]+)-([0-9]+)$#', '$1', $field_slug, 1 );
		}

		return is_numeric( $result ) ? $result : false;
	}

	/**
	 * Get string title.
	 * @return string|boolean
	 */
	public function get_string_title() {
		if ( null === $this->string_id ) {
			$this->string_id = $this->extract_string_id( $this->field_slug );
		}

		return apply_filters( 'wpml_string_title_from_id', false, $this->string_id );
	}
}
