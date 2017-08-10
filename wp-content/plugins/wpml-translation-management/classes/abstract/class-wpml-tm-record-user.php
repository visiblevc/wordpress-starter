<?php

class WPML_TM_Record_User {

	/** @var WPML_TM_Records $tm_records */
	protected $tm_records;

	/**
	 * WPML_TM_Record_User constructor.
	 *
	 * @param WPML_TM_Records $tm_records
	 */
	public function __construct( &$tm_records ) {
		$this->tm_records = &$tm_records;
	}

}