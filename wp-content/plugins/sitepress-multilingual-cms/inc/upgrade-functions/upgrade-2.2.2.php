<?php
$iclsettings = get_option('icl_sitepress_settings');
if(is_array($iclsettings['translation-management']['custom_fields_readonly_config'])){
    $iclsettings['translation-management']['custom_fields_readonly_config'] = array_unique($iclsettings['translation-management']['custom_fields_readonly_config']);
    update_option('icl_sitepress_settings', $iclsettings);
}
delete_option($wpdb->prefix . 'icl_translators_cached');
delete_option($wpdb->prefix . 'icl_non_translators_cached');



  

