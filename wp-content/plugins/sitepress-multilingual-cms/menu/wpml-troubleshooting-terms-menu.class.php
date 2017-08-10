<?php

class WPML_Troubleshooting_Terms_Menu {

	/**
	 * Displays the admin notice informing about terms in the old format, using the language suffix.
	 * The notice is displayed until it is either dismissed or the update button is pressed.
	 */
	public static function display_terms_with_suffix_admin_notice() {
		global $sitepress;
		if ( ! $sitepress->get_setting( 'taxonomy_names_checked' ) ) {
			$suffix_count = count( WPML_Terms_Translations::get_all_terms_with_language_suffix() );
			if ( $suffix_count > 0 ) {
				$message = '<p>';
				$message .= sprintf( __( "This version of WPML allows the use of taxonomy terms with the same name across multiple languages. Your site currently has %d taxonomy terms that require an update, so they will not be displayed with language suffixes.", "sitepress" ), $suffix_count );
				$message .= '</p>';
				if ( defined( 'ICL_PLUGIN_URL' ) ) {
					$message .= '<p><a href="' . admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php#termsuffixupdate' ) . '"><button class="button-primary">Open terms update page</button></a>';
				}

				ICL_AdminNotifier::addMessage( "termssuffixnotice", $message, 'error', true, false, false, 'terms-suffix', true );
			}
			$sitepress->set_setting( 'taxonomy_names_checked', true, true );
		}


		//TODO: [WPML 3.3] the ICL_AdminNotifier class got improved and we should not call \ICL_AdminNotifier::displayMessages to display an admin notice
		ICL_AdminNotifier::displayMessages( 'terms-suffix' );
	}

	/**
	 * Returns the HTML for the display of all terms with a language suffix in the troubleshooting menu.
	 * @return string
	 */
	public static function display_terms_with_suffix() {

		$terms_to_display = WPML_Terms_Translations::get_all_terms_with_language_suffix();

		$output = '';

		if ( ! empty( $terms_to_display ) ) {

			$output = '<div class="icl_cyan_box">';
			$output .= '<table class="widefat" id="icl-updated-term-names-table">';
			$output .= '<a name="termsuffixupdate"></a>';
			$output .= '<tr><h3>' . __( "Remove language suffixes from taxonomy names.", 'sitepress' ) . '</h3></tr>';

			$output .= '<tr id="icl-updated-term-names-headings"><th></th><th>' . __( "Old Name", "sitepress" ) . '</th><th>' . __( "Updated Name", "sitepress" ) . '</th><th>' . __( "Affected Taxonomies", "sitepress" ) . '</th></tr>';

			foreach ( $terms_to_display as $term_id => $term ) {

				$updated_term_name = self::strip_language_suffix( $term[ 'name' ] );

				$output .= '<tr class="icl-term-with-suffix-row"><td>';
				$output .= '<input type="checkbox" checked="checked" name="' . $updated_term_name . '" value="' . $term_id . '"/>';
				$output .= '</td>';
				$output .= '<td>' . $term[ 'name' ] . '</td>';
				$output .= '<td id="term_' . $term_id . '">' . $updated_term_name . '</td>';
				$output .= '<td>' . join( ', ', $term[ 'taxonomies' ] ) . '</td>';
				$output .= '</tr>';
			}
			$output .= '</table>';

			$output .= '</br></br>';
			$output .= '<button id="icl-update-term-names" class="button-primary">' . __( 'Update term names', 'sitepress' ) . '</button>';
			$output .= '<button id="icl-update-term-names-done" class="button-primary" disabled="disabled" style="display:none;">' . __( 'All term names updated', 'sitepress' ) . '</button>';
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * @param string $term_name
	 * Strips a term off all language suffixes in the form @<lang_code> on it.
	 *
	 * @return string
	 */
	public static function strip_language_suffix( $term_name ) {
		global $wpdb;

		$lang_codes = $wpdb->get_col( "SELECT code FROM {$wpdb->prefix}icl_languages" );

		$new_name_parts = explode( ' @', $term_name );

		$new_name_parts = array_filter( $new_name_parts );

		$last_part = array_pop( $new_name_parts );

		while ( in_array( $last_part, $lang_codes ) ) {
			$last_part = array_pop( $new_name_parts );
		}

		$new_name = '';
		if ( ! empty( $new_name_parts ) ) {
			$new_name = join( ' @', $new_name_parts ) . ' @';
		}

		$new_name .= $last_part;

		return $new_name;
	}

	/**
	 * Ajax handler for the troubleshoot page. Updates the term name on those terms given via the Ajax action.
	 */
	public static function wpml_update_term_names_troubleshoot() {
		global $wpdb;
		ICL_AdminNotifier::removeMessage( 'termssuffixnotice' );

		$term_names = array();

		$nonce = filter_input( INPUT_POST, '_icl_nonce', FILTER_SANITIZE_STRING );
		if ( !wp_verify_nonce( $nonce, 'update_term_names_nonce' ) ) {
			die( 'Wrong Nonce' );
		}

		$request_post_terms = filter_input(INPUT_POST, 'terms');
		if ( $request_post_terms ) {
			$term_names = json_decode( stripcslashes( $request_post_terms ) );
			if ( ! is_object( $term_names ) ) {
				$term_names = array();
			}
		}

		$updated = array();

		foreach ( $term_names as $term_id => $new_name ) {
			$res = $wpdb->update( $wpdb->terms, array( 'name' => $new_name ), array( 'term_id' => $term_id ) );
			if ( $res ) {
				$updated[ ] = $term_id;
			}
		}

		wp_send_json_success( $updated );
	}
}