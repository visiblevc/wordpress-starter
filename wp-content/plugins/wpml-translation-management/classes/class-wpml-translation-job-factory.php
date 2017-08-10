<?php

class WPML_Translation_Job_Factory extends WPML_Abstract_Job_Collection {

	/** @var  WPML_TM_Records $tm_records */
	private $tm_records;

	/**
	 * @param WPML_TM_Records $tm_records
	 */
	public function __construct( &$tm_records ) {
		$wpdb = $tm_records->wpdb();
		parent::__construct( $wpdb );
		$this->tm_records = &$tm_records;
	}

	/**
	 * @return WPML_TM_Records
	 */
	public function &tm_records() {

		return $this->tm_records;
	}

	public function init_hooks(){
		add_filter( 'wpml_translation_jobs', array(
			$this,
			'get_translation_jobs_filter'
		), 10, 2 );
		add_filter( 'wpml_translation_job_types', array(
			$this,
			'get_translation_job_types_filter'
		), 10, 2 );
		add_filter( 'wpml_get_translation_job', array(
			$this,
			'get_translation_job_filter'
		), 10, 3 );
	}

	/**
	 * Creates a local translation job for a given post and target language and returns the job_id of the created job.
	 *
	 * @param int    $post_id
	 * @param string $target_language_code
	 * @param int|null   $translator_id
	 *
	 * @return int|null
	 */
	public function create_local_post_job( $post_id, $target_language_code, $translator_id = null ) {
		global $wpml_post_translations, $iclTranslationManagement;

		$source_language_code = $wpml_post_translations->get_element_lang_code( $post_id );
		$trid                 = $wpml_post_translations->get_element_trid( $post_id );
		if ( $source_language_code && $trid ) {
			$dummy_basket_data = array(
				'post'           => array( $post_id ),
				'translate_from' => $source_language_code,
				'translate_to'   => array( $target_language_code => 1 )
			);

			if ( null !== $translator_id ) {
				$dummy_basket_data['translators'] = array( $target_language_code => $translator_id );
			}

			$iclTranslationManagement->send_jobs( $dummy_basket_data );
		}

		return $this->job_id_by_trid_and_lang( $trid, $target_language_code );
	}

	public function get_translation_jobs_filter( $jobs, $args ) {

		return array_merge( $jobs, $this->get_translation_jobs( $args ) );
	}

	public function get_translation_job_filter( $job_id, $include_non_translatable_elements = false, $revisions = 0 ) {

		return $this->get_translation_job( $job_id, $include_non_translatable_elements, $revisions );
	}

	/**
	 * @param int  $job_id
	 * @param bool $include_non_translatable_elements
	 * @param int  $revisions
	 * @param bool $as_job_instance returns WPML_Element_Translation_Job instead of plain object if true
	 *
	 * @return bool|stdClass|WPML_Element_Translation_Job
	 */
	public function get_translation_job( $job_id, $include_non_translatable_elements = false, $revisions = 0, $as_job_instance = false ) {
		$job_data = false;
		$job      = $this->retrieve_job_data( $job_id );
		if ( (bool) $job !== false ) {
			$job_data = $this->complete_job_data( $job, $include_non_translatable_elements, $revisions );
		}

		if ( $as_job_instance === true ) {
			$job_arr  = $this->plain_objects_to_job_instances( array( $job ) );
			$job_data = end( $job_arr );
		}

		return $job_data;
	}

	/**
	 * @param int $translation_id
	 *
	 * @return bool|stdClass|WPML_Element_Translation_Job
	 */
	public function job_by_translation_id( $translation_id ) {
		$row = $this->tm_records->icl_translations_by_translation_id( $translation_id );

		return $row
			? $this->get_translation_job(
				$this->job_id_by_trid_and_lang(
					$row->trid(), $row->language_code() ), false, 0, true )
			: 0;
	}

	public function string_job_by_translation_id( $string_translation_id ) {
		return new WPML_String_Translation_Job( $string_translation_id );
	}

	public function job_id_by_trid_and_lang( $trid, $target_language_code ) {
		global /** @var TranslationManagement $iclTranslationManagement */
		$iclTranslationManagement;

		return $iclTranslationManagement->get_translation_job_id( $trid, $target_language_code );
	}

	public function get_translation_jobs( array $args = array(), $only_ids = false, $as_job_instances = false ) {
		global $wpdb;

		/** @var $order_by array */
		/** @var $include_unassigned bool */
		$include_unassigned = false;
		$order_by           = array();

		extract( $args, EXTR_OVERWRITE );

		$order_by = is_scalar( $order_by ) ? array( $order_by ) : $order_by;
		if ( $include_unassigned ) {
			$order_by[] = 'j.translator_id DESC';
		}
		$order_by[] = ' j.job_id DESC ';
		$order_by   = implode( ', ', $order_by );

		$where = $this->build_where_clause( $args );

		$jobs_sql = $this->get_job_sql( $where, $order_by, $only_ids );
		$jobs     = $wpdb->get_results( $jobs_sql );
		if ( $only_ids === false ) {
			$jobs = $this->add_data_to_post_jobs( $jobs );
		}
		if ( $as_job_instances === true ) {
			$jobs = $this->plain_objects_to_job_instances( $jobs );
		}

		return $jobs;
	}

	private function add_data_to_post_jobs( $jobs ) {
		global $iclTranslationManagement, $sitepress;

		foreach ( $jobs as $job_index => $job ) {
			$post_id = $job->original_doc_id;
			$doc     = $iclTranslationManagement->get_post( $post_id, $job->element_type_prefix );

			if ( $doc ) {
				$element_language_details = $sitepress->get_element_language_details( $post_id,
					$job->original_post_type );
				$language_from_code       = $element_language_details->language_code;
				$edit_url                 = get_edit_post_link( $doc->ID );

				if ( $iclTranslationManagement->is_external_type( $job->element_type_prefix ) ) {
					$post_title = $this->get_external_job_post_title( $job->job_id, $post_id );
					$edit_url   = apply_filters( 'wpml_external_item_url', '', $post_id );
					$edit_url   = apply_filters( 'wpml_document_edit_item_url', $edit_url, $doc->kind_slug, $doc->ID );
				} else {
					$post_title = $doc->post_title;
					$edit_url   = apply_filters( 'wpml_document_edit_item_url',
						$edit_url,
						$job->original_post_type,
						$doc->ID );
				}
				$ldf                                      = $sitepress->get_language_details( $language_from_code );
				$jobs[ $job_index ]->original_doc_id      = $doc->ID;
				$jobs[ $job_index ]->language_code_source = $language_from_code;
			} else {
				$post_title                               = __( 'The original has been deleted!', 'sitepress' );
				$edit_url                                 = '';
				$jobs[ $job_index ]->original_doc_id      = 0;
				$jobs[ $job_index ]->language_code_source = null;

				$ldf[ 'display_name' ] = __( 'Deleted', 'sitepress' );
			}

			$jobs[ $job_index ]->post_title = $post_title;
			$jobs[ $job_index ]->edit_link  = $edit_url;

			$ldt = $sitepress->get_language_details( $job->language_code );

			$jobs[ $job_index ]->lang_text            = $ldf[ 'display_name' ] . ' &raquo; ' . $ldt[ 'display_name' ];
			$jobs[ $job_index ]->lang_text_with_flags = '<span class="wpml-title-flag">' . $sitepress->get_flag_img( $ldf[ 'code' ]) . '</span> ' . $ldf[ 'display_name' ] .
															' &raquo; <span class="wpml-title-flag">' .
															$sitepress->get_flag_img( $ldt[ 'code' ]) . '</span> ' . $ldt[ 'display_name' ];
			$jobs[ $job_index ]->language_text_source = $ldf[ 'display_name' ];
			$jobs[ $job_index ]->language_text_target = $ldt[ 'display_name' ];
			$jobs[ $job_index ]->language_code_target = $job->language_code;
		}

		return $jobs;
	}

	private function retrieve_job_data( $job_ids ) {
		global $wpdb;

		$job_ids = is_scalar( $job_ids ) ? array( $job_ids ) : $job_ids;
		if ( (bool) $job_ids === false ) {
			return array();
		}
		list( $prefix_select, $prefix_posts_join ) = $this->left_join_post();
		$job_id_in    = wpml_prepare_in( $job_ids, '%d' );
		$limit        = count( $job_ids );
		$data_query = 'SELECT ' . $this->get_job_select() . ",
								  {$prefix_select}
						FROM " . $this->get_table_join( count($job_ids) === 1 ) . "
			            {$prefix_posts_join}
						WHERE j.job_id IN ({$job_id_in})
						  AND iclt.field_type = 'original_id'
			            LIMIT %d";
		$data_prepare = $wpdb->prepare( $data_query, $limit );
		$data         = $wpdb->get_results( $data_prepare );

		return (bool) $data === false ? array() : ( $limit === 1 ? $data[ 0 ] : $data );
	}

	private function get_job_sql( $where, $order_by, $only_ids = false ) {
		global $wpdb;

		list( $prefix_select, $prefix_posts_join ) = $this->left_join_post();
		$cols = "j.job_id, s.batch_id,
				 {$prefix_select}"
		        . ( $only_ids === false ? ',' . $this->get_job_select() : '' );

		return "SELECT SQL_CALC_FOUND_ROWS
					{$cols}
                FROM " . $this->get_table_join() . "
                {$prefix_posts_join}
                LEFT JOIN {$wpdb->users} u
                  ON s.translator_id = u.ID
                WHERE ( {$where} )
                  AND iclt.field_type = 'original_id'
                ORDER BY {$order_by}
            ";
	}

	public function get_translation_job_types_filter( $value, $args ) {
		global $wpdb, $sitepress;

		$where     = $this->build_where_clause( $args );
		$job_types = $wpdb->get_results( "SELECT DISTINCT
				    SUBSTRING_INDEX(ito.element_type, '_', 1) AS element_type_prefix,
				    ito.element_type AS original_post_type
                    FROM " . $this->get_table_join() . "
                    LEFT JOIN {$wpdb->prefix}posts p
                      ON t.element_id = p.ID
                    LEFT JOIN {$wpdb->users} u
                      ON s.translator_id = u.ID
                    WHERE {$where}
                      AND iclt.field_type = 'original_id'
                " );

		$post_types = $sitepress->get_translatable_documents( true );
		$post_types = apply_filters( 'wpml_get_translatable_types', $post_types );
		$output     = array();

		foreach ( $job_types as $job_type ) {
			$type = $job_type->original_post_type;
			$name = $type;
			switch ( $job_type->element_type_prefix ) {
				case 'post':
					$type = substr( $type, 5 );
					break;

				case 'package':
					$type = substr( $type, 8 );
					break;
			}

			$output[ $job_type->element_type_prefix . '_' . $type ] = array_key_exists( $type, $post_types )
				? $post_types[ $type ]->labels->singular_name : $name;
		}

		return $output;
	}

	private function get_job_select(
		$icl_translate_alias = 'iclt',
		$icl_translations_translated_alias = 't',
		$icl_translations_original_alias = 'ito',
		$icl_translation_status_alias = 's',
		$icl_translate_job_alias = 'j'
	) {

		return "{$icl_translate_job_alias}.rid,
				{$icl_translate_job_alias}.translator_id,
				{$icl_translations_translated_alias}.translation_id,
				{$icl_translation_status_alias}.batch_id,
				{$icl_translate_job_alias}.translated,
				{$icl_translate_job_alias}.manager_id,
				{$icl_translation_status_alias}.status,
				{$icl_translation_status_alias}.needs_update,
				{$icl_translation_status_alias}.translation_service,
				{$icl_translations_translated_alias}.trid,
				{$icl_translations_translated_alias}.language_code,
				{$icl_translations_translated_alias}.source_language_code,
				{$icl_translate_alias}.field_data AS original_doc_id,
				{$icl_translate_alias}.job_id,
				{$icl_translations_original_alias}.element_type AS original_post_type";
	}

	private function add_job_elements( $job, $include_non_translatable_elements ) {
		global $wpdb;

		$jelq = ! $include_non_translatable_elements ? ' AND field_translate = 1' : '';

		$elements = $wpdb->get_results( $wpdb->prepare( " SELECT *
                                                        FROM {$wpdb->prefix}icl_translate
                                                        WHERE job_id = %d {$jelq}
                                                        ORDER BY tid ASC",
			$job->job_id ) );

		// allow adding custom elements
		$job->elements = apply_filters( 'icl_job_elements', $elements, $job->original_doc_id, $job->job_id );

		return $job;
	}

	/**
	 * @param $job_id
	 * @param $post_id
	 *
	 * @return mixed|string|void
	 */
	private function get_external_job_post_title( $job_id, $post_id ) {
		global $wpdb;

		$title_and_name = $wpdb->get_row( $wpdb->prepare( "
													 SELECT n.field_data AS name, t.field_data AS title
													 FROM {$wpdb->prefix}icl_translate AS n
													 JOIN {$wpdb->prefix}icl_translate AS t
													  ON n.job_id = t.job_id
													 WHERE n.job_id = %d
													  AND n.field_type = 'name'
													  AND t.field_type = 'title'
													  LIMIT 1
													  ",
			$job_id ) );

		$post_title = $title_and_name !== null ? base64_decode( $title_and_name->name ? $title_and_name->name : $title_and_name->title ) : '';
		$post_title = apply_filters( 'wpml_tm_external_translation_job_title', $post_title, $post_id );

		return $post_title;
	}

	private function complete_job_data( $job, $include_non_translatable_elements, $revisions ) {
		global $sitepress, $wpdb;

		$_ld                = $sitepress->get_language_details( $job->source_language_code );
		$job->from_language = $_ld[ 'display_name' ];
		$_ld                = $sitepress->get_language_details( $job->language_code );
		$job->to_language   = $_ld[ 'display_name' ];
		$job                = $this->add_job_elements( $job, $include_non_translatable_elements );

		//do we have a previous version
		if ( $revisions > 0 ) {
			$prev_version_job_id = $wpdb->get_var( $wpdb->prepare( "
                                                                SELECT MAX(job_id)
                                                                FROM {$wpdb->prefix}icl_translate_job
                                                                WHERE rid=%d
                                                                  AND job_id < %d",
				$job->rid,
				$job->job_id ) );
			if ( $prev_version_job_id ) {
				$job->prev_version = $this->get_translation_job( $prev_version_job_id, false, $revisions - 1 );
			}
		}

		return $job;
	}
}
