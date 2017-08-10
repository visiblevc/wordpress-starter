<?php

class WPML_ST_Page_Translation {
	/**
	 * @var int
	 */
	private $string_id;
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $context;

	/**
	 * @var string
	 */
	private $gettext_context;

	/**
	 * @var string
	 */
	private $value;

	/**
	 * @var bool
	 */
	private $has_translation = false;

	/**
	 * WPML_ST_Page_Translation constructor.
	 *
	 * @param int $string_id
	 * @param string $name
	 * @param string $context
	 * @param string $value
	 * @param bool $has_translation
	 * @param string $gettext_context
	 */
	public function __construct( $string_id, $name, $context, $value, $has_translation = false, $gettext_context = '' ) {
		$this->validate_values( $string_id, $name, $context );

		$this->string_id = (int) $string_id;
		$this->name    = (string) $name;
		$this->context = (string) $context;
		$this->gettext_context = (string) $gettext_context;
		$this->value   = (string) $value;
		$this->has_translation = (bool) $has_translation;
	}

	/**
	 * @return int
	 */
	public function get_string_id() {
		return $this->string_id;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_context() {
		return $this->context;
	}

	/**
	 * @return string
	 */
	public function get_gettext_context() {
		return $this->gettext_context;
	}

	/**
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @return bool
	 */
	public function has_translation() {
		return $this->has_translation;
	}

	/**
	 * @param int $string_id
	 * @param $name
	 * @param $context
	 */
	private function validate_values( $string_id, $name, $context ) {
		if ( empty( $string_id ) ) {
			throw new InvalidArgumentException( 'String id cannot be empty' );
		}

		if ( $name === '' ) {
			throw new InvalidArgumentException( 'Translation name cannot be empty' );
		}
	}
}
