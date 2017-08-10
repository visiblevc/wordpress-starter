<?php

class WPML_Settings_Helper extends WPML_SP_And_PT_User {

	function set_post_type_translatable( $post_type ) {
		$sync_settings               = $this->sitepress->get_setting( 'custom_posts_sync_option', array() );
		$sync_settings[ $post_type ] = 1;
		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'custom_posts_sync_option', $sync_settings, true );
		$this->sitepress->verify_post_translations( $post_type );
		$this->post_translation->reload();
	}

	function set_post_type_not_translatable( $post_type ) {
		$sync_settings = $this->sitepress->get_setting( 'custom_posts_sync_option', array() );
		if ( isset( $sync_settings[ $post_type ] ) ) {
			unset( $sync_settings[ $post_type ] );
		}

		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'custom_posts_sync_option', $sync_settings, true );
	}

	function set_taxonomy_translatable( $taxonomy ) {
		$sync_settings              = $this->sitepress->get_setting( 'taxonomies_sync_option', array() );
		$sync_settings[ $taxonomy ] = 1;
		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'taxonomies_sync_option', $sync_settings, true );
		$this->sitepress->verify_taxonomy_translations( $taxonomy );
	}

	function set_taxonomy_not_translatable( $taxonomy ) {
		$sync_settings = $this->sitepress->get_setting( 'taxonomies_sync_option', array() );
		if ( isset( $sync_settings[ $taxonomy ] ) ) {
			unset( $sync_settings[ $taxonomy ] );
		}

		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'taxonomies_sync_option', $sync_settings, true );
	}

	function activate_slug_translation( $post_type ) {
		$slug_settings                          = $this->sitepress->get_setting( 'posts_slug_translation', array() );
		$slug_settings[ 'types' ]               = isset( $slug_settings[ 'types' ] )
			? $slug_settings[ 'types' ] : array();
		$slug_settings[ 'types' ][ $post_type ] = 1;
		$slug_settings[ 'on' ]                  = 1;

		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'posts_slug_translation', $slug_settings, true );
	}

	function deactivate_slug_translation( $post_type ) {
		$slug_settings = $this->sitepress->get_setting( 'posts_slug_translation', array() );
		if ( isset( $slug_settings[ 'types' ][ $post_type ] ) ) {
			unset( $slug_settings[ 'types' ][ $post_type ] );
		}

		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'posts_slug_translation', $slug_settings, true );
	}

	/**
	 * @param array[] $taxs_obj_type
	 *
	 * @see \WPML_Config::maybe_add_filter
	 *
	 * @return array
	 */
	function _override_get_translatable_taxonomies( $taxs_obj_type ) {
		global $wp_taxonomies;

		$taxs        = $taxs_obj_type['taxs'];
		$object_type = $taxs_obj_type['object_type'];
		foreach ( $taxs as $k => $tax ) {
			if ( ! $this->sitepress->is_translated_taxonomy( $tax ) ) {
				unset( $taxs[ $k ] );
			}
		}
		$tm_settings = $this->sitepress->get_setting( 'translation-management', array() );
		foreach ( $tm_settings['taxonomies_readonly_config'] as $tx => $translate ) {
			if ( $translate
			     && ! in_array( $tx, $taxs )
			     && isset( $wp_taxonomies[ $tx ] )
			     && in_array( $object_type, $wp_taxonomies[ $tx ]->object_type )
			) {
				$taxs[] = $tx;
			}
		}

		$ret = array( 'taxs' => $taxs, 'object_type' => $taxs_obj_type['object_type'] );

		return $ret;
	}

	/**
	 * @param array[] $types
	 *
	 * @see \WPML_Config::maybe_add_filter
	 *
	 * @return array
	 */
	function _override_get_translatable_documents( $types ) {
		global $wp_post_types;

		$tm_settings = $this->sitepress->get_setting('translation-management', array());
		foreach ( $types as $k => $type ) {
			if ( isset( $tm_settings[ 'custom-types_readonly_config' ][ $k ] )
				 && ! $tm_settings[ 'custom-types_readonly_config' ][ $k ]
			) {
				unset( $types[ $k ] );
			}
		}
		foreach ( $tm_settings[ 'custom-types_readonly_config' ] as $cp => $translate ) {
			if ( $translate && ! isset( $types[ $cp ] ) && isset( $wp_post_types[ $cp ] ) ) {
				$types[ $cp ] = $wp_post_types[ $cp ];
			}
		}

		return $types;
	}

	/**
	 * Updates the custom post type translation settings with new settings.
	 *
	 * @param array $new_options
	 *
	 * @uses \SitePress::get_setting
	 * @uses \SitePress::save_settings
	 *
	 * @return array new custom post type settings after the update
	 */
	function update_cpt_sync_settings( array $new_options ) {
		$cpt_sync_options = $this->sitepress->get_setting( 'custom_posts_sync_option', array() );
		$cpt_sync_options = array_merge( $cpt_sync_options, $new_options );
		$new_options      = array_filter( $new_options );

		$this->clear_ls_languages_cache();

		do_action( 'wpml_verify_post_translations', $new_options );
		do_action( 'wpml_save_cpt_sync_settings' );
		$this->sitepress->set_setting( 'custom_posts_sync_option', $cpt_sync_options, true );

		return $cpt_sync_options;
	}

	/**
	 * @param string $config_type
	 */
	function maybe_add_filter( $config_type ) {
		if ( $config_type === 'taxonomies' ) {
			add_filter( 'get_translatable_taxonomies',
			            array( $this, '_override_get_translatable_taxonomies' ) );
		} elseif ( $config_type === 'custom-types' ) {
			add_filter( 'get_translatable_documents',
			            array( $this, '_override_get_translatable_documents' ) );
		}
	}

	private function clear_ls_languages_cache() {
		$cache = new WPML_WP_Cache( 'ls_languages' );
		$cache->flush_group_cache();
	}
}