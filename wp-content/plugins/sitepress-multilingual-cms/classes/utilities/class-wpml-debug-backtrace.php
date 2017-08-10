<?php

class WPML_Debug_BackTrace {

	private $debug_backtrace = array();

	private $php_version;
	private $limit;
	private $provide_object;
	private $ignore_args;
	private $debug_backtrace_function;

	/**
	 * @param string $php_version
	 * @param int $limit
	 * @param bool $provide_object
	 * @param bool $ignore_args
	 * @param string $debug_backtrace_function
	 */
	public function __construct( $php_version, $limit = 0, $provide_object = false, $ignore_args = true, $debug_backtrace_function = null ) {
		if ( ! $debug_backtrace_function ) {
			$debug_backtrace_function = 'debug_backtrace';
		}
		$this->php_version = $php_version;
		$this->limit = $limit;
		$this->provide_object = $provide_object;
		$this->ignore_args = $ignore_args;
		$this->debug_backtrace_function = $debug_backtrace_function;
	}

	public function is_function_in_call_stack( $function_name, $refresh = true ) {
		if ( empty( $this->debug_backtrace ) || $refresh ) {
			$this->get_backtrace();
		}
		$found = false;
		foreach ( $this->debug_backtrace as $debug_backtrace ) {
			if ( $debug_backtrace['function'] == $function_name && ! isset( $debug_backtrace['class'] ) ) {
				$found = true;
				break;
			}
		}
		return $found;
	}

	public function is_class_function_in_call_stack( $class_name, $function_name, $refresh = true ) {
		if ( empty( $this->debug_backtrace ) || $refresh ) {
			$this->get_backtrace();
		}
		$found = false;
		foreach ( $this->debug_backtrace as $debug_backtrace ) {
			if ( isset( $debug_backtrace['class'] ) && $debug_backtrace['class'] == $class_name
			     && $debug_backtrace['function'] == $function_name
			) {
				$found = true;
				break;
			}
		}
		return $found;

	}

	public function get_backtrace() {
		$options = false;

		if ( version_compare( $this->php_version, '5.3.6' ) < 0 ) {
			// Before 5.3.6, the only values recognized are TRUE or FALSE,
			// which are the same as setting or not setting the DEBUG_BACKTRACE_PROVIDE_OBJECT option respectively.
			$options = $this->provide_object;
		} else {
			// As of 5.3.6, 'options' parameter is a bitmask for the following options:
			if ( $this->provide_object ) {
				$options |= DEBUG_BACKTRACE_PROVIDE_OBJECT;
			}
			if ( $this->ignore_args ) {
				$options |= DEBUG_BACKTRACE_IGNORE_ARGS;
			}
		}
		if ( version_compare( $this->php_version, '5.4.0' ) >= 0 ) {
			$actual_limit    = $this->limit == 0 ? 0 : $this->limit + 3;
			$this->debug_backtrace = (array) call_user_func_array( $this->debug_backtrace_function, array( $options, $actual_limit ) ); //add one item to include the current frame
		} elseif ( version_compare( $this->php_version, '5.2.4' ) >= 0 ) {
			//@link https://core.trac.wordpress.org/ticket/20953
			$this->debug_backtrace = (array) call_user_func_array( $this->debug_backtrace_function, array() );
		} else {
			$this->debug_backtrace = (array) call_user_func_array( $this->debug_backtrace_function, array( $options ) );
		}

		$this->remove_frames_for_this_class();

	}

	private function remove_frames_for_this_class() {
		for ( $i = 0; $i < 3; $i ++ ) {
			$this->remove_last_frame();
		}
	}

	public function remove_last_frame() {
		if ( $this->debug_backtrace ) {
			array_shift( $this->debug_backtrace );
		}
	}

}