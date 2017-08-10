<?php

class WPML_TP_Project_Factory {

	/**
	 * @param Object $service
	 * @param string $delivery
	 *
	 * @return TranslationProxy_Project
	 */
	public function project( $service, $delivery = 'xmlrpc' ) {

		return new TranslationProxy_Project( $service, $delivery );
	}
}