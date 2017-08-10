<?php
require_once WPML_TM_PATH . '/inc/translation-jobs/wpml-translation-jobs-collection.class.php';

class WPML_Translation_Jobs_Table {

	private $page = 1;
	private $per_page = 20;
	/**
	 * @var WPML_Translation_Jobs_Collection
	 */
	private $translation_jobs_collection;
	private $translation_filter = array( 'limit_no' => 1000, 'translator_id' => '' );


	public function __construct( $iclTranslationManagement ) {
		$this->tm_instance = $iclTranslationManagement;
		$this->load_pagination_params();
		$this->load_filter_params();
		$this->load_translation_jobs_collection();
	}

	private function load_translation_jobs_collection() {
		global $wpdb;

		$this->translation_jobs_collection = new WPML_Translation_Jobs_Collection( $wpdb, $this->translation_filter );
	}

	private function load_filter_params() {

		if ( isset( $_SESSION[ 'translation_jobs_filter' ] ) ) {
			$this->translation_filter = $_SESSION[ 'translation_jobs_filter' ];
		}

		if ( isset( $_POST[ 'filter_lang_from' ] ) ) {
			$this->translation_filter[ 'from' ] = $_POST[ 'filter_lang_from' ];
		}
		if ( isset( $_POST[ 'filter_lang_to' ] ) ) {
			$this->translation_filter[ 'to' ] = $_POST[ 'filter_lang_to' ];
		}
		if ( isset( $_POST[ 'filter_translator_id' ] ) ) {
			$this->translation_filter[ 'translator_id' ] = $_POST[ 'filter_translator_id' ];
		}
		if ( isset( $_POST[ 'filter_job_status' ] ) ) {
			$this->translation_filter[ 'status' ] = $_POST[ 'filter_job_status' ];
		}
	}

	private function load_pagination_params() {
		if ( isset( $_POST[ 'pagination_page' ] ) && isset( $_POST[ 'pagination_page_size' ] ) ) {
			$this->page     = (int) $_POST[ 'pagination_page' ];
			$this->per_page = (int) $_POST[ 'pagination_page_size' ];
		}
	}

	public function get_filter() {
		return $this->translation_filter;
	}

	public function get_paginated_jobs() {
		$paginated_results = $this->translation_jobs_collection->get_paginated_batches( $this->page, $this->per_page );
		$item_count        = $this->translation_jobs_collection->get_count();

		/** @var WPML_Translation_Batch $batch */
		foreach ( $paginated_results[ 'batches' ] as $batch_id => $batch ) {
			/** @var WPML_Translation_Batch $batch */
			$paginated_results[ 'batches' ][ $batch_id ] = $batch->get_jobs_as_array();
		}

		$data = array(
			'Flat_Data' => array_values( $paginated_results[ 'batches' ] ),
			'metrics'   => array(
				'item_count'    => $item_count,
				'batch_metrics' => array_values( $paginated_results[ 'metrics' ] )
			)
		);

		return $data;
	}
}
