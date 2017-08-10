<?php

class WPML_TM_Service_Activation_AJAX extends WPML_TM_AJAX_Factory_Obsolete {
	private $script_handle = 'wpml_tm_service_activation';

	private $ignore_local_jobs;

	/**
	 * @var WPML_Translation_Job_Factory
	 */
	private $job_factory;

	/**
	 * @param WPML_WP_API                  $wpml_wp_api
	 * @param WPML_Translation_Job_Factory $job_factory
	 */
	public function __construct( &$wpml_wp_api, &$job_factory ) {
		parent::__construct($wpml_wp_api);
		$this->wpml_wp_api = &$wpml_wp_api;
		$this->job_factory = &$job_factory;
		$this->add_ajax_action( 'wp_ajax_wpml_cancel_open_local_translators_jobs',
		                        array( $this, 'cancel_open_local_translators_jobs' ) );
		$this->add_ajax_action( 'wp_ajax_wpml_keep_open_local_translators_jobs',
		                        array( $this, 'keep_open_local_translators_jobs' ) );
		$this->init();

		$this->ignore_local_jobs = get_transient( $this->script_handle . '_ignore_local_jobs' );
		if ( $this->ignore_local_jobs == 1 ) {
			$this->ignore_local_jobs = true;
		} else {
			$this->ignore_local_jobs = false;
		}
	}

	public function get_ignore_local_jobs() {
		return $this->ignore_local_jobs;
	}

	public function set_ignore_local_jobs( $value ) {
		if ( $value == true ) {
			set_transient( $this->script_handle . '_ignore_local_jobs', 1 );
			$this->ignore_local_jobs = true;
		} else {
			delete_transient( $this->script_handle . '_ignore_local_jobs' );
			$this->ignore_local_jobs = false;
		}
	}

	public function cancel_open_local_translators_jobs() {
		$translation_filter = array( 'service' => 'local', 'translator' => 0, 'status__not' => ICL_TM_COMPLETE );
		$translation_jobs   = $this->job_factory->get_translation_jobs( $translation_filter, true, true );
		$jobs_count = count( $translation_jobs );
		$deleted = 0;
		if ( $jobs_count ) {
			foreach ( $translation_jobs as $job ) {
				/** @var WPML_Translation_Job $job */
				if ( $job && $job->cancel() ) {
					$deleted += 1;
				}
			}
		}
		$this->set_ignore_local_jobs( false );

		return $this->wpml_wp_api->wp_send_json_success( array(
			                                                 'opens'     => $jobs_count - $deleted,
			                                                 'cancelled' => $deleted
		                                                 ) );
	}

	public function keep_open_local_translators_jobs() {
		$this->set_ignore_local_jobs( true );
		return $this->wpml_wp_api->wp_send_json_success( 'Ok!' );
	}

	public function register_resources() {
		wp_register_script( $this->script_handle, WPML_TM_URL . '/res/js/service-activation.js', array( 'jquery', 'jquery-ui-dialog', 'underscore' ), false, true );
	}

	public function enqueue_resources( $hook_suffix ) {
		$this->register_resources();
		$strings = array(
			'alertTitle'          => _x( 'Incomplete local translation jobs', 'Incomplete local jobs after TS activation: [response] 00 Title', 'wpml-translation-management' ),
			'cancelledJobs'       => _x( 'Cancelled local translation jobs:', 'Incomplete local jobs after TS activation: [response] 01 Cancelled', 'wpml-translation-management' ),
			'openJobs'            => _x( 'Open local translation jobs:', 'Incomplete local jobs after TS activation: [response] 02 Open', 'wpml-translation-management' ),
			'errorCancellingJobs' => _x( 'Unable to cancel some or all jobs', 'Incomplete local jobs after TS activation: [response] 03 Error', 'wpml-translation-management' ),
			'errorGeneric'        => _x( 'Unable to complete the action', 'Incomplete local jobs after TS activation: [response] 04 Error', 'wpml-translation-management' ),
			'keepLocalJobs'       => _x( 'Local translation jobs will be kept and the above notice hidden.', 'Incomplete local jobs after TS activation: [response] 10 Close button', 'wpml-translation-management' ),
			'closeButton'         => _x( 'Close', 'Incomplete local jobs after TS activation: [response] 20 Close button', 'wpml-translation-management' ),
			'confirm'             => _x( 'Are you sure you want to do this?', 'Incomplete local jobs after TS activation: [confirmation] 01 Message', 'wpml-translation-management' ),
			'yes'                 => _x( 'Yes', 'Incomplete local jobs after TS activation: [confirmation] 01 Yes', 'wpml-translation-management' ),
			'no'                  => _x( 'No', 'Incomplete local jobs after TS activation: [confirmation] 01 No', 'wpml-translation-management' ),
		);
		wp_localize_script( $this->script_handle, $this->script_handle . '_strings', $strings );
		wp_enqueue_script( $this->script_handle );
	}
}