<?php
/**
 * @package wpml-core
 */

function update_gettext_context_schema() {
	
	if ( ! icl_table_column_exists( 'icl_strings', 'domain_name_context_md5' ) ) {

		$columns_definitions = array( );
		
		if ( ! icl_table_column_exists( 'icl_strings', 'gettext_context' ) ) {
			$columns_definitions[ ] = array(
				'action' => 'ADD',
				'name'   => 'gettext_context',
				'type'   => 'TEXT',
				'null'   => false,
				'after'  => 'status',
				);
		}
		
		$columns_definitions[ ] = array(
				'action'  => 'ADD',
				'name'    => 'domain_name_context_md5',
				'type'    => 'VARCHAR(32)',
				'charset' => 'LATIN1',
				'null'    => false,
				'default' => '',
				'after'   => 'gettext_context',
			);
		
		if ( icl_alter_table_columns( 'icl_strings', $columns_definitions ) ) {
			if ( icl_table_index_exists( 'icl_strings', 'context_name' ) ) {
				if ( icl_drop_table_index( 'icl_strings', 'context_name' ) ) {
				}
			}
			if ( icl_table_index_exists( 'icl_strings', 'context_name_gettext_context' ) ) {
				if ( icl_drop_table_index( 'icl_strings', 'context_name_gettext_context' ) ) {
				}
			}
		}

		update_domain_name_context( );

		$index_definition = array(
			'name'    => 'uc_domain_name_context_md5',
			'type'    => 'BTREE',
			'choice'  => 'UNIQUE',
			'columns' => array( 'domain_name_context_md5' ),
		);
		icl_create_table_index( 'icl_strings', $index_definition );
		
	}
	
	if ( icl_table_column_exists( 'icl_strings', 'gettext_context_md5' ) ) {
		$columns_definitions = array(
									 array(
										'action'  => 'DROP',
										'name'    => 'gettext_context_md5',
										)
									);
		icl_alter_table_columns( 'icl_strings', $columns_definitions);
	}
	
}

function update_domain_name_context( ) {
	
	global $wpdb;
	
	$results = $wpdb->get_results("SELECT id, name, value, context as domain, gettext_context FROM {$wpdb->prefix}icl_strings WHERE id > 0");
	
	$domain_name_context_md5_used = array( );
	$duplicate_count = 0;
	foreach( $results as $string ) {
		
		$domain_name_context_md5 = md5( $string->domain . $string->name . $string->gettext_context );
		while ( in_array( $domain_name_context_md5, $domain_name_context_md5_used ) ) {
			
			/* We need to handle duplicates because previous versions of WPML didn't strictly
			 * disallow them when handling gettext contexts.
			 * This solution doesn't solve the problem because there is no solution
			 * It just stops any DB errors about duplicate keys.
			 */
			
			$duplicate_count++;
			$domain_name_context_md5 = md5( $string->domain . $string->name . 'duplicate-' . $duplicate_count . '-' . $string->gettext_context );
		}
		
		$domain_name_context_md5_used[ ] = $domain_name_context_md5;
		
		$data  = array(
					   'domain_name_context_md5' => $domain_name_context_md5,
					  );
		$where = array( 'id' => $string->id );
		$wpdb->update( $wpdb->prefix . 'icl_strings', $data, $where );				
	}
}

update_gettext_context_schema( );

