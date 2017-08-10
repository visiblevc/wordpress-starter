<?php

function update_icl_strings_charset_and_collations() {
	global $wpdb;

	if ( ! icl_table_column_exists( 'icl_strings', 'domain_name_context_md5' ) ) {
		include_once __DIR__ . '/upgrade-3.2.3.php';
	}
	
	$collate = false;

	if ( method_exists( $wpdb, 'has_cap' ) && $wpdb->has_cap( 'collation' ) ) {
		$collate = true;
	}

	$language_data = upgrade_3_5_1_get_language_charset_and_collation();

	$sql_template = "ALTER TABLE `{$wpdb->prefix}%s` MODIFY `%s` VARCHAR(%d) CHARACTER SET %s %s";

	$fields = array(
		array(
			'table' => 'icl_strings',
			'column' => 'name',
			'size' => WPML_STRING_TABLE_NAME_CONTEXT_LENGTH,
			'charset' => 'UTF8',
			'collation' => $collate ? 'COLLATE utf8_general_ci' : ''
		),
		array(
			'table' => 'icl_strings',
			'column' => 'context',
			'size' => WPML_STRING_TABLE_NAME_CONTEXT_LENGTH,
			'charset' => 'UTF8',
			'collation' => $collate ? 'COLLATE utf8_general_ci' : ''
		),
		array(
			'table' => 'icl_strings',
			'column' => 'domain_name_context_md5',
			'size' => 32,
			'charset' => 'LATIN1',
			'collation' => $collate ? 'COLLATE latin1_general_ci' : ''
		),
	);

	foreach ( $fields as $setting ) {
		if ( $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}{$setting['table']}'" ) ) {
			$sql = sprintf( $sql_template, $setting['table'], $setting['column'], $setting['size'], $setting['charset'], $setting['collation'] );

			if ( $wpdb->query( $sql ) === false ) {
				throw new Exception( $wpdb->last_error );
			}
		}
	}
}

function upgrade_3_5_1_get_language_charset_and_collation() {

	global $wpdb;

	$data = null;

	$column_data = $wpdb->get_results( "SELECT * FROM INFORMATION_SCHEMA.COLUMNS where TABLE_NAME='{$wpdb->prefix}icl_strings' AND TABLE_SCHEMA='{$wpdb->dbname}' ");
	foreach ( $column_data as $column ) {
		if ( 'language' === $column->COLUMN_NAME ) {
			$data['collation'] = $column->COLLATION_NAME;
			$data['charset'] = $column->CHARACTER_SET_NAME;
		}
	}

	return $data;
}

update_icl_strings_charset_and_collations();
