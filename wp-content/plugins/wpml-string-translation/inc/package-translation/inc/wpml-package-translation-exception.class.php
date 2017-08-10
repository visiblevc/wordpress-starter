<?php
class WPML_Package_Exception extends Exception {
	public $type;

	public function __construct($type = '', $message = "", $code = 0) {
		parent::__construct($message, $code);
		$this->type = $type;
	}
}