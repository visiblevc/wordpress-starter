<?php

// WPML Sunrise Script - START
// Version 1.0beta
// Place this script in the wp-content folder and add "define('SUNRISE', 'on');" in wp-config.php n order to enable using different domains for different languages in multisite mode
//
// Experimental feature
define('WPML_SUNRISE_MULTISITE_DOMAINS', true);
add_filter('query', 'sunrise_wpml_filter_queries');
function sunrise_wpml_filter_queries($q){
	global $wpdb, $table_prefix, $current_blog;

	static $no_recursion;

	if(empty($current_blog) && empty($no_recursion)){

		$no_recursion = true;

		$domain_found = preg_match("#SELECT \\* FROM {$wpdb->blogs} WHERE domain = '(.*)'#", $q, $matches) || preg_match("#SELECT  blog_id FROM {$wpdb->blogs}  WHERE domain IN \\( '(\S*)' \\)#", $q, $matches);

		if( $domain_found ){

			if(!$wpdb->get_row($q)){

				$icl_blogs = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
				foreach($icl_blogs as $blog_id){
					$prefix = $blog_id > 1 ? $table_prefix . $blog_id . '_' : $table_prefix;
					$icl_settings = $wpdb->get_var("SELECT option_value FROM {$prefix}options WHERE option_name='icl_sitepress_settings'");
					if($icl_settings){
						$icl_settings = unserialize($icl_settings);
						if($icl_settings && $icl_settings['language_negotiation_type'] == 2){
							if( in_array( 'http://' . $matches[1], $icl_settings['language_domains'] ) ) {
								$found_blog_id = $blog_id;
								break;
							}
							if( in_array( $matches[1], $icl_settings['language_domains'] ) ) {
								$found_blog_id = $blog_id;
								break;
							}
						}
					}
				}

				if ( isset( $found_blog_id ) && $found_blog_id ) {
					$q = $wpdb->prepare("SELECT * FROM {$wpdb->blogs} WHERE blog_id = %d ", $found_blog_id );
				}
			}

		}

		$no_recursion = false;

	}


	return $q;
}
// WPML Sunrise Script - END

?>
