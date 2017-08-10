<?php

/**
 * @return WPML_TM_Element_Translations
 */
function wpml_tm_load_element_translations() {
	global $wpml_tm_element_translations, $wpdb, $wpml_post_translations, $wpml_term_translations;

	if ( ! isset( $wpml_tm_element_translations ) ) {
		require_once WPML_TM_PATH . '/inc/core/wpml-tm-element-translations.class.php';
		$tm_records                   = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$wpml_tm_element_translations = new WPML_TM_Element_Translations( $tm_records );
		$wpml_tm_element_translations->init_hooks();
	}

	return $wpml_tm_element_translations;
}

function wpml_tm_load_status_display_filter() {
	global $wpml_tm_status_display_filter, $iclTranslationManagement, $sitepress, $wpdb;

	$blog_translators = wpml_tm_load_blog_translators();
	$tm_api           = new WPML_TM_API( $blog_translators, $iclTranslationManagement );
	$tm_api->init_hooks();
	if ( ! isset( $wpml_tm_status_display_filter ) ) {
		$status_helper                 = wpml_get_post_status_helper();
		$job_factory                   = wpml_tm_load_job_factory();
		$wpml_tm_status_display_filter = new WPML_TM_Translation_Status_Display(
			$wpdb,
			$sitepress,
			$status_helper,
			$job_factory,
			$tm_api
		);
	}

	$wpml_tm_status_display_filter->init();
}

/**
 * @return WPML_Translation_Proxy_Basket_Networking
 */
function wpml_tm_load_basket_networking() {
	global $iclTranslationManagement, $wpdb;

	require_once WPML_TM_PATH . '/inc/translation-proxy/wpml-translationproxy-basket-networking.class.php';

	$basket = new WPML_Translation_Basket( $wpdb );

	return new WPML_Translation_Proxy_Basket_Networking( $basket, $iclTranslationManagement );
}

/**
 * @return WPML_Translation_Proxy_Networking
 */
function wpml_tm_load_tp_networking() {
	global $wpml_tm_tp_networking;

	if ( ! isset( $wpml_tm_tp_networking ) ) {
		$wpml_tm_tp_networking = new WPML_Translation_Proxy_Networking( new WP_Http() );
	}

	return $wpml_tm_tp_networking;
}

/**
 * @return WPML_TM_Blog_Translators
 */
function wpml_tm_load_blog_translators() {
	global $wpdb, $sitepress, $wpml_post_translations, $wpml_term_translations;

	$tm_records = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );

	return new WPML_TM_Blog_Translators( $sitepress, $tm_records );
}

/**
 * @return WPML_TM_Mail_Notification
 */
function wpml_tm_init_mail_notifications() {
	global $wpml_tm_mailer, $sitepress, $wpdb, $iclTranslationManagement, $wpml_translation_job_factory;

	if ( ! isset( $wpml_tm_mailer ) ) {
		require_once WPML_TM_PATH . '/inc/local-translation/wpml-tm-mail-notification.class.php';
		$blog_translators         = wpml_tm_load_blog_translators();
		$iclTranslationManagement = $iclTranslationManagement ? $iclTranslationManagement : wpml_load_core_tm();
		if ( empty( $iclTranslationManagement->settings ) ) {
			$iclTranslationManagement->init();
		}
		$settings                 = isset( $iclTranslationManagement->settings['notification'] )
			? $iclTranslationManagement->settings['notification'] : array();
		$wpml_tm_mailer           = new WPML_TM_Mail_Notification( $sitepress,
		                                                           $wpdb,
		                                                           $wpml_translation_job_factory,
		                                                           $blog_translators,
		                                                           $settings );
	}
	$wpml_tm_mailer->init();

	return $wpml_tm_mailer;
}

/**
 * @return WPML_Dashboard_Ajax
 */
function wpml_tm_load_tm_dashboard_ajax(){
	global $wpml_tm_dashboard_ajax;

	if ( ! isset( $wpml_tm_dashboard_ajax ) ) {
		require_once WPML_TM_PATH . '/menu/dashboard/wpml-tm-dashboard-ajax.class.php';
		$wpml_tm_dashboard_ajax = new WPML_Dashboard_Ajax();

		if ( defined( 'OTG_TRANSLATION_PROXY_URL' ) ) {
			$wpml_tp_communication = new WPML_TP_Communication( OTG_TRANSLATION_PROXY_URL, new WP_Http() );
			$wpml_tp_api           = new WPML_TP_API( $wpml_tp_communication, '1.1', new WPML_TM_Log() );
			new WPML_TP_API_AJAX( $wpml_tp_api );
		}
	}

	return $wpml_tm_dashboard_ajax;
}

/**
 * @return WPML_Translation_Job_Factory
 */
function wpml_tm_load_job_factory() {
	global $wpml_translation_job_factory, $wpdb, $wpml_post_translations, $wpml_term_translations;

	if ( ! isset( $wpml_translation_job_factory ) ) {
		$tm_records                   = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$wpml_translation_job_factory = new WPML_Translation_Job_Factory( $tm_records );
		$wpml_translation_job_factory->init_hooks();
	}

	return $wpml_translation_job_factory;
}

if ( defined( 'DOING_AJAX' ) ) {
	$wpml_tm_dashboard_ajax = wpml_tm_load_tm_dashboard_ajax();
	add_action( 'init', array( $wpml_tm_dashboard_ajax, 'init_ajax_actions' ) );
} elseif ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] == WPML_TM_FOLDER . '/menu/main.php'
           && ( ! isset( $_GET['sm'] ) || $_GET['sm'] === 'dashboard' )
) {
	$wpml_tm_dashboard_ajax = wpml_tm_load_tm_dashboard_ajax();
	add_action( 'wpml_tm_scripts_enqueued', array( $wpml_tm_dashboard_ajax, 'enqueue_js' ) );
}

function tm_after_load() {
	global $wpml_tm_translation_status, $wpdb, $wpml_post_translations, $wpml_term_translations;

	if ( ! isset( $wpml_tm_translation_status ) ) {
		require_once WPML_TM_PATH . '/inc/actions/wpml-tm-action-helper.class.php';
		require_once WPML_TM_PATH . '/inc/translation-jobs/collections/wpml-abstract-job-collection.class.php';
		require_once WPML_TM_PATH . '/inc/translation-proxy/wpml-translation-basket.class.php';
		require_once WPML_TM_PATH . '/inc/translation-jobs/wpml-translation-batch.class.php';
		require_once WPML_TM_PATH . '/inc/translation-proxy/translationproxy.class.php';
		require_once WPML_TM_PATH . '/inc/ajax.php';
		wpml_tm_load_job_factory();
		wpml_tm_init_mail_notifications();
		wpml_tm_load_element_translations();
		$tm_records                 = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$wpml_tm_translation_status = new WPML_TM_Translation_Status( $tm_records );
		$wpml_tm_translation_status->init();
		add_action( 'wpml_pre_status_icon_display', 'wpml_tm_load_status_display_filter' );
		require_once WPML_TM_PATH . '/inc/wpml-private-actions.php';
	}
}

/**
 * @return WPML_TM_Records
 */
function wpml_tm_get_records() {
	global $wpdb, $wpml_post_translations, $wpml_term_translations;

	return new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
}

/**
 * @return WPML_TM_Xliff_Frontend
 */
function setup_xliff_frontend() {
	global $sitepress;

	$job_factory    = wpml_tm_load_job_factory();
	$xliff_frontend = new WPML_TM_Xliff_Frontend( $job_factory, $sitepress );
	add_action( 'init', array( $xliff_frontend, 'init' ), $xliff_frontend->get_init_priority() );

	return $xliff_frontend;
}

if ( defined( 'WPML_ST_VERSION' ) ) {
	add_action( 'wpml_st_below_menu', array( 'WPML_Remote_String_Translation', 'display_string_menu' ) );
	//Todo: [WPML 3.3] this needs to be moved to ST plugin
	add_action( 'wpml_tm_send_string_jobs', array( 'WPML_Remote_String_Translation', 'send_strings_jobs' ), 10, 5 );
}