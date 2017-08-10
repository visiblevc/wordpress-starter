<?php

class WPML_TP_Service_Invalidation_Ajax_Action extends WPML_TP_Service_Ajax_Action {

	protected function action() {
		$this->tp_auth_factory->tp_service_invalidation()->run();

		return __( 'Service invalidated.',
			'wpml-translation-management' );
	}

	protected function error_message() {
		return __( 'Unable to invalidate this service. Please contact WPML support.',
			'wpml-translation-management' );
	}
}