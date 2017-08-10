<?php

// Add a new `language_context` index to icl_strings table
$sql = "ALTER TABLE `{$wpdb->prefix}icl_translate` CHANGE field_data field_data longtext NOT NULL";
$wpdb->query($sql);
$sql = "ALTER TABLE `{$wpdb->prefix}icl_translate` CHANGE field_data_translated field_data_translated longtext NOT NULL";
$wpdb->query($sql);
