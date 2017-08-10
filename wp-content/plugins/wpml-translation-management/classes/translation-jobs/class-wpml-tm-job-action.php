<?php

abstract class WPML_TM_Job_Action {

	/** @var  WPML_TM_Job_Action_Factory $job_action_factory */
	protected $job_action_factory;


	/**
	 * WPML_TM_Job_Action constructor.
	 *
	 * @param WPML_TM_Job_Action_Factory $job_action_factory
	 */
	public function __construct( &$job_action_factory ) {
		$this->job_action_factory = &$job_action_factory;
	}
}