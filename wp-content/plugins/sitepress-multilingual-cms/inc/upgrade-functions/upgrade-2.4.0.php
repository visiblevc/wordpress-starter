<?php
  if(!isset($wp_roles)) $wp_roles = new WP_Roles();
  
  $iclsettings = get_option('icl_sitepress_settings');
  $iclsettings['st']['translated-users'] = array_keys($wp_roles->roles);
  update_option('icl_sitepress_settings', $iclsettings);
  
?>
