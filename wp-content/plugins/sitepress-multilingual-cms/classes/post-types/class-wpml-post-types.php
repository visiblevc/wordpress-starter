<?php

class WPML_Post_Types extends WPML_SP_User {

	public function get_translatable( $exclude_standard = false ) {
		$custom_posts = array();
		$post_types = $this->sitepress->get_translatable_documents( true );

		foreach ( $post_types as $k => $v ) {
			if ( ! $exclude_standard || ! in_array( $k, array( 'post', 'page', 'attachment' ), true ) ) {
				$custom_posts[ $k ] = $v;
			}
		}

		return $custom_posts;
	}

	public function get_readonly() {
		$wp_post_types = $this->sitepress->get_wp_api()->get_wp_post_types_global();

		$types = array();
		$tm_settings = $this->sitepress->get_setting( 'translation-management', array() );
		foreach ( array_keys( $tm_settings['custom-types_readonly_config'] ) as $cp ) {
			if ( isset( $wp_post_types[ $cp ] ) ) {
				$types[ $cp ] = $wp_post_types[ $cp ];
			}
		}

		return $types;
	}

	public function get_translatable_and_readonly( $exclude_standard = false ) {
		return $this->get_translatable( $exclude_standard ) + $this->get_readonly();
	}

}
