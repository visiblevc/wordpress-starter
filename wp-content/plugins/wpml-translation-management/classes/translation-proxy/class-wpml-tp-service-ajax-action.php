<?php

abstract class WPML_TP_Service_Ajax_Action {

	/** @var  WPML_TP_Service_Authentication_Factory */
	protected $tp_auth_factory;

	/**
	 * WPML_TP_Service_Ajax_Action constructor.
	 *
	 * @param WPML_TP_Service_Authentication_Factory $tp_auth_factory
	 */
	public function __construct( &$tp_auth_factory ) {
		$this->tp_auth_factory = &$tp_auth_factory;
	}

	/**
	 * Executes either a service authentication or invalidation depending on the request
	 *
	 * @return array
	 */
	public function run() {
		$errors = 0;
		try {
			$message = $this->action();
		} catch ( Exception $e ) {
			$message = $this->error_message();
			$errors  = 1;
		}

		return array(
			'errors'  => $errors,
			'message' => $message,
			'reload'  => $errors ? 0 : 1
		);
	}

	/**
	 * @return string the success message for the action
	 */
	protected abstract function action();

	/**
	 * @return string the error message for the action
	 */
	protected abstract function error_message();
}