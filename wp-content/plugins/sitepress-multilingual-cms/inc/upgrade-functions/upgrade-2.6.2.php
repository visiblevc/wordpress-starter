<?php
  $sql = "ALTER TABLE  `{$wpdb->prefix}icl_strings` CHANGE  `language`  `language` VARCHAR( 7 ) NOT NULL";
  $wpdb->query($sql);
  
  $sql = "ALTER TABLE  `{$wpdb->prefix}icl_locale_map` CHANGE  `code`  `code` VARCHAR( 7 ) NOT NULL";
  $wpdb->query($sql);
  
  $iclsettings = get_option('icl_sitepress_settings');
  $iclsettings['posts_slug_translation']['on'] = 0;
  update_option('icl_sitepress_settings', $iclsettings);
