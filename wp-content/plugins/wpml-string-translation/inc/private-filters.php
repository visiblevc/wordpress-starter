<?php

/**
 * @param array $source_languages
 *
 * @return array[]
 */
function filter_tm_source_langs( $source_languages ) {
	global $wpdb, $sitepress;

	$tm_filter = new WPML_TM_Filters( $wpdb, $sitepress );

	return $tm_filter->filter_tm_source_langs( $source_languages );
}

/**
 *
 * @param bool       $assigned_correctly
 * @param string     $string_translation_id in the format used by
 *                                          TM functionality as
 *                                          "string|{$string_translation_id}"
 * @param int        $translator_id
 * @param int|string $service
 *
 * @return bool
 */
function wpml_st_filter_job_assignment( $assigned_correctly, $string_translation_id, $translator_id, $service ) {
	global $wpdb, $sitepress;

	$tm_filter = new WPML_TM_Filters( $wpdb, $sitepress );

	return $tm_filter->job_assigned_to_filter( $assigned_correctly, $string_translation_id, $translator_id, $service );
}

/**
 * @param string $notice
 * @param array  $custom_posts
 *
 * @return string
 */
function filter_tm_cpt_dashboard_notice( $notice, $custom_posts ) {
	global $sitepress, $wpml_st_string_factory;

	$tm_filter      = new WPML_TM_Widget_Filter( $sitepress, $wpml_st_string_factory );
	$admin_notifier = new WPML_Admin_Notifier();

	return $tm_filter->filter_cpt_dashboard_notice( $notice, $custom_posts, $admin_notifier );
}

add_filter( 'wpml_tm_allowed_source_languages', 'filter_tm_source_langs', 10, 1 );
add_filter( 'wpml_tm_dashboard_cpt_notice', 'filter_tm_cpt_dashboard_notice', 10, 3 );
add_filter( 'wpml_job_assigned_to_after_assignment', 'wpml_st_filter_job_assignment', 10, 4 );

function wpml_st_blog_title_filter( $val ) {
	return icl_t( 'WP', 'Blog Title', $val );
}

function wpml_st_blog_description_filter( $val ) {

	return icl_t( 'WP', 'Tagline', $val );
}