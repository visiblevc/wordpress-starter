<?php
require dirname( __FILE__ ) . '/wpml-menu-sync-functionality.class.php';
require dirname( __FILE__ ) . '/menu-item-sync.class.php';

class ICLMenusSync extends WPML_Menu_Sync_Functionality {
    public $menus;
    public $is_preview               = false;
    public $sync_data                = false;
    public $string_translation_links = array();
    public $operations               = array();
	/** @var  WPML_Menu_Item_Sync $menu_item_sync */
	private $menu_item_sync;

	/**
	 * @param SitePress             $sitepress
	 * @param wpdb                  $wpdb
	 * @param WPML_Post_Translation $post_translations
	 * @param WPML_Term_Translation $term_translations
	 */
	function __construct( &$sitepress, &$wpdb, &$post_translations, &$term_translations ) {
		parent::__construct( $sitepress, $wpdb, $post_translations, $term_translations );

		$this->menu_item_sync = new WPML_Menu_Item_Sync( $this->sitepress, $this->wpdb, $this->post_translations, $this->term_translations );
		$this->init_hooks();
	}

	function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 20 );

		if ( isset( $_GET['updated'] ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	function init( $previous_menu = false ) {
		$action = filter_input( INPUT_POST, 'action' );
		$nonce  = (string) filter_input( INPUT_POST, '_icl_nonce_menu_sync' );

		if ( $action && ! wp_verify_nonce( $nonce, '_icl_nonce_menu_sync' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}
		$this->menu_item_sync->cleanup_broken_page_items();

		if (!session_id()) {
			session_start();
		}

		if ( $action === 'icl_msync_preview' ) {
			$this->is_preview = true;
			$this->sync_data  = isset( $_POST['sync'] ) ? array_map( 'stripslashes_deep', $_POST['sync'] ) : false;
			$previous_menu = isset( $_SESSION[ 'wpml_menu_sync_menu' ] ) ? $_SESSION[ 'wpml_menu_sync_menu' ] : null;
		} 

		if ( $previous_menu ) {
			$this->menus = $previous_menu;
		} else {
			$this->get_menus_tree();
			$_SESSION[ 'wpml_menu_sync_menu' ] = $this->menus;
		}

	}

	function get_menu_names() {
		$menu_names = array();
		global $sitepress, $wpdb;

		$menus = $wpdb->get_results( $wpdb->prepare( "
            SELECT tm.term_id, tm.name FROM {$wpdb->terms} tm 
                JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = tm.term_id
                JOIN {$wpdb->prefix}icl_translations tr ON tr.element_id = tx.term_taxonomy_id AND tr.element_type='tax_nav_menu'
            WHERE tr.language_code=%s
        ", $sitepress->get_default_language() ) );

		if ( $menus ) {
			foreach ( $menus as $menu ) {
				$menu_names[] = $menu->name;
			}
		}

		return $menu_names;
	}

	function get_menus_tree() {
		global $sitepress, $wpdb;

		$menus = $wpdb->get_results( $wpdb->prepare( "
            SELECT tm.term_id, tm.name FROM {$wpdb->terms} tm 
                JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = tm.term_id
                JOIN {$wpdb->prefix}icl_translations tr ON tr.element_id = tx.term_taxonomy_id AND tr.element_type='tax_nav_menu'
            WHERE tr.language_code=%s
        ", $sitepress->get_default_language() ) );

		if ( $menus ) {
			foreach ( $menus as $menu ) {
				$this->menus[ $menu->term_id ] = array(
					'name'         => $menu->name,
					'items'        => $this->get_menu_items( $menu->term_id, true ),
					'translations' => $this->get_menu_translations( $menu->term_id )
				);
			}

			$this->add_ghost_entries();
			$this->set_new_menu_order();
		}
	}

	private function get_menu_options( $menu_id ) {
		$menu_options = get_option( 'nav_menu_options' );
		$options      = array(
			'auto_add' => isset( $menu_options['auto_add'] ) && in_array(
					$menu_id,
					$menu_options['auto_add']
				)
		);

		return $options;
	}

	function add_ghost_entries() {
		if ( is_array( $this->menus ) ) {
			foreach ( $this->menus as $menu_id => $menu ) {
				if ( ! is_array( $menu['translations'] ) ) {
					continue;
				}
				foreach ( $menu['translations'] as $language => $tmenu ) {
					if ( ! empty( $tmenu ) ) {
						foreach ( $tmenu['items'] as $titem ) {
							// has a place in the default menu?
							$exists = false;
							foreach ( $this->menus[ $menu_id ]['items'] as $item ) {
								if ( $item['translations'][ $language ]['ID'] == $titem['ID'] ) {
									$exists = true;
								}
							}
							if ( ! $exists ) {
								$this->menus[ $menu_id ]['translations'][ $language ]['deleted_items'][] = array(
									'ID'         => $titem['ID'],
									'title'      => $titem['title'],
									'menu_order' => $titem['menu_order']
								);
							}
						}
					}
				}
			}
		}
	}

	function set_new_menu_order() {

		if ( is_array( $this->menus ) ) {
			foreach ( $this->menus as $menu_id => $menu ) {
				$menu_index_by_lang = array();
				foreach ( $menu[ 'items' ] as $item_id => $item ) {
					foreach ( $item[ 'translations' ] as $language => $item_translation ) {
						if ( $item_translation[ 'ID' ] ) {
							$new_menu_order                                                                                    = empty( $menu_index_by_lang[ $language ] ) ? 1 : $menu_index_by_lang[ $language ] + 1;
							$menu_index_by_lang[ $language ]                                                                   = $new_menu_order;
							$this->menus[ $menu_id ][ 'items' ][ $item_id ][ 'translations' ][ $language ][ 'menu_order_new' ] = $new_menu_order;
						}
					}
				}
			}
		}
	}

	function do_sync( array $data ) {
		
		$this->menus = isset( $this->menus ) ? $this->menus : array();
		$this->menus = empty( $data['menu_translation'] ) ? $this->menus : $this->menu_item_sync->sync_menu_translations( $data['menu_translation'],
		                                                                                                                  $this->menus );
		if ( ! empty( $data['options_changed'] ) ) {
			$this->menu_item_sync->sync_menu_options( $data['options_changed'] );
		}
		if ( ! empty( $data['del'] ) ) {
			$this->menu_item_sync->sync_deleted_menus( $data['del'] );
		}
		$this->menus = empty( $data['mov'] ) ? $this->menus : $this->menu_item_sync->sync_moved_items( $data['mov'],
		                                                                                               $this->menus );
		$this->menus = empty( $data['add'] ) ? $this->menus : $this->menu_item_sync->sync_added_items( $data['add'],
		                                                                                               $this->menus );
		if ( ! empty( $data['label_changed'] ) ) {
			$this->menu_item_sync->sync_caption( $data['label_changed'] );
		}
		if ( ! empty( $data['url_changed'] ) ) {
			$this->menu_item_sync->sync_urls( $data['url_changed'] );
		}
		if ( ! empty( $data['label_missing'] ) ) {
			$this->menu_item_sync->sync_missing_captions( $data['label_missing'] );
		}
		if ( ! empty( $data['url_missing'] ) ) {
			$this->menu_item_sync->sync_urls_to_add( $data['url_missing'] );
		}

		$this->menus = isset( $this->menus ) ? $this->menu_item_sync->sync_menu_order( $this->menus ) : $this->menus;
		$this->menu_item_sync->cleanup_broken_page_items();

		return $this->menus;
	}

	function render_items_tree_default( $menu_id, $parent = 0, $depth = 0 ) {
		global $sitepress;

		$active_language_codes = array_keys( $sitepress->get_active_languages() );
		$need_sync             = 0;
		$default_language      = $sitepress->get_default_language();
		foreach ( $this->menus[ $menu_id ][ 'items' ] as $item ) {

			// deleted items #2 (menu order beyond)
			static $d2_items = array();
			$deleted_items = array();
			if ( isset( $this->menus[ $menu_id ][ 'translation' ] ) && is_array( $this->menus[ $menu_id ][ 'translation' ] ) ) {
				foreach ( $this->menus[ $menu_id ][ 'translations' ] as $language => $tmenu ) {

					if ( ! isset( $d2_items[ $language ] ) ) {
						$d2_items[ $language ] = array();
					}

					if ( ! empty( $this->menus[ $menu_id ][ 'translations' ][ $language ][ 'deleted_items' ] ) ) {
						foreach ( $this->menus[ $menu_id ][ 'translations' ][ $language ][ 'deleted_items' ] as $deleted_item ) {
							if ( ! in_array( $deleted_item[ 'ID' ],
											 $d2_items[ $language ] ) && $deleted_item[ 'menu_order' ] > count( $this->menus[ $menu_id ][ 'items' ] )
							) {
								$deleted_items[ $language ][ ] = $deleted_item;
								$d2_items[ $language ][ ]      = $deleted_item[ 'ID' ];
							}
						}
					}
				}
			}
			if ( $deleted_items ) {
				?>
				<tr>
					<td>&nbsp;</td>
					<?php foreach ( $sitepress->get_active_languages() as $language ): if ( $language[ 'code' ] == $default_language ) {
						continue;
					} ?>
						<td>
							<?php if ( isset( $deleted_items[ $language[ 'code' ] ] ) ): ?>
								<?php $need_sync ++; ?>
								<?php foreach ( $deleted_items[ $language[ 'code' ] ] as $deleted_item ): ?>
									<?php echo str_repeat( ' - ', $depth ) ?><span
										class="icl_msync_item icl_msync_del"><?php echo esc_html( $deleted_item[ 'title' ] ) ?></span>
									<input type="hidden"
										   name="sync[del][<?php echo esc_attr( $menu_id ) ?>][<?php echo esc_attr( $language[ 'code' ] ) ?>][<?php echo esc_attr( $deleted_item[ 'ID' ] ) ?>]"
										   value="<?php echo esc_attr( $deleted_item[ 'title' ] ) ?>"/>
									<?php $this->operations[ 'del' ] = empty( $this->operations[ 'del' ] ) ? 1
										: $this->operations[ 'del' ] ++; ?>
									<br/>
								<?php endforeach; ?>
							<?php else: ?>
							<?php endif; ?>
						</td>
					<?php endforeach; ?>
				</tr>
				<?php
			}

			// show deleted item?
			static $mo_added = array();
			$deleted_items = array();
			if ( isset( $this->menus[ $menu_id ][ 'translation' ] ) && is_array( $this->menus[ $menu_id ][ 'translation' ] ) ) {
				foreach ( $this->menus[ $menu_id ][ 'translations' ] as $language => $tmenu ) {

					if ( ! isset( $mo_added[ $language ] ) ) {
						$mo_added[ $language ] = array();
					}

					if ( ! empty( $this->menus[ $menu_id ][ 'translations' ][ $language ][ 'deleted_items' ] ) ) {
						foreach ( $this->menus[ $menu_id ][ 'translations' ][ $language ][ 'deleted_items' ] as $deleted_item ) {

							if ( ! in_array( $item[ 'menu_order' ],
											 $mo_added[ $language ] ) && $deleted_item[ 'menu_order' ] == $item[ 'menu_order' ]
							) {
								$deleted_items[ $language ] = $deleted_item;
								$mo_added[ $language ][ ]   = $item[ 'menu_order' ];
								$need_sync ++;
							}

						}
					}
				}
			}

			$this->render_deleted_items( $deleted_items, $need_sync, $depth, $menu_id );

			if ( $item[ 'parent' ] == $parent ) {
				?>
				<tr>
					<td><?php
						echo str_repeat( ' - ', $depth ) . $item[ 'title' ];
						?></td>
					<?php
					foreach ( $active_language_codes as $lang_code ) {
						if ( $lang_code === $default_language ) {
							continue;
						} ?>
						<td>
							<?php
							$item_translation = $item[ 'translations' ][ $lang_code ];
							$item_id          = $item[ 'ID' ];
							echo str_repeat( ' - ', $depth );
							$need_sync ++;
							if ( ! empty( $item_translation[ 'ID' ] ) ) {
								// item translation exists
								$item_sync_needed = false;
								if ( $item_translation[ 'menu_order' ] != $item_translation[ 'menu_order_new' ] || $item_translation[ 'depth' ] != $item[ 'depth' ] ) { // MOVED
									echo '<span class="icl_msync_item icl_msync_mov">' . esc_html( $item_translation[ 'title' ] ) . '</span>';
									echo '<input type="hidden" name="sync[mov][' . esc_attr( $menu_id ) . '][' . esc_attr( $item[ 'ID' ] ) . '][' . esc_attr( $lang_code ) . '][' . esc_attr( $item_translation[ 'menu_order_new' ] ) . ']" value="' . esc_attr( $item_translation[ 'title' ] ) . '" />';
									$this->operations[ 'mov' ] = empty( $this->operations[ 'mov' ] ) ? 1
										: $this->operations[ 'mov' ] ++;
										
									$item_sync_needed = true;
								}
								if ( $item_translation[ 'label_missing' ] ) {
									$this->index_changed( 'label_missing',
														  $item_id,
														  $item_translation[ 'title' ],
														  $menu_id,
														  $lang_code );
									$item_sync_needed = true;
								}
								if ( $item_translation[ 'label_changed' ] ) {
									$this->index_changed( 'label_changed',
														  $item_id,
														  $item_translation[ 'title' ],
														  $menu_id,
														  $lang_code );
									$item_sync_needed = true;
								}
								if ( $item_translation[ 'url_missing' ] ) {
									$this->index_changed( 'url_missing',
														  $item_id,
														  $item_translation[ 'url' ],
														  $menu_id,
														  $lang_code );
									$item_sync_needed = true;
								}
								if ( $item_translation[ 'url_changed' ] ) {
									$this->index_changed( 'url_changed',
														  $item_id,
														  $item_translation[ 'url' ],
														  $menu_id,
														  $lang_code );
									$item_sync_needed = true;
								}
								if ( ! $item_sync_needed ) { // NO CHANGE
									$need_sync --;
									echo esc_html( $item_translation[ 'title' ] );
								}
							} elseif ( $item_translation[ 'object_type' ] === 'custom' ) {
								// item translation does not exist but is a custom item that will be created
								echo '<span class="icl_msync_item icl_msync_add">' . esc_html( $item_translation[ 'title' ] ) . ' @' . esc_html( $lang_code ) . '</span>';
								echo '<input type="hidden" name="sync[add][' . esc_attr( $menu_id ) . '][' . esc_attr( $item[ 'ID' ] ) . '][' . esc_attr( $lang_code ) . ']" value="' . esc_attr( $item_translation[ 'title' ] . ' @' . $lang_code ) . '" />';
								$this->operations[ 'add' ] = empty( $this->operations[ 'add' ] ) ? 1
									: $this->operations[ 'add' ] ++;
							} elseif ( ! empty( $item_translation[ 'object_id' ] ) ) {
								// item translation does not exist but translated object does
								if ( $item_translation[ 'parent_not_translated' ] ) {
									echo '<span class="icl_msync_item icl_msync_not">' . esc_html( $item_translation[ 'title' ] ) . '</span>';
									$this->operations[ 'not' ] = empty( $this->operations[ 'not' ] ) ? 1
										: $this->operations[ 'not' ] ++;
								} elseif ( ! icl_object_id( $item[ 'ID' ], 'nav_menu_item', false, $lang_code ) ) {
									// item translation does not exist but translated object does
									echo '<span class="icl_msync_item icl_msync_add">' . esc_html( $item_translation[ 'title' ] ) . '</span>';
									echo '<input type="hidden" name="sync[add][' . esc_attr( $menu_id ) . '][' . esc_attr( $item[ 'ID' ] ) . '][' . esc_attr( $lang_code ) . ']" value="' . esc_attr( $item_translation[ 'title' ] ) . '" />';
									$this->operations[ 'add' ] = empty( $this->operations[ 'add' ] ) ? 1
										: $this->operations[ 'add' ] ++;
								} else {
									$need_sync --;
								}
							} else {
								// item translation and object translation do not exist
								echo '<i class="inactive">' . esc_html__( 'Not translated', 'sitepress' ) . '</i>';
								$need_sync --;
							}
							?>
						</td>
					<?php } ?>
				</tr>
				<?php

				if ( $this->_item_has_children( $menu_id, $item[ 'ID' ] ) ) {
					$need_sync += $this->render_items_tree_default( $menu_id, $item[ 'ID' ], $depth + 1 );
				}
			}
		}

		if ( $depth == 0 ) {
			$this->render_option_update( $active_language_codes, $default_language, $menu_id, $need_sync );
		}

		return $need_sync;
	}

	private function render_option_update( $active_language_codes, $default_language, $menu_id, &$need_sync ) {

		?>
		<tr><?php
		foreach ( $active_language_codes as $lang_code ) {
			?>
			<td><?php
			if ( $lang_code === $default_language ) {
				esc_html_e( 'Menu Option: auto_add', 'sitepress' );
				continue;
			}
			$menu_options  = $this->get_menu_options( $menu_id );
			$translated_id = $this->get_translated_menu( $menu_id, $lang_code );
			$change        = false;
			if ( ! isset( $translated_id[ 'id' ] ) || $menu_options != $this->get_menu_options( $translated_id[ 'id' ] ) ) {
				$need_sync ++;
				$change = true;
			}
			if ($change) {
				$this->index_changed( 'options_changed',
									  'auto_add',
									  $menu_options[ 'auto_add' ],
									  $menu_id,
									  $lang_code,
									  $change );
			} else {
				echo esc_html( $menu_options[ 'auto_add' ] );
			}
		}
		?></td><?php
	}

	private function render_deleted_items( $deleted_items, &$need_sync, $depth, $menu_id ) {
		global $sitepress;

		if ( $deleted_items ) {
			?>
			<tr>
				<td>&nbsp;</td>
				<?php foreach ( $sitepress->get_active_languages() as $language ): if ( $language[ 'code' ] === $sitepress->get_default_language() ) {
					continue;
				} ?>
					<td>
						<?php if ( isset( $deleted_items[ $language[ 'code' ] ] ) ): ?>
							<?php $need_sync ++; ?>
							<?php echo str_repeat( ' - ', $depth ) ?><span
								class="icl_msync_item icl_msync_del"><?php echo esc_html( $deleted_items[ $language[ 'code' ] ][ 'title' ] ) ?></span>
							<input type="hidden"
								   name="sync[del][<?php echo esc_attr( $menu_id ) ?>][<?php echo esc_attr( $language[ 'code' ] ) ?>][<?php echo esc_attr( $deleted_items[ $language[ 'code' ] ][ 'ID' ] ) ?>]"
								   value="<?php echo esc_attr( $deleted_items[ $language[ 'code' ] ][ 'title' ] ) ?>"/>
							<?php $this->operations[ 'del' ] = empty( $this->operations[ 'del' ] ) ? 1
								: $this->operations[ 'del' ] ++; ?>
						<?php else: ?>
						<?php endif; ?>
					</td>
				<?php endforeach; ?>
			</tr>
			<?php
		}
	}

	private function index_changed( $index, $item_id, $item_translation, $menu_id, $lang_code, $change = true ) {
		$this->string_translation_links[ $this->menus[ $menu_id ][ 'name' ] ] = 1;

		$additional_class = $change ? 'icl_msync_' . $index : '';
		echo '<span class="icl_msync_item ' . esc_attr( $additional_class ) . '">'
			 . ( ! $item_translation ? 0 : esc_html( $item_translation ) )
			 . '</span>'
			 . '<input type="hidden" name="sync[' . esc_attr( $index ) . '][' . esc_attr( $menu_id ) . '][' . esc_attr( $item_id ) . '][' . esc_attr( $lang_code ) . ']" value="'
			 . esc_attr( $item_translation ) . '" />';
		if ( $change ) {
			$this->operations[ $index ] = empty( $this->operations[ $index ] ) ? 1 : $this->operations[ $index ] ++;
		}
	}

	function _item_has_children( $menu_id, $item_id )
	{
		$has = false;
		foreach ( $this->menus[ $menu_id ][ 'items' ] as $item ) {
			if ( $item[ 'parent' ] == $item_id ) {
				$has = true;
			}
		}

		return $has;
	}

	function get_item_depth( $menu_id, $item_id ) {
		$depth = 0;
		$parent = 0;

		do {
			foreach ( $this->menus[ $menu_id ][ 'items' ] as $item ) {
				if ( $item[ 'ID' ] == $item_id ) {
					$parent = $item[ 'parent' ];
					if ( $parent > 0 ) {
						$depth++;
						$item_id = $parent;
					} else {
						break;
					}
				}
			}
		} while ( $parent > 0 );

		return $depth;

	}

	function admin_notices()
	{
		echo '<div class="updated"><p>' . esc_html__( 'Menu(s) syncing complete.', 'sitepress' ) . '</p></div>';
	}

	public function display_menu_links_to_string_translation() {
		$menu_links_data = $this->get_links_for_menu_strings_translation();

		if ( count( $menu_links_data ) > 0 ) {
			echo '<p>';
			esc_html_e( "Your menu includes custom items, which you need to translate using WPML's String Translation.", 'sitepress' );
			echo '<br/>';
			esc_html_e( '1. Translate these strings: ', 'sitepress' );
			$i = 0;
			foreach ( $menu_links_data['items'] as $menu_name => $menu_url ) {
				if ( $i > 0 ) {
					echo ', ';
				}
				echo '<a href="' . esc_url( $menu_url ) . '">' . esc_html( $menu_name ) . '</a>' . PHP_EOL;
				$i ++;
			}
			echo '<br/>';
			esc_html_e( "2. When you're done translating, return here and run the menu synchronization again. This will use the strings that you translated to update the menus.", 'sitepress' );
			echo '</p>';
		}
	}

	public function get_links_for_menu_strings_translation() {
		$menu_links = array();

		$wpml_st_folder = $this->sitepress->get_wp_api()->constant( 'WPML_ST_FOLDER' );

		if ( $wpml_st_folder ) {
			$wpml_st_contexts = icl_st_get_contexts( false );
			$wpml_st_contexts = wp_list_pluck( $wpml_st_contexts, 'context' );
			$menu_names       = $this->get_menu_names();

			foreach ( $menu_names as $k => $menu_name ) {
				if ( ! in_array( $menu_name . ' menu', $wpml_st_contexts, true ) ) {
					unset( $menu_names[ $k ] );
				}
			}

			if ( ! empty( $menu_names ) ) {
				$menu_url_base = add_query_arg( 'page', urlencode($wpml_st_folder . '/menu/string-translation.php'), 'admin.php' );

				foreach ( $menu_names as $menu_name ) {
					$menu_url                 = add_query_arg( 'context', urlencode($menu_name . ' menu'), $menu_url_base );
					$menu_links[ $menu_name ] = $menu_url;
				}
			}
		}

		$response = array();
		if ( $menu_links ) {
			$response = array(
				'label' => esc_html__( 'Translate menu strings and URLs for:', 'sitepress' ),
				'items' => $menu_links,
			);
		}

		return $response;
	}
}
