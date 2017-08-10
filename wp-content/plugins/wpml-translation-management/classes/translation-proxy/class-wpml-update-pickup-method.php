<?php

class WPML_Update_PickUp_Method {

	private $sitepress;

	public function __construct( $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function update_pickup_method( $data, $project = false ) {
		$method                                   = isset( $data['icl_translation_pickup_method'] ) ? intval( $data['icl_translation_pickup_method'] ) : null;
		$iclsettings['translation_pickup_method'] = $method;
		$response                                 = 'ok';
		try {
			if ( $project ) {
				$project->set_delivery_method( ICL_PRO_TRANSLATION_PICKUP_XMLRPC == $method ? 'xmlrpc' : 'polling' );
				$this->sitepress->save_settings( $iclsettings );
			} elseif ( ICL_PRO_TRANSLATION_PICKUP_XMLRPC == $method ) {
				$response = 'no-ts';
			}
		} catch ( RuntimeException $e) {
			$response = 'cant-update';
		}

		return $response;
	}
}