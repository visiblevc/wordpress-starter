<?php

class WPML_Menu_Item_Sync extends WPML_Menu_Sync_Functionality {

	/** @var array $labels_to_add */
	private $labels_to_add = array();
	/** @var array $urls_to_add */
	private $urls_to_add = array();

	/**
	 * @return int the number of removed broken page items
	 */
	function cleanup_broken_page_items() {

		return $this->wpdb->query( "
			DELETE o FROM {$this->wpdb->term_relationships} o
			JOIN {$this->wpdb->postmeta} pm
				ON pm.post_id = o.object_id
			JOIN {$this->wpdb->posts} p
				ON p.ID = pm.post_id
			JOIN {$this->wpdb->postmeta} pm_type
				ON pm_type.post_id = pm.post_id
			WHERE p.post_type = 'nav_menu_item'
				AND pm.meta_key = '_menu_item_object_id'
				AND pm_type.meta_key = '_menu_item_type'
				AND pm_type.meta_value = 'post_type'
				AND pm.meta_value = 0" );
	}

	function sync_deleted_menus( $deleted_data ) {
		foreach ( $deleted_data as $languages ) {
			foreach ( $languages as $items ) {
				foreach ( $items as $item_id => $name ) {
					wp_delete_post( $item_id, true );
					$delete_trid = $this->post_translations->get_element_trid( $item_id );
					if ( $delete_trid ) {
						$this->sitepress->delete_element_translation( $delete_trid, 'post_nav_menu_item' );
					}
				}
			}
		}
	}

	function sync_menu_options( $options_data ) {
		foreach ( $options_data as $menu_id => $translations ) {
			foreach ( $translations as $language => $option ) {
				$translated_menu_id = $this->term_translations->term_id_in( $menu_id, $language );
				if ( isset( $option['auto_add'] ) ) {
					$nav_menu_option             = (array) get_option( 'nav_menu_options' );
					$nav_menu_option['auto_add'] = isset( $nav_menu_option['auto_add'] ) ? $nav_menu_option['auto_add'] : array();
					if ( $option['auto_add'] && ! in_array( $translated_menu_id, $nav_menu_option['auto_add'] ) ) {
						$nav_menu_option['auto_add'][] = $translated_menu_id;
					} elseif ( ! $option['auto_add'] && false !== ( $key = array_search( $translated_menu_id,
					                                                                     $nav_menu_option['auto_add'] ) )
					) {
						unset( $nav_menu_option['auto_add'][ $key ] );
					}
					$nav_menu_option['auto_add'] = array_intersect( $nav_menu_option['auto_add'],
					                                                wp_get_nav_menus( array( 'fields' => 'ids' ) ) );
					update_option( 'nav_menu_options', array_filter( $nav_menu_option ) );
					wp_defer_term_counting( false );
					do_action( 'wp_update_nav_menu', $translated_menu_id );
				}
			}
		}
	}

	function sync_menu_order( array $menus ) {
		global $wpdb;

		foreach ( $menus as $menu_id => $menu ) {
			$menu_index_by_lang = array();
			foreach ( $menu['items'] as $item_id => $item ) {
				foreach ( $item['translations'] as $language => $item_translation ) {
					if ( $item_translation['ID'] ) {
						$new_menu_order                  = empty( $menu_index_by_lang[ $language ] ) ? 1 : $menu_index_by_lang[ $language ] + 1;
						$menu_index_by_lang[ $language ] = $new_menu_order;
						if ( $new_menu_order != $menus[ $menu_id ]['items'][ $item_id ]['translations'][ $language ]['menu_order'] ) {
							$menus[ $menu_id ]['items'][ $item_id ]['translations'][ $language ]['menu_order'] = $new_menu_order;
							$wpdb->update( $wpdb->posts,
							               array( 'menu_order' => $new_menu_order ),
							               array( 'ID' => $item_translation['ID'] ) );
						}
					}
				}
			}
		}

		return $menus;
	}

	function sync_added_items( array $added_data, array $menus ) {
		global $wpdb;

		$current_language = $this->sitepress->get_current_language();
		foreach ( $added_data as $menu_id => $items ) {
			foreach ( $items as $language => $translations ) {
				foreach ( $translations as $item_id => $name ) {
					$trid                = $this->get_or_set_trid( $item_id, $this->sitepress->get_default_language() );
					$translated_object   = $menus[ $menu_id ]['items'][ $item_id ]['translations'][ $language ];
					$menu_name           = $this->get_menu_name( $menu_id );
					$object_type         = $translated_object['object_type'];
					$object_title        = $translated_object['title'];
					$object_url          = $translated_object['url'];
					$icl_st_label_exists = false;
					$icl_st_url_exists   = false;
					if ( $object_type === 'custom' && ( function_exists( 'icl_t' ) || ! $this->string_translation_default_language_ok() ) ) {
						if ( function_exists( 'icl_t' ) ) {
							$this->sitepress->switch_lang( $language, false );
							$item             = new stdClass();
							$item->url        = $object_url;
							$item->ID         = $item_id;
							$item->post_title = $object_title;
							list( $object_title, $object_url ) = $this->icl_t_menu_item( $menu_name,
							                                                             $item,
							                                                             $language,
							                                                             $icl_st_label_exists,
							                                                             $icl_st_url_exists );
							$this->sitepress->switch_lang( $current_language, false );

							if ( ! $icl_st_label_exists ) {
								if( isset( $current_language ) ) {
									icl_register_string( $menu_name . ' menu',
										'Menu Item Label ' . $item_id,
										$object_title, false, $current_language );
								} else {
									icl_register_string( $menu_name . ' menu',
										'Menu Item Label ' . $item_id,
										$object_title );
								}
							}
							if ( ! $icl_st_url_exists ) {
								if( isset( $current_language ) ) {
									icl_register_string( $menu_name . ' menu', 'Menu Item URL ' . $item_id, $object_url, false, $current_language );
								} else {
									icl_register_string( $menu_name . ' menu', 'Menu Item URL ' . $item_id, $object_url );
								}
							}
						} else {
							$object_title = $name;
						}
					}

					$menu_data = array(
						'menu-item-db-id'       => 0,
						'menu-item-object-id'   => $translated_object['object_id'],
						'menu-item-object'      => $translated_object['object'],
						'menu-item-parent-id'   => 0,
						'menu-item-position'    => 0,
						'menu-item-type'        => $object_type,
						'menu-item-title'       => $object_title,
						'menu-item-url'         => $object_url,
						'menu-item-description' => '',
						'menu-item-attr-title'  => $translated_object['attr-title'],
						'menu-item-target'      => $translated_object['target'],
						'menu-item-classes'     => ( $translated_object['classes'] ? implode( ' ',
						                                                                      $translated_object['classes'] ) : '' ),
						'menu-item-xfn'         => $translated_object['xfn'],
						'menu-item-status'      => 'publish',
					);

					$translated_menu_id = $menus[ $menu_id ]['translations'][ $language ]['id'];

					remove_filter( 'get_term', array( $this->sitepress, 'get_term_adjust_id' ), 1 );
					$translated_item_id = wp_update_nav_menu_item( $translated_menu_id, 0, $menu_data );

					// set language explicitly since the 'wp_update_nav_menu_item' is still TBD
					$this->sitepress->set_element_language_details( $translated_item_id,
					                                                'post_nav_menu_item',
					                                                $trid,
					                                                $language );

					$menu_tax_id_prepared = $wpdb->prepare( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu' LIMIT 1",
					                                        $translated_menu_id );
					$menu_tax_id          = $wpdb->get_var( $menu_tax_id_prepared );

					if ( $translated_item_id && $menu_tax_id ) {
						$rel_prepared = $wpdb->prepare( "SELECT object_id FROM {$wpdb->term_relationships} WHERE object_id=%d AND term_taxonomy_id=%d LIMIT 1",
						                                $translated_item_id,
						                                $menu_tax_id );
						$rel          = $wpdb->get_var( $rel_prepared );
						if ( ! $rel ) {
							$wpdb->insert( $wpdb->term_relationships,
							               array(
								               'object_id'        => $translated_item_id,
								               'term_taxonomy_id' => $menu_tax_id
							               ) );
						}
					}
					
					$menus[ $menu_id ][ 'items' ][ $item_id ][ 'translations' ][ $language ][ 'ID' ] = $translated_item_id;
				}
			}
		}
		$this->fix_hierarchy_added_items( $added_data );

		return $menus;
	}

	function sync_moved_items( array $moved_data, array $menus ) {
		global $wpdb;

		foreach ( $moved_data as $menu_id => $items ) {
			foreach ( $items as $language => $changes ) {
				foreach ( $changes as $item_id => $details ) {
					$trid               = $this->get_or_set_trid( $item_id, $this->sitepress->get_default_language() );
					$translated_item_id = $menus[ $menu_id ]['items'][ $item_id ]['translations'][ $language ]['ID'];

					$new_menu_order                                                                    = key( $details );
					$menus[ $menu_id ]['items'][ $item_id ]['translations'][ $language ]['menu_order'] = $new_menu_order;

					$wpdb->update( $wpdb->posts,
					               array( 'menu_order' => $new_menu_order ),
					               array( 'ID' => $translated_item_id ) );

					if ( $this->post_translations->get_element_trid( $translated_item_id ) != $trid ) {
						$this->sitepress->set_element_language_details( $translated_item_id,
																		'post_nav_menu_item',
																		$trid,
																		$language );
					}
				}
			}
		}
		$this->fix_hierarchy_moved_items( $moved_data );

		return $menus;
	}

	function sync_caption( $label_change_data ) {
		foreach ( $label_change_data as $languages ) {
			foreach ( $languages as $language => $items ) {
				foreach ( $items as $item_id => $name ) {
					$trid = $this->sitepress->get_element_trid( $item_id, 'post_nav_menu_item' );
					if ( $trid ) {
						$item_translations = $this->sitepress->get_element_translations( $trid,
						                                                                 'post_nav_menu_item',
						                                                                 true );
						if ( isset( $item_translations[ $language ] ) ) {
							$translated_item             = get_post( $item_translations[ $language ]->element_id );
							if ( $translated_item->post_title != $name ) {
								$translated_item->post_title = $name;
								wp_update_post( $translated_item );
							}
						}
					}
				}
			}
		}
	}

	function sync_urls( $url_change_data ) {
		foreach ( $url_change_data as $languages ) {
			foreach ( $languages as $language => $items ) {
				foreach ( $items as $item_id => $url ) {
					$trid = $this->sitepress->get_element_trid( $item_id, 'post_nav_menu_item' );
					if ( $trid ) {
						$item_translations = $this->sitepress->get_element_translations( $trid,
						                                                                 'post_nav_menu_item',
						                                                                 true );
						if ( isset( $item_translations[ $language ] ) ) {
							$translated_item_id = $item_translations[ $language ]->element_id;
							if ( $url ) {
								update_post_meta( $translated_item_id, '_menu_item_url', $url );
							}
						}
					}
				}
			}
		}
	}

	function sync_missing_captions( $label_missing ) {
		foreach ( $label_missing as $menu_id => $languages ) {
			foreach ( $languages as $items ) {
				foreach ( $items as $item_id => $name ) {
					if ( ! in_array( $menu_id . '-' . $item_id, $this->labels_to_add ) ) {
						$item = get_post( $item_id );
						icl_register_string( $this->get_menu_name( $menu_id ) . ' menu',
						                     'Menu Item Label ' . $item_id,
						                     $item->post_title );
						$this->labels_to_add[] = $menu_id . '-' . $item_id;
					}
				}
			}
		}
	}

	function sync_urls_to_add( $url_missing_data ) {
		foreach ( $url_missing_data as $menu_id => $languages ) {
			foreach ( $languages as $items ) {
				foreach ( $items as $item_id => $url ) {
					if ( ! in_array( $menu_id . '-' . $item_id, $this->urls_to_add ) ) {
						icl_register_string( $this->get_menu_name( $menu_id ) . ' menu',
						                     'Menu Item URL ' . $item_id,
						                     $url );
						$this->urls_to_add[] = $menu_id . '-' . $item_id;
					}
				}
			}
		}
	}

	private function fix_hierarchy_added_items( $added_data ) {
		foreach ( $added_data as $menu_id => $items ) {
			foreach ( $items as $language => $translations ) {
				foreach ( $translations as $item_id => $name ) {
					$this->fix_hierarchy_for_item( $item_id, $language );
				}
			}
		}
	}

	private function fix_hierarchy_moved_items( $moved_data ) {
		foreach ( $moved_data as $menu_id => $items ) {
			foreach ( $items as $language => $changes ) {
				foreach ( $changes as $item_id => $details ) {
					$this->fix_hierarchy_for_item( $item_id, $language );
				}
			}
		}
	}
	
	private function fix_hierarchy_for_item( $item_id, $language ) {
		$parent_item                    = get_post_meta( $item_id, '_menu_item_menu_item_parent', true );
		$translated_item_id             = $this->post_translations->element_id_in( $item_id,
																				   $language );
		$translated_parent_menu_item_id = $this->post_translations->element_id_in( $parent_item,
																				   $language );
		$translated_parent_menu_item_id = $translated_parent_menu_item_id == $translated_item_id
			? false : $translated_parent_menu_item_id;

		update_post_meta( $translated_item_id,
						  '_menu_item_menu_item_parent',
						  $translated_parent_menu_item_id );
		
	}

	private function get_or_set_trid( $item_id, $language_code ) {
		$trid = $this->post_translations->get_element_trid( $item_id );
		if ( ! $trid ) {
			$this->sitepress->set_element_language_details( $item_id,
			                                                'post_nav_menu_item',
			                                                false,
			                                                $language_code );
			$trid = $this->post_translations->get_element_trid( $item_id );
		}

		return $trid;
	}
}