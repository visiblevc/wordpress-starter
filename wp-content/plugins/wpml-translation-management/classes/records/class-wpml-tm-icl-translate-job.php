<?php

class WPML_TM_ICL_Translate_Job extends WPML_TM_Record_User {

	private $table = 'icl_translate_job';
	private $job_id = 0;

	/**
	 * WPML_TM_ICL_Translation_Status constructor.
	 *
	 * @param WPML_TM_Records $tm_records
	 * @param int             $job_id
	 */
	public function __construct( &$tm_records, $job_id ) {
		parent::__construct( $tm_records );
		$job_id           = (int) $job_id;
		if ( $job_id > 0 ) {
			$this->job_id = $job_id;
		} else {
			throw new InvalidArgumentException( 'Invalid Job ID: ' . $job_id );
		}
	}

	/**
	 * @return int
	 */
	public function translator_id() {

		return $this->tm_records->icl_translation_status_by_rid( $this->rid() )
		                        ->translator_id();
	}

	/**
	 * @return string|int
	 */
	public function service() {

		return $this->tm_records->icl_translation_status_by_rid( $this->rid() )
		                        ->service();
	}

	/**
	 * @param array $args in the same format used by \wpdb::update()
	 *
	 * @return $this
	 */
	public function update( $args ) {
		$wpdb = $this->tm_records->wpdb();
		$wpdb->update(
			$wpdb->prefix . $this->table, $args, array( 'job_id' => $this->job_id ) );

		return $this;
	}

	/**
	 * @return bool true if this job is the most recent job for the element it
	 * belongs to and hence may be updated.
	 */
	public function is_open() {
		$wpdb = $this->tm_records->wpdb();

		return $this->job_id === (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(job_id)
				 FROM {$wpdb->prefix}{$this->table}
				 WHERE rid = %d",
				$this->rid() ) );
	}

	public function rid() {
		$wpdb = $this->tm_records->wpdb();

		return $wpdb->get_var(
			$wpdb->prepare( " SELECT rid
									FROM {$wpdb->prefix}{$this->table}
									WHERE job_id = %d LIMIT 1",
				$this->job_id ) );
	}
}