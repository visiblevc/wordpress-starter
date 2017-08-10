<?php
require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-translation-job-helper.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-translation-job-helper-with-api.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/wpml-translation-jobs-collection.class.php';
require_once WPML_TM_PATH . '/inc/translation-jobs/helpers/wpml-save-translation-data-action.class.php';

function wpml_tm_save_job_fields_from_post( $job_id ) {
	$job = new WPML_Post_Translation_Job( $job_id );
	$job->update_fields_from_post();
}

add_action( 'wpml_save_job_fields_from_post', 'wpml_tm_save_job_fields_from_post', 10, 1 );

/**
 * @param array $data
 */
function wpml_tm_save_data( array $data ) {
	global $wpml_translation_job_factory;

	$save_factory     = new WPML_TM_Job_Action_Factory( $wpml_translation_job_factory );
	$save_data_action = $save_factory->save_action( $data );
	$save_data_action->save_translation();
	$redirect_target = $save_data_action->get_redirect_target();
	if ( (bool) $redirect_target === true ) {
		wp_redirect( $redirect_target );
	}
}

add_action( 'wpml_save_translation_data', 'wpml_tm_save_data', 10, 1 );

function wpml_tm_add_translation_job( $rid, $translator_id, $translation_package ) {

	$helper = new WPML_TM_Action_Helper();
	$helper->add_translation_job( $rid,
	                              $translator_id,
	                              $translation_package );
}

add_action( 'wpml_add_translation_job', 'wpml_tm_add_translation_job', 10, 3 );

require_once dirname( __FILE__ ) . '/wpml-private-filters.php';

function wpml_set_job_translated_term_values( $job_id, $delete = false ) {

	$job_object = new WPML_Post_Translation_Job( $job_id );
	$job_object->load_terms_from_post_into_job( $delete );
}

add_action( 'wpml_added_local_translation_job', 'wpml_set_job_translated_term_values', 10, 2 );

function wpml_tm_save_post( $post_id, $post, $force_set_status ) {
	global $wpdb, $wpml_post_translations, $wpml_term_translations;

	require_once WPML_TM_PATH . '/inc/actions/wpml-tm-post-actions.class.php';
	$action_helper    = new WPML_TM_Action_Helper();
	$blog_translators = wpml_tm_load_blog_translators();
	$tm_records       = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
	$save_post_action = new WPML_TM_Post_Actions( $action_helper, $blog_translators, $tm_records );
	if ( $post->post_type == 'revision' || $post->post_status == 'auto-draft' || isset( $_POST['autosave'] ) ) {
		return;
	}
	$save_post_action->save_post_actions( $post_id, $post, $force_set_status );
}

add_action( 'wpml_tm_save_post', 'wpml_tm_save_post', 10, 3 );

function wpml_tm_assign_translation_job( $job_id, $translator_id, $service = 'local', $type ) {
	global $wpml_translation_job_factory;

	$job = $type === 'string'
		? new WPML_String_Translation_Job( $job_id )
		: $wpml_translation_job_factory->get_translation_job( $job_id,
		                                                      false,
		                                                      0,
		                                                      true );
	if ( $job ) {
		$job->assign_to( $translator_id, $service );
	}
}

add_action( 'wpml_tm_assign_translation_job', 'wpml_tm_assign_translation_job', 10, 4 );

/**
 * Potentially handles the request to add strings to the translation basket,
 * triggered by String Translation.
 */
function wpml_tm_add_strings_to_basket() {
	if ( isset( $_POST['icl_st_action'] )
	     && $_POST['icl_st_action'] === 'send_strings'
	     && wpml_is_action_authenticated( 'icl-string-translation' )
	) {
		global $wpdb;

		$basket_instance    = new WPML_Translation_Basket( $wpdb );
		$st_request_handler = new WPML_TM_String_Basket_Request( $basket_instance );
		$st_request_handler->send_to_basket( $_POST );
	}
}

if ( is_admin() ) {
	add_action( 'init', 'wpml_tm_add_strings_to_basket' );
}
