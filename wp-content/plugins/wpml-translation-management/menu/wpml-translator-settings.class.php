<?php

class WPML_Translator_Settings extends WPML_WPDB_And_SP_User {

	/** @var TranslationProxy_Service $active_service */
	private $active_service;

	/** @var string $service_name  */
	private $service_name;

	/** @var TranslationManagement $tm_instance */
	private $tm_instance;

	/**
	 * @param wpdb                  $wpdb
	 * @param SitePress             $sitepress
	 * @param TranslationManagement $tm_instance
	 */
	public function __construct( &$wpdb, &$sitepress, &$tm_instance ) {
		parent::__construct( $wpdb, $sitepress );
		$this->tm_instance    = &$tm_instance;
		$this->active_service = TranslationProxy::get_current_service();
		$this->service_name   = TranslationProxy::get_current_service_name();
	}

    public function build_content_translators() {
        global $current_user;
	    $selected_translator = $this->tm_instance->get_selected_translator();
	    $active_service      = $this->active_service;
	    $service_name        = $this->service_name;

        if ( current_user_can( 'list_users' ) || current_user_can( 'manage_options' ) ) {
            add_filter( 'icl_translation_services_button', array( $this, 'icl_local_add_translator_button' ) );
            add_filter( 'icl_translation_services_button', array( 'TranslationProxy', 'get_current_service_info' ) );

            $only_local_translators = ( $active_service && (
                    !TranslationProxy_Service::is_authenticated( $active_service )
                    || ( $active_service && !$active_service->has_translator_selection )
                ) );
            ?>
            <div id="icl-your-translators">
            <?php

	        if ( $this->translation_service_has_translators( $active_service ) ) {
                if ( $only_local_translators ) {
                    $translation_dashboard_url = "admin.php?page=" . WPML_TM_FOLDER . "/menu/main.php&sm=dashboard";
                    $translation_dashboard_link = sprintf( '<a href="%s">' . __( 'Translation Dashboard',
                                                                                 'wpml-translation-management' ) . '</a>',
                                                           $translation_dashboard_url );
                    $service_html = '<p>';
                    $service_html .= sprintf( __( 'This section is for selecting WPML (local) translators only. If you wish to use %s, please go to %s.' ),
                                              '<strong>' . $service_name . '</strong>',
                                              $translation_dashboard_link );
                    $service_html .= '</p>';
                } else {
                    $service_html = TranslationProxy::get_service_translators_info();
                }

                ICL_AdminNotifier::display_instant_message( $service_html );
            }

            if ( $selected_translator && $selected_translator->ID ) {

                // Edit form
                echo '<h3>' . __( 'Edit translator', 'wpml-translation-management' ) . '</h3>';
                echo '<form id="icl_tm_adduser" method="post" action="">' . "\r\n";
                echo $this->icl_local_edit_translator_form( 'edit', $selected_translator ) . "\r\n";
                echo '</form>' . "\r\n";
            } else {

                // Services add translator form

                // Services hook
                $services_buttons = apply_filters( 'icl_translation_services_button', array() );
                if ( !empty( $services_buttons ) ) {

                    if ( !$only_local_translators ) {
                        // Toggle button
                        echo '<input type="submit" class="button secondary" id="icl_add_translator_form_toggle" value="' . __( 'Add Translator',
                                                                                                                               'wpml-translation-management' ) . ' &raquo;" />' . "\r\n";
                    }
                    // Toggle div start
                    $form_classes = array( 'translator-form-wrapper' );
                    if ( !isset( $_GET[ 'service' ] ) && !$only_local_translators ) {
                        $form_classes[ ] = 'hidden';
                    }
                    echo '<div id="icl_add_translator_form_wrapper" class="' . implode( ' ', $form_classes ) . '">';
                    // Open form
                    echo '<form id="icl_tm_adduser" method="post" action="">';
                    $languages = $this->get_translation_languages();
                    $from =
                        '<label>'
                        . __( 'From language:', 'wpml-translation-management' )
                        . '&nbsp;<select name="from_lang" id="edit-from">'
                        . "\r\n"
                        . '<option value="0">'
                        . __( 'Choose', 'wpml-translation-management' )
                        . '</option>'
                        . "\r\n";
                    $to = '<label>' . __( 'To language:',
                                          'wpml-translation-management' ) . '&nbsp;<select name="to_lang" id="edit-to">' . "\r\n" . '<option value="0">' . __( 'Choose',
                                                                                                                                                               'wpml-translation-management' ) . '</option>' . "\r\n";

                    foreach ( $languages as $language ) {
                        // select language from request
                        $selected_from = ( isset( $_GET[ 'icl_lng' ] ) && $_GET[ 'icl_lng' ] == $language[ 'code' ] )
                            ? ' selected="selected"' : '';

                        $from .= '<option ' . $selected_from . '  value="' . $language[ 'code' ] . '"' . @strval( $selected_from ) . '>' . $language[ 'display_name' ] . '</option>' . "\r\n";
                        $to .= '<option value="' . $language[ 'code' ] . '"' . '>' . $language[ 'display_name' ] . '</option>' . "\r\n";
                    }

                    echo $from . '</select></label>' . "\r\n";
                    echo $to . '</select></label>' . "\r\n";

                    if ( !$only_local_translators ) {
                        // Services radio boxes
                        echo '<h4 style="margin-bottom:5px;">' . __( 'Select translation service',
                                                                     'wpml-translation-management' ) . '</h4>' . "\r\n";
                    } else {
                        echo '<h4 style="margin-bottom:5px;">' . __( 'Select translators',
                                                                     'wpml-translation-management' ) . '</h4>' . "\r\n";
                    }

                    foreach ( $services_buttons as $service => $button ) {
                        if ( $only_local_translators && $service != 'local' ) {
                            continue;
                        }

                        if ( !isset( $button[ 'has_translator_selection' ] ) || $button[ 'has_translator_selection' ] ) {
                            $selected = '';
                            if ( count($services_buttons)==1 || ($only_local_translators && $service == 'local') || ( isset( $_GET[ 'service' ] ) && $_GET[ 'service' ] == $service )) {
                                $selected = ' checked="checked"';
                            }

                            if ( !$only_local_translators && $service != 'local' ) {
                                $selected = ' checked="checked"';
                            }

                            $title = array();
                            $has_translator_selection = isset( $service[ 'has_translator_selection' ] )
                                ? $service[ 'has_translator_selection' ] : false;
                            echo '<div style="margin-bottom:5px;">';

                            $display = ( $only_local_translators && $service == 'local' ) ? ' style="display:none;"'
                                : '';

                            echo '<input type="radio"
										id="radio-' . $service . '"
										name="services"
										data-has_translator_selection="' . $has_translator_selection . '"
										value="' . $service . '"' . $selected . $display . ' />';
                            if ( isset( $button[ 'name' ] ) && $button[ 'name' ] ) {
                                $title[ ] = '<label for="radio-' . $service . '"' . $display . '>&nbsp;' . $button[ 'name' ];
                            }
                            if ( isset( $button[ 'description' ] ) && $button[ 'description' ] ) {
                                $title[ ] = $button[ 'description' ];
                            }
                            if ( isset( $button[ 'more_link' ] ) && $button[ 'more_link' ] ) {
                                $title[ ] = $button[ 'more_link' ];
                            }
                            echo implode( ' - ', $title ) . "\r\n";
                            echo '</label>';
                            echo isset( $button[ 'content' ] ) && $button[ 'content' ] ? $button[ 'content' ] . "\r\n"
                                : '';
                            echo isset( $button[ 'messages' ] ) && $button[ 'messages' ]
                                ? $button[ 'messages' ] . "\r\n" : '';
                            if ( isset( $button[ 'setup_url' ] ) && $button[ 'setup_url' ] ) {
                                echo '<input type="hidden"
									id="' . $service . '_setup_url"
									name="' . $service . '_setup_url"
									value="' . $button[ 'setup_url' ] . '"
									/>' . "\r\n";
                            }
                            echo '</div>';
                        }
                    }
                    echo '<br style="clear:both;" />';
                    echo '<input id="icl_add_translator_submit" class="button-primary" type="submit" value="' . esc_attr( __( 'Add translator',
                                                                                                                              'wpml-translation-management' ) ) . '" />' . "\r\n";
                    echo '</form>' . "\r\n";
                    echo '</div>' . "\r\n";
                } else {
                    _e( 'No add translator interface available', 'wpml-translation-management' );
                }
            }

            // Translators lists

            // Local translators
            $blog_users_t = TranslationManagement::get_blog_translators();

            if ( TranslationProxy::translator_selection_available() ) {
                $other_service_translators = TranslationProxy_Translator::translation_service_translators_list();
            }

            ?>
            <?php if ( !empty( $blog_users_t ) || !empty( $other_service_translators ) ) { ?>
                <h3><?php _e( 'Current translators', 'wpml-translation-management' ); ?></h3>
                <table class="widefat fixed striped" cellspacing="0">
                    <thead>
                    <?php $this->translators_head_foot_row() ?>
                    </thead>

                    <tfoot>
                    <?php $this->translators_head_foot_row() ?>
                    </tfoot>

                    <tbody class="list:user user-list">
                    <?php if ( !empty( $blog_users_t ) ): foreach ( $blog_users_t as $bu ): ?>
                        <?php
                        if ( $current_user->ID == $bu->ID ) {
                            $edit_link = 'profile.php';
                        } else {
                            $edit_link = esc_url( add_query_arg( 'wp_http_referer',
                                                                 urlencode( esc_url( stripslashes( $_SERVER[ 'REQUEST_URI' ] ) ) ),
                                                                 "user-edit.php?user_id=$bu->ID" ) );
                        }
                        $language_pairs = get_user_meta( $bu->ID, $this->wpdb->prefix . 'language_pairs', true );
                        ?>
                        <tr>
                            <td class="column-title">
                                <strong><a class="row-title"
                                           href="<?php echo $edit_link ?>"><?php echo $bu->user_login; ?></a></strong>

                                <div class="row-actions">
                                    <a class="edit"
                                       href="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&amp;sm=translators&amp;icl_tm_action=remove_translator&amp;remove_translator_nonce=<?php
                                       echo wp_create_nonce( 'remove_translator' ) ?>&amp;user_id=<?php echo $bu->ID ?>"><?php _e( 'Remove',
                                                                                                                                   'wpml-translation-management' ) ?></a>
                                    |
                                    <a class="edit"
                                       href="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&amp;sm=translators&icl_tm_action=edit&amp;user_id=<?php echo $bu->ID ?>">
                                        <?php _e( 'Language pairs', 'wpml-translation-management' ) ?></a>
                                </div>
                            </td>
                            <td class="column-translator-languages">
                                <?php
                                $langs = $this->get_translation_languages();
                                ?>
                                <ul>
                                    <?php foreach ( $language_pairs as $from => $lp ): ?>
                                        <?php
                                        $tos = array();
                                        foreach ( $lp as $to => $null ) {
                                            if ( isset( $langs[ $to ] ) ) {
                                                $tos[ ] = $langs[ $to ][ 'display_name' ];
                                            } elseif ( $to ) {
                                                $_lang = $this->sitepress->get_language_details( $to );
                                                if ( $_lang ) {
                                                    $tos[ ] = '<i>' . $_lang[ 'display_name' ] . __( ' (inactive)',
                                                                                                     'wpml-translation-management' ) . '</i>';
                                                }
                                            }
                                        }
                                        ?>
                                        <li><?php @printf( __( '%s to %s', 'wpml-translation-management' ),
                                                           $langs[ $from ][ 'display_name' ],
                                                           join( ', ', $tos ) ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                Local
                            </td>
                            <td>
                                <a href="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&amp;sm=translators&icl_tm_action=edit&amp;user_id=<?php echo $bu->ID ?>"><?php _e( 'edit languages',
                                                                                                                                                                                      'wpml-translation-management' ) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    <?php if ( !empty( $other_service_translators ) ): foreach ( $other_service_translators as $rows ): ?>
                        <?php
                        if ( !isset( $trstyle ) || $trstyle ) {
                            $trstyle = '';
                        } else {
                            $trstyle = ' class="alternate"';
                        }
                        $language_pairs = isset( $rows[ 'langs' ] ) ? $rows[ 'langs' ] : '';
                        ?>
                        <tr<?php echo $trstyle ?>>
                            <td class="column-title">
                                <strong><?php echo isset( $rows[ 'name' ] ) ? $rows[ 'name' ] : ''; ?></strong>

                                <div class="row-actions">
                                    <?php echo isset( $rows[ 'action' ] ) ? $rows[ 'action' ] : ''; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $langs = $this->get_translation_languages();
                                if ( is_array( $language_pairs ) ) {
                                    ?>
                                    <ul>
                                        <?php
                                        foreach ( $language_pairs as $from => $lp ) {

                                            $from = isset( $langs[ $from ][ 'display_name' ] )
                                                ? $langs[ $from ][ 'display_name' ] : $from;
                                            $tos = array();
                                            foreach ( $lp as $to ) {
                                                $tos[ ] = isset( $langs[ $to ][ 'display_name' ] )
                                                    ? $langs[ $to ][ 'display_name' ] : $to;
                                            }
                                            ?>
                                            <li><?php printf( __( '%s to %s', 'wpml-translation-management' ),
                                                              $from,
                                                              join( ', ', $tos ) ); ?></li>
                                        <?php
                                        }
                                        ?>
                                    </ul>
                                <?php
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo isset( $rows[ 'type' ] ) ? ( icl_do_not_promote()
                                    ? __( 'Translation Service',
                                          'sitepress' ) : $rows[ 'type' ] ) : ''; ?>
                            </td>
                            <td>
                                <?php echo isset( $rows[ 'action' ] ) ? $rows[ 'action' ] : ''; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>

                </table>
            <?php
            } else {
                $message = __( "You haven't added any translator accounts yet.", 'wpml-translation-management' );
                ICL_AdminNotifier::display_instant_message( $message, 'warning' );
            }
            wp_nonce_field( 'get_users_not_trans_nonce', 'get_users_not_trans_nonce' );
        } //if ( current_user_can('list_users') )
        ?>
        </div>
    <?php

    }

    /**
     * Implementation of 'icl_translation_services_button' hook
     *
     * @param array $buttons
     *
     * @return array
     */
    public function icl_local_add_translator_button( $buttons = array() ) {
        $buttons[ 'local' ] = $this->icl_local_edit_translator_form();
        if ( isset( $buttons[ 'local' ][ 'content' ] ) && $buttons[ 'local' ][ 'content' ] ) {
            $buttons[ 'local' ][ 'content' ] = '<div id="local_translations_add_translator_toggle" style="display:none;">' . $buttons[ 'local' ][ 'content' ] . '</div>';
        }
        return $buttons;
    }

	public function build_content_translation_services() {

        $has_errors = false;

		$reload = filter_input( INPUT_GET, 'reload_services', FILTER_VALIDATE_BOOLEAN );
		$services = TranslationProxy::services( $reload );
        $has_errors |= icl_handle_error( $services );
        if(TranslationProxy::$errors) {
            $has_errors |= true;
            foreach(TranslationProxy::$errors as $error) {
                icl_handle_error($error);
            }
        }

		$active_service = TranslationProxy::get_current_service();
		if ( is_wp_error( $active_service ) ) {
            $has_errors |= icl_handle_error( $active_service );
			$active_service = false;
		}

		$service_activation_button_class = 'button-primary';
		if($active_service) {
			$service_activation_button_class = 'button-secondary';
		}

		?>
		<div class="js-available-services">
			<?php
			if ( !TranslationProxy::get_tp_default_suid()) {
				echo $this->wpml_refresh_translation_services_button();
			}
			if ( $this->translation_service_has_translators( $active_service ) ) {
				echo $this->flush_website_details_cache_button();
			}
            if(!$has_errors) {
                ?>
                <div class="icl-current-service">
                    <?php
                    if ( $active_service ) {
                        ?>
                        <div class="img-wrap">
                            <img src="<?php echo $active_service->logo_url; ?>"
                                 alt="<?php echo $active_service->name ?>"/>
                        </div>

                        <div class="desc">
                            <?php if ( ! TranslationProxy::get_tp_default_suid() ) { ?>
                                <h3><?php _e( 'Current service', 'wpml-translation-management' ) ?></h3>
                            <?php } ?>
                            <h4><?php echo $active_service->name ?></h4>

                            <p>
                                <?php echo $active_service->description ?>
                            </p>
                            <?php
                            echo translation_service_details( $active_service, true );

                            do_action( 'translation_service_authentication' );
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
                if ( ! TranslationProxy::get_tp_default_suid() && ! empty( $services ) ) {
                    ?>
                    <ul class="icl-available-services">
                        <?php foreach ( $services as $service ) {
                            $state = ( $active_service && ( $service->id == $active_service->id ) ) ? "active" : "inactive";
                            if ( $state === 'inactive' ) {
                                ?>
                                <li>
                                    <div class="img-wrap js-activate-service"
                                         data-target-id="<?php echo $service->id; ?>">
                                        <img src="<?php echo $service->logo_url; ?>"
                                             alt="<?php echo $service->name ?>"/>
                                    </div>
                                    <h4><?php echo $service->name; ?></h4>

                                    <p>
                                        <?php echo $service->description; ?>
                                        <?php echo translation_service_details( $active_service, true ); ?>
                                    </p>

                                    <p>
                                        <button type="submit"
                                                class="js-activate-service-id <?php echo $service_activation_button_class; ?>"
                                                data-id="<?php echo $service->id; ?>"
                                                data-custom-fields="<?php echo esc_attr( wp_json_encode( $service->custom_fields ) ); ?>">
                                            <?php _e( 'Activate', 'wpml-translation-management' ) ?>
                                        </button>
                                        <?php
                                        if ( isset( $service->doc_url ) && $service->doc_url ) {
                                            ?>
                                            &nbsp;<a href="<?php echo $service->doc_url; ?>"
                                                     target="_blank"><?php echo __( 'Documentation', 'wpml-translation-management' ); ?></a>
                                            <?php
                                        }
                                        ?>
                                    </p>
                                </li>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                    <?php
                }
            }
			?>
		</div>
		<?php
	}

    private function translators_head_foot_row() {
        ?>
        <tr class="thead">
            <th><?php _e( 'Name', 'wpml-translation-management' ) ?></th>
            <th><?php _e( 'Languages', 'wpml-translation-management' ) ?></th>
            <th><?php _e( 'Service', 'wpml-translation-management' ) ?></th>
            <th><?php _e( 'Action', 'wpml-translation-management' ) ?></th>
        </tr>
        <?php
    }

    /**
     * Implementation of 'icl_translation_services_button' hook
     *
     * @return string
     */
    private function wpml_refresh_translation_services_button() {
        return '<a href=' . "admin.php?page=" . WPML_TM_FOLDER . "/menu/main.php&sm=translators&reload_services=true"
               . ' type="submit" class="button secondary" id="wpml-refresh-translation-services">' . __( 'Refresh Available Services List', 'wpml-translation-management' ) . ' &raquo; </a>' . "\r\n";
    }

    /**
     * Add/edit local translator form
     *
     * @param string $action add|edit
     * @param int|object $selected_translator
     *
     * @return mixed
     */
    private function icl_local_edit_translator_form( $action = 'add', $selected_translator = 0 ) {
        $blog_users_nt = $this->tm_instance->get_blog_not_translators();
        $output = '';
        $return[ 'name' ] = __( 'Local', 'wpml-translation-management' );
        $return[ 'description' ] = __( 'Your own translators', 'wpml-translation-management' );

        if ( $action === 'add' && empty( $blog_users_nt ) ) {
            $alert_message = '<p>';
            $alert_message .= __( 'All WordPress users are already translators. To add more translators, first create accounts for them.',
                                  'wpml-translation-management' );
            $alert_message .= '</p>';
            $return[ 'content' ] = '';
            $return[ 'messages' ] = ICL_AdminNotifier::display_instant_message( $alert_message,
                                                                                'information',
                                                                                false,
                                                                                true );

            return $return;
        }

        $output .= '<div id="icl_tm_add_user_errors">
        <span class="icl_tm_no_to">' . __( 'Select user.', 'wpml-translation-management' ) . '</span>
    </div>
    <input type="hidden" name="icl_tm_action" value="' . $action . '_translator" />' . wp_nonce_field( $action . '_translator',
                                                                                                       $action . '_translator_nonce',
                                                                                                       true,
                                                                                                       false );
        if ( !$selected_translator ):
            $output .= '<input type="hidden" id="icl_tm_selected_user" name="user_id" />';
            $output .= '<input type="text" id="icl_quick_src_users" placeholder="' . esc_attr__( 'search',
                                                                                                 'wpml-translation-management' ) . '" />';
            $output .= '&nbsp;<span id="icl_user_src_nf"></span>';
            $output .= '<img style="display:none;margin-left:3px;" src="' . esc_url( admin_url( 'images/wpspin_light.gif' ) ) . '" class="waiting" alt="" />';
            $output .= '<p>' . __( 'To add translators, they must first have accounts in WordPress. Translators can have any editing privileges, including subscriber.' ) . '</p>';
        else:
            $output .=
                '<span class="updated fade" style="padding:4px">'
                . sprintf( __( 'Editing language pairs for <strong>%s</strong>', 'wpml-translation-management' ),
                           esc_html( $selected_translator->display_name ) . ' (' . $selected_translator->user_login . ')' )
                . '</span>';
            $output .= '<input type="hidden" name="user_id" value="' . $selected_translator->ID . '" />';
        endif;

        if ( $selected_translator ) {
            $output .= '<br />

      <div class="icl_tm_lang_pairs"';
            if ( $selected_translator ): $output .= ' style="display:block"'; endif;
            $output .= '>
          <ul>';

            $languages = $this->get_translation_languages();
            foreach ( $languages as $from_lang ):
                $lang_from_selected = false;
                if ( $selected_translator && 0 < @count( $selected_translator->language_pairs[ $from_lang[ 'code' ] ] ) ):
                    $lang_from_selected = true;
                endif;
                $output .= '<li class="js-icl-tm-lang-from';
                if ( $lang_from_selected ) {
                    $output .= ' js-lang-from-selected';
                }
                $output .= '">';
                $output .= '<label><input class="icl_tm_from_lang" type="checkbox"';
                if ( $lang_from_selected ):
                    $output .= ' checked="checked"';
                endif;
                $output .= ' />&nbsp;';
                $output .= sprintf( __( 'From %s', 'wpml-translation-management' ), $from_lang[ 'display_name' ] ) . '</label>
              <div class="icl_tm_lang_pairs_to"';
                if ( $selected_translator && 0 < @count( $selected_translator->language_pairs[ $from_lang[ 'code' ] ] ) ):
                    $output .= ' style="display:block"';
                endif;
                $output .= '>
                  <small>' . __( 'to', 'wpml-translation-management' ) . '</small>
                  <ul>';

                foreach ( $languages as $to_lang ):
                    if ( $from_lang[ 'code' ] === $to_lang[ 'code' ] ) {
                        continue;
                    }
                    $lang_selected = false;
                    if ( $selected_translator->ID && isset( $selected_translator->language_pairs[ $from_lang[ 'code' ] ][ $to_lang[ 'code' ] ] ) ) {
                        $lang_selected = true;
                    }
                    $output .= '<li class="js-icl-tm-lang-pair';
                    if ( $lang_selected ) {
                        $output .= ' js-lang-pair-selected';
                    }
                    $output .= '">
                      <label><input class="icl_tm_to_lang" type="checkbox" name="lang_pairs[' . $from_lang[ 'code' ] . '][' . $to_lang[ 'code' ] . ']" value="1"';
                    if ( $lang_selected ) {
                        $output .= ' checked="checked"';
                    }
                    $output .= ' />&nbsp;';
                    $output .= $to_lang[ 'display_name' ] . '</label>&nbsp;
                      </li>';
                endforeach;
                $output .= '</ul>
              </div>
              </li>';
            endforeach;

            $output .= '</ul>';
            $output .= '</div><input class="button-primary" type="submit" value="';
            $output .= $selected_translator
                ? esc_attr( __( 'Update',
                                'wpml-translation-management' ) )
                : esc_attr( __( 'Add as translator',
                                'wpml-translation-management' ) );
            $output .= '" />&nbsp;<input type="submit" value="' . __( 'Cancel',
                                                                      'wpml-translation-management' ) . '" name="cancel" class="button-secondary" onclick="history.go(-1); return false;" />';
        }
        $return[ 'content' ] = $output;

        return ( $action == 'edit' ) ? $output : $return;
    }

    private function get_translation_languages(){

        return $languages = apply_filters( 'wpml_tm_allowed_source_languages', $this->sitepress->get_active_languages() );
    }

	private function flush_website_details_cache_button() {
		$ts_name   = TranslationProxy::get_current_service_name();
		$link_text = sprintf( __( 'Refresh Translators data from %s', 'wpml-translation-management' ), $ts_name );

		$nonce = wp_create_nonce( 'wpml-flush-website-details-cache' );

		return '<a href="#" data-nonce="' . $nonce . '" type="submit" class="button secondary js-flush-website-details-cache">' . $link_text . ' &raquo;</a>' . PHP_EOL;
	}

	/**
	 * @param $active_service
	 *
	 * @return bool
	 */
	private function translation_service_has_translators( $active_service ) {
		return $active_service && TranslationProxy::translator_selection_available();
	}
}