<?php

class WPML_Ajax_Response {

	private $success;
	private $response_data;
	
	public function __construct( $success, $response_data ) {
		$this->success       = $success;
		$this->response_data = $response_data;
	}
	
	public function send_json() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( $this->success ) {
				wp_send_json_success( $this->response_data );
			} else {
				wp_send_json_error( $this->response_data );
			}
		} else {
			throw new WPML_Not_Doing_Ajax_On_Send_Exception( $this );
		}
	}
	
	public function is_success() {
		return $this->success;
	}

	public function get_response() {
		return $this->response_data;
	}
}

class WPML_Not_Doing_Ajax_On_Send_Exception extends Exception {

	public $response;

	public function __construct( $response ) {
		parent::__construct( 'Not doing AJAX' );
		$this->response = $response;
	}
};
