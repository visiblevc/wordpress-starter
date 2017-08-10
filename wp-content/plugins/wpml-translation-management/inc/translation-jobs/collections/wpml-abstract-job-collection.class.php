<?php

class WPML_Abstract_Job_Collection {
	/** @var WPDB $wpdb */
	public $wpdb;

	/**
	 * WPML_Abstract_Job_Collection constructor.
	 *
	 * @param WPDB $wpdb
	 */
	public function __construct( WPDB $wpdb ) {
		$this->wpdb = $wpdb;
	}

	protected function get_table_join(
		$single = false,
		$icl_translate_alias = 'iclt',
		$icl_translations_translated_alias = 't',
		$icl_translations_original_alias = 'ito',
		$icl_translation_status_alias = 's',
		$icl_translate_job_alias = 'j'
	) {
		$wpdb = &$this->wpdb;

		$max_rev_snippet = $single === true ? ''
			: "JOIN (SELECT rid, MAX(job_id) job_id FROM {$wpdb->prefix}icl_translate_job GROUP BY rid ) jobmax
					ON ( {$icl_translate_job_alias}.revision IS NULL
	                    AND {$icl_translate_job_alias}.rid = jobmax.rid)
                        OR ( {$icl_translate_job_alias}.job_id = jobmax.job_id
                        AND {$icl_translate_job_alias}.translated = 1)";

		return "{$wpdb->prefix}icl_translate_job {$icl_translate_job_alias}
                JOIN {$wpdb->prefix}icl_translation_status {$icl_translation_status_alias}
                  ON {$icl_translate_job_alias}.rid = {$icl_translation_status_alias}.rid
                JOIN {$wpdb->prefix}icl_translations {$icl_translations_translated_alias}
                  ON {$icl_translation_status_alias}.translation_id = {$icl_translations_translated_alias}.translation_id
                JOIN {$wpdb->prefix}icl_translate {$icl_translate_alias}
                  ON {$icl_translate_alias}.job_id = {$icl_translate_job_alias}.job_id
                JOIN {$wpdb->prefix}icl_translations {$icl_translations_original_alias}
                  ON {$icl_translations_original_alias}.element_id = {$icl_translate_alias}.field_data
                    AND {$icl_translations_original_alias}.trid = {$icl_translations_translated_alias}.trid
                {$max_rev_snippet}";
	}

	protected function left_join_post( $icl_translations_alias = 'ito', $posts_alias = 'p' ) {

		$join   = "LEFT JOIN {$this->wpdb->prefix}posts {$posts_alias}
                  ON {$icl_translations_alias}.element_id = {$posts_alias}.ID
                     AND {$icl_translations_alias}.element_type = CONCAT('post_', {$posts_alias}.post_type)";
		$select = "IF({$posts_alias}.post_type IS NOT NULL, 'post', 'package') as element_type_prefix";

		return array( $select, $join );
	}

	protected function plain_objects_to_job_instances( $jobs ) {
		foreach ( $jobs as $key => $job ) {
			if ( ! is_object( $job ) || ! isset( $job->element_type_prefix ) || ! isset( $job->job_id ) ) {
				unset( $jobs[ $key ] );
				continue;
			}

			$jobs[ $key ] = $job->element_type_prefix === 'post'
					? new WPML_Post_Translation_Job( $job->job_id, $job->batch_id )
					: ( $job->element_type_prefix === 'string'
							? new WPML_String_Translation_Job( $job->job_id )
							: new WPML_External_Translation_Job( $job->job_id, $job->batch_id ) );
		}

		return $jobs;
	}

	protected function build_where_clause( $args ) {
		// defaults
		/** @var string $translator_id */
		/** @var int|bool $status */
		/** @var int|bool $status__not */
		/** @var bool $include_unassigned */
		/** @var int $limit_no */
		/** @var array $language_pairs */
		/** @var string|bool $service */
		$args_default = array(
			'translator_id'      => 0,
			'status'             => false,
			'status__not'        => false,
			'include_unassigned' => false,
			'language_pairs'     => array(),
			'service'            => false
		);

		extract( $args_default );
		extract( $args, EXTR_OVERWRITE );

		$where = " s.status > " . ICL_TM_NOT_TRANSLATED;
		$where .= $status != '' ? " AND s.status=" . intval( $status ) : '';
		$where .= $status != ICL_TM_DUPLICATE ? " AND s.status <> " . ICL_TM_DUPLICATE : '';
		$where .= $status__not !== false ? " AND s.status <> " . $status__not : '';
		$where .= ! empty( $from ) ? $this->wpdb->prepare( " AND t.source_language_code = %s ", $from ) : '';
		$where .= ! empty( $to ) ? $this->wpdb->prepare( " AND t.language_code = %s ", $to ) : '';

		if ( $translator_id !== "" ) {
			if ( ! is_numeric( $translator_id ) ) {
				$_exp          = explode( '-', $translator_id );
				$service       = isset( $_exp[ 1 ] ) ? implode( '-', array_slice( $_exp, 1 ) ) : 'local';
				$translator_id = isset( $_exp[ 2 ] ) ? $_exp[ 2 ] : false;
			} else {
				$service = 'local';
			}
			$language_pairs = empty( $to ) || empty( $from ) ?
					get_user_meta( $translator_id, $this->wpdb->prefix . 'language_pairs', true )
					: $language_pairs;

			$translator_id_query_parts = array();
			if ( (int) $translator_id != 0 ) {
				$translator_id_query_parts[] = $this->wpdb->prepare( "j.translator_id = %d", $translator_id );
				if ( $include_unassigned ) {
					$translator_id_query_parts[ ] = " j.translator_id = 0 OR j.translator_id IS NULL ";
				}
				if ( (bool) $translator_id_query_parts === true ) {
					$where .= " AND (" . join( ' OR ', $translator_id_query_parts ) . ") ";
				}
			}
		}

		$where .= ! empty( $service ) ? $this->wpdb->prepare( " AND s.translation_service=%s ", $service ) : '';

		if ( empty( $from ) && (bool) $language_pairs !== false && is_array( $language_pairs ) && $translator_id ) {
			// only if we filter by translator, make sure to use just the 'from' languages that apply
			// in no translator_id, omit condition and all will be pulled
			if ( ! empty( $to ) ) {
				// get 'from' languages corresponding to $to (to $translator_id)
				$from_languages = array();
				foreach ( $language_pairs as $fl => $tls ) {
					if ( isset( $tls[ $to ] ) ) {
						$from_languages[ ] = $fl;
					}
				}
				$where .= $from_languages ? " AND t.source_language_code IN (" . wpml_prepare_in(
								$from_languages
						) . ") " : '';
			} else {
				// all to all case
				// get all possible combinations for $translator_id
				$from_languages   = array_keys( $language_pairs );
				$where_conditions = array();
				foreach ( $from_languages as $fl ) {
					$where_conditions[ ] = $this->wpdb->prepare(
							" (t.source_language_code = %s AND t.language_code IN (" . wpml_prepare_in(
									array_keys( $language_pairs[ $fl ] )
							) . ")) ",
							$fl
					);
				}
				$where .= ! empty( $where_conditions ) ? ' AND ( ' . join( ' OR ', $where_conditions ) . ') ' : '';
			}
		}

		if ( empty( $to )
		     && $translator_id
		     && ! empty( $from )
		     && isset( $language_pairs[ $from ] )
		     && (bool) $language_pairs[ $from ] !== false
		) {
			// only if we filter by translator, make sure to use just the 'from' languages that apply
			// in no translator_id, omit condition and all will be pulled
			// get languages the user can translate into from $from
			$where .= " AND t.language_code IN(" . wpml_prepare_in( array_keys( $language_pairs[ $from ] ) ) . ")";
		}

		$where .= ! empty( $type ) ? $this->wpdb->prepare( " AND ito.element_type=%s ", $type ) : '';

		return $where;
	}
}