<?php

class WPML_TM_Records {
	/** @var WPDB $wpdb */
	public $wpdb;

	/** @var array $cache */
	private $cache = array(
		'icl_translations' => array(),
		'status'           => array()
	);

	private $preloaded_statuses = null;

	/** @var  WPML_Frontend_Post_Actions | WPML_Admin_Post_Actions $wpml_post_translations */
	private $wpml_post_translations;

	/** @var WPML_Term_Translation $wpml_term_translations */
	private $wpml_term_translations;

	public function __construct( $wpdb, $wpml_post_translations, $wpml_term_translations ) {
		$this->wpdb                   = &$wpdb;
		$this->wpml_post_translations = $wpml_post_translations;
		$this->wpml_term_translations = $wpml_term_translations;
	}

	public function wpdb() {
		return $this->wpdb;
	}

	public function get_new_wpml_wp_cache( $group = '' ) {
		return new WPML_WP_Cache( $group );
	}

	public function get_post_translations() {
		return $this->wpml_post_translations;
	}

	public function get_term_translations() {
		return $this->wpml_term_translations;
	}

	/**
	 * @param int $translation_id
	 *
	 * @return WPML_TM_ICL_Translation_Status
	 */
	public function icl_translation_status_by_translation_id( $translation_id ) {

		if ( ! isset( $this->cache['status'][ $translation_id ] ) ) {
			$this->maybe_preload_translation_statuses();
			$this->cache['status'][ $translation_id ] = new WPML_TM_ICL_Translation_Status( $this->wpdb, $this, $translation_id );
		}

		return $this->cache['status'][ $translation_id ];
	}

	private function maybe_preload_translation_statuses() {
		if ( null === $this->preloaded_statuses ) {
			$translation_ids = $this->wpml_post_translations->get_translations_ids();
			if ( $translation_ids ) {
				$translation_ids          = implode( ',', $translation_ids );
				$this->preloaded_statuses = $this->wpdb->get_results(
					"SELECT *
					FROM {$this->wpdb->prefix}icl_translation_status
					WHERE translation_id in ({$translation_ids})"
				);
			} else {
				$this->preloaded_statuses = array();
			}
		}
	}

	public function get_preloaded_translation_status( $translation_id, $rid ) {
		$data = null;
		if ( $this->preloaded_statuses ) {
			foreach ( $this->preloaded_statuses as $status ) {
				if ( $translation_id && $status->translation_id == $translation_id ) {
					$data = $status;
					break;
				} elseif ( $rid && $status->translation_id == $rid ) {
					$data = $status;
					break;
				}
			}
		}

		return $data;
	}

	/**
	 * @param int $rid
	 *
	 * @return WPML_TM_ICL_Translation_Status
	 */
	public function icl_translation_status_by_rid( $rid ) {

		return new WPML_TM_ICL_Translation_Status( $this->wpdb, $this, $rid, 'rid' );
	}

	/**
	 * @param int $job_id
	 *
	 * @return WPML_TM_ICL_Translate_Job
	 */
	public function icl_translate_job_by_job_id( $job_id ) {

		return new WPML_TM_ICL_Translate_Job( $this, $job_id );
	}

	/**
	 * @param int $translation_id
	 *
	 * @return WPML_TM_ICL_Translations
	 */
	public function icl_translations_by_translation_id( $translation_id ) {

		return new WPML_TM_ICL_Translations( $this, $translation_id );
	}

	/**
	 * @param int $element_id
	 * @param string $type_prefix
	 *
	 * @return WPML_TM_ICL_Translations
	 */
	public function icl_translations_by_element_id_and_type_prefix(
		$element_id,
		$type_prefix
	) {
		$key = md5( $element_id . $type_prefix );
		if ( ! isset( $this->cache['icl_translations'][ $key ] ) ) {
			$this->cache['icl_translations'][ $key ] = new WPML_TM_ICL_Translations( $this,
				array(
					'element_id'  => $element_id,
					'type_prefix' => $type_prefix
				), 'id_type_prefix' );
		}

		return $this->cache['icl_translations'][ $key ];
	}

	/**
	 * @param int $trid
	 * @param string $lang
	 *
	 * @return WPML_TM_ICL_Translations
	 */
	public function icl_translations_by_trid_and_lang( $trid, $lang ) {
		$key = md5( $trid . $lang );
		if ( ! isset( $this->cache['icl_translations'][ $key ] ) ) {
			$this->cache['icl_translations'][ $key ] = new WPML_TM_ICL_Translations( $this,
				array(
					'trid'          => $trid,
					'language_code' => $lang
				), 'trid_lang' );
		}

		return $this->cache['icl_translations'][ $key ];
	}

	/**
	 * @param int $trid
	 *
	 * @return int[]
	 */	public function get_element_ids_from_trid( $trid ) {
		return $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT element_id
				 FROM {$this->wpdb->prefix}icl_translations
				 WHERE trid = %d",
				$trid )
		);
	}

}