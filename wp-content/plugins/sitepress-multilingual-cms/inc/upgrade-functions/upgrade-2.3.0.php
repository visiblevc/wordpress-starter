<?php

$icl_rc_widget = get_option('widget_recent_comments');
if(!empty($icl_rc_widget)){
    $rc_widgets = get_option('widget_recent-comments');
    if(!empty($rc_widgets)){
        $rc_widgets[] = $icl_rc_widget;
    }else{
        $rc_widgets = array(
            '1' => $icl_rc_widget,
            '_multiwidget' => 1
        );
        
    }
    update_option('widget_recent-comments', $rc_widgets);
    delete_option('widget_recent_comments');
    
}
$wpdb->query("ALTER TABLE {$wpdb->prefix}icl_string_translations MODIFY COLUMN value TEXT NULL DEFAULT NULL");

$wpdb->query("ALTER TABLE  {$wpdb->prefix}icl_string_translations ADD translator_id bigint(20) NULL DEFAULT NULL, ADD translation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");

// Disable the auto registration of strings if we are upgrading to 2.3.0
$iclsettings = get_option('icl_sitepress_settings');
$iclsettings['st']['icl_st_auto_reg'] = 'disable';
update_option('icl_sitepress_settings', $iclsettings);

// The icl_translators_cached format has change at some point.
// Let's clear the cache so it gets rebuilt.
delete_option($wpdb->prefix . 'icl_translators_cached');
delete_option($wpdb->prefix . 'icl_non_translators_cached');

