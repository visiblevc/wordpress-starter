<?php

class WPML_TP_Service_Invalidation extends WPML_TP_Service_Action {
	/**
	 * Invalidates the authentication data for the currently active service
	 */
	public function run() {
		$service = $this->get_current_service();
		if ( (bool) $service === false ) {
			throw new RuntimeException( 'Tried to invalidate a service, but no service is active!' );
		}
		$service->custom_fields_data = false;
		$this->set_current_service( $service );
	}
}