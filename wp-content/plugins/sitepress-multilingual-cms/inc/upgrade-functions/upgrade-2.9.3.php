<?php

$widget_strings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}icl_strings WHERE context = 'Widgets' AND name LIKE 'widget body - %'");
foreach($widget_strings as $string){
    $wpdb->update($wpdb->prefix . 'icl_strings', array('name' => 'widget body - ' . md5($string->value)), array('id' => $string->id));
}  

$widget_strings = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}icl_strings WHERE context = 'Widgets' AND name LIKE 'widget title - %'");
foreach($widget_strings as $string){
    $wpdb->update($wpdb->prefix . 'icl_strings', array('name' => 'widget title - ' . md5($string->value)), array('id' => $string->id));
}  


// Add a new `language_context` index to icl_strings table
$sql = "ALTER TABLE `{$wpdb->prefix}icl_strings` ADD INDEX `language_context` ( `context` , `language` )";
$wpdb->query($sql);

?>
