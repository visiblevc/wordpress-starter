<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Upgrade_Command_Definition {
	private $class_name;
	private $dependencies = array();
	/** @var array Can be 'admin', 'ajax' or 'front-end' */
	private $scopes = array();
	private $method;

	/**
	 * WPML_Upgrade_Command_Definition constructor.
	 *
	 * @param string $class_name
	 * @param array  $dependencies
	 * @param array  $scopes
	 * @param string $method
	 */
	public function __construct( $class_name, array $dependencies, array $scopes, $method = null ) {
		$this->class_name   = $class_name;
		$this->dependencies = $dependencies;
		$this->scopes       = $scopes;
		$this->method       = $method;
	}

	/**
	 * @return array
	 */
	public function get_dependencies() {
		return $this->dependencies;
	}

	/**
	 * @return string
	 */
	public function get_class_name() {
		return $this->class_name;
	}

	/**
	 * @return string
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * @return array
	 */
	public function get_scopes() {
		return $this->scopes;
	}
}