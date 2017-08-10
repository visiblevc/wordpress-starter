<?php

class WPML_TM_User {

	/** @var  TranslationManagement $tm_instance */
	protected $tm_instance;

	/**
	 * WPML_Custom_Field_Setting_Factory constructor.
	 *
	 * @param TranslationManagement $tm_instance
	 */
	public function __construct( &$tm_instance ) {
		$this->tm_instance = &$tm_instance;
	}
}