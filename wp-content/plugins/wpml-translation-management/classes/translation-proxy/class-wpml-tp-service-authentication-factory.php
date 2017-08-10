<?php

class WPML_TP_Service_Authentication_Factory extends WPML_SP_User {

	/**
	 * @var WPML_Translation_Proxy_Networking $tp_networking
	 */
	private $tp_networking;

	/**
	 * @var WPML_TP_Project_Factory $project_factory
	 */
	private $project_factory;

	/**
	 * WPML_TP_Service_Authentication_Factory constructor.
	 *
	 * @param SitePress                         $sitepress
	 * @param WPML_Translation_Proxy_Networking $tp_networking
	 * @param WPML_TP_Project_Factory           $project_factory
	 */
	public function __construct(
		&$sitepress,
		&$tp_networking,
		&$project_factory
	) {
		parent::__construct( $sitepress );
		$this->project_factory = &$project_factory;
		$this->tp_networking   = &$tp_networking;
	}

	/**
	 * Instantiates a \WPML_TP_Service_Authentication instance
	 *
	 * @param stdClass $custom_field_data
	 *
	 * @return WPML_TP_Service_Authentication
	 */
	public function tp_authentication( $custom_field_data ) {

		return new WPML_TP_Service_Authentication( $this->sitepress,
			$this->tp_networking, $this->project_factory, $custom_field_data );
	}

	public function tp_service_invalidation() {

		return new WPML_TP_Service_Invalidation( $this->sitepress );
	}
}