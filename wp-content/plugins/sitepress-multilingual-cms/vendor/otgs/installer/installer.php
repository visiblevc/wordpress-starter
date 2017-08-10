<?php 
define('WP_INSTALLER_VERSION', '1.7.15');
  
include_once dirname(__FILE__) . '/includes/installer.class.php';

function WP_Installer() {
    return WP_Installer::instance();
}


WP_Installer();

include_once WP_Installer()->plugin_path() . '/includes/installer-api.php';
include_once WP_Installer()->plugin_path() . '/includes/translation-service-info.class.php';
include_once WP_Installer()->plugin_path() . '/includes/class-installer-dependencies.php';

// Ext function 
function WP_Installer_Show_Products($args = array()){
    
    WP_Installer()->show_products($args);
    
}