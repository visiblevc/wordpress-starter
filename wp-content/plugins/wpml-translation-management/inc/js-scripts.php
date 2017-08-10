<?php

/**
 * Registers scripts so that they can be reused throughout WPML plugins
 */
function wpml_tm_register_js_scripts() {
	wp_register_script(
		'wpml-tm-editor-templates',
		WPML_TM_URL . '/res/js/translation-editor/templates.js',
		array(),
		WPML_TM_VERSION,
		true
	);
	wp_register_script(
		'wpml-tm-editor-job',
		WPML_TM_URL . '/res/js/translation-editor/wpml-tm-editor-job.js',
		array( 'underscore', 'backbone' ),
		WPML_TM_VERSION,
		true
	);
	
	$scripts = array(
		'wpml-tm-editor-job-field-view',
		'wpml-tm-editor-job-basic-field-view',
		'wpml-tm-editor-job-single-line-field-view',
		'wpml-tm-editor-job-textarea-field-view',
		'wpml-tm-editor-job-wysiwyg-field-view',
		'wpml-tm-editor-field-view-factory',
		'wpml-tm-editor-section-view',
		'wpml-tm-editor-group-view',
		'wpml-tm-editor-image-view',
		'wpml-tm-editor-main-view',
		'wpml-tm-editor-header-view',
		'wpml-tm-editor-note-view',
		'wpml-tm-editor-footer-view',
		'wpml-tm-editor-languages-view',
		'wpml-tm-editor-copy-all-dialog',
		'wpml-tm-editor-edit-independently-dialog'
		);
	
	foreach( $scripts as $script ) {
		wp_register_script(
			$script,
			WPML_TM_URL . '/res/js/translation-editor/' . $script . '.js',
			array( 'wpml-tm-editor-job' ),
			WPML_TM_VERSION,
			true
		);
	}

	wp_register_script(
		'wpml-tm-editor-scripts',
		WPML_TM_URL . '/res/js/translation-editor/translation-editor.js',
		array_merge( array( 'jquery', 'jquery-ui-dialog', 'wpml-tm-editor-templates', 'wpml-tm-editor-job'), $scripts ),
		WPML_TM_VERSION,
		true
	);
	wp_register_script( 'wpml-tp-polling-box-populate',
		WPML_TM_URL . '/res/js/tp-polling/box-populate.js',
		array( 'jquery' ), WPML_TM_VERSION );
	wp_register_script( 'wpml-tp-polling',
		WPML_TM_URL . '/res/js/tp-polling/poll-for-translations.js',
		array( 'wpml-tp-polling-box-populate' ), WPML_TM_VERSION );
	wp_register_script( 'wpml-tp-polling-setup',
		WPML_TM_URL . '/res/js/tp-polling/box-setup.js',
		array( 'wpml-tp-polling' ), WPML_TM_VERSION );
	wp_register_script( 'wpml-tm-mcs',
		WPML_TM_URL . '/res/js/mcs/wpml-tm-mcs.js',
		array( 'wpml-tp-polling' ), WPML_TM_VERSION );
	wp_register_script( 'wpml-tm-mcs-translate-link-targets',
		WPML_TM_URL . '/res/js/mcs/wpml-tm-mcs-translate-link-targets.js',
		array(), WPML_TM_VERSION );
}

if ( is_admin() ) {
	add_action( 'admin_enqueue_scripts', 'wpml_tm_register_js_scripts' );
} else {
	add_action( 'wp_enqueue_scripts', 'wpml_tm_register_js_scripts' );
}
