<?php
wp_cache_add_global_groups( array( 'sitepress_ms' ) );

$filtered_action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE  );
$filtered_action = $filtered_action ? $filtered_action : filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE  );
if( 0 === strcmp( $filtered_action, 'resetwpml' ) ) {
    include_once ICL_PLUGIN_PATH . '/inc/functions-troubleshooting.php';    
}

add_action('network_admin_menu', 'icl_network_administration_menu');
add_action('wpmuadminedit', 'icl_wpmuadminedit');

function icl_wpmuadminedit(){
    if(!isset($_REQUEST['action'])) return;

    $filtered_action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE  );
    $filtered_action = $filtered_action ? $filtered_action : filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE  );

	switch( $filtered_action ){
        case 'resetwpml':  icl_network_reset_wpml(); break;
        case 'deactivatewpml':  icl_network_deactivate_wpml(); break;
        case 'activatewpml':  icl_network_activate_wpml(); break;
    }
}

function icl_network_administration_menu() {
    add_menu_page (
        __ ( 'WPML', 'sitepress' ),
        __ ( 'WPML', 'sitepress' ),
        'manage_sitess',
        basename ( ICL_PLUGIN_PATH ) . '/menu/network.php',
        null,
        ICL_PLUGIN_URL . '/res/img/icon16.png'
    );
    add_submenu_page (
        basename ( ICL_PLUGIN_PATH ) . '/menu/network.php',
        __ ( 'Network settings', 'sitepress' ),
        __ ( 'Network settings', 'sitepress' ),
        'manage_sites',
        basename ( ICL_PLUGIN_PATH ) . '/menu/network.php'
    );
}

function icl_network_reset_wpml( ) {
	
	icl_reset_wpml();
	
	wp_redirect( network_admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/network.php&updated=true&action=resetwpml' ) );
}

function icl_network_deactivate_wpml($blog_id = false){
    global $wpdb;
    
	$filtered_action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
	$filtered_action = $filtered_action ? $filtered_action : filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );

	if( 0 === strcmp( $filtered_action, 'deactivatewpml' ) ) {
		if ( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'deactivatewpml' ) ) {
			return;
        }
    }
    
    if(empty($blog_id)){
	    $filtered_id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
		$filtered_id = $filtered_id ? $filtered_id : filter_input( INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
        $blog_id = $filtered_id !== false ? $filtered_id : $wpdb->blogid;
    }
      
    if($blog_id){
        switch_to_blog($blog_id);
        update_option('_wpml_inactive', true);
        restore_current_blog();
    }    

    wp_redirect(network_admin_url('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/network.php&updated=true&action=deactivatewpml'));
}

function icl_network_activate_wpml( $blog_id = false ) {
    global $wpdb;

	$filtered_action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE  );
	$filtered_action = $filtered_action ? $filtered_action : filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE  );

    if ( 0 === strcmp( $filtered_action, 'activatewpml' ) ) {
		if ( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'activatewpml' ) ) {
			return;
        }
    }

    if(empty($blog_id)){
			$filtered_id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
        $filtered_id = $filtered_id ? $filtered_id : filter_input( INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
		$blog_id     = $filtered_id !== false ? $filtered_id : $wpdb->blogid;
	}

	if ( $blog_id ) {
		switch_to_blog( $blog_id );
		delete_option( '_wpml_inactive' );
		restore_current_blog();
	}

    wp_redirect(network_admin_url('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/network.php&updated=true&action=activatewpml'));
    exit();
}