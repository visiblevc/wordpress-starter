<?php
/**
 * @package wpml-core
 */

global $wpdb;

// change icl_translation_status.translation_package from text to longtext
$sql = "ALTER TABLE {$wpdb->prefix}icl_translation_status MODIFY COLUMN translation_package longtext NOT NULL";
$wpdb->query( $sql );

