<?php

class WPML_TM_Translation_Status_Display {

	const BLOCKED_LINK = '###';

	private $statuses = array();

	/**
	 * @var WPML_Post_Status
	 */
	private $status_helper;

	/**
	 * @var WPML_Translation_Job_Factory
	 */
	private $job_factory;

	/**
	 * @var WPML_TM_API
	 */
	private $tm_api;

	/**
	 * @var WPML_Post_Translation
	 */
	private $post_translations;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * WPML_TM_Translation_Status_Display constructor.
	 *
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 * @param WPML_Post_Status $status_helper
	 * @param WPML_Translation_Job_Factory $job_factory
	 * @param WPML_TM_API $tm_api
	 */
	public function __construct(
		&$wpdb,
		&$sitepress,
		&$status_helper,
		&$job_factory,
		&$tm_api
	) {
		$this->post_translations = $sitepress->post_translations();
		$this->wpdb = $wpdb;
		$this->status_helper = &$status_helper;
		$this->job_factory   = &$job_factory;
		$this->tm_api        = &$tm_api;
		$this->sitepress     = $sitepress;
	}

	public function init() {
		add_action( 'wpml_cache_clear', array( $this, 'init' ), 11, 0 );
		add_filter( 'wpml_icon_to_translation', array(
			$this,
			'filter_status_icon'
		), 10, 4 );
		add_filter( 'wpml_link_to_translation', array(
			$this,
			'filter_status_link'
		), 10, 4 );
		add_filter( 'wpml_text_to_translation', array(
			$this,
			'filter_status_text'
		), 10, 4 );
		$this->statuses = array();

		$this->maybe_preload_stats();
	}

	private function maybe_preload_stats() {
		$trids = $this->post_translations->get_trids();
		$this->load_stats( $trids );
	}

	private function load_stats( $trids ) {
		$trids = implode( ',', $trids );
		$trids_query = $trids ? "i.trid IN ( {$trids} )" : '1=1';
		$stats = $this->wpdb->get_results(
			"SELECT st.status, l.code, st.translator_id, st.translation_service, i.trid
				FROM {$this->wpdb->prefix}icl_languages l
				LEFT JOIN {$this->wpdb->prefix}icl_translations i
					ON l.code = i.language_code
				JOIN {$this->wpdb->prefix}icl_translation_status st
					ON i.translation_id = st.translation_id
				WHERE l.active = 1
					AND {$trids_query}
					OR i.trid IS NULL",
			ARRAY_A
		);
		foreach ( $stats as $element ) {
			$this->statuses[ $element['trid'] ][ $element['code'] ] = $element;
		}

	}

	public function filter_status_icon( $icon, $post_id, $lang, $trid ) {
		$this->maybe_load_stats( $trid );
		$element_id  = $this->post_translations->get_element_id( $lang, $trid );
		$source_lang = $this->post_translations->get_source_lang_code( $element_id );

		if ( $this->is_in_progress( $trid, $lang ) ) {
			$icon = 'in-progress.png';
		} elseif ( $this->is_in_basket( $trid, $lang )
		           || ( ! $this->is_lang_pair_allowed( $lang, $source_lang ) && $element_id )
		) {
			$icon = 'edit_translation_disabled.png';
		} elseif ( ! $this->is_lang_pair_allowed( $lang, $source_lang ) && ! $element_id ) {
			$icon = 'add_translation_disabled.png';
		}

		return $icon;
	}

	public function filter_status_text( $text, $original_post_id, $lang, $trid ) {
		$this->maybe_load_stats( $trid );
		if ( $this->is_remote( $trid, $lang ) ) {
			$language = $this->sitepress->get_language_details( $lang );
			$text     = sprintf(
				__(
					"You can't edit this translation, because this translation to %s is already in progress.",
					'sitepress'
				),
				$language['display_name']
			);

		} elseif ( $this->is_in_basket( $trid, $lang ) ) {
			$text = __(
				'Cannot edit this item, because it is currently in the translation basket.',
				'sitepress'
			);
		} elseif ( $this->is_lang_pair_allowed( $lang ) && $this->is_in_progress( $trid, $lang ) ) {
			$language = $this->sitepress->get_language_details( $lang );
			$text     = sprintf( __( 'Edit the %s translation', 'sitepress' ), $language['display_name'] );
		}

		return $text;
	}

	/**
	 * @param string $link
	 * @param int $post_id
	 * @param string $lang
	 * @param int $trid
	 *
	 * @return string
	 */
	public function filter_status_link( $link, $post_id, $lang, $trid ) {
		$translated_element_id = $this->post_translations->get_element_id( $lang,
			$trid );
		$source_lang = $this->post_translations->get_source_lang_code( $translated_element_id );
		if ( (bool) $translated_element_id
		     && (bool) $source_lang === false
		) {
			return $link;
		}
		$this->maybe_load_stats( $trid );
		$is_remote        = $this->is_remote( $trid, $lang );
		$is_in_progress   = $this->is_in_progress( $trid, $lang );
		$use_tm_editor    = $this->is_using_tm_editor();
		$use_tm_editor    = apply_filters( 'wpml_use_tm_editor', $use_tm_editor );
		$source_lang_code = $this->post_translations->get_element_lang_code( $post_id );
		if ( ( $is_remote && $is_in_progress ) || $this->is_in_basket( $trid,
				$lang ) || ! $this->is_lang_pair_allowed( $lang, $source_lang )
		) {
			$link = self::BLOCKED_LINK;
		} elseif ( $source_lang_code !== $lang ) {
			if ( ( $is_in_progress && ! $is_remote ) || ( $use_tm_editor && $translated_element_id ) ) {
				$job_id = $this->job_factory->job_id_by_trid_and_lang( $trid, $lang );
				if ( $job_id ) {
					$link = $this->get_link_for_existing_job( $job_id );
				} else {
					$link = $this->get_link_for_new_job( $trid, $lang, $source_lang_code );
				}
			} elseif ( $use_tm_editor && ! $translated_element_id ) {
				$link = $this->get_link_for_new_job( $trid, $lang, $source_lang_code );
			}
		}

		return $link;
	}

	private function is_using_tm_editor() {
		$tm_settings = $this->sitepress->get_setting( 'translation-management' );
		$sitepress_settings = $this->sitepress->get_settings();
		$use_tm_editor = 0;

		if( array_key_exists( 'doc_translation_method', $tm_settings ) ) {
			$use_tm_editor = $tm_settings['doc_translation_method'];
		} elseif ( array_key_exists( 'doc_translation_method', $sitepress_settings ) ) {
			$use_tm_editor = $this->sitepress->get_setting( 'doc_translation_method' );
		}

		return $use_tm_editor;
	}

	private function get_link_for_new_job( $trid, $lang, $source_lang_code ) {
		$args = array(
			'trid'                 => $trid,
			'language_code'        => $lang,
			'source_language_code' => $source_lang_code
		);

		return add_query_arg( $args, $this->get_tm_editor_base_url() );
	}

	private function get_link_for_existing_job( $job_id ) {
		$args = array( 'job_id' => $job_id );

		return add_query_arg( $args, $this->get_tm_editor_base_url() );
	}

	private function get_tm_editor_base_url() {
		$args = array(
			'page'       => WPML_TM_FOLDER . '/menu/translations-queue.php',
			'return_url' => rawurlencode( esc_url_raw( stripslashes( $this->get_return_url() ) ) )
		);

		return add_query_arg( $args, 'admin.php' );
	}

	private function get_return_url() {
		$args = array( 'wpml_tm_saved', 'wpml_tm_cancel' );

		return remove_query_arg( $args );
	}

	/**
	 * @param string $lang_to
	 * @param string $lang_from
	 *
	 * @return bool
	 */
	private function is_lang_pair_allowed( $lang_to, $lang_from = null ) {

		return $this->tm_api->is_translator_filter(
			false, $this->sitepress->get_wp_api()->get_current_user_id(),
			array(
				'lang_from'      => $lang_from ? $lang_from : $this->sitepress->get_current_language(),
				'lang_to'        => $lang_to,
				'admin_override' => $this->is_current_user_admin(),
			) );
	}

	private function is_current_user_admin() {

		return $this->sitepress->get_wp_api()
		                       ->current_user_can( 'manage_options' );
	}

	/**
	 * @todo make this into a proper active record user
	 *
	 * @param int $trid
	 */
	private function maybe_load_stats( $trid ) {
		if ( ! isset( $this->statuses[ $trid ] ) ) {
			$this->statuses[ $trid ] = array();
			$this->load_stats( array( $trid ) );
		}
	}

	private function is_remote( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ]['translation_service'] )
		       && (bool) $this->statuses[ $trid ][ $lang ]['translation_service'] !== false
		       && $this->statuses[ $trid ][ $lang ]['translation_service'] !== 'local';
	}

	private function is_in_progress( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ]['status'] )
		       && ( $this->statuses[ $trid ][ $lang ]['status'] == ICL_TM_IN_PROGRESS
		            || $this->statuses[ $trid ][ $lang ]['status'] == ICL_TM_WAITING_FOR_TRANSLATOR );
	}

	private function is_wrong_translator( $trid, $lang ) {

		return isset( $this->statuses[ $trid ][ $lang ]['translator_id'] )
		       && $this->statuses[ $trid ][ $lang ]['translator_id']
		          != $this->sitepress->get_wp_api()->get_current_user_id()
		       && ! $this->is_current_user_admin();
	}

	private function is_in_basket( $trid, $lang ) {

		return $this->status_helper
			       ->get_status( false, $trid, $lang ) === ICL_TM_IN_BASKET;
	}
}