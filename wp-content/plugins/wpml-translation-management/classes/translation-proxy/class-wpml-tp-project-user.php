<?php

abstract class WPML_TP_Project_User {

	/** @var TranslationProxy_Project $project */
	protected $project;

	/**
	 * WPML_TP_Project_User constructor.
	 *
	 * @param TranslationProxy_Project $project
	 */
	public function __construct( &$project ) {
		$this->project = &$project;
	}
}