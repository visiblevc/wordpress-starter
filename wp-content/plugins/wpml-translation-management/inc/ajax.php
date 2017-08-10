<?php
global $wpdb;

require_once WPML_TM_PATH . '/menu/basket-tab/wpml-basket-tab-ajax.class.php';

$basket_ajax = new WPML_Basket_Tab_Ajax( TranslationProxy::get_current_project(),
                                         wpml_tm_load_basket_networking(),
                                         new WPML_Translation_Basket( $wpdb ) );
add_action( 'init', array( $basket_ajax, 'init' ) );

function icl_get_jobs_table() {
	require_once WPML_TM_PATH . '/menu/wpml-translation-jobs-table.class.php';
	global $iclTranslationManagement;

	$nonce = filter_input( INPUT_POST, 'icl_get_jobs_table_data_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	if ( !wp_verify_nonce( $nonce, 'icl_get_jobs_table_data_nonce' ) ) {
		die( 'Wrong Nonce' );
	}

	$table = new WPML_Translation_Jobs_Table($iclTranslationManagement);
	$data  = $table->get_paginated_jobs();

	wp_send_json_success( $data );
}


/**
 * Ajax handler for saving translation job field contents
 */
function wpml_save_job_ajax() {
	if ( ! wpml_is_action_authenticated( 'wpml_save_job' ) ) {
		die( 'Wrong Nonce' );
	}
	$data      = array();
	$post_data = WPML_TM_Post_Data::strip_slashes_for_single_quote( $_POST['data'] );
	parse_str( $post_data, $data );

	$job = new WPML_TM_Editor_Job_Save( );
	
	$job_details = array( 'job_type'             => $data[ 'job_post_type' ],
						  'job_id'               => $data[ 'job_post_id' ],
						  'target'               => $data[ 'target_lang' ],
						  'translation_complete' => isset( $data[ 'complete' ] ) ? true : false );
	$job = apply_filters( 'wpml-translation-editor-fetch-job',  $job, $job_details);
	
	$ajax_response = $job->save( $data );
	$ajax_response->send_json();
	
}

add_action( 'wp_ajax_wpml_save_job_ajax', 'wpml_save_job_ajax' );

/**
 * Ajax action, that populates the blue TP job status box
 */
function icl_populate_translations_pickup_box() {
	if ( ! wpml_is_action_authenticated( 'icl_populate_translations_pickup_box' ) ) {
		die( 'Wrong Nonce' );
	}
	global $sitepress;

	$factory     = new WPML_TP_Polling_Status_Factory( $sitepress );
	$project     = TranslationProxy::get_current_project();
	$ajax_action = new WPML_TP_Pickup_Box_Ajax_Action( $sitepress, $factory,
		$project );
	$result      = $ajax_action->run();
	call_user_func_array( $result[0], array( $result[1] ) );
}

function icl_pickup_translations() {
	if ( ! wpml_is_action_authenticated( 'icl_pickup_translations' ) ) {
		die( 'Wrong Nonce' );
	}
	global $ICL_Pro_Translation, $wpdb, $wpml_post_translations, $wpml_term_translations;
	$job_factory         = wpml_tm_load_job_factory();
	$wpml_tm_records     = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
	$cms_id_helper       = new WPML_TM_CMS_ID( $wpml_tm_records, $job_factory );
	$project             = TranslationProxy::get_current_project();
	$remote_sync_factory = new WPML_TP_Remote_Sync_Factory( $project,
		$ICL_Pro_Translation,
		$cms_id_helper );

	$pickup = new WPML_TP_Polling_Pickup( $ICL_Pro_Translation, $remote_sync_factory );
	wp_send_json_success( $pickup->poll_job( $_POST ) );
}

function icl_pickup_translations_complete() {
	global $sitepress;

	$sitepress->set_setting( 'last_picked_up', time(), true );
}

function icl_get_blog_users_not_translators() {
	global $iclTranslationManagement;
	$translator_drop_down_options = array();

	$nonce = filter_input( INPUT_POST, 'get_users_not_trans_nonce' );
	if ( ! wp_verify_nonce( $nonce, 'get_users_not_trans_nonce' ) ) {
		die( 'Wrong Nonce' );
	}

	$blog_users_nt = $iclTranslationManagement->get_blog_not_translators();

	foreach ( (array) $blog_users_nt as $u ) {
		$label                           = $u->display_name . ' (' . $u->user_login . ')';
		$value                           = esc_attr( $u->display_name );
		$translator_drop_down_options[ ] = array(
			'label' => $label,
			'value' => $value,
			'id'    => $u->ID
		);
	}

	wp_send_json_success( $translator_drop_down_options );
}

/**
 * Ajax handler for canceling translation Jobs.
 */
function icl_cancel_translation_jobs() {
	if ( !wpml_is_action_authenticated ( 'icl_cancel_translation_jobs' ) ) {
		die( 'Wrong Nonce' );
	}

	/** @var TranslationManagement $iclTranslationManagement */
	global $iclTranslationManagement;

	$job_ids = isset( $_POST[ 'job_ids' ] ) ? $_POST[ 'job_ids' ] : false;
	if ( $job_ids ) {
		foreach ( (array) $job_ids as $key => $job_id ) {
			$iclTranslationManagement->cancel_translation_request( $job_id );
		}
	}

	wp_send_json_success( $job_ids );
}

/**
 * Ajax action for authenticating and invalidating a translation service
 */
function wpml_tm_translation_service_authentication_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'],
			'translation_service_authentication' )
	) {
		die( 'Wrong Nonce' );
	}
	/** @var SitePress $sitepress */
	global $sitepress;

	$networking      = wpml_tm_load_tp_networking();
	$project_factory = new WPML_TP_Project_Factory();
	$auth_factory    = new WPML_TP_Service_Authentication_Factory( $sitepress,
		$networking, $project_factory );
	if ( empty( $_POST['invalidate'] ) && isset( $_POST['service_id'] ) && isset( $_POST['custom_fields'] ) ) {
		$authentication_action = new WPML_TP_Service_Authentication_Ajax_Action( $auth_factory,
			$_POST['custom_fields'] );
	} elseif ( ! empty( $_POST['invalidate'] ) ) {
		$authentication_action = new WPML_TP_Service_Invalidation_Ajax_Action( $auth_factory );
	}

	if ( ! isset( $authentication_action ) ) {
		die( 'Invalid Request' );
	}
	wp_send_json_success( $authentication_action->run() );
}

add_action( 'wp_ajax_translation_service_authentication',
	'wpml_tm_translation_service_authentication_ajax' );