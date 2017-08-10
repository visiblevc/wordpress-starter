<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

//It should only be loaded on the admin side
if( !is_admin() ){
    if(!function_exists('WP_Installer_Setup')){ function WP_Installer_Setup(){} }
    $wp_installer_instance = null;
    return;
}


$wp_installer_instance = dirname(__FILE__) . '/installer.php';


// Global stack of instances
global $wp_installer_instances;
$wp_installer_instances[$wp_installer_instance] = array(
    'bootfile'  => $wp_installer_instance,
    'version'   => '1.7.15'
);


/* EXCEPTIONS ********************************************************************************************/
// Exception: When WPML prior 3.2 is used, that instance must be used regardless of another newer instance
// Case 1: WPML loaded before Types - eliminate other instances
if( defined('ICL_SITEPRESS_VERSION') && version_compare(ICL_SITEPRESS_VERSION, '3.2', '<') ) {
    foreach($wp_installer_instances as $key => $instance) {
        if(isset($instance['args']['site_key_nags'])){
            $wp_installer_instances[$key]['version'] = '9.9';
        }else{
            $wp_installer_instances[$key]['version'] = '0';
        }
    }
}

// Exception: Types 1.8.9 (Installer 1.7.0) with WPML before 3.3 (Installer before 1.7.0)
// New products file http://d2salfytceyqoe.cloudfront.net/wpml-products33.json overrides the old one
// while the WPML's instance is being used
// => Force using the new Installer Instance
if( defined('ICL_SITEPRESS_VERSION') && version_compare(ICL_SITEPRESS_VERSION, '3.3.1', '<') ) {

    // if Installer 1.7.0+ is present, unregister Installer from old WPML
    // Force Installer 1.7.0+ being used over older Installer versions
    $installer_171_plus_on = false;
    foreach($wp_installer_instances as $key => $instance) {
        if( version_compare( $instance['version'], '1.7.1', '>=' ) ){
            $installer_171_plus_on = true;
            break;
        }
    }

    if( $installer_171_plus_on ){
        foreach($wp_installer_instances as $key => $instance) {

            if( version_compare( $instance['version'], '1.7.0', '<' ) ){
                unset( $wp_installer_instances[$key] );
            }

        }
    }

}

// Exception: When using the embedded plugins module allow the set up to run completely with the
// Installer instance that triggers it
if( isset( $_POST['installer_instance'] ) && isset( $wp_installer_instances[$_POST['installer_instance']] ) ){
    $wp_installer_instances[$_POST['installer_instance']]['version'] = '999';
}
/* EXCEPTIONS ********************************************************************************************/


// Only one of these in the end
remove_action('after_setup_theme', 'wpml_installer_instance_delegator', 1);
add_action('after_setup_theme', 'wpml_installer_instance_delegator', 1);

// When all plugins load pick the newest version
if(!function_exists('wpml_installer_instance_delegator')){
    function wpml_installer_instance_delegator(){
        global $wp_installer_instances;

        // version based election
        foreach($wp_installer_instances as $instance){

            if(!isset($delegate)){
                $delegate = $instance;
                continue;
            }
            
            if(version_compare($instance['version'], $delegate['version'], '>')){
                $delegate = $instance;    
            }
        }

        // priority based election
        $highest_priority = null;
        foreach($wp_installer_instances as $instance) {
            if(isset($instance['args']['high_priority'])){
                if(is_null($highest_priority) || $instance['args']['high_priority'] <= $highest_priority){
                    $highest_priority = $instance['args']['high_priority'];
                    $delegate = $instance;
                }
            }
        }

        // Exception: When WPML prior 3.2 is used, that instance must be used regardless of another newer instance
        // Case 2: WPML loaded after Types
        if( defined('ICL_SITEPRESS_VERSION') && version_compare(ICL_SITEPRESS_VERSION, '3.2', '<') ) {
            foreach($wp_installer_instances as $key => $instance) {
                if(isset($instance['args']['site_key_nags'])){
                    $delegate = $instance;
                    $wp_installer_instances = array($key => $delegate); //Eliminate other instances
                    break;
                }
            }
        }

        include_once $delegate['bootfile'];
        
        // set configuration
        if(strpos(realpath($delegate['bootfile']), realpath(TEMPLATEPATH)) === 0){
            $delegate['args']['in_theme_folder'] = dirname(ltrim(str_replace(realpath(TEMPLATEPATH), '', realpath($delegate['bootfile'])), '\\/'));            
        }        
        if(isset($delegate['args']) && is_array($delegate['args'])){
            foreach($delegate['args'] as $key => $value){                
                WP_Installer()->set_config($key, $value);                
            }
        }
        
    }
}  

if(!function_exists('WP_Installer_Setup')){
    
    // $args:
    // plugins_install_tab = true|false (default: true) 
    // repositories_include = array() (default: all)
    // repositories_exclude = array() (default: none)
    // template = name (default: default)            
    // 
    // Ext function 
    function WP_Installer_Setup($wp_installer_instance, $args = array()){
        global $wp_installer_instances;
        
        $wp_installer_instances[$wp_installer_instance]['args'] = $args;

    }
    
}