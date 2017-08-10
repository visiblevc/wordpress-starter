<?php

class WPML_TP_Remote_Job_Sync_Existing extends WPML_TP_Remote_Job_Sync {

	/**
	 * Checks if a given cms_id is missing an entry in the WPML tables
	 *
	 * @return bool if true the cms_id does not have corresponding data in WPML
	 */
	public function not_in_sync() {

		return ( ! empty( $this->data['cms_id'] )
		         && ! $this->cms_id_helper->get_translation_id( $this->data['cms_id'] ) )
		       || ( empty( $this->data['cms_id'] ) && apply_filters( 'wpml_st_job_state_pending',
				false,
				$this->data ) ) || $this->data['job_state'] === 'translation_ready';
	}

	/**
	 * Synchronises the job with Translation Proxy and increments the completed
	 * job count in case of having downloaded a job.

	 *
*@param WPML_TP_Polling_Counts $counts count object to be updated
	 */
	protected function sync_action( &$counts ) {
		if ( (bool) $this->data['cms_id'] === true ) {
			$this->cms_id_helper->get_translation_id( $this->data['cms_id'],
				$this->project->service() );
		}
		$this->data['job_state'] = $this->data['job_state'] === 'delivered' ? 'translation_ready' : $this->data['job_state'];
		if ( $this->data['job_state'] === 'translation_ready'
		     && $this->pro_translation->poll_updated_job_status_with_log( array(
				$this->data['id'],
				$this->data['cms_id'],
				'translation_ready',
			), true ) === 1
		) {
			$counts->complete_job();
		}
	}

	protected function is_data_valid( array $data ) {

		return $data['job_state'] !== 'cancelled';
	}
}