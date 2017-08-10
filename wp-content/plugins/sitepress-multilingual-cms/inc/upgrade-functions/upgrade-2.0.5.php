<?php

$iclsettings = get_option('icl_sitepress_settings');
$iclsettings['remember_language'] = 24;

update_option('icl_sitepress_settings', $iclsettings);
$wpdb->update($wpdb->prefix . 'icl_languages', array('default_locale'=>'it_IT'), array('code'=>'it'));
  

