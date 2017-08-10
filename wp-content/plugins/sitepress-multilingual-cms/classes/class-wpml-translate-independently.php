<?php

class WPML_Translate_Independently {

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'edit_form_top', array( $this, 'wpml_add_duplicate_identifier' ), 10, 1 );
		add_action( 'wpml_scripts_setup', array( $this, 'localize_scripts' ), PHP_INT_MAX );
		add_action( 'wp_ajax_check_duplicate', array( $this, 'wpml_translate_independently' ) );
		add_filter( 'tiny_mce_before_init', array( $this, 'add_tiny_mce_change_detection' ), 999, 1 );
	}

	public function wpml_translate_independently() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : null;
		$nonce = isset( $_POST['icl_duplciate_nonce'] ) ? sanitize_text_field( $_POST['icl_duplciate_nonce'] ) : '';

		if ( wp_verify_nonce( $nonce, 'icl_check_duplicates' ) || null === $post_id ) {
			if ( delete_post_meta( $post_id, '_icl_lang_duplicate_of' ) ) {
				wp_send_json_success( true );
			} else {
				wp_send_json_error( false );
			}
		} else {
			wp_send_json_error( false );
		}
	}

	public function wpml_add_duplicate_identifier( $post ) {
		if ( '' !== get_post_meta( $post->ID, '_icl_lang_duplicate_of', true ) ) {
			echo '<input type="hidden" id="icl-duplicate-post-nonce" name="icl-duplicate-post-nonce" value="' . wp_create_nonce( 'icl_check_duplicates' ) . '" />';
			echo '<input type="hidden" id="icl-duplicate-post" name="icl-duplicate-post" value="' . absint( $post->ID ) . '"/>';
		}
	}

	public function localize_scripts() {
		$success = _x( 'You are updating a duplicate post.', '1/2 Confirm to make duplicated translations independent', 'sitepress' ) . "\n";
		$success .= _x( 'To not lose your changes, WPML will set this post to be translated independently.', '2/2 Confirm to make duplicated translations independent', 'sitepress' ) . "\n";
		$duplicate_data = array(
			'icl_duplicate_message' => $success,
			'icl_duplicate_fail'    => __( 'Unable to remove relationship!', 'sitepress' ),
		);
		wp_localize_script( 'sitepress-post-edit', 'icl_duplicate_data', $duplicate_data );
	}

	/**
	 * Add callback to detect post editor change.
	 * @param  array $initArray
	 *
	 * @return array
	 */
	public function add_tiny_mce_change_detection( $initArray ) {
		$initArray['setup'] = 'function(ed) {
                  ed.on(\'change\', function() {
                    edit_form_change();
                  });
            }';
		return $initArray;
	}
}