<?php

class WPML_TP_Remote_Job_Sync_Cancelled extends WPML_TP_Remote_Job_Sync {

	/**
	 * Checks if a given cms_id is missing an entry in the WPML tables
	 *
	 * @return bool if true the cms_id does not have corresponding data in WPML
	 */
	public function not_in_sync() {

		return empty( $this->data['cms_id'] ) || $this->cms_id_helper->get_translation_id( $this->data['cms_id'] );
	}

	/**
	 * Synchronises the job with Translation Proxy and increments the cancelled
	 * job count in case of having cancelled a job.
	 *
	 * @param WPML_TP_Polling_Counts $counts count object to be updated
	 */
	protected function sync_action( &$counts ) {
		if ( $this->pro_translation->poll_updated_job_status_with_log( array(
			$this->data['id'],
			$this->data['cms_id'],
			'cancelled'
		                                                                 ), true )
		) {
			$counts->cancel_job();
		}
	}

	protected function is_data_valid( array $data ) {

		return $data['job_state'] === 'cancelled';
	}
}