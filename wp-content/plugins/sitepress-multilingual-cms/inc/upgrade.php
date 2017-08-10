<?php

$wp_api = new WPML_WP_API();

if (!defined('ICL_SITEPRESS_DEV_VERSION') && ( $wp_api->version_compare_naked( get_option( 'icl_sitepress_version' ), ICL_SITEPRESS_VERSION, '=' ) || ( isset( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] == 'error_scrape' ) || ! isset( $wpdb ) )) {
	return;
}


if ( get_option( 'icl_sitepress_version' ) && version_compare( get_option( 'icl_sitepress_version' ), '1.7.0', '<' ) ) {
	define( 'WPML_UPGRADE_NOT_POSSIBLE', true );
	add_action( 'admin_notices', 'icl_plugin_too_old' );

	return;
}
    
add_action('plugins_loaded', 'icl_plugin_upgrade' , 1);


function icl_plugin_upgrade(){
    global $wpdb;
    
    $iclsettings = get_option('icl_sitepress_settings');
    
    require_once ICL_PLUGIN_PATH . '/inc/cache.php';
    icl_cache_clear('locale_cache_class');
    icl_cache_clear('flags_cache_class');
    icl_cache_clear('language_name_cache_class');
    
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.7.2', '<')){    
        $wpdb->update($wpdb->prefix.'icl_flags', array('flag'=>'ku.png'), array('lang_code'=>'ku'));                            
        $wpdb->update($wpdb->prefix.'icl_languages_translations', array('name'=>'Magyar'), array('language_code'=>'hu', 'display_language_code'=>'hu'));
        $wpdb->update($wpdb->prefix.'icl_languages_translations', array('name'=>'Hrvatski'), array('language_code'=>'hr', 'display_language_code'=>'hr'));
        $wpdb->update($wpdb->prefix.'icl_languages_translations', array('name'=>'فارسی'), array('language_code'=>'fa', 'display_language_code'=>'fa'));
    }
    
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.7.3', '<')){    
        $wpdb->update($wpdb->prefix.'icl_languages_translations', array('name'=>'پارسی'), array('language_code'=>'fa', 'display_language_code'=>'fa'));
    }

    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.7.7', '<')){    
        if(!isset($iclsettings['promote_wpml'])){
            $iclsettings['promote_wpml'] = 0;
            update_option('icl_sitepress_settings',$iclsettings);
        }
        if(!isset($iclsettings['auto_adjust_ids'])){
            $iclsettings['auto_adjust_ids'] = 0;
            update_option('icl_sitepress_settings',$iclsettings);
        }
        
        $wpdb->query("UPDATE {$wpdb->prefix}icl_translations SET element_type='tax_post_tag' WHERE element_type='tag'"); // @since 3.1.5 - mysql_* function deprecated in php 5.5+
        $wpdb->query("UPDATE {$wpdb->prefix}icl_translations SET element_type='tax_category' WHERE element_type='category'");
    }

    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '1.7.8', '<')){    
        $res = $wpdb->get_results("SELECT ID, post_type FROM {$wpdb->posts}");
	    $post_types = array();
        foreach($res as $row){
            $post_types[$row->post_type][] = $row->ID;
        }
        foreach($post_types as $type=>$ids){
            if(!empty($ids)){
								$q = "UPDATE {$wpdb->prefix}icl_translations SET element_type=%s WHERE element_type='post' AND element_id IN(".join(',',$ids).")";
								$q_prepared = $wpdb->prepare($q, 'post_'.$type);
                $wpdb->query($q_prepared);    // @since 3.1.5 - mysql_* function deprecated in php 5.5+
            }
        }
        
        // fix categories & tags in icl_translations
        $res = $wpdb->get_results("SELECT term_taxonomy_id, taxonomy FROM {$wpdb->term_taxonomy}"); 
        foreach($res as $row) { 
            $icltr = $wpdb->get_row(
										$wpdb->prepare("SELECT translation_id, element_type FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type LIKE %s", 
										array( $row->term_taxonomy_id, wpml_like_escape('tax_') . '%' ))
														);
            if('tax_' . $row->taxonomy != $icltr->element_type){
                $wpdb->update($wpdb->prefix . 'icl_translations', array('element_type'=>'tax_'.$row->taxonomy), array('translation_id'=>$icltr->translation_id));
            }
        }
    }
    
    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '2.0.0', '<')){    
        include_once ICL_PLUGIN_PATH . '/inc/upgrade-functions/upgrade-2.0.0.php';
        
        if(empty($iclsettings['migrated_2_0_0'])){
            define('ICL_MULTI_STEP_UPGRADE', true);
            return; // GET OUT AND DO NOT SET THE NEW VERSION
        }
    }

    if(get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), '2.0.4', '<')){    
        $sql = "ALTER TABLE {$wpdb->prefix}icl_translation_status ADD COLUMN `_prevstate` longtext";
        $wpdb->query($sql);
    }
    
	$versions = array(
		'2.0.5',
		'2.2.2',
		'2.3.0',
		'2.3.1',
		'2.3.3',
		'2.4.0',
		'2.5.0',
		'2.5.2',
		'2.6.0',
		'2.7'  ,
		'2.9'  ,
		'2.9.3',
		'3.1'  ,
		'3.1.5',
		'3.1.8',
		'3.1.9.5',
		'3.2',
		'3.2.3',
		'3.3',
		'3.3.7',
		'3.5.1',
	);

	foreach($versions as $version) {
		icl_upgrade_version( $version );
	}
    
	//Forcing upgrade logic when ICL_SITEPRESS_DEV_VERSION is defined
	//This allow to run the logic between different alpha/beta/RC versions
	//since we are now storing only the formal version in the options
	if(defined('ICL_SITEPRESS_DEV_VERSION')) {
		icl_upgrade_version(ICL_SITEPRESS_DEV_VERSION, true);
	}

    if(version_compare(get_option('icl_sitepress_version'), ICL_SITEPRESS_VERSION, '<')){
        update_option('icl_sitepress_version', ICL_SITEPRESS_VERSION);
    }

    do_action( 'wpml_upgraded', ICL_SITEPRESS_VERSION );
}

function icl_upgrade_version($version, $force = false){
    global $wpdb, $sitepress_settings, $sitepress, $iclsettings;

	if(!$force && defined('WPML_FORCE_UPDATES')) {
		$force = WPML_FORCE_UPDATES;
	}

	if($force || (get_option('icl_sitepress_version') && version_compare(get_option('icl_sitepress_version'), $version, '<' ))){
        $upg_file = ICL_PLUGIN_PATH . '/inc/upgrade-functions/upgrade-' . $version . '.php';        
        if(file_exists($upg_file) && is_readable($upg_file)){
            if(!defined('WPML_DOING_UPGRADE')){
                define('WPML_DOING_UPGRADE', true);    
            }
            include_once $upg_file;
        }
    }        
}  

function icl_plugin_too_old(){
    ?>
    <div class="error message">
        <p><?php 
            printf(__("<strong>WPML notice:</strong> Upgrades to this version are only supported from versions %s and above. To upgrade from version %s, first, download <a%s>2.0.4</a>, do the DB upgrade and then go to this version.", 'sitepress'),
                '1.7.0', get_option('icl_sitepress_version'), ' href="http://downloads.wordpress.org/plugin/sitepress-multilingual-cms.2.0.4.zip"'); ?></p>
    </div>
    <?php
    
}

function icl_table_column_exists( $table_name, $column_name ) {
	global $wpdb;

	$query         = "
				SELECT count(*) FROM information_schema.COLUMNS
				WHERE COLUMN_NAME = %s AND TABLE_NAME = %s AND TABLE_SCHEMA = %s
				";
	$args          = array( $column_name, $wpdb->prefix . $table_name, DB_NAME );
	$sql           = $wpdb->prepare( $query, $args );
	$column_exists = $wpdb->get_var( $sql );

	return (bool) $column_exists;
}

function icl_table_index_exists( $table_name, $index_name ) {
	global $wpdb;

	$query         = "
				SELECT count(*) FROM information_schema.STATISTICS
				    WHERE INDEX_NAME = %s AND TABLE_NAME = %s AND TABLE_SCHEMA = %s;
				";
	$args          = array( $index_name, $wpdb->prefix . $table_name, DB_NAME );
	$sql           = $wpdb->prepare( $query, $args );
	$column_exists = $wpdb->get_var( $sql );

	return (bool) $column_exists;
}

function icl_alter_table_columns( $table_name, $column_definitions ) {
	global $wpdb;

	$result = false;

	if ( ! is_array( $column_definitions ) ) {
		$column_definitions = array( $column_definitions );
	}

	$query = "ALTER TABLE `" . $wpdb->prefix . $table_name . "` ";
	$args  = array();

	$counter = 0;

	$query_parts = array();
	foreach ( $column_definitions as $column_definition ) {
		
		if ( isset( $column_definition[ 'action' ] ) && $column_definition[ 'action' ] == 'ADD' ) {
			$required_keys = array(
				'action',
				'name',
				'type',
			);
		} else {
			$required_keys = array(
				'action',
				'name',
			);
		}

		if ( icl_array_has_required_keys( $column_definition, $required_keys ) ) {

			if ( $counter > 0 ) {
				$query_parts[ ] = ",";
			}
			$query_parts[ ] = $column_definition[ 'action' ];
			$query_parts[ ] = "`" . $column_definition[ 'name' ] . "`";
			if ( isset( $column_definition[ 'type' ] ) ) {
				$query_parts[ ] = $column_definition[ 'type' ];
			}
			if ( isset( $column_definition[ 'charset' ] ) ) {
				$query_parts[ ] = 'CHARACTER SET ' . $column_definition[ 'charset' ];
			}
			if ( isset( $column_definition[ 'null' ] ) ) {
				$query_parts[ ] = $column_definition[ 'null' ] ? 'NULL' : 'NOT NULL';
			}
			if ( isset( $column_definition[ 'default' ] ) ) {
				$query_parts[ ] = 'DEFAULT %s';
				$args[ ]        = $column_definition[ 'default' ];
			}
			if ( isset( $column_definition[ 'after' ] ) ) {
				$query_parts[ ] = 'AFTER `' . $column_definition[ 'after' ] . '`';
			}
			$counter ++;
		} else {
			$args = array();
			break;
		}
	}

	if ( $query_parts ) {
		$query .= implode( ' ', $query_parts );
		if ( sizeof( $args ) > 0 ) {
			$sql = $wpdb->prepare( $query, $args );
		} else {
			$sql = $query;
		}
		$result = $wpdb->query( $sql );
	}

	return $result;
}

function icl_drop_table_index( $table_name, $index_name ) {
	global $wpdb;

	$query = "ALTER TABLE `" . $wpdb->prefix . $table_name . "` ";
	$query .= "DROP INDEX `" . $index_name . "`;";

	return $wpdb->query( $query );
}

function icl_create_table_index( $table_name, $index_definition ) {
	global $wpdb;

	$result = false;

	$required_keys = array(
		'name',
		'columns',
	);

	if ( icl_array_has_required_keys( $index_definition, $required_keys ) && $index_definition[ 'columns' ] ) {

		$query = "ALTER TABLE `" . $wpdb->prefix . $table_name . "` ";
		$query .= "ADD ";

		if ( isset( $index_definition[ 'choice' ] ) ) {
			$query .= $index_definition[ 'choice' ] . " ";
		}

		$query .= "`" . $index_definition[ 'name' ] . "` ";

		$query .= '(`' . implode( '`, `', $index_definition[ 'columns' ] ) . '`) ';

		if ( isset( $index_definition[ 'type' ] ) ) {
			$query .= 'USING ' . $index_definition[ 'type' ] . " ";
		}

		$result = $wpdb->query( $query );
	}

	return $result;
}

/**
 * @param $array
 * @param $required_keys
 *
 * @return bool
 */
function icl_array_has_required_keys( $array, $required_keys ) {
	return count( array_intersect_key( array_flip( $required_keys ), $array ) ) === count( $required_keys );
}