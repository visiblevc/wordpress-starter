<?php

function icl_reset_wpml( $blog_id = false ) {
	global $wpdb, $sitepress_settings;

	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'resetwpml' ) {
		check_admin_referer( 'resetwpml' );
	}

	if ( empty( $blog_id ) ) {
	    $filtered_id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
		$filtered_id = $filtered_id ? $filtered_id : filter_input( INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
        $blog_id = $filtered_id !== false ? $filtered_id : $wpdb->blogid;
	}

	if ( $blog_id || ! function_exists( 'is_multisite' ) || ! is_multisite() ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			switch_to_blog( $blog_id );
		}

		do_action( 'wpml_reset_plugins_before' );

		wp_clear_scheduled_hook( 'update_wpml_config_index' );

		$icl_tables = array(
			$wpdb->prefix . 'icl_languages',
			$wpdb->prefix . 'icl_languages_translations',
			$wpdb->prefix . 'icl_translations',
			$wpdb->prefix . 'icl_translation_status',
			$wpdb->prefix . 'icl_translate_job',
			$wpdb->prefix . 'icl_translate',
			$wpdb->prefix . 'icl_locale_map',
			$wpdb->prefix . 'icl_flags',
			$wpdb->prefix . 'icl_content_status',
			$wpdb->prefix . 'icl_core_status',
			$wpdb->prefix . 'icl_node',
			$wpdb->prefix . 'icl_strings',
			$wpdb->prefix . 'icl_string_packages',
			$wpdb->prefix . 'icl_translation_batches',
			$wpdb->prefix . 'icl_string_translations',
			$wpdb->prefix . 'icl_string_status',
			$wpdb->prefix . 'icl_string_positions',
			$wpdb->prefix . 'icl_message_status',
			$wpdb->prefix . 'icl_reminders',
		);

		foreach ( $icl_tables as $icl_table ) {
			$wpdb->query( "DROP TABLE IF EXISTS " . $icl_table );
		}

		delete_option( 'icl_sitepress_settings' );
		delete_option( 'icl_sitepress_version' );
		delete_option( '_icl_cache' );
		delete_option( '_icl_admin_option_names' );
		delete_option( 'wp_icl_translators_cached' );
		delete_option( 'wpml32_icl_non_translators_cached' );
		delete_option( 'WPLANG' );
		delete_option( 'wpml-package-translation-db-updates-run' );
		delete_option( 'wpml-package-translation-refresh-required' );
		delete_option( 'wpml-package-translation-string-packages-table-updated' );
		delete_option( 'wpml-package-translation-string-table-updated' );
		delete_option( 'icl_translation_jobs_basket' );
		delete_option( 'widget_icl_lang_sel_widget' );
		delete_option( 'icl_admin_messages' );
		delete_option( 'icl_adl_settings' );
		delete_option( 'wpml_tp_com_log' );
		delete_option( 'wpml_config_index' );
		delete_option( 'wpml_config_index_updated' );
		delete_option( 'wpml_config_files_arr' );
		delete_option( 'wpml_language_switcher' );

		$sitepress_settings = null;
		wp_cache_init();

		$wpml_cache_directory = new WPML_Cache_Directory( new WPML_WP_API() );
		$wpml_cache_directory->remove();

		do_action( 'wpml_reset_plugins_after' );
		
		$wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
		if ( ! isset( $wpmu_sitewide_plugins[ ICL_PLUGIN_FOLDER . '/sitepress.php' ] ) ) {
			$file = plugin_basename( WP_PLUGIN_DIR . '/' . ICL_PLUGIN_FOLDER . '/sitepress.php' );
			remove_action( 'deactivate_' . $file, 'icl_sitepress_deactivate' );
			deactivate_plugins( basename( ICL_PLUGIN_PATH ) . '/sitepress.php' );
			$ra                                                   = get_option( 'recently_activated' );
			$ra[ basename( ICL_PLUGIN_PATH ) . '/sitepress.php' ] = time();
			update_option( 'recently_activated', $ra );
		} else {
			update_option( '_wpml_inactive', true );
		}

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			restore_current_blog();
		}
	}
}

/**
 * Ajax handler for type assignment fix troubleshoot action
 */
function icl_repair_broken_type_and_language_assignments() {
	global $sitepress;

	$lang_setter = new WPML_Fix_Type_Assignments( $sitepress );
	$rows_fixed  = $lang_setter->run();

	wp_send_json_success( $rows_fixed );
}