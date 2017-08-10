<?php

/**
 * Class WPML_TM_CMS_ID
 */
class WPML_TM_CMS_ID extends WPML_TM_Record_User {

	private $cms_id_parts_glue = '_';
	private $cms_id_parts_fallback_glue = '|||';

	/** @var  WPML_Translation_Job_Factory $tm_job_factory */
	private $job_factory;

	/** @var wpdb $wpdb */
	private $wpdb;

	/**
	 * WPML_TM_CMS_ID constructor.
	 *
	 * @param WPML_TM_Records              $tm_records
	 * @param WPML_Translation_Job_Factory $job_factory
	 */
	public function __construct( &$tm_records, &$job_factory ) {
		parent::__construct( $tm_records );
		$this->job_factory = &$job_factory;
		$this->wpdb        = $this->tm_records->wpdb();
	}

	/**
	 * @param int    $post_id
	 * @param string $post_type
	 * @param string $source_language
	 * @param string $target_language
	 *
	 * @return string
	 */
	public function build_cms_id( $post_id, $post_type, $source_language, $target_language ) {
		$cms_id_parts = array( $post_type, $post_id, $source_language, $target_language );

		return implode( $this->cms_id_parts_glue, $cms_id_parts );
	}

	/**
	 * Returns the cms_id for a given job
	 *
	 * @param int $job_id
	 *
	 * @return false|string
	 */
	function cms_id_from_job_id( $job_id ) {
		$original_element_row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT o.element_id,
									o.element_type,
									o.language_code as source_lang,
									i.language_code as target_lang
							FROM {$this->wpdb->prefix}icl_translations o
							JOIN {$this->wpdb->prefix}icl_translations i
								ON i.trid = o.trid
									AND i.source_language_code = o.language_code
							JOIN {$this->wpdb->prefix}icl_translation_status s
								ON s.translation_id = i.translation_id
							JOIN {$this->wpdb->prefix}icl_translate_job j
								ON j.rid = s.rid
							WHERE j.job_id = %d
							LIMIT 1", $job_id ) );

		$type_parts = (bool) $original_element_row === true ? explode( '_', $original_element_row->element_type, 2 ) : false;

		return count( $type_parts ) === 2
			? $this->build_cms_id( $original_element_row->element_id, end( $type_parts ), $original_element_row->source_lang, $original_element_row->target_lang )
			: false;
	}

	/**
	 * @param string $cms_id
	 *
	 * @return array;
	 */
	public function parse_cms_id( $cms_id ) {
		if ( $this->is_standard_format( $cms_id ) ) {
			$parts = array_filter( explode( $this->cms_id_parts_glue, $cms_id ) );
			while ( count( $parts ) > 4 ) {
				$parts_copy = $parts;
				$parts[0]   = $parts_copy[0] . $this->cms_id_parts_glue . $parts_copy[1];
				unset( $parts_copy[0] );
				unset( $parts_copy[1] );
				$parts = array_merge( array( $parts[0] ), array_values( array_filter( $parts_copy ) ) );
			}
		} else {
			$parts = explode( $this->cms_id_parts_fallback_glue, $cms_id );
		}

		return array_pad( array_slice( $parts, 0, 4 ), false, 4 );
	}

	/**
	 * @param string   $cms_id
	 * @param bool|TranslationProxy_Service $translation_service
	 *
	 * @return int|null translation id for the given cms_id's target
	 */
	public function get_translation_id( $cms_id, $translation_service = false ) {
		list( $post_type, $element_id, , $target_lang ) = $this->parse_cms_id( $cms_id );
		$translation = $this->wpdb->get_row( $this->wpdb->prepare( "
													SELECT t.translation_id, j.job_id, t.element_id
													FROM {$this->wpdb->prefix}icl_translations t
													JOIN {$this->wpdb->prefix}icl_translations o
														ON o.trid = t.trid
															AND o.element_type = t.element_type
													LEFT JOIN {$this->wpdb->prefix}icl_translation_status st
														ON st.translation_id = t.translation_id
													LEFT JOIN {$this->wpdb->prefix}icl_translate_job j
														ON j.rid = st.rid
													WHERE o.element_id=%d
														AND t.language_code=%s
														AND o.element_type LIKE %s
													LIMIT 1",
			$element_id, $target_lang, '%_' . $post_type ) );
		$translation_id = $this->maybe_cleanup_broken_row( $translation, $translation_service );
		if ( $translation_service && ! isset( $translation_id ) && $translation_service ) {
			$job_id         = $this->job_factory->create_local_post_job( $element_id, $target_lang );
			$job            = $this->job_factory->get_translation_job( $job_id, false, false, true );
			$translation_id = $job ? $job->get_translation_id() : 0;
			if ( $translation_id ) {
				$this->tm_records->icl_translation_status_by_translation_id( $translation_id )->update( array(
					'status'              => ICL_TM_IN_PROGRESS,
					'translation_service' => $translation_service->id
				) );
			}
		}

		return $translation_id;
	}

	private function maybe_cleanup_broken_row( $translation, $translation_service ) {
		if ( $translation
		     && ( $translation_id = $translation->translation_id )
		     && ! $translation->element_id
		     && $translation_service
		     && ! $translation->job_id
		) {
			$this->tm_records->icl_translations_by_translation_id( $translation_id )->delete();
			$translation_id = null;
		}

		return isset( $translation_id ) ? $translation_id : null;
	}

	/**
	 * @param $cms_id
	 *
	 * @return bool
	 */
	private function is_standard_format( $cms_id ) {

		return count( array_filter( explode( $this->cms_id_parts_fallback_glue, $cms_id ) ) ) < 3;
	}
}