<?php

class WPML_Translation_Batch extends WPML_Abstract_Job_Collection{

	private $name = false;
	private $id = false;
	private $url = false;
	/** @var WPML_Translation_Job[] $job_objects  */
	private $job_objects = array();

	/**
	 * @param wpdb $wpdb
	 * @param int  $batch_id
	 */
	public function __construct( &$wpdb, $batch_id = 0 ) {
		parent::__construct( $wpdb );
		$this->id   = $batch_id > 0 ? $batch_id : $this->retrieve_generic_batch_id();
		$this->name = $batch_id <= 0 ? $this->generate_generic_batch_name() : false;
	}

	public function reload() {
		global $wpdb;

		list( $type_select, $post_join ) = $this->left_join_post();

		$jobs = $wpdb->get_results( $wpdb->prepare(
			"	SELECT j.job_id,
				s.batch_id,
				{$type_select}
				FROM " . $this->get_table_join() . "
				{$post_join}
				WHERE s.batch_id = %d
					AND j.revision IS NULL",
			$this->id ) );
		$jobs = $this->plain_objects_to_job_instances( $jobs );
		foreach ( $jobs as $job ) {
			$this->add_job( $job );
		}
	}

	public function get_batch_url() {
		if ( $this->url === false ) {
			$this->url = TranslationManagement::get_batch_url( $this->id );
		}

		return $this->url;
	}

	public function get_batch_meta_array() {
		$in_active_ts   = $this->belongs_to_active_ts();
		$notifications  = $this->ts_supports_notifications();
		$batch_id       = $this->get_batch_tp_id();
		$batch_url      = $this->get_batch_url();
		$batch_name     = $batch_url ? $this->get_batch_name() : '';
		$item_count     = $this->get_item_count();

		return array(
			'in_active_ts'  => $in_active_ts,
			'notifications' => $notifications,
			'batch_id'      => $batch_id,
			'batch_url'     => $batch_url,
			'batch_name'    => $batch_name,
			'item_count'    => $item_count,
			'last_update'   => $this->get_last_update(),
			'status_array'  => $this->get_status_array(),
			'display_from'  => 1,
			'display_to'    => $item_count
		);
	}

	/**
	 * Cancels all remote translation jobs in this batch
	 */
	public function cancel_all_remote_jobs() {
		/**
		 * @var wpdb                    $wpdb
		 * @var TranslationManagement   $iclTranslationManagement
		 * @var WPML_String_Translation $WPML_String_Translation
		 */
		global $wpdb, $iclTranslationManagement, $WPML_String_Translation;

		$translation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT translation_id
                 FROM {$wpdb->prefix}icl_translation_status
                 WHERE batch_id = %d
                  AND translation_service <> 'local' ",
				$this->id
			)
		);
		foreach ( $translation_ids as $translation_id ) {
			$iclTranslationManagement->cancel_translation_request( $translation_id );
		}

		$string_translation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE batch_id = %d
                  AND translation_service <> 'local' ",
				$this->id
			)
		);
		foreach ( $string_translation_ids as $st_trans_id ) {
			$rid = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(rid)
                     FROM {$wpdb->prefix}icl_string_status
                     WHERE string_translation_id = %d",
					$st_trans_id
				)
			);
			if ( $rid ) {
				$WPML_String_Translation->cancel_remote_translation( $rid );
			}
		}
	}

	//todo: [WPML 3.2.1] This method and other similar methods can likely be removed
	public function get_last_update() {
		return TranslationManagement::get_batch_last_update( $this->id );
	}

	/**
	 * @param WPML_Translation_Job $job
	 */
	public function add_job( $job ) {
		$this->job_objects[ $job->get_id() ] = $job;
	}

	public function get_jobs_as_array() {
		$res = array();
		krsort( $this->job_objects );
		foreach ( $this->job_objects as $job ) {
			$res[ ] = $job->to_array();
		}

		return $res;
	}

	public function get_item_count() {

		return count( $this->job_objects );
	}

	public function get_id() {

		return $this->id;
	}

	public function get_batch_name() {
		if ( $this->name == false ) {
			$this->name = TranslationManagement::get_batch_name( $this->get_id() );
		}

		return $this->name;
	}

	public function get_batch_tp_id() {
		return TranslationManagement::get_batch_tp_id( $this->id );
 	}

	public function get_status_array() {
		$status_array = array();
		foreach ( $this->job_objects as $job ) {
			if ( ! isset( $status_array[ $job->get_status() ] ) ) {
				$status_array[ $job->get_status() ] = 0;
			}
			$status_array[ $job->get_status() ] ++;
		}

		return $status_array;
	}

	private function retrieve_generic_batch_id() {
		return TranslationProxy_Batch::update_translation_batch( $this->generate_generic_batch_name() );
	}

	private function generate_generic_batch_name() {
		return 'Manual Translations from ' . date( 'F \t\h\e jS\, Y' );
	}

	private function belongs_to_active_ts() {
		global $wpdb;

		$service_id = TranslationProxy::get_current_service_id();
		$batch_id   = $this->get_id();

		$result = false;
		if ( $service_id ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE batch_id = %d AND translation_service = %s LIMIT 1", array( $batch_id, $service_id ) ) );
			$result |= $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE batch_id = %d AND translation_service = %s LIMIT 1", array( $batch_id, $service_id ) ) );
		}
		return $result;
	}

	private function ts_supports_notifications() {
		$translation_service = TranslationProxy::get_current_service();
		return $supports_notifications = isset($translation_service->notification) ? $translation_service->notification : true;
	}

	public function clear_batch_data() {
		TranslationProxy_Basket::set_batch_data( null );
	}
}
