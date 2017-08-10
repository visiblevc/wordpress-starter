<?php

class WPML_TP_API {
	private $params = array();
	/**
	 * @var WPML_TP_Communication
	 */
	private $wpml_tp_communication;

	/**
	 * WPML_TP_API constructor.
	 *
	 * @param WPML_TP_Communication $wpml_tp_communication
	 * @param string                $api_version
	 * @param WPML_TM_Log           $logger
	 */
	public function __construct( WPML_TP_Communication $wpml_tp_communication, $api_version = '1.1', WPML_TM_Log $logger = null ) {
		$this->wpml_tp_communication = $wpml_tp_communication;
		$this->params['api_version'] = $api_version;
		$this->logger                = $logger;
	}

	/**
	 * @param TranslationProxy_Project $project
	 *
	 * @return null|WP_Error
	 */
	public function refresh_language_pairs( TranslationProxy_Project $project ) {

		$this->log( 'Refresh language pairs -> Request sent' );

		$this->add_param( 'project', array( 'refresh_language_pairs' => 1 ) );
		$this->add_param( 'refresh_language_pairs', 1 );
		$this->add_param( 'project_id', $project->id );
		$this->add_param( 'accesskey', $project->access_key );

		$this->wpml_tp_communication->set_method( 'PUT' );
		$this->wpml_tp_communication->set_request_format( 'json' );
		$this->wpml_tp_communication->set_response_format( 'json' );
		$this->wpml_tp_communication->request_must_respond( false );

		return $this->wpml_tp_communication->projects( $this->params );
	}

	private function add_param( $name, $value ) {
		$this->params[ $name ] = $value;
	}

	private function log( $action, $params = array() ) {
		if ( null !== $this->logger ) {
			$this->logger->log( $action, $params );
		}
	}
}
