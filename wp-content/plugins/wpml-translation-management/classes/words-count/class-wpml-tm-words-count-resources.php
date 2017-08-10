<?php

class WPML_TM_Words_Count_Resources extends WPML_TM_Resources_Factory {

	private $script_handle = 'wpml_tm_words_count';

	public function enqueue_resources( $hook_suffix ) {
		if ( $this->wpml_wp_api->is_dashboard_tab() ) {
			wp_enqueue_script( $this->script_handle );
			wp_enqueue_style( $this->script_handle );
		}
	}

	public function register_resources( $hook_suffix ) {
		if ( $this->wpml_wp_api->is_dashboard_tab() ) {
			wp_register_script( $this->script_handle, WPML_TM_URL . '/res/js/word-count/words-count-model.js', array(
				'jquery',
				'jquery-ui-accordion',
				'jquery-ui-dialog'
			) );
			wp_register_style( $this->script_handle, WPML_TM_URL . '/res/css/words-count.css' );
		}
	}
}