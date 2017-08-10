<?php
require ICL_PLUGIN_PATH . '/menu/post-menus/post-edit-screen/wpml-sync-custom-field-note.class.php';

class WPML_Admin_Scripts_Setup extends WPML_Full_Translation_API {

	/** @var string $page */
	private $page;

	/**
	 * @param wpdb                  $wpdb
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translation
	 * @param WPML_Term_Translation $term_translation
	 * @param string                $page
	 */
	public function __construct( &$wpdb, &$sitepress, &$post_translation, &$term_translation, $page ) {
		parent::__construct( $sitepress, $wpdb, $post_translation, $term_translation );
		add_action( 'admin_print_scripts', array( $this, 'wpml_js_scripts_setup' ) );
		add_action( 'admin_print_styles', array( $this, 'wpml_css_setup' ) );
		$this->page = $page;
	}

	private function print_js_globals() {
		$icl_ajax_url_root = rtrim( get_site_url(), '/' );
		if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) {
			$icl_ajax_url_root = str_replace( 'http://', 'https://', $icl_ajax_url_root );
		}
		$icl_ajax_url = $icl_ajax_url_root . '/wp-admin/admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/languages.php';
		?>
        <script type="text/javascript">
            // <![CDATA[
            var icl_ajx_url = '<?php echo esc_url( $icl_ajax_url ); ?>',
                icl_ajx_saved = '<?php echo icl_js_escape( __( 'Data saved', 'sitepress' ) ); ?>',
                icl_ajx_error = '<?php echo icl_js_escape( __( 'Error: data not saved', 'sitepress' ) ); ?>',
                icl_default_mark = '<?php echo icl_js_escape( __( 'default', 'sitepress' ) ); ?>',
                icl_this_lang = '<?php echo esc_js( $this->sitepress->get_current_language() ); ?>',
                icl_ajxloaderimg_src = '<?php echo esc_url( ICL_PLUGIN_URL ); ?>/res/img/ajax-loader.gif',
                icl_cat_adder_msg = '<?php echo icl_js_escape( sprintf( __( 'To add categories that already exist in other languages go to the <a%s>category management page</a>', 'sitepress' ), ' href="' . admin_url( 'edit-tags.php?taxonomy=category' ) . '"' ) );?>';
            // ]]>

			<?php if ( ! $this->sitepress->get_setting( 'ajx_health_checked' ) && ! (bool) get_option( '_wpml_inactive' ) ) : ?>
            addLoadEvent(function () {
                jQuery.ajax({
                    type: "POST", url: icl_ajx_url, data: "icl_ajx_action=health_check", error: function (msg) {
                        var icl_initial_language = jQuery('#icl_initial_language');
                        if (icl_initial_language.length) {
                            icl_initial_language.find('input').attr('disabled', 'disabled');
                        }
                        jQuery('.wrap').prepend('<div class="error"><p><?php
								echo icl_js_escape( sprintf( __( "WPML can't run normally. There is an installation or server configuration problem. %sShow details%s", 'sitepress' ),
									'<a href="#" onclick="jQuery(this).parent().next().slideToggle()">', '</a>' ) );
								?></p><p style="display:none"><?php echo icl_js_escape( __( 'AJAX Error:', 'sitepress' ) )?> ' + msg.statusText + ' [' + msg.status + ']<br />URL:' + icl_ajx_url + '</p></div>');
                    }
                });
            });
			<?php endif; ?>
        </script>
		<?php

	}

	public function wpml_js_scripts_setup() {
	//TODO: [WPML 3.3] move javascript to external resource (use wp_localize_script() to pass arguments)
		global $pagenow, $sitepress;
		$default_language = $this->sitepress->get_default_language();
		$current_language = $this->sitepress->get_current_language();
		$page_basename = $this->page;

		$this->print_js_globals();

		$wpml_script_setup_args['default_language'] = $default_language;
		$wpml_script_setup_args['current_language'] = $current_language;
		do_action('wpml_scripts_setup', $wpml_script_setup_args);

		if ( 'options-reading.php' === $pagenow ) {
			$this->print_reading_options_js();
		} elseif ( in_array( $pagenow, array(
				'categories.php',
				'edit-tags.php',
				'edit.php',
				'term.php'
			), true )
		           && $current_language !== $default_language
		) {
			$this->correct_status_links_js( $current_language );
		}

		if ( 'edit-tags.php' === $pagenow || 'term.php' === $pagenow ) {
			$post_type = isset( $_GET['post_type'] ) ? '&post_type=' . esc_html( $_GET['post_type'] ) : '';
			$admin_url = admin_url( 'edit-tags.php' );
			$admin_url = add_query_arg( 'taxonomy', esc_js( $_GET['taxonomy'] ), $admin_url );
			$admin_url = add_query_arg( 'lang', $current_language, $admin_url );
			$admin_url = add_query_arg( 'message', 3, $admin_url );
			if ( $post_type ) {
				$admin_url = add_query_arg( 'post_type', $post_type, $admin_url );
			}
			?>
			<script type="text/javascript">
				addLoadEvent(function () {
					var edit_tag = jQuery('#edittag');
					if (edit_tag.find('[name="_wp_original_http_referer"]').length && edit_tag.find('[name="_wp_http_referer"]').length) {
						edit_tag.find('[name="_wp_original_http_referer"]').val('<?php echo esc_js( $admin_url ); ?>');
					}
				});
			</script>
		<?php
		}
		$trid        = filter_input( INPUT_GET, 'trid', FILTER_SANITIZE_NUMBER_INT );
		$source_lang = null !== $trid ? filter_input( INPUT_GET,
		                                              'source_lang',
		                                              FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : null;
		if ( 'post-new.php' === $pagenow ) {
			if ( $trid ) {
				$translations = $this->post_translations->get_element_translations( false, $trid );
				remove_filter(
					'pre_option_sticky_posts',
					array(
						$sitepress,
						'option_sticky_posts',
					)
				); // remove filter used to get language relevant stickies. get them all
				$sticky_posts = get_option( 'sticky_posts' );
				add_filter( 'pre_option_sticky_posts',
				            array( $sitepress, 'option_sticky_posts' ),
				            10,
				            2 ); // add filter back
				$is_sticky = false;
				foreach ( $translations as $t ) {
					if ( in_array( $t, $sticky_posts ) ) {
						$is_sticky = true;
						break;
					}
				}
				if ( $this->sitepress->get_setting( 'sync_ping_status' ) || $this->sitepress->get_setting( 'sync_comment_status' ) ) {
					$this->print_ping_and_comment_sync_js( $trid, $source_lang );
				}
				if ( $this->sitepress->get_setting( 'sync_private_flag' ) && 'private' === $this->post_translations->get_original_post_status( $trid, $source_lang ) ) {
					?>
					<script type="text/javascript">addLoadEvent(function () {
							jQuery('#visibility-radio-private').attr('checked', 'checked');
							jQuery('#post-visibility-display').html('<?php echo icl_js_escape(__('Private', 'sitepress')); ?>');
						});
					</script><?php
				}
				if ( $this->sitepress->get_setting( 'sync_post_taxonomies' ) ) {
					$this->print_tax_sync_js();
				}
				$custom_field_note = new WPML_Sync_Custom_Field_Note( $this->sitepress );
				$custom_field_note->print_sync_copy_custom_field_note( $source_lang, $translations );
			}
			?>
			<?php if ( ! empty( $is_sticky ) && $this->sitepress->get_setting( 'sync_sticky_flag' ) ): ?>
				<script type="text/javascript">
					addLoadEvent(
						function () {
							jQuery('#sticky').attr('checked', 'checked');
							var post_visibility_display = jQuery('#post-visibility-display');
							post_visibility_display.html(post_visibility_display.html() + ', <?php echo icl_js_escape(__('Sticky', 'sitepress')) ?>');
						});
				</script>
			<?php endif; ?>
		<?php
		}
		if ( ( 'page-new.php' === $pagenow || ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) ) )
		     && ( $trid
		          && ( $this->sitepress->get_setting( 'sync_page_template' )
		               || $this->sitepress->get_setting( 'sync_page_ordering' ) ) )
		) {
			$this->print_mo_sync_js( $trid, $source_lang );
		}

		if ( $this->sitepress->is_post_edit_screen() && $this->sitepress->get_setting( 'sync_post_date' ) ) {
			$this->print_sync_date_js();
		}
		if ( 'post-new.php' === $pagenow && isset( $_GET[ 'trid' ] ) && $sitepress->get_setting( 'sync_post_format' ) && function_exists (
				'get_post_format'
			)
		) {
			$format = $this->post_translations->get_original_post_format($trid, $source_lang);
			?>
			<script type="text/javascript">
				addLoadEvent(function () {
					jQuery('#post-format-' + '<?php echo $format ?>').attr('checked', 'checked');
				});
			</script><?php
		}

		wp_enqueue_script ( 'theme-preview' );

		if ( 'languages' === $page_basename || 'string-translation' === $page_basename ) {
			wp_enqueue_script ( 'wp-color-picker' );
			wp_register_style (
				'wpml-color-picker',
				ICL_PLUGIN_URL . '/res/css/colorpicker.css',
				array( 'wp-color-picker' ),
				ICL_SITEPRESS_VERSION
			);
			wp_enqueue_style ( 'wpml-color-picker' );
			wp_enqueue_script ( 'jquery-ui-sortable' );
		}
	}

	/**
	 * Prints JavaScript to display correct links on the posts by status break down and also fixes links
	 * to category and tag pages
	 *
	 * @param string $current_language
	 */
	private function correct_status_links_js( $current_language ) {
		?>
		<script type="text/javascript">
			addLoadEvent(
				function () {
					jQuery(document).ready(
						function () {
							jQuery('.subsubsub>li a').each(
								function () {
									var h = jQuery(this).attr('href');
									var urlg = -1 === h.indexOf('?') ? '?' : '&';
									jQuery(this).attr('href', h + urlg + 'lang=<?php echo esc_js( $current_language ); ?>');
								}
							);
							jQuery('.column-categories a, .column-tags a, .column-posts a').each(
								function () {
									jQuery(this).attr('href', jQuery(this).attr('href') + '&lang=<?php echo esc_js( $current_language ); ?>');
								}
							);
						}
					);
				}
			);
		</script>
		<?php
	}

	/**
	 * Prints the JavaScript for synchronizing page order or page template on the post edit screen.
	 *
	 * @param int    $trid
	 * @param string $source_lang
	 */
	private function print_mo_sync_js( $trid, $source_lang ) {
		$menu_order    = $this->sitepress->get_setting( 'sync_page_ordering' )
			? $this->post_translations->get_original_menu_order( $trid, $source_lang )
			: null;
		$page_template = $this->sitepress->get_setting( 'sync_page_template' )
			? get_post_meta( $this->post_translations->get_element_id( $source_lang, $trid ),
			                 '_wp_page_template',
			                 true )
			: null;
		if ( $menu_order || $page_template ) {
			?>
			<script type="text/javascript">addLoadEvent(function () { <?php
						if($menu_order){ ?>
					jQuery('#menu_order').val(<?php echo esc_js( $menu_order ); ?>);
					<?php }
			if($page_template && 'default' !== $page_template){ ?>
					jQuery('#page_template').val('<?php echo esc_js( $page_template ); ?>');
					<?php }
			?>
				});</script><?php
		}
	}

	/**
	 * Prints the JavaScript for synchronizing ping and comment status for a post translation on the post edit screen.
	 *
	 * @param int    $trid
	 * @param string $source_lang
	 */
	private function print_ping_and_comment_sync_js( $trid, $source_lang ) {
		?>
		<script type="text/javascript">addLoadEvent(function () {
				var comment_status = jQuery('#comment_status');
				var ping_status = jQuery('#ping_status');
				<?php if($this->sitepress->get_setting('sync_comment_status')): ?>
				<?php if($this->post_translations->get_original_comment_status($trid, $source_lang) === 'open'): ?>
				comment_status.attr('checked', 'checked');
				<?php else: ?>
				comment_status.removeAttr('checked');
				<?php endif; ?>
				<?php endif; ?>
				<?php if($this->sitepress->get_setting('sync_ping_status')): ?>
				<?php if($this->post_translations->get_original_ping_status($trid, $source_lang) === 'open'): ?>
				ping_status.attr('checked', 'checked');
				<?php else: ?>
				ping_status.removeAttr('checked');
				<?php endif; ?>
				<?php endif; ?>
			});</script><?php
	}

	/**
	 * Prints the JavaScript for disabling editing the post_date on the post edit screen,
	 * when the synchronize post_date for translations setting is activated.
	 */
	private function print_sync_date_js() {
		$post_id = $this->get_current_req_post_id();
		if ( $post_id !== null ) {
			$original_id = $this->post_translations->get_original_element( $post_id );
			if ( $original_id && (int) $original_id !== (int) $post_id ) {
				$original_date = get_post_field( 'post_date', $original_id );
				$exp           = explode( ' ', $original_date );
				list( $aa, $mm, $jj ) = explode( '-', $exp[0] );
				list( $hh, $mn, $ss ) = explode( ':', $exp[1] );
				?>
				<script type="text/javascript">
					addLoadEvent(
						function () {
							jQuery('#aa').val('<?php echo esc_js( $aa ); ?>').attr('readonly', 'readonly');
							jQuery('#mm').val('<?php echo esc_js( $mm ); ?>').attr('disabled', 'disabled').attr('id', 'mm-disabled').attr('name', 'mm-disabled');
							// create a hidden element for month because we wont get anything returned from the disabled month dropdown.
							jQuery('<input type="hidden" id="mm" name="mm" value="<?php echo $mm ?>" />').insertAfter('#mm-disabled')
							jQuery('#jj').val('<?php echo esc_js( $jj ); ?>').attr('readonly', 'readonly');
							jQuery('#hh').val('<?php echo esc_js( $hh ); ?>').attr('readonly', 'readonly');
							jQuery('#mn').val('<?php echo esc_js( $mn ); ?>').attr('readonly', 'readonly');
							jQuery('#ss').val('<?php echo esc_js( $ss ); ?>').attr('readonly', 'readonly');
							var timestamp = jQuery('#timestamp');
							timestamp.find('b').append(( '<span> <?php esc_html_e('Copied From the Original', 'sitepress') ?></span>'));
							timestamp.next().html('<span style="margin-left:1em;"><?php esc_html_e('Edit', 'sitepress') ?></span>');
						});
				</script>
				<?php
			}
		}
	}

	private function print_tax_sync_js() {
		$post_type         = isset( $_GET['post_type'] ) ? $_GET['post_type'] : 'post';
		$source_lang       = isset( $_GET['source_lang'] )
			? filter_input( INPUT_GET, 'source_lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			: $this->sitepress->get_default_language();

		$trid = filter_var( $_GET['trid'], FILTER_SANITIZE_NUMBER_INT );
		$translations      = $this->sitepress->get_element_translations( $trid, 'post_' . $post_type );
		if ( ! isset( $translations[ $source_lang ] ) ) {
			return;
		}

		$current_lang      = $this->sitepress->get_current_language();
		$translatable_taxs = $this->sitepress->get_translatable_taxonomies( true, $post_type );
		$all_taxs          = get_object_taxonomies( $post_type );
		
		$js                = array();
		
		$this->sitepress->switch_lang($source_lang);
		foreach ( $all_taxs as $tax ) {
			$tax_detail = get_taxonomy( $tax );
			$terms      = get_the_terms( $translations[ $source_lang ]->element_id, $tax );
			$term_names = array();
			if ( $terms ) {
				foreach ( $terms as $term ) {
					if ( $tax_detail->hierarchical ) {
						$term_id = in_array( $tax, $translatable_taxs )
							? $this->term_translations->term_id_in( $term->term_id,
							                                        $current_lang,
							                                        false ) : $term->term_id;
						$js[]    = "jQuery('#in-" . $tax . "-" . $term_id . "').attr('checked', 'checked');";
					} else {
						if ( in_array( $tax, $translatable_taxs ) ) {
							$term_id = $this->term_translations->term_id_in( $term->term_id, $current_lang, false );
							if ( $term_id ) {
								$term         = get_term( $term_id, $tax );
								$term_names[] = esc_js( $term->name );
							}
						} else {
							$term_names[] = esc_js( $term->name );
						}
					}
				}
			}

			if ( $term_names ) {
				$js[] = "jQuery('#" . esc_js( $tax ) . ".taghint').css('visibility','hidden');";
				$js[] = "jQuery('#new-tag-" . esc_js( $tax ) . "').val('" . esc_js( join( ', ', $term_names ) ) . "');";
			}
		}
		$this->sitepress->switch_lang($current_lang);

		if ( $js ) {
			?>
            <script type="text/javascript">
			// <![CDATA[
			addLoadEvent(function(){
			    <?php echo join( PHP_EOL, $js ); ?>
			    jQuery().ready(function() {
			        jQuery(".tagadd").click();
                    jQuery('html, body').prop({scrollTop:0});
                    jQuery('#title').focus();
			    });
			});
			// ]]>
			</script>
            <?php
		}
	}

	private function get_current_req_post_id() {

		return isset( $_GET['post'] ) ? filter_var( $_GET['post'], FILTER_SANITIZE_NUMBER_INT ) : null;
	}

	private function print_reading_options_js(){
		list( $warn_home, $warn_posts ) = $this->verify_home_and_blog_pages_translations ();
		if ( $warn_home || $warn_posts ) {
			?>
			<script type="text/javascript">
				addLoadEvent(function () {
					jQuery('input[name="show_on_front"]').parent().parent().parent().parent().append('<?php echo str_replace("'","\\'",$warn_home . $warn_posts); ?>');
				});
			</script>
		<?php
		}
	}

	function wpml_css_setup() {
		if ( isset( $_GET[ 'page' ] ) ) {
			$page          = basename( $_GET[ 'page' ] );
			$page_basename = str_replace( '.php', '', $page );
			$page_basename = preg_replace('/[^\w-]/', '', $page_basename);
		}
		wp_enqueue_style( 'sitepress-style', ICL_PLUGIN_URL . '/res/css/style.css', array(), ICL_SITEPRESS_VERSION );
		if ( isset( $page_basename ) && file_exists( ICL_PLUGIN_PATH . '/res/css/' . $page_basename . '.css' ) ) {
			wp_enqueue_style( 'sitepress-' . $page_basename, ICL_PLUGIN_URL . '/res/css/' . $page_basename . '.css', array(), ICL_SITEPRESS_VERSION );
		}

		wp_register_style( 'otgs-dialogs', ICL_PLUGIN_URL . '/res/css/otgs-dialogs.css', null, ICL_SITEPRESS_VERSION );
		wp_register_style( 'wpml-dialog', ICL_PLUGIN_URL . '/res/css/dialog.css', array('wp-jquery-ui-dialog', 'otgs-dialogs'), ICL_SITEPRESS_VERSION );
		wp_enqueue_style( 'wpml-dialog');


		wp_register_style( 'otgs-ico', ICL_PLUGIN_URL . '/res/css/otgs-ico.css', null, ICL_SITEPRESS_VERSION );
		wp_enqueue_style( 'otgs-ico');
		
		
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'translate-taxonomy', ICL_PLUGIN_URL . '/res/css/taxonomy-translation.css', array(), ICL_SITEPRESS_VERSION );
		
	}

	private function verify_home_and_blog_pages_translations() {
		$warn_home     = $warn_posts = '';
		$page_on_front = get_option ( 'page_on_front' );
		if ( 'page' === get_option ( 'show_on_front' ) && $page_on_front ) {
			$warn_home = $this->missing_page_warning (
				$page_on_front,
				__ ( 'Your home page does not exist or its translation is not published in %s.', 'sitepress' )
			);
		}
		$page_for_posts = get_option ( 'page_for_posts' );
		if ( $page_for_posts ) {
			$warn_posts = $this->missing_page_warning (
				$page_for_posts,
				__ ( 'Your blog page does not exist or its translation is not published in %s.', 'sitepress' ),
				'margin-top:4px;'
			);
		}

		return array( $warn_home, $warn_posts );
	}

	/**
	 * @param int    $original_page_id
	 * @param string $label
	 * @param string $additional_css
	 *
	 * @return string
	 */
	private function missing_page_warning( $original_page_id, $label, $additional_css = '' ) {
		$warn_posts = '';
		if ( $original_page_id ) {
			$page_posts_translations = $this->post_translations->get_element_translations( $original_page_id );
			$missing_posts           = array();
			$active_languages        = $this->sitepress->get_active_languages();
			foreach ( $active_languages as $lang ) {
				if ( ! isset( $page_posts_translations[ $lang['code'] ] )
				     || get_post_status( $page_posts_translations[ $lang['code'] ] ) !== 'publish'
				) {
					$missing_posts[] = $lang['display_name'];
				}
			}
			if ( ! empty( $missing_posts ) ) {
				$warn_posts = '<div class="icl_form_errors" style="font-weight:bold;' . $additional_css . '">';
				$warn_posts .= sprintf( $label, join( ', ', $missing_posts ) );
				$warn_posts .= '<br />';
				$warn_posts .= '<a href="' . get_edit_post_link( $original_page_id ) . '">' . __(
						'Edit this page to add translations',
						'sitepress'
					) . '</a>';
				$warn_posts .= '</div>';
			}
		}

		return $warn_posts;
	}
}