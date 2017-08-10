<?php

/**
 * Class WPML_TP_Polling_Pickup
 */
class WPML_TP_Polling_Pickup {

	/** @var WPML_Pro_Translation $pro_translation */
	private $pro_translation;

	/** @var WPML_TP_Remote_Sync_Factory $remote_sync_factory */
	private $remote_sync_factory;

	/**
	 * WPML_TP_Polling_Pickup constructor.
	 *
	 * @param WPML_Pro_Translation        $pro_translation
	 * @param WPML_TP_Remote_Sync_Factory $remote_sync_factory
	 */
	public function __construct(
		&$pro_translation, &$remote_sync_factory
	) {
		$this->pro_translation     = &$pro_translation;
		$this->remote_sync_factory = &$remote_sync_factory;
	}

	/**
	 * Synchronizes one job with Translation Proxy
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function poll_job( array $data ) {
		$job     = ! empty( $data['job_polled'] ) ? $data['job_polled'] : false;

		$results = array(
			'errors' => empty( $data['error_jobs'] ) ? array() : $data['error_jobs']
		);
		$counts  = new WPML_TP_Polling_Counts(
			empty( $data['completed_jobs'] ) ? 0 : (int) $data['completed_jobs'],
			empty( $data['cancelled_jobs'] ) ? 0 : (int) $data['cancelled_jobs']
		);

		if ( $job && in_array( $job['job_state'], array(
				'cancelled',
				'translation_ready',
				'delivered',
				'waiting_translation'
			) )
		) {
			/** @var array $job */
			$remote_job_sync = $this->remote_sync_factory->remote_job_sync( $job );
			if ( $remote_job_sync->not_in_sync() ) {
				try {
					//Poll requests happens here
					$remote_job_sync->sync( $counts );
				} catch ( Exception $e ) {
					$results['errors']   = (array) $results['errors'];
					$results['errors'][] = $e->getMessage();
				}
			}
		}
		if ( ! empty( $results['errors'] ) ) {
			$status    = __( 'Error', 'sitepress' );
			$errors    = join( "\n",
				array_filter( (array) $results['errors'] ) );
			$job_error = true;
		} else {
			$status    = __( 'OK', 'sitepress' );
			$job_error = false;
		}
		if ( $counts->completed() === 1 ) {
			$status_completed = __( '1 translation has been fetched from the translation service.',
				'sitepress' );
		} elseif ( $counts->completed() > 1 ) {
			$status_completed = sprintf( __( '%d translations have been fetched from the translation service.',
				'sitepress' ), $counts->completed() );
		} else {
			$status_completed = '';
		}
		if ( $counts->cancelled() > 0 ) {
			$status_cancelled = sprintf( __( '%d translations have been marked as cancelled.',
				'sitepress' ), $counts->cancelled() );
		}

		return array(
			'job_error' => $job_error,
			'status'    => $status,
			'errors'    => isset( $errors ) ? $errors : '',
			'completed' => $status_completed,
			'cancelled' => isset( $status_cancelled ) ? $status_cancelled : '',
		);
	}
}