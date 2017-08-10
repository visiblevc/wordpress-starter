<?php

class WPML_Translation_Job_Helper_With_API extends WPML_Translation_Job_Helper {

	/** @var  WPML_Element_Translation_Package $package_helper */
	protected $package_helper;

	function __construct() {
		$this->package_helper = new WPML_Element_Translation_Package();
	}

	protected function get_translation_job( $job_id, $include_non_translatable_elements = false, $revisions = 0 ) {
		global $wpml_translation_job_factory;

		return $wpml_translation_job_factory->get_translation_job( $job_id, $include_non_translatable_elements, $revisions );
	}

	protected function get_lang_by_rid( $rid ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare(
			"SELECT i.language_code
			FROM {$wpdb->prefix}icl_translations i
			JOIN {$wpdb->prefix}icl_translation_status s
				ON s.translation_id = i.translation_id
			WHERE s.rid = %d
			LIMIT 1",
			$rid ) );
	}
}