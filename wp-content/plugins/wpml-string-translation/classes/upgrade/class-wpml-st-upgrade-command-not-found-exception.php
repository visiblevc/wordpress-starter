<?php


class WPML_ST_Upgrade_Command_Not_Found_Exception extends InvalidArgumentException {
	/**
	 * @param string $class_name
	 * @param int $code
	 * @param Exception $previous
	 */
	public function __construct( $class_name, $code = 0, Exception $previous = null ) {
		$msg = sprintf( 'Class %s is not valid String Translation upgrade strategy', $class_name );
		parent::__construct( $msg, $code, $previous );
	}
}