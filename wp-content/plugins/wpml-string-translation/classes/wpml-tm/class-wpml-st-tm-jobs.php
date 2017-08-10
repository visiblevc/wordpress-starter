<?php

class WPML_ST_TM_Jobs extends WPML_WPDB_User {

	/**
	 * @param wpdb $wpdb
	 * WPML_ST_TM_Jobs constructor.
	 */
	public function __construct( &$wpdb ) {
		parent::__construct( $wpdb );
		add_filter( 'wpml_tm_jobs_union_table_sql', array(
			$this,
			'jobs_union_table_sql_filter'
		), 10, 2 );
		add_filter( 'wpml_post_translation_original_table', array(
			$this,
			'filter_tm_post_job_table'
		), 10, 1 );
		add_filter( 'wpml_st_job_state_pending', array(
			$this,
			'tm_external_job_in_progress_filter'
		), 10, 2 );
	}

	/**
	 * @param bool         $in_progress_status
	 * @param array|object $job_arr
	 *
	 * @return bool true if a job is in progress for the given arguments
	 */
	public function tm_external_job_in_progress_filter( $in_progress_status, $job_arr ) {
		$job_arr = (array) $job_arr;
		if ( isset( $job_arr['batch'] ) ) {
			$job_arr['batch'] = (array) $job_arr['batch'];
		}

		return isset( $job_arr['batch']['id'] )
		       && empty( $job_arr['cms_id'] )
		       && ! empty( $job_arr['id'] )
		       && ! empty( $job_arr['job_state'] )
		       && $job_arr['job_state'] === 'delivered'
		       && $this->wpdb->get_var( $this->wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$this->wpdb->prefix}icl_core_status ct
			JOIN {$this->wpdb->prefix}icl_string_status st
				ON ct.rid = st.rid
			JOIN {$this->wpdb->prefix}icl_string_translations t
				ON st.string_translation_id = t.id
				WHERE ct.rid = %d AND t.status < %d
		", $job_arr['id'], ICL_TM_COMPLETE ) ) ? true : $in_progress_status;
	}

	public function jobs_union_table_sql_filter( $sql_statements, $args ) {
		return $this->get_jobs_table_sql_part( $sql_statements, $args );
	}

	/**
	 * @param string $table
	 *
	 * @return string
	 */
	public function filter_tm_post_job_table( $table ) {
		return " (SELECT ID, post_type FROM {$table}
						UNION ALL
					SELECT ID, NULL as post_type FROM {$this->wpdb->prefix}icl_string_packages) ";
	}

	private function get_jobs_table_sql_part( $sql_statements, $args ) {
		$sql_statements[] = "SELECT  st.translator_id,
					st.id AS job_id,
					'string' AS element_type_prefix,
					st.batch_id
			FROM {$this->wpdb->prefix}icl_string_translations st
				JOIN {$this->wpdb->prefix}icl_strings s
					ON s.id = st.string_id
      " . $this->build_string_where( $args );

		return $sql_statements;
	}

	private function build_string_where( $args ) {
		$string_where  = '';
		$translator_id = '';
		$from          = '';
		$to            = '';
		$status        = '';
		$service       = false;

		extract( $args, EXTR_OVERWRITE );

		$wheres     = array();
		$where_args = array();

		if ( true === (bool) $from ) {
			$wheres[]     = 's.language = %s';
			$where_args[] = $from;
		}
		if ( true === (bool) $to ) {
			$wheres[]     = 'st.language = %s';
			$where_args[] = $to;
		}
		if ( $status ) {
			$wheres[]     = 'st.status = %d';
			$where_args[] = ICL_TM_IN_PROGRESS === (int) $status ? ICL_TM_WAITING_FOR_TRANSLATOR : $status;
		}

		$service = is_numeric( $translator_id ) ? 'local' : $service;
		$service = 'local' !== $service && false !== strpos( $translator_id, 'ts-' ) ? substr( $translator_id, 3 ) : $service;

		if ( 'local' === $service ) {
			$wheres[]     = 'st.translator_id = %s';
			$where_args[] = $translator_id;
		}

		if ( false !== $service ) {
			$wheres[]     = 'st.translation_service = %s';
			$where_args[] = $service;
		}

		if ( count( $wheres ) > 0 && count( $wheres ) === count( $where_args ) ) {
			$where_sql = implode( ' AND ', $wheres );

			$string_where = 'WHERE ' . $this->wpdb->prepare( $where_sql, $where_args );
		}

		return $string_where;
	}
}
