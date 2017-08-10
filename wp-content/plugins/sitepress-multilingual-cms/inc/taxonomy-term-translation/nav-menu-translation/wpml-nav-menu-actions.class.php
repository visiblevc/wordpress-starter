<?php

/**
 * Class WPML_Nav_Menu_Actions
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 */
class WPML_Nav_Menu_Actions extends WPML_Full_Translation_API {

	/**
	 * @param SitePress             $sitepress
	 * @param wpdb                  $wpdb
	 * @param WPML_Post_Translation $post_translations
	 * @param WPML_Term_Translation $term_translations
	 */
	public function __construct(&$sitepress, &$wpdb, &$post_translations, &$term_translations) {
		parent::__construct( $sitepress, $wpdb, $post_translations, $term_translations );
		add_action ( 'wp_delete_nav_menu', array( $this, 'wp_delete_nav_menu' ) );
		add_action ( 'wp_create_nav_menu', array( $this, 'wp_update_nav_menu' ), 10, 2 );
		add_action ( 'wp_update_nav_menu', array( $this, 'wp_update_nav_menu' ), 10, 2 );
		add_action ( 'wp_update_nav_menu_item', array( $this, 'wp_update_nav_menu_item' ), 10, 3 );
		add_action ( 'delete_post', array( $this, 'wp_delete_nav_menu_item' ) );
		add_filter ( 'pre_update_option_theme_mods_' . get_option( 'stylesheet' ), array( $this, 'pre_update_theme_mods_theme' ) );
		if(is_admin()){
			add_filter('theme_mod_nav_menu_locations', array($this, 'theme_mod_nav_menu_locations'));
		}
	}

	public function wp_delete_nav_menu( $id ) {
		$menu_id_tt = $this->wpdb->get_var (
			$this->wpdb->prepare (
				"SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",
				$id
			)
		);

		$update_args = array(
			'element_id' => $menu_id_tt,
			'element_type' => 'tax_nav_menu',
			'context' => 'tax'
		);
		do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

		$q          = "DELETE FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type='tax_nav_menu' LIMIT 1";
		$q_prepared = $this->wpdb->prepare ( $q, $menu_id_tt );
		$this->wpdb->query ( $q_prepared );

		do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );
	}

	function wp_update_nav_menu( $menu_id, $menu_data = null ) {
		if ( $menu_data ) {
			$trid          = $this->get_trid_from_post_data();
			$language_code = $this->get_save_lang( $menu_id );
			$menu_id_tt    = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu' LIMIT 1",
					$menu_id
				)
			);
			$this->term_translations->reload();
			$this->sitepress->set_element_language_details( $menu_id_tt, 'tax_nav_menu', $trid, $language_code );
		}
	}

	function wp_update_nav_menu_item( $menu_id, $menu_item_db_id, $args ) {
		$menu_lang = $this->term_translations->lang_code_by_termid( $menu_id );
		$trid      = $this->post_translations->get_element_trid( $menu_item_db_id );
		if ( array_key_exists( 'menu-item-type', $args )
		     && ( $args['menu-item-type'] === 'post_type' || $args['menu-item-type'] === 'taxonomy' )
		     && array_key_exists( 'menu-item-object-id', $args )
		     && $menu_id > 0
		) {
			$language_code_item = $args['menu-item-type'] === 'post_type'
				? $this->post_translations->get_element_lang_code( $args['menu-item-object-id'] )
				: $this->term_translations->lang_code_by_termid( $args['menu-item-object-id'] );
			$language_code_item = $language_code_item ? $language_code_item : $this->sitepress->get_current_language();
			if ( $language_code_item !== $menu_lang ) {
				wp_remove_object_terms( (int) $menu_item_db_id, (int) $menu_id, 'nav_menu' );
			}
		}

		$language_code = isset( $language_code_item ) && $language_code_item
			? $language_code_item : ( $menu_lang ? $menu_lang : $this->sitepress->get_current_language() );
		$this->sitepress->set_element_language_details( $menu_item_db_id, 'post_nav_menu_item', $trid, $language_code );
	}

	public function wp_delete_nav_menu_item( $menu_item_id ) {
		$post = get_post( $menu_item_id );
		if ( ! empty( $post->post_type ) && $post->post_type == 'nav_menu_item' ) {

			$update_args = array(
				'element_id' => $menu_item_id,
				'element_type' => 'post_nav_menu_item',
				'context' => 'post'
			);

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'before_delete' ) ) );

			$q          = "DELETE FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type='post_nav_menu_item' LIMIT 1";
			$q_prepared = $this->wpdb->prepare( $q, $menu_item_id );
			$this->wpdb->query( $q_prepared );

			do_action( 'wpml_translation_update', array_merge( $update_args, array( 'type' => 'after_delete' ) ) );
		}
	}

	public function pre_update_theme_mods_theme( $val ) {
		$default_language = $this->sitepress->get_default_language ();
		$current_language = $this->sitepress->get_current_language ();

		if ( isset( $val[ 'nav_menu_locations' ] )
		     && filter_input ( INPUT_GET, 'action' ) === 'delete'
		     && $current_language !== $default_language
		) {
			$val[ 'nav_menu_locations' ] = get_theme_mod ( 'nav_menu_locations' );
		}

		if ( isset( $val[ 'nav_menu_locations' ] ) ) {
			foreach ( (array) $val[ 'nav_menu_locations' ] as $k => $v ) {
				if ( !$v && $current_language !== $default_language ) {
					$tl = get_theme_mod ( 'nav_menu_locations' );
					if ( isset( $tl[ $k ] ) ) {
						$val[ 'nav_menu_locations' ][ $k ] = $tl[ $k ];
					}
				} else {
					$val[ 'nav_menu_locations' ][ $k ] = icl_object_id (
						$val[ 'nav_menu_locations' ][ $k ],
						'nav_menu',
						true,
						$default_language
					);
				}
			}
		}

		return $val;
	}

	public function theme_mod_nav_menu_locations( $theme_locations ) {
		if ( is_admin() && (bool) $theme_locations === true ) {
			$current_lang = $this->sitepress->get_current_language();
			foreach ( (array) $theme_locations as $location => $menu_id ) {
				$translated_menu_id = $this->term_translations->term_id_in( $menu_id, $current_lang );
				if ( $translated_menu_id ) {
					$theme_locations[ $location ] = $translated_menu_id;
				}
			}
		}

		return $theme_locations;
	}

	private function get_save_lang( $menu_id ) {
		$language_code = isset( $_POST[ 'icl_nav_menu_language' ] )
				? $_POST[ 'icl_nav_menu_language' ] : $this->term_translations->lang_code_by_termid ( $menu_id );
		$language_code = $language_code ? $language_code : $this->sitepress->get_current_language();

		return $language_code;
	}

	/**
	 * @return bool|int|mixed|null|string
	 */
	private function get_trid_from_post_data() {
		$trid = null;
		if ( ! empty( $_POST['icl_translation_of'] ) && $_POST['icl_translation_of'] !== 'none' ) {
			$trid = $this->sitepress->get_element_trid( $_POST['icl_translation_of'], 'tax_nav_menu' );

			return $trid;
		} elseif ( isset( $_POST['icl_nav_menu_trid'] ) ) {
			$trid = ( (int) $_POST['icl_nav_menu_trid'] );

			return $trid;
		}

		return $trid;
	}
}