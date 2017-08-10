<?php
require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-element-translation-job.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-external-translation-job.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-post-translation-job.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/jobs/wpml-string-translation-job.class.php';

class WPML_Translation_Jobs_Collection extends WPML_Abstract_Job_Collection{

	/** @var WPML_Translation_Batch[] $translation_batches */
	private $translation_batches = array();

	private $count = 0;
	private $first_count;
	private $last_count;
	private $before_count = 0;
	private $after_count = 0;
	/** @var array $filter */
	private $filter;

	/**
	 * @param wpdb  $wpdb
	 * @param array $icl_translation_filter
	 */
	public function __construct( &$wpdb, $icl_translation_filter ) {
		parent::__construct( $wpdb );
		$this->filter = $icl_translation_filter;
	}

	/**
	 * @param int $page
	 * @param int $per_page
	 *
	 * @return array
	 */
	public function get_paginated_batches( $page, $per_page ) {
		$this->load_translation_jobs( $page, $per_page );

		$metrics = array();
		$batches = array();
		if ( $this->translation_batches ) {
			krsort( $this->translation_batches );
			foreach ( $this->translation_batches as $id => $batch ) {
				$metrics[ $id ]                 = $batch->get_batch_meta_array();
				$batches[ $id ]                 = $batch;
			}
		}

		$first_batch_metric                 = array_shift( $metrics );
		$first_batch_metric['display_from'] = $this->before_count + 1;
		$first_batch_metric['item_count']   = $this->first_count;
		array_unshift( $metrics, $first_batch_metric );
		$last_batch_metric                  = array_pop( $metrics );
		$last_batch_metric['display_to']    = $this->last_count - $this->after_count;
		$last_batch_metric['item_count']    = $this->last_count;
		$metrics[]                          = $last_batch_metric;

		return array( 'batches' => $batches, 'metrics' => $metrics );
	}

	/**
	 * Returns the number of jobs that meet the filter \WPML_Translation_Jobs_Collection::$filter in the database
	 *
	 * @return int
	 */
	public function get_count() {

		return $this->count;
	}

	/**
	 * @param WPML_Translation_Job $job
	 */
	public function add_job( $job ) {
		$batch_id = $job->get_batch_id();
		$batch    = array_key_exists( $batch_id, $this->translation_batches )
				? $this->translation_batches[ $batch_id ] : new WPML_Translation_Batch( $this->wpdb, $batch_id );

		$batch->add_job( $job );
		$this->translation_batches[ $batch->get_id() ] = $batch;
	}

	private function load_translation_jobs( $page, $per_page ) {
		$this->translation_batches = array();
		list( $jobs, $count, $before_count, $after_count, $first_count, $last_count ) = $this->get_jobs_table(
				$this->filter,
				array(
						'page'     => $page,
						'per_page' => $per_page
				) );
		$this->count        = $count;
		$this->after_count  = $after_count;
		$this->before_count = $before_count;
		$this->first_count  = $first_count;
		$this->last_count   = $last_count;
		if ( is_array( $jobs ) ) {
			foreach ( $jobs as $job ) {
				$this->add_job( $job );
			}
		}
	}

	/**
	 * @param array $args
	 * @param array $pagination_args
	 *
	 * @return array
	 */
	private function get_jobs_table( array $args = array(), array $pagination_args = array( 'page' => 1, 'per_page' => 10 ) ) {
		$where_jobs        = $this->build_where_clause( $args );
		$jobs_table_union  = $this->get_jobs_union_table_sql( $where_jobs, $args );

		$only_ids_query = 'SELECT SQL_CALC_FOUND_ROWS ';
		$only_ids_query .= 'jobs.job_id, jobs.translator_id, jobs.job_id, jobs.batch_id, jobs.element_type_prefix ';
		$only_ids_query .= 'FROM ' . $jobs_table_union . ' ';
		$only_ids_query .= 'LIMIT %d, %d';

		$page           = max( ( $pagination_args['page'] - 1 ), 0 ) * $pagination_args['per_page'];

		$only_ids_query = $this->wpdb->prepare( $only_ids_query, array( $page, $pagination_args['per_page'] ) );
		$data           = $this->wpdb->get_results( $only_ids_query );
		$count          = $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );

		$result = array( array(), $count, 0, 0, 0, 0 );

		if ( true === (bool) $data ) {
			$result = $this->calculate_batch_counts( $data, $count, $pagination_args, $jobs_table_union );
		}

		return $result;
	}

	/**
	 * @param array  $data
	 * @param int    $count
	 * @param array  $pagination_args
	 * @param string $jobs_table_union
	 *
	 * @return array
	 */
	private function calculate_batch_counts( $data, $count, $pagination_args, $jobs_table_union ) {
		$first_job                 = reset( $data );
		$last_job                  = end( $data );
		$first_batch               = $first_job->batch_id;
		$last_batch                = $last_job->batch_id;
		$count_select_from_snippet = 'SELECT COUNT(jdata.job_id) FROM (SELECT job_id, batch_id FROM ' . $jobs_table_union;
		$count_where_snippet       = ' ) AS jdata WHERE jdata.batch_id = %d';
		$before_count_query        = $count_select_from_snippet . ' LIMIT %d' . $count_where_snippet;
		$page                      = $pagination_args['page'];
		$per_page                  = $pagination_args['per_page'];
		$count_before              = $page > 1 ? $this->wpdb->get_var( $this->wpdb->prepare( $before_count_query,
		                                                                                     array(
			                                                                                     ( $page - 1 ) * $per_page,
			                                                                                     $first_batch
		                                                                                     ) ) ) : 0;
		$count_first               = $this->wpdb->get_var( $this->wpdb->prepare( $before_count_query,
		                                                                         array( PHP_INT_MAX, $first_batch ) ) );
		$after_count_query         = $count_select_from_snippet . ' LIMIT %d, %d' . $count_where_snippet;
		$count_after               = $page * $per_page > $count ? 0 : $this->wpdb->get_var( $this->wpdb->prepare( $after_count_query,
		                                                                                                          array(
			                                                                                                          $page * $per_page,
			                                                                                                          PHP_INT_MAX,
			                                                                                                          $last_batch
		                                                                                                          )
		) );
		$count_last                = $this->wpdb->get_var( $this->wpdb->prepare( $after_count_query,
		                                                             array( 0, PHP_INT_MAX, $last_batch ) ) );

		return array(
				$this->plain_objects_to_job_instances( $data ),
				$count,
				$count_before,
				$count_after,
				$count_first,
				$count_last,
		);
	}

	private function get_jobs_union_table_sql( $where_jobs, $args ) {
		$union_sql      = '';
		$sql_statements = array();

		if ( $where_jobs ) {
			$sql_statements[] = "SELECT s.translator_id,
						j.job_id,
						IF(p.post_type IS NOT NULL, 'post', 'package') AS element_type_prefix,
						s.batch_id
				FROM {$this->wpdb->prefix}icl_translation_status s
				JOIN {$this->wpdb->prefix}icl_translations t
					ON t.translation_id = s.translation_id
				JOIN {$this->wpdb->prefix}icl_translate_job j
					ON j.rid = s.rid
						AND j.revision IS NULL
				JOIN {$this->wpdb->prefix}icl_translations o
					ON o.trid = t.trid
						AND o.language_code = t.source_language_code
				JOIN " . apply_filters( 'wpml_post_translation_original_table', $this->wpdb->posts ) . " p
					ON o.element_id = p.ID
						AND ( o.element_type = CONCAT('post_', p.post_type) OR p.post_type IS NULL )
				JOIN {$this->wpdb->prefix}icl_translate tr_rows
                    ON tr_rows.job_id = j.job_id
                        AND tr_rows.field_type = 'original_id'
                        AND tr_rows.field_data = o.element_id
				WHERE " . $where_jobs;
		}

		$sql_statements = apply_filters( 'wpml_tm_jobs_union_table_sql', $sql_statements, $args );

		if ( count( $sql_statements ) > 0 ) {
			$union_sql = '(' . implode( "\nUNION ALL\n", $sql_statements ) . ") jobs
                INNER JOIN {$this->wpdb->prefix}icl_translation_batches b
                    ON b.id = jobs.batch_id
                ORDER BY jobs.batch_id DESC, jobs.element_type_prefix, jobs.job_id DESC";
		}

		return $union_sql;
	}
}
