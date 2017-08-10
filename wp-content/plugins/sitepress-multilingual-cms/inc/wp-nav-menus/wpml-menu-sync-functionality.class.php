<?php

abstract class WPML_Menu_Sync_Functionality extends WPML_Full_Translation_API {

	private $menu_items_cache;

	/**
	 * @param SitePress             $sitepress
	 * @param wpdb                  $wpdb
	 * @param WPML_Post_Translation $post_translations
	 * @param WPML_Term_Translation $term_translations
	 */
	function __construct( &$sitepress, &$wpdb, &$post_translations, &$term_translations ) {
		parent::__construct( $sitepress, $wpdb, $post_translations, $term_translations );
		$this->menu_items_cache = array();
	}

	function get_menu_items( $menu_id, $translations = true ) {
		$key = $menu_id . '-';
		if ( $translations ) {
			$key .= 'trans';
		} else {
			$key .= 'no-trans';
		}
		
		if ( ! isset( $this->menu_items_cache[ $key ] ) ) {
			
			if ( ! isset( $this->menu_items_cache[ $menu_id ] ) ) {
				$this->menu_items_cache[ $menu_id ] = wp_get_nav_menu_items( (int) $menu_id );
			}
			$items = $this->menu_items_cache[ $menu_id ];
			$menu_items = array();
	
			foreach ( $items as $item ) {
				$item->object_type = get_post_meta( $item->ID, '_menu_item_type', true );
				$_item_add         = array(
					'ID'          => $item->ID,
					'menu_order'  => $item->menu_order,
					'parent'      => $item->menu_item_parent,
					'object'      => $item->object,
					'url'         => $item->url,
					'object_type' => $item->object_type,
					'object_id'   => empty( $item->object_id ) ? get_post_meta( $item->ID,
																				'_menu_item_object_id',
																				true ) : $item->object_id,
					'title'       => $item->title,
					'depth'       => $this->get_menu_item_depth( $item->ID ),
				);
	
				if ( $translations ) {
					$_item_add['translations'] = $this->get_menu_item_translations( $item, $menu_id );
				}
				$menu_items[ $item->ID ] = $_item_add;
			}
			
			$this->menu_items_cache[ $key ] = $menu_items;
		}

		return $this->menu_items_cache[ $key ];
	}

	function sync_menu_translations( $menu_trans_data, $menus ) {
		global $wpdb;

		foreach ( $menu_trans_data as $menu_id => $translations ) {
			foreach ( $translations as $language => $name ) {
				$_POST['icl_translation_of']    = $wpdb->get_var( $wpdb->prepare( "	SELECT term_taxonomy_id
																					FROM {$wpdb->term_taxonomy}
																					WHERE term_id=%d
																						AND taxonomy='nav_menu'
																					LIMIT 1",
				                                                                  $menu_id ) );
				$_POST['icl_nav_menu_language'] = $language;

				$menu_indentation = '';
				$menu_increment   = 0;
				do {
					$new_menu_id      = wp_update_nav_menu_object( 0,
					                                               array(
						                                               'menu-name' => $name . $menu_indentation
						                                                              . ( $menu_increment
								                                               ? $menu_increment : '' )
					                                               )
					);
					$menu_increment   = $menu_increment != '' ? $menu_increment + 1 : 2;
					$menu_indentation = '-';
				} while ( is_wp_error( $new_menu_id ) && $menu_increment < 10 );

				$menus[ $menu_id ]['translations'][ $language ] = array( 'id' => $new_menu_id );
			}
		}

		return $menus;
	}

	/**
	 * @param $item
	 * @param $menu_id
	 *
	 * @return array
	 */
	function get_menu_item_translations( $item, $menu_id ) {
		$languages         = array_keys( $this->sitepress->get_active_languages() );
		$item_translations = $this->post_translations->get_element_translations( $item->ID );
		$languages         = array_diff( $languages, array( $this->sitepress->get_default_language() ) );
		$translations      = array_fill_keys( $languages, false );
		foreach ( $languages as $lang_code ) {
			$item->object_type    = property_exists( $item, 'object_type' ) ? $item->object_type : $item->type;
			$translated_object_id = (int)icl_object_id( $item->object_id,
				( $item->object_type === 'custom' ? 'nav_menu_item' : $item->object ),
				                                   false,
				                                   $lang_code );
			if ( ! $translated_object_id && $item->object_type !== 'custom' ) {
				continue;
			}

			$translated_object_title = '';
			$translated_object_url   = $item->url;
			$icl_st_label_exists     = true;
			$icl_st_url_exists       = true;
			$label_changed           = false;
			$url_changed             = false;

			if ( $item->object_type === 'post_type' ) {
				list( $translated_object_id, $item_translations ) = $this->maybe_reload_post_item( $translated_object_id,
				                                                                                   $item_translations,
				                                                                                   $item,
				                                                                                   $lang_code );
				$translated_object = get_post( $translated_object_id );
				if ( $translated_object->post_status === 'trash' ) {
					$translated_object_id = false;
				} else {
					$translated_object_title = $translated_object->post_title;
				}
			} elseif ( $item->object_type === 'taxonomy' ) {
				$translated_object       = get_term( $translated_object_id,
				                                     get_post_meta( $item->ID, '_menu_item_object', true ) );
				$translated_object_title = $translated_object->name;
			} elseif ( $item->object_type === 'custom' ) {
				$translated_object_title = $item->post_title;
				if ( defined( 'WPML_ST_PATH' ) ) {
					list( $translated_object_url, $translated_object_title, $url_changed, $label_changed ) = $this->st_actions( $lang_code,
					                                                                                                            $menu_id,
					                                                                                                            $item,
					                                                                                                            $translated_object_id,
					                                                                                                            $translated_object_title,
					                                                                                                            $translated_object_url,
																																$icl_st_label_exists,
																																$icl_st_url_exists );
				}
			}
			$this->fix_assignment_to_menu($item_translations, (int)$menu_id);
			$this->fix_language_conflicts();

			$translated_item_id = isset( $item_translations[ $lang_code ] ) ? (int) $item_translations[ $lang_code ] : false;
			$item_depth         = $this->get_menu_item_depth( $translated_item_id );
			if ( $translated_item_id ) {
				$translated_item               = get_post( $translated_item_id );
				$translated_object_title       = ! empty( $translated_item->post_title ) && ! $icl_st_label_exists ? $translated_item->post_title : $translated_object_title;
				$translate_item_parent_item_id = (int) get_post_meta( $translated_item_id,
				                                                      '_menu_item_menu_item_parent',
				                                                      true );
				if ( $item->menu_item_parent > 0
				     && $translate_item_parent_item_id != $this->post_translations->element_id_in( $item->menu_item_parent,
				                                                                                   $lang_code )
				) {
					$translate_item_parent_item_id = 0;
					$item_depth                    = 0;
				}
				$translation = array(
					'menu_order' => $translated_item->menu_order,
					'parent'     => $translate_item_parent_item_id
				);
			} else {
				$translation = array(
					'menu_order' => ( $item->object_type === 'custom' ? $item->menu_order : 0 ),
					'parent'     => 0
				);
			}

			$translation['ID']                    = $translated_item_id;
			$translation['depth']                 = $item_depth;
			$translation['parent_not_translated'] = $this->is_parent_not_translated( $item, $lang_code );
			$translation['object']                = $item->object;
			$translation['object_type']           = $item->object_type;
			$translation['object_id']             = $translated_object_id;
			$translation['title']                 = $translated_object_title;
			$translation['url']                   = $translated_object_url;
			$translation['target']                = $item->target;
			$translation['classes']               = $item->classes;
			$translation['xfn']                   = $item->xfn;
			$translation['attr-title']            = $item->attr_title;
			$translation['label_changed']         = $label_changed;
			$translation['url_changed']           = $url_changed;
			if ( $this->string_translation_default_language_ok() ) {
				$translation['label_missing'] = ! $icl_st_label_exists;
				$translation['url_missing']   = ! $icl_st_url_exists;
			} else {
				$translation['label_missing'] = false;
				$translation['url_missing']   = false;
			}

			$translations[ $lang_code ] = $translation;
		}

		return $translations;
	}

	/**
	 * Synchronises a page menu item's translations' trids according to the trids of the pages they link to.
	 *
	 * @param object $menu_item
	 *
	 * @return int number of affected menu item translations
	 */
	function sync_page_menu_item_trids( $menu_item ) {
		$changed = 0;
		if ( $menu_item->object_type === 'post_type' ) {
			$translations = $this->post_translations->get_element_translations( $menu_item->ID );
			if ( (bool) $translations === true ) {
				get_post_meta( $menu_item->menu_item_parent, '_menu_item_object_id', true );
				$orphans = $this->wpdb->get_results( $this->get_page_orphan_sql( array_keys( $translations ),
				                                                           $menu_item->ID ) );
				if ( (bool) $orphans === true ) {
					$trid = $this->post_translations->get_element_trid( $menu_item->ID );
					foreach ( $orphans as $orphan ) {
						$this->sitepress->set_element_language_details( $orphan->element_id,
						                                                'post_nav_menu_item',
						                                                $trid,
						                                                $orphan->language_code );
						$changed ++;
					}
				}
			}
		}

		return $changed;
	}

	/**
	 * @param  int $menu_id
	 * @param bool $include_original
	 *
	 * @return bool|array
	 */
	function get_menu_translations( $menu_id, $include_original = false ) {
		$languages    = array_keys( $this->sitepress->get_active_languages() );
		$translations = array();
		foreach ( $languages as $lang_code ) {
			if ( $include_original || $lang_code !== $this->sitepress->get_default_language() ) {
				$menu_translated_id = $this->term_translations->term_id_in( $menu_id, $lang_code );
				$menu_data          = array();
				if ( $menu_translated_id ) {
					$menu_object  = $this->wpdb->get_row( $this->wpdb->prepare( "
                        SELECT t.term_id, t.name
                        FROM {$this->wpdb->terms} t
                        JOIN {$this->wpdb->term_taxonomy} x
                        	ON t.term_id = t.term_id
                        WHERE t.term_id = %d
                        	AND x.taxonomy='nav_menu'
                        LIMIT 1",
					                                                $menu_translated_id ) );
					$current_lang = $this->sitepress->get_current_language();
					$this->sitepress->switch_lang( $lang_code, false );
					$menu_data = array(
						'id'    => $menu_object->term_id,
						'name'  => $menu_object->name,
						'items' => $this->get_menu_items( $menu_translated_id, false )
					);
					$this->sitepress->switch_lang( $current_lang, false );
				}
				$translations[ $lang_code ] = $menu_data;
			}
		}

		return $translations;
	}

	/**
	 * @todo Handle this differently once non-English ST languages are possible
	 *
	 * @return bool
	 */
	protected function string_translation_default_language_ok() {

		return $this->sitepress->get_default_language() === 'en';
	}

	protected function get_menu_name( $menu_id ) {
		$menu = wp_get_nav_menu_object( $menu_id );

		return $menu ? $menu->name : false;
	}

	/**
	 * @param $menu_id
	 * @param $language_code
	 *
	 * @return bool
	 */
	protected function get_translated_menu( $menu_id, $language_code = false ) {
		$language_code = $language_code ? $language_code : $this->sitepress->get_default_language();
		$menus         = $this->get_menu_translations( $menu_id, true );

		return isset( $menus[ $language_code ] ) ? $menus[ $language_code ] : false;
	}

	protected function icl_t_menu_item( $menu_name, $item, $lang_code, &$icl_st_label_exists, &$icl_st_url_exists ) {
		$translated_object_title_t = icl_t( $menu_name . ' menu',
		                                    'Menu Item Label ' . $item->ID,
		                                    $item->post_title,
		                                    $icl_st_label_exists,
		                                    true,
		                                    $lang_code );
		$translated_object_url_t   = icl_t( $menu_name . ' menu',
		                                    'Menu Item URL ' . $item->ID,
		                                    $item->url,
		                                    $icl_st_url_exists,
		                                    true,
		                                    $lang_code );

		return array( $translated_object_title_t, $translated_object_url_t );
	}

	/**
	 * @param object $item
	 * @param string $lang_code
	 *
	 * @return int
	 */
	private function is_parent_not_translated( $item, $lang_code ) {

		if ( $item->menu_item_parent > 0 ) {
			$item_parent_object_id = get_post_meta( $item->menu_item_parent, '_menu_item_object_id', true );
			$item_parent_object    = get_post_meta( $item->menu_item_parent, '_menu_item_object', true );
			$parent_element_type   = $item_parent_object === 'custom' ? 'nav_menu_item' : $item_parent_object;
			$parent_translated     = icl_object_id( $item_parent_object_id,
			                                        $parent_element_type,
			                                        false,
			                                        $lang_code );
		}

		return isset( $parent_translated ) && ! $parent_translated ? 1 : 0;
	}

	private function get_page_orphan_sql( $existing_languages, $menu_item_id ) {
		$wpdb = &$this->wpdb;

		return $wpdb->prepare(
			"SELECT it.element_id, it.language_code
			FROM {$wpdb->prefix}icl_translations it
			JOIN {$wpdb->posts} pt
				ON pt.ID = it.element_id
					AND pt.post_type = 'nav_menu_item'
					AND it.element_type = 'post_nav_menu_item'
					AND it.language_code NOT IN (" . wpml_prepare_in( $existing_languages ) . ")
			JOIN {$wpdb->prefix}icl_translations io
				ON io.element_id = %d
					AND io.element_type = 'post_nav_menu_item'
					AND io.trid != it.trid
			JOIN {$wpdb->posts} po
				ON po.ID = io.element_id
					AND po.post_type = 'nav_menu_item'
			JOIN {$wpdb->postmeta} mo
				ON mo.post_id = po.ID
					AND mo.meta_key = '_menu_item_object_id'
			JOIN {$wpdb->postmeta} mt
				ON mt.post_id = pt.ID
					AND mt.meta_key = '_menu_item_object_id'
			JOIN {$wpdb->prefix}icl_translations page_t
				ON mt.meta_value = page_t.element_id
					AND page_t.element_type = 'post_page'
			JOIN {$wpdb->prefix}icl_translations page_o
				ON mo.meta_value = page_o.element_id
					AND page_o.trid = page_t.trid
			WHERE ( SELECT COUNT(count.element_id)
					FROM {$wpdb->prefix}icl_translations count
					WHERE count.trid = it.trid ) = 1",
			$menu_item_id );
	}

	private function maybe_reload_post_item( $translated_object_id, $item_translations, $item, $lang_code ) {
		if ( $this->sync_page_menu_item_trids( $item ) > 0 ) {
			$item_translations    = $this->post_translations->get_element_translations( $item->ID );
			$translated_object_id = $this->post_translations->element_id_in( $item->object_id,
			                                                                 $lang_code );
			$translated_object_id = $translated_object_id === null ? false : $translated_object_id;
		}

		return array( $translated_object_id, $item_translations );
	}

	private function get_menu_item_depth( $item_id ) {
		$depth = 0;
		do {
			$object_parent = get_post_meta( $item_id, '_menu_item_menu_item_parent', true );
			if ( $object_parent == $item_id ) {
				$depth = 0;
				break;
			} elseif ( $object_parent ) {
				$item_id = $object_parent;
				$depth ++;
			}
		} while ( $object_parent > 0 );

		return $depth;
	}

	private function st_actions( $lang_code,
								 $menu_id,
								 $item,
								 $translated_object_id,
								 $translated_object_title,
								 $translated_object_url,
								 &$icl_st_label_exists,
								 &$icl_st_url_exists ) {
		if ( ! function_exists( 'icl_translate' ) ) {
			require WPML_ST_PATH . '/inc/functions.php';
		}

		$this->sitepress->switch_lang( $lang_code );

		$label_changed             = false;
		$url_changed               = false;
		$menu_name                 = $this->get_menu_name( $menu_id );
		$translated_object_title_t = '';
		$translated_object_url_t   = '';
		$translated_menu_id        = $this->term_translations->term_id_in( $menu_id, $lang_code );

		if ( function_exists( 'icl_t' ) && $this->string_translation_default_language_ok() ) {
			list( $translated_object_title_t, $translated_object_url_t ) = $this->icl_t_menu_item( $menu_name,
			                                                                                       $item,
			                                                                                       $lang_code,
			                                                                                       $icl_st_label_exists,
			                                                                                       $icl_st_url_exists );
		} elseif ( $translated_object_id && isset( $item_translations[ $lang_code ] ) ) {
			$translated_menu_items = wp_get_nav_menu_items( $translated_menu_id );
			foreach ( $translated_menu_items as $translated_menu_item ) {
				if ( $translated_menu_item->ID == $translated_object_id ) {
					$translated_object_title_t  = $translated_menu_item->title;
					$translated_object_url_t    = $translated_menu_item->url;
					$translated_menu_item_found = true;
					break;
				}
			}
			if ( empty( $translated_menu_item_found ) ) {
				$translated_object_title_t = $item->post_title . ' @' . $lang_code;
				$translated_object_url_t   = $item->url;
			}
		} else {
			$translated_object_title_t = $item->post_title . ' @' . $lang_code;
			$translated_object_url_t   = $item->url;
		}
			
		$this->sitepress->switch_lang();

		if ( $translated_object_id ) {
			$translated_object = get_post( $translated_object_id );
			if ( $this->string_translation_default_language_ok() ) {
				$label_changed = $translated_object_title_t != $translated_object->post_title;
				$url_changed   = $translated_object_url_t != get_post_meta( $translated_object_id,
				                                                            '_menu_item_url',
				                                                            true );
			}

			$translated_object_title = $icl_st_label_exists ? $translated_object_title_t : $translated_object_title;
			$translated_object_url   = $icl_st_url_exists ? $translated_object_url_t : $translated_object_url;
		}

		return array(
			$translated_object_url,
			$translated_object_title,
			$url_changed,
			$label_changed
		);
	}

	/**
	 * @param $item_translations
	 * @param $menu_id
	 */
	private function fix_assignment_to_menu( $item_translations, $menu_id ) {
		foreach ($item_translations as $lang_code => $item_id) {
			$correct_menu_id = $this->term_translations->term_id_in( $menu_id, $lang_code );
			if ($correct_menu_id) {
				$ttid_trans = $this->wpdb->get_var( $this->wpdb->prepare( "	SELECT tt.term_taxonomy_id
																			FROM {$this->wpdb->term_taxonomy} tt
																			LEFT JOIN {$this->wpdb->term_relationships} tr
																				ON tt.term_taxonomy_id = tr.term_taxonomy_id
																					AND tr.object_id = %d
																			WHERE tt.taxonomy = 'nav_menu'
																				AND tt.term_id = %d
																				AND tr.term_taxonomy_id IS NULL
																			LIMIT 1", $item_id, $correct_menu_id ) );
				if ($ttid_trans) {
					$this->wpdb->insert( $this->wpdb->term_relationships, array('object_id' => $item_id, 'term_taxonomy_id' => $ttid_trans) );
				}
			}
		}
	}

	/**
	 * Removes potentially mis-assigned menu items from their menu, whose language differs from that of their
	 * associated menu.
	 */
	private function fix_language_conflicts(){
		$wrong_items = $this->wpdb->get_results( "	SELECT r.object_id, t.term_taxonomy_id
													FROM {$this->wpdb->term_relationships} r
													  JOIN {$this->wpdb->prefix}icl_translations ip
													  JOIN {$this->wpdb->posts} p
														ON ip.element_type = CONCAT('post_', p.post_type)
														   AND ip.element_id = p.ID
														   AND ip.element_id = r.object_id
													  JOIN {$this->wpdb->prefix}icl_translations it
													  JOIN {$this->wpdb->term_taxonomy} t
														ON it.element_type = CONCAT('tax_', t.taxonomy)
														   AND it.element_id = t.term_taxonomy_id
														   AND it.element_id = r.term_taxonomy_id
													WHERE p.post_type = 'nav_menu_item'
													  AND t.taxonomy = 'nav_menu'
													  AND ip.language_code != it.language_code" );
		foreach ($wrong_items as $item) {
			$this->wpdb->delete( $this->wpdb->term_relationships, array('object_id' => $item->object_id, 'term_taxonomy_id' => $item->term_taxonomy_id) );
		}
	}
}