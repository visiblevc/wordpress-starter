<?php

/**
 * Class WPML_Flags
 *
 * @package wpml-core
 */
class WPML_Flags extends WPML_WPDB_User {

	private $icl_flag_cache;
	
	public function __construct( &$wpdb ) {
		parent::__construct( $wpdb );
		$this->icl_flag_cache = new icl_cache( 'flags', true );
	}

	public function get_flag( $lang_code ) {
		if ( isset( $this->icl_flag_cache ) ) {
			$flag = $this->icl_flag_cache->get( $lang_code );
		} else {
			$flag = null;
		}
		if ( !$flag ) {
			$flag = $this->wpdb->get_row( $this->wpdb->prepare("SELECT flag, from_template
                                                    FROM {$this->wpdb->prefix}icl_flags
                                                    WHERE lang_code=%s", $lang_code ) );
			if ( isset( $this->icl_flag_cache ) ) {
				$this->icl_flag_cache->set( $lang_code, $flag );
			}
		}

		return $flag;
	}
	
	function get_flag_url( $code ) {
		$flag = $this->get_flag( $code );
		if ( $flag->from_template ) {
			$wp_upload_dir = wp_upload_dir();
			$flag_url      = $wp_upload_dir[ 'baseurl' ] . '/flags/' . $flag->flag;
		} else {
			$flag_url = ICL_PLUGIN_URL . '/res/flags/' . $flag->flag;
		}

		return $flag_url;
	}
	
	function clear() {
		$this->icl_flag_cache->clear();
	}
	
}