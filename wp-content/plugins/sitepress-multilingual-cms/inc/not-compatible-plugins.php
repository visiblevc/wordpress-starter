<?php
$icl_ncp_plugins = array(
    'absolute-links/absolute-links-plugin.php',
    'cms-navigation/CMS-Navigation.php'
);  
$active_plugins = get_option('active_plugins');

$icl_ncp_plugins = array_intersect($icl_ncp_plugins, $active_plugins);

if(!empty($icl_ncp_plugins)){
    $icl_sitepress_disabled = true;
    icl_suppress_activation();
    
    
    add_action('admin_notices', 'icl_incomp_plugins_warn');
	function icl_incomp_plugins_warn() {
		global $icl_ncp_plugins;
		echo '<div class="error"><ul><li><strong>';
		esc_html_e( 'WPML cannot be activated together with these older plugins:', 'sitepress' );
		echo '<ul style="list-style:disc;margin:20px;">';
		foreach ( $icl_ncp_plugins as $incp ) {
			echo '<li>' . esc_html( $incp ) . '</li>';
		}
		echo '</ul>';
		esc_html_e( 'WPML will be deactivated', 'sitepress' );
		echo '</strong></li></ul></div>';
	}
}else{
    $icl_sitepress_disabled = false;
}

$filtered_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE);
if( 0 === strcmp( $filtered_page, ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php' ) || isset($pagenow) && $pagenow=='index.php'){
    $icl_ncp_plugins2 = array(
        'wp-no-category-base/no-category-base.php'
    );  
    $active_plugins = get_option('active_plugins');
    $icl_ncp_plugins2 = array_intersect($icl_ncp_plugins2, $active_plugins);
    if(!empty($icl_ncp_plugins2)){
	    if( 0 === strcmp( $filtered_page, ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php' ) ){
            add_action('admin_notices', 'icl_incomp_plugins_warn2');
		    function icl_incomp_plugins_warn2() {
			    global $icl_ncp_plugins2;
			    echo '<a name="icl_inc_plugins_notice"></a><div class="error" style="padding:10px;">';
			    esc_html_e( 'These plugins are known to have compatibiliy issues with WPML:', 'sitepress' );
			    echo '<ul style="list-style:disc;margin-left:20px;">';
			    foreach ( $icl_ncp_plugins2 as $incp ) {
				    echo '<li>' . esc_html( $incp ) . '</li>';
			    }
			    echo '</ul>';
			    echo '</div>';
		    }
        }
    }
}


// WCML versions before 3.8 are not fully compatible with WPML versions after 3.4
add_action('admin_head', 'wpml_wcml_3_8_is_required');
function wpml_wcml_3_8_is_required(){

    if( defined('WCML_VERSION') ){

        $message_id = 'icl_wcml_3_8_is_required';

        if ( version_compare( WCML_VERSION, '3.8', '<' ) ) {
            $message = array(
                'id' => $message_id,
                'type' => 'icl-admin-message-warning',
                'limit_to_page' => 'wpml-wcml',
                'admin_notice' => true,
                'classes' => array( 'error' ),
                'text' => sprintf( __( "%sIMPORTANT:%s You are using a version of WooCommerce Multilingual that is not fully compatible with the current WPML version. The %sproducts translation editor has been deactivated%s for this reason.%sPlease upgrade to %sWooCommerce Multilingual 3.8%s to restore the translation editor for products and use all the other functions.", 'sitepress' ),
                    '<strong>', '</strong>', '<strong>', '</strong>', '<br /><br />', '<strong><a href="https://wpml.org/?p=867248">', '</a></strong>' )
            );
            ICL_AdminNotifier::add_message( $message );

            ?>

            <?php if( isset( $_GET['page'] ) && $_GET['page'] == 'wpml-wcml'): ?>
            <script>
                jQuery(document).ready(function () {
                    jQuery('.wcml_details').unbind('click');
                    jQuery('.wcml_products_translation input[type=text], .wcml_products_translation textarea, .wcml_products_translation button').attr('disabled', 'disabled');
                    jQuery('.wcml_products a.wcml_details').css('text-decoration', 'line-through');
                    jQuery('.wcml_products').on('click', 'a.wcml_details', function () {
                        location.href = '#adminmenumain';
                        jQuery('#icl-id-icl_wcml_3_8_is_required').fadeIn(100).fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
                        return false;
                    })
                })
            </script>
            <?php endif; ?>

            <?php

        } else {

            ICL_AdminNotifier::remove_message( $message_id );

        }

    }
}

