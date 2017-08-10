<?php

class WPML_TP_Service_Authentication_Ajax_Action extends WPML_TP_Service_Ajax_Action {

	/** @var  string $custom_field_json */
	private $custom_field_json;

	/**
	 * WPML_TP_Service_Authentication_Ajax_Action constructor.
	 *
	 * @param WPML_TP_Service_Authentication_Factory $tp_auth_factory
	 * @param string                                 $custom_fields_json
	 */
	public function __construct(
		&$tp_auth_factory,
		$custom_fields_json
	) {
		parent::__construct( $tp_auth_factory );
		$this->custom_field_json = $custom_fields_json;
	}

	protected function action() {
		$this->tp_auth_factory->tp_authentication(
			json_decode( stripslashes( $this->custom_field_json ) )
		)->run();

		return __( 'Service activated.', 'wpml-translation-management' );
	}

	protected function error_message() {

		return __( 'Unable to activate this service. Please check entered data and try again.',
			'wpml-translation-management' );
	}
}