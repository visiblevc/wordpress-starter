<?php

class WPML_Admin_Language_Switcher {
    
    function render() {
        /** @var $wp_admin_bar WP_Admin_Bar */
        global $wpdb, $wp_admin_bar, $pagenow, $mode, $sitepress;
    
        $all_languages_enabled = true;
        $current_page      = basename( $_SERVER[ 'SCRIPT_NAME' ] );
        $post_type         = false;
        $trid              = false;
        $translations      = false;
        $languages_links   = array();
    
        // individual translations
        $is_post = false;
        $is_tax  = false;
        $is_menu = false;
    
        $current_language = $sitepress->get_current_language();
        $current_language = $current_language ? $current_language : $sitepress->get_default_language();

        switch ( $pagenow ) {
            case 'post.php':
                $is_post           = true;
                $post_id           = @intval( $_GET[ 'post' ] );
                $post              = get_post( $post_id );
    
                $post_language = $sitepress->get_language_for_element( $post_id, 'post_' . get_post_type( $post_id ) );
                if ( $post_language && $post_language != $current_language ) {
                    $sitepress->switch_lang( $post_language );
                    $current_language = $sitepress->get_current_language();
                }
                $trid         = $sitepress->get_element_trid( $post_id, 'post_' . $post->post_type );
                $translations = $sitepress->get_element_translations( $trid, 'post_' . $post->post_type, true );
    
                break;
            case 'post-new.php':
                $all_languages_enabled = false;
                if ( isset( $_GET[ 'trid' ] ) ) {
                    $trid         = intval( $_GET[ 'trid' ] );
                    $post_type    = isset( $_GET[ 'post_type' ] ) ? $_GET[ 'post_type' ] : 'post';
                    $translations = $sitepress->get_element_translations( $trid, 'post_' . $post_type, true );
                    $is_post      = true;
                }
                break;
            case 'edit-tags.php':
			case 'term.php':
                $is_tax = true;
                if ( $sitepress->get_wp_api()->is_term_edit_page() ) {
                    $all_languages_enabled = false;
                }
    
                $taxonomy = $_GET['taxonomy'];
                $term_tax_id = 0;
    
                if ( isset( $_GET[ 'tag_ID' ] ) ) {
                    $term_id     = @intval( $_GET[ 'tag_ID' ] );
                    $term_tax_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy=%s AND term_id=%d", $taxonomy, $term_id ) );
                }
                if ( $term_tax_id ) {
                    $trid = $sitepress->get_element_trid( $term_tax_id, 'tax_' . $taxonomy );
                }
                if ( $trid ) {
                    $translations = $sitepress->get_element_translations( $trid, 'tax_' . $taxonomy, true );
                }
    
                break;
            case 'nav-menus.php':
                $is_menu = true;
                if ( isset( $_GET[ 'menu' ] ) && $_GET[ 'menu' ] ) {
                    $menu_id      = $_GET[ 'menu' ];
                    $trid         = $trid = $sitepress->get_element_trid( $menu_id, 'tax_nav_menu' );
                    $translations = $sitepress->get_element_translations( $trid, 'tax_nav_menu', true );
                }
                $all_languages_enabled = false;
                break;
            case 'upload.php':
                if ( $mode == 'grid' ) {
                    $all_languages_enabled = false;
                }
                break;
    
        }

			$active_languages = $sitepress->get_active_languages();
			if ( 'all' !== $current_language ) {
				$current_active_language = $active_languages[ $current_language ];
			}
			$active_languages = apply_filters( 'wpml_admin_language_switcher_active_languages', $active_languages );
			if ( 'all' !== $current_language && ! isset( $active_languages[ $current_language ] ) ) {
				array_unshift( $active_languages, $current_active_language );
			}

        foreach ( $active_languages as $lang ) {
            $current_page_lang = $current_page;
    
            if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
                parse_str( $_SERVER[ 'QUERY_STRING' ], $query_vars );
                unset( $query_vars[ 'lang' ], $query_vars[ 'admin_bar' ] );
            } else {
                $query_vars = array();
            }
            // individual translations
            if ( $is_post ) {
                if ( isset( $translations[ $lang[ 'code' ] ] ) && isset( $translations[ $lang[ 'code' ] ]->element_id ) ) {
                    $query_vars[ 'post' ] = $translations[ $lang[ 'code' ] ]->element_id;
                    unset( $query_vars[ 'source_lang' ] );
                    $current_page_lang      = 'post.php';
                    $query_vars[ 'action' ] = 'edit';
                } else {
                    $current_page_lang = 'post-new.php';
                    if ( isset( $post ) ) {
                        $query_vars[ 'post_type' ]   = $post->post_type;
                        $query_vars[ 'source_lang' ] = $current_language;
                    } else {
                        $query_vars[ 'post_type' ] = $post_type;
                    }
                    $query_vars[ 'trid' ] = $trid;
                    unset( $query_vars[ 'post' ], $query_vars[ 'action' ] );
                }
            } elseif ( $is_tax ) {
                if ( isset( $translations[ $lang[ 'code' ] ] ) && isset( $translations[ $lang[ 'code' ] ]->element_id ) ) {
                    $query_vars[ 'tag_ID' ] = $translations[ $lang[ 'code' ] ]->element_id;
                } else {
                    $query_vars[ 'trid' ]        = $trid;
                    $query_vars[ 'source_lang' ] = $current_language;
                    unset( $query_vars[ 'tag_ID' ], $query_vars[ 'action' ] );
                }
            } elseif ( $is_menu ) {
                if ( !empty( $menu_id ) ) {
                    if ( isset( $translations[ $lang[ 'code' ] ]->element_id ) ) {
                        $query_vars[ 'menu' ] = $translations[ $lang[ 'code' ] ]->element_id;
                    } else {
                        $query_vars[ 'menu' ]   = 0;
                        $query_vars[ 'trid' ]   = $trid;
                        $query_vars[ 'action' ] = 'edit';
                    }
                }
            }
    
            $query_string = http_build_query( $query_vars );
    
            $query = '?';
            if ( !empty( $query_string ) ) {
                $query .= $query_string . '&';
            }
            $query .= 'lang=' . $lang[ 'code' ]; // the default language need to specified explicitly yoo in order to set the lang cookie
    
            $link_url = admin_url( $current_page_lang . $query );
    
            $flag = $sitepress->get_flag( $lang[ 'code' ] );
    
            if ( $flag->from_template ) {
                $wp_upload_dir = wp_upload_dir();
                $flag_url      = $wp_upload_dir[ 'baseurl' ] . '/flags/' . $flag->flag;
            } else {
                $flag_url = ICL_PLUGIN_URL . '/res/flags/' . $flag->flag;
            }
    
            $languages_links[ $lang[ 'code' ] ] = array(
                'url'     => $link_url . '&admin_bar=1',
                'current' => $lang[ 'code' ] == $current_language,
                'anchor'  => $lang[ 'display_name' ],
                'flag'    => '<img class="icl_als_iclflag" src="' . $flag_url . '" alt="' . $lang[ 'code' ] . '" width="18" height="12" />'
            );
    
        }
    
        if ( $all_languages_enabled ) {
            $query = '?';
            if ( !empty( $query_string ) ) {
                $query .= $query_string . '&';
            }
            $query .= 'lang=all';
            $link_url = admin_url( basename( $_SERVER[ 'SCRIPT_NAME' ] ) . $query );
    
            $languages_links[ 'all' ] = array(
                'url'  => $link_url, 'current' => 'all' == $current_language, 'anchor' => __( 'All languages', 'sitepress' ),
                'flag' => '<img class="icl_als_iclflag" src="' . ICL_PLUGIN_URL . '/res/img/icon16.png" alt="all" width="16" height="16" />'
            );
        } else {
            // set the default language as current
            if ( 'all' == $current_language ) {
                $current_language = $sitepress->get_default_language();
                $languages_links[ $current_language ][ 'current' ] = true;
            }
        }

			$current_language_item = $languages_links[ $current_language ];
			$languages_links       = apply_filters( 'wpml_admin_language_switcher_items', $languages_links );
			if ( ! isset( $languages_links[ $current_language ] ) ) {
				$languages_links = array_merge( array( $current_language => $current_language_item ), $languages_links );
			}

        $parent = 'WPML_ALS';
        $lang   = $languages_links[ $current_language ];
        // Current language
        $wp_admin_bar->add_menu( array(
                                      'parent' => false, 'id' => $parent,
                                      'title'  => $lang[ 'flag' ] . '&nbsp;' . $lang[ 'anchor' ] . '&nbsp;&nbsp;<img title="' . __( 'help', 'sitepress' ) . '" id="wpml_als_help_link" src="' . ICL_PLUGIN_URL . '/res/img/question1.png" alt="' . __( 'help', 'sitepress' ) . '" width="16" height="16"/>',
                                      'href'   => false, 'meta' => array(
                'title' => __( 'Showing content in:', 'sitepress' ) . ' ' . $lang[ 'anchor' ],
            )
                                 ) );
    
        if ( $languages_links ) {
            foreach ( $languages_links as $code => $lang ) {
                if ( $code == $current_language )
                    continue;
                $wp_admin_bar->add_menu( array(
                                              'parent' => $parent, 'id' => $parent . '_' . $code, 'title' => $lang[ 'flag' ] . '&nbsp;' . $lang[ 'anchor' ], 'href' => $lang[ 'url' ], 'meta' => array(
                        'title' => __( 'Show content in:', 'sitepress' ) . ' ' . $lang[ 'anchor' ],
                    )
                                         ) );
            }
        }
    
        add_action( 'all_admin_notices', array($this, 'help_popup' ) );
    }
    
    function help_popup()
    {
        ?>
            <div id="icl_als_help_popup" class="icl_cyan_box icl_pop_info">
                <img class="icl_pop_info_but_close" align="right" src="<?php echo ICL_PLUGIN_URL . '/res/img/ico-close.png'?>" width="12" height="12" alt="x" />
                <?php echo sprintf( __( 'This language selector determines which content to display. You can choose items in a specific language or in all languages. To change the language of the WordPress Admin interface, go to <a%s>your profile</a>.', 'sitepress' ), ' href="' . admin_url( 'profile.php' ) . '"' );?>
            </div>
        <?php
    }
    
}
