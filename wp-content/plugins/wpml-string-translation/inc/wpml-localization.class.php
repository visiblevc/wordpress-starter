<?php

class WPML_Localization {

	public function get_theme_localization_stats() {
		$theme_localization_domains = icl_get_sub_setting( 'st', 'theme_localization_domains' );
		return $this->get_domain_stats( $theme_localization_domains, 'theme' );
	}
	
	private function get_domain_stats( $localization_domains, $default, $no_wordpress = false ) {
		global $wpdb;
		
		$results                    = array();
		if ( $localization_domains ) {
			$domains = array();

			foreach ( (array) $localization_domains as $domain ) {
				if ( ! ($no_wordpress && $domain == 'WordPress' ) ) {
					$domains[ ] = $domain ? $domain : $default;
				}
			}
			if ( ! empty( $domains ) ) {
				$results = $wpdb->get_results( "
		            SELECT context, status, COUNT(id) AS c
		            FROM {$wpdb->prefix}icl_strings
		            WHERE context IN ('" . join( "','", $domains ) . "')
		            GROUP BY context, status
		        " );
			}
		}

		return $this->results_to_array( $results );
	}

	public function get_plugin_localization_stats() {
		$plugin_localization_domains = icl_get_sub_setting( 'st', 'plugin_localization_domains', array() );
		$results = array();
		
		foreach ( $plugin_localization_domains as $plugin => $localization_domains ) {
			$results[ $plugin ] = $this->get_domain_stats( array_keys( $localization_domains ), 'plugin', true );
		}
		return $results;
	}

	public function get_wrong_plugin_localization_stats() {
		global $wpdb;

		$results = $wpdb->get_results( "
	        SELECT context, status, COUNT(id) AS c
	        FROM {$wpdb->prefix}icl_strings
	        WHERE context LIKE ('plugin %')
	        GROUP BY context, status
	    " );

		return $this->results_to_array( $results );
	}
	
	public function get_wrong_theme_localization_stats() {
		global $wpdb;

		$results = $wpdb->get_results( "
	        SELECT context, status, COUNT(id) AS c
	        FROM {$wpdb->prefix}icl_strings
	        WHERE context LIKE ('theme %')
	        GROUP BY context, status
	    " );

		$results = $this->results_to_array( $results );

		$theme_path = TEMPLATEPATH;
		$old_theme_context = 'theme ' . basename( $theme_path );
		
		unset( $results[ $old_theme_context ] );
		
		return $results;
		
	}
	
	public function does_theme_require_rescan() {
		global $wpdb;
	
		$theme_path = TEMPLATEPATH;
		$old_theme_context = 'theme ' . basename( $theme_path );


		$result = $wpdb->get_var( $wpdb->prepare( "
	        SELECT COUNT(id) AS c
	        FROM {$wpdb->prefix}icl_strings
	        WHERE context = %s",
			$old_theme_context
			) );
		
		return $result ? true : false;
	}
	
	public function get_most_popular_domain( $plugin ) {
		$plugin_localization_domains = icl_get_sub_setting( 'st', 'plugin_localization_domains' );
		
		$most_popular = '';
		$most_count = 0;

		foreach( $plugin_localization_domains[ $plugin ] as $name => $count) {
			if ( $name == 'WordPress' || $name == 'default' ) {
				continue;
			}
			if ($count > $most_count) {
				$most_popular = $name;
				$most_count = $count;
			}
		}
		
		return $most_popular;
	}
	private function results_to_array( $results ) {
		$stats = array();

		foreach ( $results as $r ) {
			if ( ! isset( $stats[ $r->context ][ 'complete' ] ) ) {
				$stats[ $r->context ][ 'complete' ] = 0;
			}
			if ( ! isset( $stats[ $r->context ][ 'incomplete' ] ) ) {
				$stats[ $r->context ][ 'incomplete' ] = 0;
			}
			if ( $r->status == ICL_TM_COMPLETE ) {
				$stats[ $r->context ][ 'complete' ] = $r->c;
			} else {
				$stats[ $r->context ][ 'incomplete' ] += $r->c;
			}
		}

		return $stats;
	}
}