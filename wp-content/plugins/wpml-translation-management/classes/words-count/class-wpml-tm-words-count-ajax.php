<?php

class WPML_TM_Words_Count_AJAX extends WPML_TM_AJAX_Factory_Obsolete {
	/**
	 * @var WPML_TM_Words_Count
	 */
	private $wpml_tm_words_count;

	/**
	 * @param WPML_TM_Words_Count            $wpml_tm_words_count
	 * @param WPML_TM_Words_Count_Summary_UI $wpml_tm_words_count_summary
	 * @param WPML_WP_API                    $wpml_wp_api
	 */
	public function __construct( &$wpml_tm_words_count, &$wpml_tm_words_count_summary, &$wpml_wp_api ) {
		parent::__construct( $wpml_wp_api );

		$this->wpml_tm_words_count          = &$wpml_tm_words_count;
		$this->wpml_tm_words_count_summary = &$wpml_tm_words_count_summary;
		if ( $this->wpml_wp_api->is_ajax() ) {
			$this->add_ajax_action( 'wp_ajax_wpml_words_count_summary', array( $this, 'get_summary' ) );
			$this->init();
		}
	}

	public function get_summary() {
		$result          = false;
		$source_language = filter_input( INPUT_GET, 'source_language', FILTER_DEFAULT );
		$offset          = filter_input( INPUT_GET, 'offset', FILTER_DEFAULT );
		$valid_nonce     = check_ajax_referer( 'wpml_words_count_summary', 'nonce', false );

		if ( $valid_nonce ) {
			$rows = array();
			if ( $source_language ) {
				$rows          = $this->wpml_tm_words_count->get_summary( $source_language, $offset );
				$overall_count = array_shift( $rows );
			}

			if ( count( $rows ) && isset( $overall_count ) ) {
				$this->wpml_tm_words_count_summary->rows = $rows;
				$result                                  = $this->wpml_tm_words_count_summary->get_view()
				                                           . '<span id="wpml_tm_wc_post_ratio" style="display:none;">' . (int)($overall_count * 100) . '%</span>';
			}
		}

		if ( $result ) {
			return $this->wpml_wp_api->wp_send_json_success( $result );
		} else {
			return $this->wpml_wp_api->wp_send_json_error( 'Error!' );
		}
	}

	public function enqueue_resources( $hook_suffix ) {
		return;
	}
}