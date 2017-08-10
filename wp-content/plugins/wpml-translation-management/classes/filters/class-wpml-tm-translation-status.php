<?php

class WPML_TM_Translation_Status {
	/** @var WPML_TM_Records $tm_records */
	protected $tm_records;

	private $element_id_cache;
	private $wpml_cache;

	public function __construct( WPML_TM_Records $tm_records ) {
		$this->tm_records = $tm_records;
		$this->wpml_cache = $this->tm_records->get_new_wpml_wp_cache( 'element_translations' );
	}

	public function init() {
		add_filter(
			'wpml_translation_status',
			array( $this, 'filter_translation_status' ),
			1,
			4
		);
		add_action( 'wpml_cache_clear', array( $this, 'reload' ) );
	}

	public function filter_translation_status( $status, $trid, $target_lang_code ) {
		/** @var WPML_TM_Element_Translations $wpml_tm_element_translations */
		global $wpml_tm_element_translations;

		$cache_key  = md5( $trid . $target_lang_code );
		$found      = false;
		$new_status = $this->wpml_cache->get( $cache_key, $found );
		if ( ! $found ) {
			if ( $trid ) {
				$element_ids         = array_filter( $this->get_element_ids( $trid ) );
				$element_type_prefix = $wpml_tm_element_translations->get_element_type_prefix( $trid, $target_lang_code );
				foreach ( $element_ids as $id ) {
					if ( $this->is_in_basket( $id, $target_lang_code, $element_type_prefix ) ) {
						$new_status = ICL_TM_IN_BASKET;
						break;
					} elseif ( $job_status = $this->is_in_active_job( $id, $target_lang_code, $element_type_prefix, true ) ) {
						$new_status = $job_status;
						break;
					}
				}
				$new_status = ICL_TM_IN_BASKET !== $new_status && $wpml_tm_element_translations->is_update_needed( $trid, $target_lang_code ) ? ICL_TM_NEEDS_UPDATE : $new_status;
			}

			if ( false !== $new_status ) {
				$this->wpml_cache->set( $cache_key, $new_status );
			}
		}

		return false !== $new_status ? $new_status : $status;
	}

	public function reload(){
		$this->element_id_cache = array();
		$this->wpml_cache->flush_group_cache();
	}

	public function is_in_active_job (
		$element_id,
		$target_lang_code,
		$element_type_prefix,
		$return_status = false
	) {
		$translations = $this->tm_records->icl_translations_by_element_id_and_type_prefix( $element_id,
			$element_type_prefix )->translations();
		if ( ! isset( $translations[ $target_lang_code ] ) ) {

			return false;
		}
		$element_translated = $translations[ $target_lang_code ];
		if ( ! $element_translated->source_language_code()
		     && $element_translated->element_id() == $element_id
		) {
			$res = $return_status ? ICL_TM_COMPLETE : false;
		} else {
			$res = $this->tm_records
				->icl_translation_status_by_translation_id( $element_translated->translation_id() )
				->status();
			$res = $return_status ? $res : in_array( $res, array(
				ICL_TM_IN_PROGRESS,
				ICL_TM_WAITING_FOR_TRANSLATOR
			), true );

		}

		return $res;
	}

	private function is_in_basket( $element_id, $lang, $element_type_prefix ) {
		return TranslationProxy_Basket::anywhere_in_basket(
			$element_id,
			$element_type_prefix,
			array( $lang => 1 )
		);
	}

	private function get_element_ids( $trid ) {
		if ( ! isset( $this->element_id_cache[ $trid ] ) ) {
			$elements = $this->tm_records->get_post_translations()->get_element_translations( null, $trid );
			$elements = $elements ? $elements : $this->tm_records->get_term_translations()->get_element_translations( null, $trid );
			if ( $elements ) {
				$this->element_id_cache[ $trid ] = array_values( $elements );
			} else {
				$this->element_id_cache[ $trid ] = $this->tm_records->get_element_ids_from_trid( $trid );
			}
		}

		return $this->element_id_cache[ $trid ];
	}
}