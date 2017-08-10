<?php

class WPML_TM_Page_Builders_Hooks {

	/* @var WPML_TM_Page_Builders $worker */
	private $worker;

	/**
	 * WPML_TM_Page_Builders constructor.
	 *
	 * @param WPML_TM_Page_Builders $worker
	 */
	public function __construct( WPML_TM_Page_Builders $worker = null ) {
		$this->worker = $worker;
	}

	public function init_hooks() {
		add_filter( 'wpml_tm_translation_job_data',         array( $this, 'translation_job_data_filter' ), 10, 2 );
		add_filter( 'wpml_tm_populate_prev_translation',    array( $this, 'populate_prev_translation' ), 10, 3 );
		add_action( 'wpml_pro_translation_completed',       array( $this, 'pro_translation_completed_action' ), 10, 3 );
		add_filter( 'wpml_tm_adjust_translation_fields',    array( $this, 'adjust_translation_fields_filter' ), 10, 2 );
		add_filter( 'wpml_tm_job_layout',                   array( $this, 'job_layout_filter' ) );
		add_filter( 'wpml_link_to_translation',             array( $this, 'link_to_translation_filter' ), 20, 4 );
		add_filter( 'wpml_get_translatable_types',          array( $this, 'remove_shortcode_strings_type_filter' ), 11);
	}

	/**
	 * @param array $translation_package
	 * @param mixed $post
	 *
	 * @return array
	 */
	public function translation_job_data_filter( array $translation_package, $post ) {
		$worker = $this->get_worker();
		return $worker->translation_job_data_filter( $translation_package, $post );
	}

	/**
	 * @param array  $previous_translation
	 * @param array  $translation_package
	 * @param string $language
	 *
	 * @return array
	 */
	public function populate_prev_translation( array $previous_translation, array $translation_package, $language ) {
		$worker = $this->get_worker();
		return $worker->populate_prev_translation( $previous_translation, $translation_package, $language );
	}

	/**
	 * @param int      $new_post_id
	 * @param array    $fields
	 * @param stdClass $job
	 */
	public function pro_translation_completed_action( $new_post_id, array $fields, stdClass $job ) {
		$worker = $this->get_worker();
		$worker->pro_translation_completed_action( $new_post_id, $fields, $job );
	}

	/**
	 * @param array    $fields
	 * @param stdClass $job
	 *
	 * @return array
	 */
	public function adjust_translation_fields_filter( array $fields, $job ) {
		$worker = $this->get_worker();

		return $worker->adjust_translation_fields_filter( $fields, $job );
	}

	/**
	 * @param array $layout
	 *
	 * @return array
	 */
	public function job_layout_filter( array $layout ) {
		$worker = $this->get_worker();
		return $worker->job_layout_filter( $layout );
	}

	/**
	 * @param string $link
	 * @param int    $post_id
	 * @param string $lang
	 * @param int    $trid
	 *
	 * @return string
	 */
	public function link_to_translation_filter( $link, $post_id, $lang, $trid  ) {
		$worker = $this->get_worker();
		return $worker->link_to_translation_filter( $link, $post_id, $lang, $trid );
	}

	/**
	 * Remove "Page Builder ShortCode Strings" from translation dashboard filters
	 *
	 * @param array $types
	 *
	 * @return mixed
	 */
	public function remove_shortcode_strings_type_filter( $types ) {

		if ( array_key_exists( 'page-builder-shortcode-strings', $types ) ) {
			unset( $types['page-builder-shortcode-strings'] );
		}

		return $types;
	}

	/**
	 * @return WPML_TM_Page_Builders
	 */
	private function get_worker() {
		if ( ! $this->worker ) {
			$this->worker = new WPML_TM_Page_Builders();
		}

		return $this->worker;
	}
}