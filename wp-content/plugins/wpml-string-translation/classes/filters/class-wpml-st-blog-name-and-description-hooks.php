<?php

class WPML_ST_Blog_Name_And_Description_Hooks extends WPML_SP_User {

	public function __construct( $sitepress ) {
		parent::__construct( $sitepress );
	}

	public function init_hooks() {
		$wp_api = $this->sitepress->get_wp_api();
		
		if ( ! $wp_api->is_customize_page() ) {
			$wp_api->add_filter('option_blogname', 'wpml_st_blog_title_filter' );
			$wp_api->add_filter('option_blogdescription', 'wpml_st_blog_description_filter' );
		}
	}
}