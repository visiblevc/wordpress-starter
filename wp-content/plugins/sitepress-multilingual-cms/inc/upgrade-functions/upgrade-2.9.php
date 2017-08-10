<?php
  
  $ms = array(
        
        'code'          => 'ms',
        'english_name'  => 'Malay',
        'major'         => 0,
        'active'        => 0,
        'default_locale'=> 'ms_MY',
        'tag'           => 'ms-MY',
        'encode_url'    => 0        
  );
  
  $wpdb->insert($wpdb->prefix . 'icl_languages', $ms);
  
  
  $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code' => 'ms', 'display_language_code' => 'en', 'name' => 'Malay'));
  $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code' => 'ms', 'display_language_code' => 'es', 'name' => 'Malayo'));
  $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code' => 'ms', 'display_language_code' => 'de', 'name' => 'Malay'));
  $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code' => 'ms', 'display_language_code' => 'fr', 'name' => 'Malay'));
  $wpdb->insert($wpdb->prefix . 'icl_languages_translations', array('language_code' => 'ms', 'display_language_code' => 'ms', 'name' => 'Melayu'));
  
  $wpdb->insert($wpdb->prefix . 'icl_flags', array('lang_code' => 'ms', 'flag' => 'ms.png', 'from_template' => 0));
  
  
?>
