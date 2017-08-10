<?php

function wpml_get_authenticated_action() {

	$action = filter_input( INPUT_POST, 'icl_ajx_action' );
	$action = $action ? $action : filter_input( INPUT_POST, 'action' );
	$nonce  = $action ? filter_input( INPUT_POST, '_icl_nonce' ) : null;
	if ( $nonce === null || $action === null ) {
		$action = filter_input( INPUT_GET, 'icl_ajx_action' );
		$nonce  = $action ? filter_input( INPUT_GET, '_icl_nonce' ) : null;
	}

	$authenticated_action = $action && wp_verify_nonce( (string) $nonce, $action . '_nonce' ) ? $action : null;

	return $authenticated_action;
}

/**
 * Validates a nonce according to the schema also used by \wpml_nonce_field
 *
 * @param string $action
 *
 * @return false|int
 */
function wpml_is_action_authenticated( $action ) {
	$nonce = isset( $_POST['_icl_nonce'] ) ? $_POST['_icl_nonce'] : '';
	if ( '' !== $nonce ) {
		$action = $action . '_nonce';
	} else {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
	}

	return wp_verify_nonce( $nonce, $action );
}

/**
 * Generates HTML for the hidden nonce input field following the schema
 * used by \wpml_is_action_authenticated
 *
 * @param string $action
 *
 * @return string
 */
function wpml_nonce_field( $action ) {
	return '<input name="_icl_nonce" type="hidden" value="'
	       . wp_create_nonce( $action . '_nonce' ) . '"/>';
}