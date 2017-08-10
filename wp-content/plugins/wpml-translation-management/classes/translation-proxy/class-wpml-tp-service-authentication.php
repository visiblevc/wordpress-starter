<?php

class WPML_TP_Service_Authentication extends WPML_TP_Service_Action {

	/**
	 * @var WPML_Translation_Proxy_Networking $tp_networking
	 */
	private $tp_networking;

	/**
	 * @var WPML_TP_Project_Factory $project_factory
	 */
	private $project_factory;

	/** @var  stdClass $custom_field_data */
	private $custom_field_data;

	/**
	 * WPML_TP_Service_Authentication constructor.
	 *
	 * @param SitePress                         $sitepress
	 * @param WPML_Translation_Proxy_Networking $tp_networking
	 * @param stdClass                          $custom_field_data
	 * @param WPML_TP_Project_Factory           $project_factory
	 */
	public function __construct(
		&$sitepress,
		&$tp_networking,
		&$project_factory,
		$custom_field_data
	) {
		if ( ! is_object( $custom_field_data ) ) {
			throw new InvalidArgumentException( 'Custom field data needs to be provided as an object, received: ' . serialize( $custom_field_data ) );
		}
		parent::__construct( $sitepress );
		$this->custom_field_data = $custom_field_data;
		$this->tp_networking     = &$tp_networking;
		$this->project_factory   = &$project_factory;
	}

	/**
	 * Runs the authentication request against the Translation Proxy API
	 */
	public function run() {
		$service = $this->get_current_service();
		if ( (bool) $service === false ) {
			throw new RuntimeException( 'Tried to authenticate a service, but no service is active!' );
		}
		$service->custom_fields_data = $this->custom_field_data;
		$this->set_current_service( $service );
		$project              = $this->create_project( $service );
		$translation_projects = $this->sitepress->get_setting( 'icl_translation_projects' );
		$extra_fields         = $this->tp_networking->get_extra_fields_remote( $project );
		// Store project, to be used if service is restored

		$translation_projects[ TranslationProxy_Project::generate_service_index( $service ) ] = array(
			'id'            => $project->id,
			'access_key'    => $project->access_key,
			'ts_id'         => $project->ts_id,
			'ts_access_key' => $project->ts_access_key,
			'extra_fields'  => $extra_fields
		);
		$this->sitepress->set_setting( 'icl_translation_projects',
			$translation_projects, true );
	}

	/**
	 * @param object $service
	 *
	 * @return TranslationProxy_Project
	 */
	private function create_project( $service ) {
		$icl_translation_projects = $this->sitepress->get_setting( 'icl_translation_projects',
			array() );
		$delivery                 = (int) $this->sitepress->get_setting( 'translation_pickup_method' ) === ICL_PRO_TRANSLATION_PICKUP_XMLRPC
			? "xmlrpc" : "polling";
		if ( isset( $icl_translation_projects[ TranslationProxy_Project::generate_service_index( $service ) ] ) ) {
			$project = $this->project_factory->project( $service, $delivery );
		} else {
			$wp_api      = $this->sitepress->get_wp_api();
			$url         = $wp_api->get_option( 'siteurl' );
			$name        = $wp_api->get_option( 'blogname' );
			$description = $wp_api->get_option( 'blogdescription' );
			$project     = $this->project_factory->project( $service );
			$project->create( $url, $name, $description, $delivery );
		}

		return $project;
	}
}