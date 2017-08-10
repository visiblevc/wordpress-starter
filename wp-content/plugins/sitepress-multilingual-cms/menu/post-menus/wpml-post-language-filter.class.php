<?php
require_once ICL_PLUGIN_PATH . '/menu/wpml-language-filter-bar.class.php';

class WPML_Post_Language_Filter extends WPML_Language_Filter_Bar {

	private $post_status;
	private $post_type;

	protected function sanitize_request() {
		$request_data    = parent::sanitize_request ();
		$this->post_type = $request_data[ 'req_ptype' ] ? $request_data[ 'req_ptype' ] : 'post';
		$post_statuses   = array_keys ( get_post_stati () );
		$post_status     = get_query_var ( 'post_status' );
		if ( is_string ( $post_status ) ) {
			$post_status = $post_status ? array( $post_status ) : array();
		}
		$illegal_status = array_diff ( $post_status, $post_statuses );
		$this->post_status = array_diff ( $post_status, $illegal_status );
	}

	public function register_scripts() {
		wp_register_script( 'post-edit-languages', ICL_PLUGIN_URL . '/res/js/post-edit-languages.js', array( 'jquery' ), false, true );
	}

	public function post_language_filter() {
		$this->sanitize_request();
		$this->init();
		$type = $this->post_type;

		if ( !$this->sitepress->is_translated_post_type ( $type ) ) {
			return '';
		}

		$post_edit_languages = array();
		$post_edit_languages['language_links'] = $this->language_links( $type );

		if(! $this->sitepress->get_setting( 'hide_how_to_translate' ) && $type === 'page') {
			$post_edit_languages['how_to_link'] = $this->get_how_to_link();
		}


		wp_localize_script( 'post-edit-languages', 'post_edit_languages_data', $post_edit_languages );
		wp_enqueue_script( 'post-edit-languages' );

		return $post_edit_languages;
	}

	protected function extra_conditions_snippet(){
		$extra_conditions = "";
		if ( !empty( $this->post_status ) ) {
			$status_snippet  = " AND post_status IN (" .wpml_prepare_in($this->post_status) . ") ";
			$extra_conditions .= apply_filters( '_icl_posts_language_count_status', $status_snippet );
		}

		$extra_conditions .= $this->post_status != array( 'trash' ) ? " AND post_status <> 'trash'" : '';
		$extra_conditions .= " AND post_status <> 'auto-draft' ";
		$extra_conditions .= parent::extra_conditions_snippet();

		return $extra_conditions;
	}

	protected function get_count_data( $type ) {
		$extra_conditions = $this->extra_conditions_snippet();

		return $this->wpdb->get_results( $this->wpdb->prepare("
				SELECT language_code, COUNT(p.ID) AS c
				FROM {$this->wpdb->prefix}icl_translations t
				JOIN {$this->wpdb->posts} p
					ON t.element_id=p.ID
						AND t.element_type = CONCAT('post_', p.post_type)
				WHERE p.post_type=%s {$extra_conditions}
				", $type, 'post_' . $type ) );
	}

	private function get_how_to_link() {
		return array(
			'src' => ICL_PLUGIN_URL . '/res/img/icon.png',
			'href' => 'https://wpml.org/?page_id=3416',
			'label' => __( 'How to translate', 'sitepress' ),
			'hilde_label_tooltip' => __( 'hide this', 'sitepress' ),
			'hide_label' => __( 'hide', 'sitepress' ),
			'hide_confirm' => __( 'Are you sure you want to remove this button?', 'sitepress' ),
			'hide_nonce' => wp_create_nonce( 'update_option_nonce' ),
			'hide_src' => ICL_PLUGIN_URL . '/res/img/close2.png',
		);
	}

	private function language_links( $type ) {
		$lang_links = array();

		$languages   = $this->get_counts( $type );
		$post_status = $this->post_status;
		foreach ( $this->active_languages as $code => $lang ) {
			$item = array();
			$item['type'] = $type;
			$item['statuses'] = $post_status;
			$item['code'] = $code;
			$item['name'] = $lang[ 'display_name' ];
			$item['current'] = $code === $this->current_language;
			$item['count'] = isset( $languages[ $code ] ) ? $languages[ $code ] : -1;
			$lang_links[ ] = $item;
		}

		return $lang_links;
	}
}