<?php
/**
 * @package wpml-core
 */

global $wpdb;

global $sitepress_settings;

if ( ! isset( $sitepress_settings ) ) {
	$sitepress_settings = get_option( 'icl_sitepress_settings' );
}

// change icl_translate.field_type size to 160
$sql = "ALTER TABLE {$wpdb->prefix}icl_translate MODIFY COLUMN field_type VARCHAR( 160 ) NOT NULL";
$wpdb->query( $sql );

// Add 'batch_id' column to icl_translation_status
$sql             = $wpdb->prepare( "SELECT count(*) FROM information_schema.COLUMNS
     WHERE COLUMN_NAME = 'batch_id'
     and TABLE_NAME = '{$wpdb->prefix}icl_translation_status'AND TABLE_SCHEMA = %s",
                                   DB_NAME );
$batch_id_exists = $wpdb->get_var( $sql );
if ( ! $batch_id_exists || ! (int) $batch_id_exists ) {
	$sql = "ALTER TABLE `{$wpdb->prefix}icl_translation_status` ADD batch_id int DEFAULT 0 NOT NULL;";
	$wpdb->query( $sql );
}

// Add 'batch_id' column to icl_string_translations
$sql             = $wpdb->prepare( "SELECT count(*) FROM information_schema.COLUMNS
     WHERE COLUMN_NAME = 'batch_id'
     and TABLE_NAME = '{$wpdb->prefix}icl_string_translations' AND TABLE_SCHEMA = %s",
                                   DB_NAME );
$batch_id_exists = $wpdb->get_var( $sql );
if ( ! $batch_id_exists || ! (int) $batch_id_exists ) {
	$sql = "ALTER TABLE `{$wpdb->prefix}icl_string_translations` ADD batch_id int DEFAULT -1 NOT NULL;";
	$wpdb->query( $sql );
	require dirname( __FILE__ ) . '/3.2/wpml-upgrade-string-statuses.php';
	update_string_statuses();
	fix_icl_string_status();
}

// Add 'translation_service' column to icl_string_translations
$sql             = $wpdb->prepare( "SELECT count(*) FROM information_schema.COLUMNS
     WHERE COLUMN_NAME = 'translation_service'
     and TABLE_NAME = '{$wpdb->prefix}icl_string_translations' AND TABLE_SCHEMA = %s",
                                   DB_NAME );
$batch_id_exists = $wpdb->get_var( $sql );
if ( ! $batch_id_exists || ! (int) $batch_id_exists ) {
	$sql = "ALTER TABLE `{$wpdb->prefix}icl_string_translations` ADD translation_service varchar(16) DEFAULT '' NOT NULL;";
	$wpdb->query( $sql );
}

// Add 'icl_translation_batches' table
$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}icl_translation_batches (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `batch_name` text NOT NULL,
				  `tp_id` int NULL,
				  `ts_url` text NULL,
				  `last_update` DATETIME NULL,
				  PRIMARY KEY (`id`)
				);";
$wpdb->query( $sql );


$res                 = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}icl_strings" );
$icl_strings_columns = array();
foreach ( $res as $row ) {
	$icl_strings_columns[ ] = $row->Field;
}
if ( ! in_array( 'string_package_id', $icl_strings_columns ) ) {
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}icl_strings
        ADD `string_package_id` BIGINT unsigned NULL AFTER value,
        ADD `type` VARCHAR(40) NOT NULL DEFAULT 'LINE' AFTER string_package_id,
        ADD `title` VARCHAR(160) NULL AFTER type,
        ADD INDEX (`string_package_id`)
    " );
}

$wpdb->update( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_wpml_original_post_id' ), array( 'meta_key' => 'original_post_id' ) );

$sitepress_settings = get_option( 'icl_sitepress_settings' );

if ( isset( $sitepress_settings[ 'translation-management' ] ) ) {
	$updated_tm_settings = false;
	$tm_settings     = $sitepress_settings[ 'translation-management' ];
	$tm_setting_keys = array(
		'custom_fields_translation',
		'custom_fields_readonly_config',
		'custom_fields_translation_custom_readonly',
	);
	foreach ( $tm_setting_keys as $tm_setting_key ) {
		$updated_tm_settings_key = false;
		if ( isset( $tm_settings[ $tm_setting_key ] ) ) {
			$tm_custom_fields_settings = $tm_settings[ $tm_setting_key ];
			if ( array_key_exists( 'original_post_id', $tm_custom_fields_settings ) ) {
				$tm_custom_fields_settings[ '_wpml_original_post_id' ] = $tm_custom_fields_settings[ 'original_post_id' ];
				unset( $tm_custom_fields_settings[ 'original_post_id' ] );
				$updated_tm_settings_key = true;
			}
			$index = array_search( 'original_post_id', $tm_custom_fields_settings, true );
			if ( $index ) {
				$tm_custom_fields_settings[ $index ] = '_wpml_original_post_id';
				$updated_tm_settings_key = true;
			}
			if($updated_tm_settings_key) {
				$tm_settings[ $tm_setting_key ] = $tm_custom_fields_settings;
				$updated_tm_settings = true;
			}
		}
	}
	if($updated_tm_settings) {
		$sitepress_settings[ 'translation-management' ] = $tm_settings;
		update_option( 'icl_sitepress_settings', $sitepress_settings );
	}
}
