<?php

class WPML_PB_String {

	/** @var  string $value */
	private $value;

	/** @var  string $name */
	private $name;
	/** @var  string $title */
	private $title;
	/** @var  string $editor_type */
	private $editor_type;

	public function __construct( $value, $name, $title, $editor_type ) {
		$this->value       = $value;
		$this->name        = $name;
		$this->title       = $title;
		$this->editor_type = $editor_type;
	}

	/**
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @param string $value
	 */
	public function set_value( $value ) {
		$this->value = $value;
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
	public function get_title() {
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function get_editor_type() {
		return $this->editor_type;
	}

}