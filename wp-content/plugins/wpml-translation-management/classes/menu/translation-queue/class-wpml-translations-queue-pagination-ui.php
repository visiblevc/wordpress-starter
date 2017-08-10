<?php

/**
 * Created by OnTheGo Systems
 */
class WPML_Translations_Queue_Pagination_UI {
	
	private $translation_jobs;
	private $jobs_per_page;
	
	function __construct( $translation_jobs, $jobs_per_page ) {
		$this->translation_jobs = $translation_jobs;
		$this->jobs_per_page    = $jobs_per_page;
	}

	public function show() {
        $total_count = count( $this->translation_jobs );
		$paginate    = new WPML_UI_Pagination( $total_count, $this->jobs_per_page );
		
		$paginate->show();
	}

	public function get_paged_jobs() {
		$paged    = isset( $_GET['paged'] ) ? filter_var( $_GET['paged'],
			FILTER_SANITIZE_NUMBER_INT ) : '';
		$paged    = $paged ? $paged : 1;
		$per_page = $this->jobs_per_page;

		return array_slice( $this->translation_jobs, ( $paged - 1 ) * $per_page,
			$per_page );
	}
}


