<?php

abstract class WPML_Admin_Text_Functionality {

	protected final function is_blacklisted( $option_name ) {
		global $wp_taxonomies;

		$black_list = array_fill_keys( array(
			                               'active_plugins',
			                               'wp_user_roles',
			                               '_wpml_media',
			                               'users_can_register',
			                               'admin_email',
			                               'start_of_week',
			                               'use_balanceTags',
			                               'use_smilies',
			                               'require_name_email',
			                               'comments_notify',
			                               'posts_per_rss',
			                               'sticky_posts',
			                               'page_for_post',
			                               '_wcml_settings',
			                               '_wcml_version',
			                               'page_on_front',
			                               'default_post_format',
			                               'link_manager_enabled',
			                               'icl_sitepress_settings',
			                               'wpml_config_index',
			                               'wpml_config_index_updated',
			                               'wpml_config_files_arr',
			                               'icl_admin_messages',
			                               'wpml-package-translation-db-updates-run',
			                               'wpml_media',
			                               'wpml_ta_settings',
			                               '_icl_admin_option_names',
			                               '_icl_cache',
			                               'icl_sitepress_version',
			                               'rewrite_rules',
			                               'recently_activated',
			                               'wpml_tm_version',
			                               'wp_installer_settings',
			                               'icl_adl_settings',
			                               'rss_use_excerpt',
			                               'template',
			                               'stylesheet',
			                               'comment_whitelist',
			                               'comment_registration',
			                               'html_type',
			                               'use_trackback',
			                               'default_role',
			                               'db_version',
			                               'siteurl',
			                               'home',
			                               'blogname',
			                               'blogdescription',
			                               'mailserver_url',
			                               'mailserver_login',
			                               'mailserver_pass',
			                               'mailserver_port',
			                               'default_category',
			                               'default_comment_status',
			                               'default_ping_status',
			                               'default_pingback_flag',
			                               'comment_moderation',
			                               'moderation_notify',
			                               'permalink_structure',
			                               'gzipcompression',
			                               'hack_file',
			                               'blog_charset',
			                               'ping_sites',
			                               'advanced_edit',
			                               'comment_max_links',
			                               'gmt_offset',
			                               'default_email_category',
			                               'uploads_use_yearmonth_folders',
			                               'upload_path',
			                               'blog_public',
			                               'default_link_category',
			                               'tag_base',
			                               'show_avatars',
			                               'avatar_rating',
			                               'WPLANG',
			                               'wp_icl_translators_cached',
			                               'cron',
			                               '_transient_WPML_ST_MO_Downloader_lang_map',
			                               'icl_translation_jobs_basket'
		                               ),
		                               1 );

		$tax_prefixes = array_keys( $wp_taxonomies );
		foreach ( $tax_prefixes as &$tax_name ) {
			$tax_name .= '_children';
		}
		$blacklist_prefixes = array_merge( $tax_prefixes,
		                                   array( '_transient_', '_site_transient_' ) );
		$matcher            = '#^' . join( '|^', $blacklist_prefixes ) . '#';

		return array_key_exists( $option_name, $black_list )
		       || preg_match( $matcher, $option_name ) === 1;
	}

	protected function read_admin_texts_recursive( $keys, $admin_text_context, $type, &$arr_context, &$arr_type ) {
		$keys = ! empty( $keys ) && isset( $keys ['attr']['name'] ) ? array( $keys ) : $keys;
		foreach ( $keys as $key ) {
			$key_name = $key['attr']['name'];
			if ( ! empty( $key['key'] ) ) {
				$arr[ $key_name ] = $this->read_admin_texts_recursive( $key['key'],
				                                                       $admin_text_context,
				                                                       $type,
				                                                       $arr_context,
				                                                       $arr_type );
			} else {
				$arr[ $key_name ]         = 1;
				$arr_context[ $key_name ] = $admin_text_context;
				$arr_type[ $key_name ]    = $type;
			}
		}

		return isset( $arr ) ? $arr : false;
	}

	/**
	 * @param string $key     Name of option to retrieve. Expected to not be SQL-escaped.
	 * @param mixed  $default Value to return in case the string does not exists
	 *
	 * @return mixed Value set for the option.
	 */
	protected function get_option_without_filtering( $key, $default = false ) {
		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value
												FROM {$wpdb->options}
												WHERE option_name = %s
												LIMIT 1",
		                                         $key ) );

		return isset( $value ) ? $value : $default;
	}
}