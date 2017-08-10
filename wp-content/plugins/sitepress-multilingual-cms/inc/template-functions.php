<?php
/**
 * SitePress Template functions
 * @package wpml-core
 */

/**
 * @since      3.2.3
 * @deprecated Use 'wpml_get_capabilities' instead.
 */
function icl_sitepress_get_capabilities() {
	return wpml_get_capabilities_names();
}

function wpml_get_capabilities_names() {
	$capabilities = wpml_get_capabilities();

	return array_keys( $capabilities );
}

function wpml_get_capabilities_labels() {
	$capabilities = wpml_get_capabilities();

	return array_values( $capabilities );
}

function wpml_get_capabilities() {
	$capabilities = array(
		'wpml_manage_translation_management'        => __( 'Manage Translation Management', 'sitepress' ),
		'wpml_manage_languages'                     => __( 'Manage Languages', 'sitepress' ),
		'wpml_manage_theme_and_plugin_localization' => __( 'Manage Theme and Plugin localization', 'sitepress' ),
		'wpml_manage_support'                       => __( 'Manage Support', 'sitepress' ),
		'wpml_manage_woocommerce_multilingual'      => __( 'Manage WooCommerce Multilingual', 'sitepress' ),
		'wpml_operate_woocommerce_multilingual'     => __( 'Operate WooCommerce Multilingual. Everything on WCML except the settings tab.', 'sitepress' ),
		'wpml_manage_media_translation'             => __( 'Manage Media translation', 'sitepress' ),
		'wpml_manage_navigation'                    => __( 'Manage Navigation', 'sitepress' ),
		'wpml_manage_sticky_links'                  => __( 'Manage Sticky Links', 'sitepress' ),
		'wpml_manage_string_translation'            => __( 'Manage String Translation', 'sitepress' ),
		'wpml_manage_translation_analytics'         => __( 'Manage Translation Analytics', 'sitepress' ),
		'wpml_manage_wp_menus_sync'                 => __( 'Manage WPML Menus Sync', 'sitepress' ),
		'wpml_manage_taxonomy_translation'          => __( 'Manage Taxonomy Translation', 'sitepress' ),
		'wpml_manage_troubleshooting'               => __( 'Manage Troubleshooting', 'sitepress' ),
		'wpml_manage_translation_options'           => __( 'Translation options', 'sitepress' )
	);

	return apply_filters( 'wpml_capabilities', $capabilities );
}

function wpml_get_read_only_capabilities_filter( $empty ) {
	return wpml_get_capabilities();
}

add_filter( 'wpml_capabilities_read_only', 'wpml_get_read_only_capabilities_filter', 10, 1 );

function wpml_get_roles() {
	$wp_roles[ 'label' ]        = __( 'WPML capabilities', 'sitepress' );
	$wp_roles[ 'capabilities' ] = wpml_get_capabilities();

	return apply_filters( 'wpml_roles', $wp_roles );
}

function wpml_roles_read_only_filter( $empty ) {
	return wpml_get_roles();
}

add_filter( 'wpml_roles_read_only', 'wpml_roles_read_only_filter', 10, 1 );

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_home_url' filter instead.
 */
function icl_get_home_url() {
	global $sitepress;
	$current_language = $sitepress->get_current_language();

	return $sitepress->language_url( $current_language );
}

/**
 * Get the home url in the current language
 * To be used in place of get_option('home')
 * Note: Good code will make use of get_home_url() or home_url() which apply filters natively.
 * In this case there is no need to replace anything.
 * @since 3.2
 * @return string
 * @use \SitePress::api_hooks
 */
function wpml_get_home_url_filter() {
	global $sitepress;
	$current_language = $sitepress->get_current_language();

	return $sitepress->language_url( $current_language );
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_active_languages' filter instead.
 *
 * @param string $a
 *
 * @return mixed
 */
function icl_get_languages( $a = '' ) {
	if ( $a ) {
		parse_str( $a, $args );
	} else {
		$args = '';
	}
	global $sitepress;
	$langs = $sitepress->get_ls_languages( $args );

	return $langs;
}

/**
 * Get a list of the active languages
 * Usually used to create custom language switchers
 * @since                             3.2
 *
 * @param mixed        $empty_value   This is normally the value the filter will be modifying.
 *                                    We are not filtering anything here therefore the NULL value
 *                                    This for the filter function to actually receive the full argument list:
 *                                    apply_filters( 'wpml_active_languages', '', $args)
 * @param array|string $args          {
 *                                    Optional A string of arguments to filter the language output
 *
 * @type bool          $skip_missing  How to treat languages with no translations. 0 | Skip language or 1 | Link to home of language for missing translations.
 * @type string        $link_empty_to Works in conjunction with skip_missing = 0 and allows using custom links for the languages that do not have translations
 *                                    for the current element. {%lang} can be used as placeholder for the language code. Empty by default.
 * @type string        $orderby       Accepts id|code|name Defaults to custom.
 *                                    The custom order can be defined in the WordPress admin under WPML > Languages > Language Switcher Options
 * @type string        $order         Accepts asc|desc
 *                                    }
 * @return array
 * @use \SitePress::api_hooks
 */
function wpml_get_active_languages_filter( $empty_value, $args = '' ) {
	global $sitepress;

	$args  = wp_parse_args( $args );
	return $sitepress->get_ls_languages( $args );
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_display_language_names' filter instead.
 *
 * @param      $native_name
 * @param bool $translated_name
 * @param bool $lang_native_hidden
 * @param bool $lang_translated_hidden
 *
 * @return string
 */
function icl_disp_language( $native_name, $translated_name = false, $lang_native_hidden = false, $lang_translated_hidden = false ) {
	$language_switcher = new SitePressLanguageSwitcher();

	return $language_switcher->language_display( $native_name, $translated_name, ! $lang_native_hidden, ! $lang_translated_hidden );
}

/**
 * @deprecated since 3.6.0 / See new Language Switcher API with use of Twig templates
 *
 * Get the native or translated language name or both
 * Checks if native_language_name and translated_language_name are different.
 * If so, it returns them both, otherwise, it returns only one.
 * Usually used in custom language switchers
 * @since 3.2
 *
 * @param mixed       $empty_value
 *
 * @see   \wpml_get_active_languages_filter
 *
 * @param string      $native_name            Required The language native name
 * @param string|bool $translated_name        Required The language translated name Defaults to FALSE
 * @param bool        $lang_native_hidden     Optional, default is FALSE 0|false or 1|true Whether to hide the language native name or not.
 * @param bool        $lang_translated_hidden Optional, default is FALSE 0|false or 1|true Whether to hide the language translated name or not.
 *
 * @return string HTML content
 * @use \SitePress::api_hooks
 */
function wpml_display_language_names_filter( $empty_value, $native_name, $translated_name = false, $lang_native_hidden = false, $lang_translated_hidden = false ) {
	$language_switcher = new SitePressLanguageSwitcher();

	return $language_switcher->language_display( $native_name, $translated_name, ! $lang_native_hidden, ! $lang_translated_hidden );
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_element_link' filter instead.
 *
 * @param        $element_id
 * @param string $element_type
 * @param string $link_text
 * @param array  $optional_parameters
 * @param string $anchor
 * @param bool   $echo
 * @param bool   $return_original_if_missing
 *
 * @return string
 */
function icl_link_to_element(
	$element_id,
	$element_type = 'post',
	$link_text = '',
	$optional_parameters = array(),
	$anchor = '',
	$echo = true,
	$return_original_if_missing = true
) {
	return wpml_link_to_element_filter( $element_id,
										$element_type,
										$link_text,
										$optional_parameters,
										$anchor,
										$echo,
										$return_original_if_missing );
}

/**
 * Get the link to an element in the current language
 * Produces localized links for WordPress elements (post types and taxonomy terms)
 * @since 3.2
 *
 * @param int    $element_id                 Required The ID of the post type (post, page) or taxonomy term (tag or category) to link to.
 * @param string $element_type               Optional The type of element to link to. Can be 'post', 'page', 'tag' or 'category'.    Defaults to 'post'
 * @param string $link_text                  Optional The link text. Defaults to the element's name.
 * @param array  $optional_parameters        Optional Arguments for the link.
 * @param string $anchor                     Optional Anchor for the link.
 * @param bool   $echo                       Optional 0|false to return or 1|true to echo the localized link. Defaults to true.
 * @param bool   $return_original_if_missing Optional, default is TRUE If set to true it will always return a value (the original value, if translation is missing)
 *
 * @return string HTML content
 * @use \SitePress::api_hooks
 */
function wpml_link_to_element_filter(
	$element_id, $element_type = 'post', $link_text = '', $optional_parameters = array(), $anchor = '', $echo = true, $return_original_if_missing = true
) {
	global $sitepress, $wpdb, $wp_post_types, $wp_taxonomies;

	if ( $element_type == 'tag' ) {
		$element_type = 'post_tag';
	}
	if ( $element_type == 'page' ) {
		$element_type = 'post';
	}

	$post_types = array_keys( (array) $wp_post_types );
	$taxonomies = array_keys( (array) $wp_taxonomies );

	if ( in_array( $element_type, $taxonomies ) ) {
		$element_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id= %d AND taxonomy=%s", $element_id, $element_type ) );
	} elseif ( in_array( $element_type, $post_types ) ) {
		$element_type = 'post';
	}

	if ( ! $element_id ) {
		return '';
	}

	if ( in_array( $element_type, $taxonomies ) ) {
		$icl_element_type = 'tax_' . $element_type;
	} elseif ( in_array( $element_type, $post_types ) ) {
		$icl_element_type = 'post_' . $wpdb->get_var( $wpdb->prepare( "SELECT post_type
                                                                     FROM {$wpdb->posts}
                                                                     WHERE ID = %d", $element_id ) );
	} else {
		return '';
	}

	$trid         = $sitepress->get_element_trid( $element_id, $icl_element_type );
	$translations = $sitepress->get_element_translations( $trid, $icl_element_type );

	// current language is ICL_LANGUAGE_CODE
	if ( isset( $translations[ ICL_LANGUAGE_CODE ] ) ) {
		if ( $element_type == 'post' ) {
			$url   = get_permalink( $translations[ ICL_LANGUAGE_CODE ]->element_id );
			$title = $translations[ ICL_LANGUAGE_CODE ]->post_title;
		} elseif ( $element_type == 'post_tag' ) {
			list( $term_id, $title ) = $wpdb->get_row( $wpdb->prepare( "SELECT t.term_id, t.name FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='post_tag'", $translations[ ICL_LANGUAGE_CODE ]->element_id ), ARRAY_N );
			$url   = get_tag_link( $term_id );
			$title = apply_filters( 'single_cat_title', $title );
		} elseif ( $element_type == 'category' ) {
			list( $term_id, $title ) = $wpdb->get_row( $wpdb->prepare( "SELECT t.term_id, t.name FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='category'", $translations[ ICL_LANGUAGE_CODE ]->element_id ), ARRAY_N );
			$url   = get_category_link( $term_id );
			$title = apply_filters( 'single_cat_title', $title );
		} else {
			list( $term_id, $title ) = $wpdb->get_row( $wpdb->prepare( "SELECT t.term_id, t.name FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy=%s", $translations[ ICL_LANGUAGE_CODE ]->element_id, $element_type ), ARRAY_N );
			$url   = get_term_link( $term_id, $element_type );
			$title = apply_filters( 'single_cat_title', $title );
		}
	} else {
		if ( ! $return_original_if_missing ) {
			if ( $echo ) {
				echo '';
			}

			return '';
		}

		if ( $element_type == 'post' ) {
			$url   = get_permalink( $element_id );
			$title = get_the_title( $element_id );
		} elseif ( $element_type == 'post_tag' ) {
			$url    = get_tag_link( $element_id );
			$my_tag = &get_term( $element_id, 'post_tag', OBJECT, 'display' );
			$title  = apply_filters( 'single_tag_title', $my_tag->name );
		} elseif ( $element_type == 'category' ) {
			$url    = get_category_link( $element_id );
			$my_cat = &get_term( $element_id, 'category', OBJECT, 'display' );
			$title  = apply_filters( 'single_cat_title', $my_cat->name );
		} else {
			$url    = get_term_link( (int) $element_id, $element_type );
			$my_cat = &get_term( $element_id, $element_type, OBJECT, 'display' );
			$title  = apply_filters( 'single_cat_title', $my_cat->name );
		}
	}

	if ( ! $url || is_wp_error( $url ) ) {
		return '';
	}

	if ( ! empty( $optional_parameters ) ) {
		$url_glue = false === strpos( $url, '?' ) ? '?' : '&';
		$url .= $url_glue . http_build_query( $optional_parameters );
	}

	if ( isset( $anchor ) && $anchor ) {
		$url .= '#' . $anchor;
	}

	$link = '<a href="' . esc_url( $url ) . '">';
	if ( isset( $link_text ) && $link_text ) {
		$link .= esc_html( $link_text );
	} else {
		$link .= esc_html( $title );
	}
	$link .= '</a>';

	if ( $echo ) {
		echo $link;
	}

	return $link;
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_object_id' filter instead.
 *
 * @param             $element_id
 * @param string      $element_type
 * @param bool        $return_original_if_missing
 * @param null|string $ulanguage_code
 *
 * @return null|int
 */
function icl_object_id( $element_id, $element_type = 'post', $return_original_if_missing = false, $ulanguage_code = null ) {

	return wpml_object_id_filter($element_id, $element_type, $return_original_if_missing, $ulanguage_code);
}

/**
 * @since      3.1.6
 * @deprecated @since 3.2: use 'wpml_object_id' with the same arguments
 * @use \SitePress::api_hooks
 */
add_filter( 'translate_object_id', 'icl_object_id', 10, 4 );

/**
 * Get the element in the current language
 * @since 3.2
 *
 * @param int         $element_id                 Use term_id for taxonomies, post_id for posts
 * @param string      $element_type               Use post, page, {custom post type name}, nav_menu, nav_menu_item, category, tag, etc.
 *                                                You can also pass 'any', to let WPML guess the type, but this will only work for posts.
 * @param bool        $return_original_if_missing Optional, default is FALSE. If set to true it will always return a value (the original value, if translation is missing).
 * @param string|NULL $language_code              Optional, default is NULL. If missing, it will use the current language.
 *                                                If set to a language code, it will return a translation for that language code or
 *                                                the original if the translation is missing and $return_original_if_missing is set to TRUE.
 *
 * @return int|NULL
 * @use \SitePress::api_hooks
 */
function wpml_object_id_filter( $element_id, $element_type = 'post', $return_original_if_missing = false, $language_code = null ) {
	global $sitepress;
	return $sitepress->get_object_id( $element_id, $element_type, $return_original_if_missing, $language_code );
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_translated_language_name' filter instead
 *
 * @param      $lang_code
 * @param bool $display_code
 *
 * @return string
 */
function icl_get_display_language_name( $lang_code, $display_code = false ) {
	global $sitepress;

	return $sitepress->get_display_language_name( $lang_code, $display_code );
}

/**
 * Returns the translated name of a language in another language.
 * The languages involved do not need to be active.
 * @since 3.2
 *
 * @param mixed       $empty_value
 *
 * @see   \wpml_get_active_languages_filter
 *
 * @param string      $lang_code          The language name will be for this language. Accepts a 2-letter code e.g. en
 * @param string|bool $display_code       The language name will display translated in this language. Accepts a 2-letter code e.g. de.
 *                                        If set to false it will return the translated name in the current language. Default is FALSE.
 *
 * @return string The language translated name
 * @use \SitePress::api_hooks
 */
function wpml_translated_language_name_filter( $empty_value, $lang_code, $display_code = false ) {
	global $sitepress;

	return $sitepress->get_display_language_name( $lang_code, $display_code );
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_current_language' filter instead.
 */
function icl_get_current_language() {
	return apply_filters( 'wpml_current_language', '' );
}

/**
 * Get the current language
 * @since      3.2
 * @deprecated Use apply_filters('wpml_current_language', '');
 * Example: $my_current_lang = apply_filters('wpml_current_language', '');
 */
function wpml_get_current_language_filter() {
	return wpml_get_current_language();
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_default_language' filter instead
 */
function icl_get_default_language() {
	global $sitepress;

	return $sitepress->get_default_language();
}

/**
 * Get the default language
 * @since 3.2
 *
 * @param mixed $empty_value
 *
 * @see   \wpml_get_active_languages_filter
 * @use \SitePress::api_hooks
 * @return string
 */
function wpml_get_default_language_filter( $empty_value) {
	return wpml_get_default_language();
}

/**
 * Returns the default language
 * @since 1.3
 * @return string
 */
function wpml_get_default_language() {
	global $sitepress;

	return $sitepress->get_default_language();
}

/**
 * Get current language
 * @since 1.3
 * @return string
 */
function wpml_get_current_language() {
	return apply_filters( 'wpml_current_language', '' );
}

/**
 * @param string $folder
 *
 * @return bool
 */
function icl_tf_determine_mo_folder( $folder ) {
	global $sitepress;
	$mo_file_search = new WPML_MO_File_Search( $sitepress );

	return $mo_file_search->determine_mo_folder( $folder );
}

/**
 * @todo: [WPML 3.3] refactor in 3.3
 *
 * @param array $attributes
 * @param bool  $checked
 * @param bool  $disabled
 *
 * @return string
 */
//$field_prefix = 'wpml_cf_translation_preferences_option_ignore_'
function wpml_input_field_helper( $attributes = array(), $checked = false, $disabled = false ) {
	if ( $disabled ) {
		$attributes[ 'readonly' ] = 'readonly';
		$attributes[ 'disabled' ] = 'disabled';
	}
	if ( $checked && array_key_exists( 'type', $attributes ) && in_array( $attributes[ 'type' ], array( 'checkbox', 'radio' ) ) ) {
		$attributes[ 'checked' ] = 'checked';
	}
	$html_attributes = array();
	if ( is_array( $attributes ) ) {
		foreach ( $attributes as $attribute => $attribute_value ) {
			if ( $attribute != 'custom' ) {
				$html_attributes[ ] = strip_tags( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			} else {
				$html_attributes[ ] = esc_attr( $attribute_value );
			}
		}
	}
	$output = '<!-- OK! -->';
	$output .= '<input';
	if ( $html_attributes ) {
		$output .= ' ' . join( ' ', $html_attributes );
	}
	$output .= '>';

	return $output;
}

/**
 * @todo: [WPML 3.3] refactor in 3.3
 *
 * @param $attributes
 * @param $caption
 *
 * @return string
 */
function wpml_label_helper( $attributes, $caption ) {
	$html_attributes = array();
	if ( is_array( $attributes ) ) {
		foreach ( $attributes as $attribute => $attribute_value ) {
			if ( $attribute != 'custom' ) {
				$html_attributes[ ] = strip_tags( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			} else {
				$html_attributes[ ] = esc_attr( $attribute_value );
			}
		}
	}

	$output = '<!-- OK! -->';
	$output .= '<label';
	if ( $html_attributes ) {
		$output .= ' ' . join( ' ', $html_attributes );
	}

	$output .= '>' . $caption . '</label>';

	return $output;
}

/**
 * @todo: [WPML 3.3] refactor in 3.3
 *
 * @param $args
 * @param $id_prefix
 * @param $value
 * @param $caption
 *
 * @return string
 */
function wpml_translation_preference_input_helper( $args, $id_prefix, $value, $caption ) {
	$output = '';

	$input_attributes = $args[ 'input_attributes' ];
	$label_attributes = $args[ 'label_attributes' ];
	$id               = $args[ 'id' ];
	$action           = $args[ 'action' ];

	$input_attributes[ 'id' ]    = $id_prefix . $id;
	$input_attributes[ 'value' ] = $value;
	$label_attributes[ 'for' ]   = $input_attributes[ 'id' ];

	$output .= wpml_input_field_helper( $input_attributes, ( $value == $action ), $args[ 'disabled' ] );
	$output .= wpml_label_helper( $label_attributes, $caption );

	return $output;
}

/**
 * @todo: [WPML 3.3] refactor in 3.3
 *
 * @param        $id
 * @param bool   $custom_field
 * @param string $class
 * @param bool   $ajax
 * @param string $default_value
 * @param bool   $fieldset
 * @param bool   $suppress_error
 *
 * @return string
 */
function wpml_cf_translation_preferences( $id, $custom_field = false, $class = 'wpml', $ajax = false, $default_value = 'ignore', $fieldset = false, $suppress_error = false ) {
	global $iclTranslationManagement;

	$output                         = '';

	if ( isset( $iclTranslationManagement ) ) {
		$section                 = 'custom_fields';
		$config_section          = $iclTranslationManagement->get_translation_setting_name( $section );
		$readonly_config_section = $iclTranslationManagement->get_readonly_translation_setting_name( $section );

		if ( $custom_field ) {
			$custom_field = @strval( $custom_field );
		}
		$class = @strval( $class );
		if ( $fieldset ) {
			$output .= '
<fieldset id="wpml_cf_translation_preferences_fieldset_' . $id . '" class="wpml_cf_translation_preferences_fieldset ' . $class . '-form-fieldset form-fieldset fieldset">' . '<legend>' . __( 'Translation preferences', 'sitepress' ) . '</legend>';
		}
		$actions  = array( 'ignore' => 0, 'copy' => 1, 'translate' => 2 );
		$action   = isset( $actions[ @strval( $default_value ) ] ) ? $actions[ @strval( $default_value ) ] : 0;
		$disabled = false;
		if ( $custom_field ) {
			if ( defined( 'WPML_TM_VERSION' ) && ! empty( $iclTranslationManagement ) ) {
				$custom_fields_settings = $iclTranslationManagement->settings[ $config_section ];
				if ( isset( $custom_fields_settings[ $custom_field ] ) ) {
					$action = intval( $custom_fields_settings[ $custom_field ] );
				}
				$custom_fields_readonly_settings = $iclTranslationManagement->settings[ $readonly_config_section ];
				$custom_fields_readonly_settings = isset( $custom_fields_readonly_settings ) ? $custom_fields_readonly_settings : array();
				$xml_override                    = in_array( $custom_field, $custom_fields_readonly_settings );
				$disabled                        = $xml_override;
				if ( $xml_override ) {
					$output .=
						'<div style="color:Red;font-style:italic;margin: 10px 0 0 0;">'
						. __( 'The translation preference for this field are being controlled by a language configuration XML file. If you want to control it manually, remove the entry from the configuration file.', 'sitepress' )
						. '</div>';
				}
			} else if ( ! $suppress_error ) {
				$output .= '<span style="color:#FF0000;">' . __( "To synchronize values for translations, you need to enable WPML's Translation Management module.", 'sitepress' ) . '</span>';
				$disabled = true;
			}
		} else if ( ! $suppress_error ) {
			$output .= '<span style="color:#FF0000;">' . __( 'Error: Something is wrong with field value. Translation preferences can not be set.', 'sitepress' ) . '</span>';
			$disabled = true;
		}
		//$disabled = !empty($disabled) ? ' readonly="readonly" disabled="disabled"' : '';
		$output .= '<div class="description ' . $class . '-form-description ' . $class . '-form-description-fieldset description-fieldset">' . __( 'Choose what to do when translating content with this field:', 'sitepress' ) . '</div>';

		$input_attributes = array(
			'name'  => 'wpml_cf_translation_preferences[' . $id . ']',
			'class' => $class . '-form-radio form-radio radio',
			'type'  => 'radio',
		);
		$label_attributes = array(
			'class' => $class . '-form-label ' . $class . '-form-radio-label',
		);

		$args = array(
			'input_attributes' => $input_attributes,
			'label_attributes' => $label_attributes,
			'disabled'         => $disabled,
			'id'               => $id,
			'action'           => $action
		);

		$output .= '<ul><li>';
		$output .= wpml_translation_preference_input_helper( $args, 'wpml_cf_translation_preferences_option_ignore_', '0', __( 'Do nothing', 'sitepress' ) );
		$output .= '</li><li>';
		$output .= wpml_translation_preference_input_helper( $args, 'wpml_cf_translation_preferences_option_copy_', '1', __( 'Copy from original', 'sitepress' ) );
		$output .= '</li><li>';
		$output .= wpml_translation_preference_input_helper( $args, 'wpml_cf_translation_preferences_option_translate_', '2', __( 'Translate', 'sitepress' ) );
		$output .= '</li></ul>';

		if ( $custom_field && $ajax ) {
			$output .= '
<div style=";margin: 5px 0 5px 0;" id="wpml_cf_translation_preferences_ajax_response_' . $id . '"></div>
<input type="button" onclick="icl_cf_translation_preferences_submit(\'' . $id . '\', jQuery(this));" style="margin-top:5px;" class="button-secondary" value="' . __( 'Apply' ) . '" name="wpml_cf_translation_preferences_submit_' . $id . '" />
<input type="hidden" name="wpml_cf_translation_preferences_data_' . $id . '" value="custom_field=' . $custom_field . '&amp;_icl_nonce=' . wp_create_nonce( 'wpml_cf_translation_preferences_nonce' ) . '" />';
		}
		if ( $fieldset ) {
			$output .= '
</fieldset>
';
		}
	}

	return $output;
}

/**
 *
 * @deprecated It will be removed in WPML 3.8.0
 *
 * @since 3.7.0
 *
 * @param $id
 * @param $custom_field
 *
 * @return bool
 */
function wpml_cf_translation_preferences_store( $id, $custom_field ) {
	if ( defined( 'WPML_TM_VERSION' ) ) {
		if ( empty( $id ) || empty( $custom_field )
		     || ! isset( $_POST[ 'wpml_cf_translation_preferences' ][ $id ] )
		) {
			return false;
		}
		$custom_field = sanitize_text_field( $custom_field );
		$action       = (int) $_POST[ 'wpml_cf_translation_preferences' ][ $id ];
		/** @var TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement;
		if ( ! empty( $iclTranslationManagement ) ) {
			$iclTranslationManagement->settings[ 'custom_fields_translation' ][ $custom_field ] = $action;
			$iclTranslationManagement->save_settings();
			return true;
		} else {
			return false;
		}
	}

	return false;
}

/**
 * @todo: [WPML 3.3] refactor in 3.3
 * wpml_get_copied_fields_for_post_edit
 * return a list of fields that are marked for copying and the
 * original post id that the fields should be copied from
 * This should be used to populate any custom field controls when
 * a new translation is selected and the field is marked as "copy" (sync)
 *
 * @param array $fields
 *
 * @return array
 */
function wpml_get_copied_fields_for_post_edit( $fields = array() ) {
	global $sitepress, $wpdb, $sitepress_settings, $pagenow;

	$copied_cf    = array( 'fields' => array() );
	$translations = null;

	if ( defined( 'WPML_TM_VERSION' ) ) {

		if ( ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) ) {
			if ( isset( $_GET[ 'trid' ] ) ) {
				$post_type = isset( $_GET[ 'post_type' ] ) ? $_GET[ 'post_type' ] : 'post';

				$translations = $sitepress->get_element_translations( $_GET[ 'trid' ], 'post_' . $post_type );

				$source_lang  = isset( $_GET[ 'source_lang' ] ) ? $_GET[ 'source_lang' ] : $sitepress->get_default_language();
				$lang_details = $sitepress->get_language_details( $source_lang );
			} else if ( $post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT ) ) {
				$post_type   = $wpdb->get_var( $wpdb->prepare( "SELECT post_type FROM {$wpdb->posts} WHERE ID = %d", $post_id ) );
				$trid        = $sitepress->get_element_trid( $post_id, 'post_' . $post_type );
				$original_id = $wpdb->get_var( $wpdb->prepare( "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE source_language_code IS NULL AND trid=%d", $trid ) );
				if ( $original_id != $post_id ) {
					// Only return information if this is not the source language post.
					$translations = $sitepress->get_element_translations( $trid, 'post_' . $post_type );
					$source_lang  = $wpdb->get_var( $wpdb->prepare( "SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE source_language_code IS NULL AND trid=%d", $trid ) );
					$lang_details = $sitepress->get_language_details( $source_lang );
				}
			}

			if ( $translations && isset( $source_lang ) ) {
				$original_custom = get_post_custom( $translations[ $source_lang ]->element_id );

				$copied_cf[ '_wpml_original_post_id' ] = $translations[ $source_lang ]->element_id;
				$ccf_note                        = '<img src="' . ICL_PLUGIN_URL . '/res/img/alert.png" alt="Notice" width="16" height="16" style="margin-right:8px" />';
				$copied_cf[ 'copy_message' ]     = $ccf_note . sprintf( __( 'WPML will copy this field from %s when you save this post.', 'sitepress' ), $lang_details[ 'display_name' ] );

				foreach ( (array) $sitepress_settings[ 'translation-management' ][ 'custom_fields_translation' ] as $key => $sync_opt ) {
					/*
					 * Added parameter $fields so except checking if field exist in DB,
					 * it can be checked if set in pre-defined fields.
					 * Noticed when testing checkbox field that does not save
					 * value to DB if not checked (omitted from list of copied fields).
					 * https://icanlocalize.basecamphq.com/projects/2461186-wpml/todo_items/169933388/comments
					 */
					if ( $sync_opt == 1
					     && ( isset( $original_custom[ $key ] ) || in_array( $key, $fields ) )
					) {
						$copied_cf[ 'fields' ][ ] = $key;
					}
				}
			}
		}
	}

	return $copied_cf;
}

/**
 * Retrieve language information of a post by its ID
 * The language information includes
 * the post locale,
 * the language text direction (True for RTL, False for LTR),
 * the post language translated name and native name and
 * whether the current language is different to the post language (True/False)
 *
 * @param mixed $empty_value
 *
 * @see  \wpml_get_active_languages_filter
 *
 * @param int   $post_id Optional The post id to retrieve information of (post, page, attachment, custom) Defaults to current post ID.
 *
 * @return array
 * @use \SitePress::api_hooks
 */
function wpml_get_language_information( $empty_value = null, $post_id = null ) {
	global $sitepress;

	if ( is_null( $post_id ) ) {
		$post_id = get_the_ID();
	}
	if ( empty( $post_id ) ) {
		return new WP_Error( 'missing_id', __( 'Missing post ID', 'sitepress' ) );
	}

	$post = get_post( $post_id );
	if ( empty( $post ) ) {
		return new WP_Error( 'missing_post', sprintf( __( 'No such post for ID = %d', 'sitepress' ), $post_id ) );
	}

	$language             = $sitepress->get_language_for_element( $post_id, 'post_' . $post->post_type );
	$language_information = $sitepress->get_language_details( $language );

	$current_language = $sitepress->get_current_language();
	$info = array(
		'language_code'      => $language,
		'locale'             => $sitepress->get_locale( $language ),
		'text_direction'     => $sitepress->is_rtl( $language ),
		'display_name'       => $sitepress->get_display_language_name( $language, $current_language ),
		'native_name'        => $language_information[ 'display_name' ],
		'different_language' => $language != $current_language
	);

	return $info;
}

/** This action is documented in  */
add_filter( 'wpcf_meta_box_post_type', 'wpml_wpcf_meta_box_order_defaults' );

/**
 * Add metabox definition to edit post type in Types
 * @since x.x.x
 *
 * @param array $boxes Meta boxes in Types.
 *
 * @return array Meta boxes in Types.
 */
function wpml_wpcf_meta_box_order_defaults( $boxes ) {
	$boxes['wpml'] = array(
		'callback' => 'wpml_custom_post_translation_options',
		'title'    => __( 'Translation', 'sitepress' ),
		'default'  => 'normal',
		'priority' => 'low',
	);

	return $boxes;
}

/**
 * @todo: [WPML 3.3] refactor in 3.3
 *
 * @param $type_id
 *
 * @return string
 */
function wpml_custom_post_translation_options() {
	global $sitepress, $sitepress_settings;
	$type_id = isset( $_GET['wpcf-post-type'] ) ? $_GET['wpcf-post-type'] : '';

	$out = '';

	$type = get_post_type_object( $type_id );

	$translated = $sitepress->is_translated_post_type( $type_id );
	if ( defined( 'WPML_TM_VERSION' ) ) {
		$link  = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup#icl_custom_posts_sync_options' );
		$link2 = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup#icl_slug_translation' );
	} else {
		$link  = admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translation-options.php#icl_custom_posts_sync_options' );
		$link2 = admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translation-options.php#icl_slug_translation' );
	}

	if ( $translated ) {

		$out .= sprintf( __( '%s is translated via WPML. %sClick here to change translation options.%s', 'sitepress' ), '<strong>' . $type->labels->singular_name . '</strong>', '<a href="' . $link . '">', '</a>' );

		if ( $type->rewrite['enabled'] ) {

			if ( $sitepress_settings['posts_slug_translation']['on'] ) {
				if ( empty( $sitepress_settings['posts_slug_translation']['types'][ $type_id ] ) ) {
					$out .= '<ul><li>' . __( 'Slugs are currently not translated.', 'sitepress' ) . '<li></ul>';
				} else {
					$out .= '<ul><li>' . __( 'Slugs are currently translated. Click the link above to edit the translations.', 'sitepress' ) . '<li></ul>';
				}
			} else {
				$out .= '<ul><li>' . sprintf( __( 'Slug translation is currently disabled in WPML. %sClick here to enable.%s', 'sitepress' ), '<a href="' . $link2 . '">', '</a>' ) . '</li></ul>';
			}
		}
	} else {

		$out .= sprintf( __( '%s is not translated. %sClick here to make this post type translatable.%s', 'sitepress' ), '<strong>' . $type->labels->singular_name . '</strong>', '<a href="' . $link . '">', '</a>' );
	}

	return $out;
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_add_language_selector' filter instead
 */
function icl_language_selector() {
	ob_start();
	do_action( 'wpml_add_language_selector' );
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}

/**
 * Display the drop down language selector
 * @since 3.2
 * Will use the language selector settings from "Language switcher as shortcode or action"
 * @use \SitePress::api_hooks
 * example: do_action( 'wpml_add_language_selector' );
 */
function wpml_add_language_selector_action() {
	do_action( 'wpml_add_language_selector' );
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_footer_language_selector' filter instead
 */
function icl_language_selector_footer() {
	ob_start();
	do_action( 'wpml_footer_language_selector' );
	$output = ob_get_contents();
	ob_end_clean();
	return $output;
}

/**
 * Display the footer language selector
 * @since 3.2
 * Will use the language selector include configuration from the WPML -> Language admin screen
 * @use \SitePress::api_hooks
 * example: do_action('wpml_footer_language_selector');
 */
function wpml_footer_language_selector_action() {
	do_action( 'wpml_footer_language_selector' );
}

/**
 * Returns an HTML hidden input field with name="lang" and value of current language
 * This is for theme authors, to make their themes compatible with WPML when using the search form.
 * In order to make the search form work properly, they should use standard WordPress template tag get_search_form()
 * In this case WPML will handle the the rest.
 * If for some reasons the template function can't be used and form is created differently,
 * authors must the following code between inside the form
 * <?php
 * if (function_exists('wpml_the_language_input_field')) {
 *    wpml_the_language_input_field();
 * }
 * @global SitePress $sitepress
 * @return string|null HTML input field or null
 * @since      3.2
 * @deprecated 3.2 use 'wpml_add_language_form_field' filter instead
 */
function wpml_get_language_input_field() {
	global $sitepress;
	if ( isset( $sitepress ) ) {
		return "<input type='hidden' name='lang' value='" . esc_attr( $sitepress->get_current_language() ) . "' />";
	}

	return null;
}

/**
 * Echoes the value returned by \wpml_get_language_input_field
 * @since      3.1.7.3
 * @deprecated 3.2 use 'wpml_add_language_form_field' filter instead
 */
function wpml_the_language_input_field() {
	echo wpml_get_language_input_field();
}

/**
 * @since 3.2
 * Returns an HTML hidden input field with name="lang" and as value the current language
 * In order to add a search form to your theme you would normally use the standard WordPress template tag: <code>get_search_form()</code>
 * If you are making use of the default WordPress search form, you do not need to edit anything. WPML will handle the rest.
 * However, there may be times when <code>get_search_form()</code> can't be used.
 * If you are creating a custom search form and you need to make it WPML compatible then this action hook is what you need.
 * Add the action hook  inside the form:
 * <?php
 * do_action('wpml_add_language_form_field');
 * ?>
 * @global SitePress $sitepress
 * @return string|null HTML input field or null
 * @use \SitePress::api_hooks
 */
function wpml_add_language_form_field_action() {
	echo wpml_get_language_form_field();
}

function wpml_language_form_field_shortcode() {
	return wpml_get_language_form_field();
}

function wpml_get_language_form_field() {
	$language_form_field = '';
	global $sitepress;
	if ( isset( $sitepress ) ) {
		$current_language    = $sitepress->get_current_language();
		$language_form_field = "<input type='hidden' name='lang' value='" . esc_attr( $current_language ) . "' />";
		$language_form_field = apply_filters( 'wpml_language_form_input_field', $language_form_field, $current_language );
	}

	return $language_form_field;
}

/**
 * @since      unknown
 * @deprecated 3.2 use 'wpml_element_translation_type' filter instead
 *
 * @param        $id
 * @param string $type
 *
 * @return bool|int
 */
function wpml_get_translation_type( $id, $type = 'post' ) {
	$translation_type = WPML_ELEMENT_IS_NOT_TRANSLATED;

	if ( $type == 'post' ) {
		$translation_type = wpml_post_has_translations( $id );
	}

	//TODO: [WPML 3.3] handle other element types (e.g. taxonomies, strings, etc.)

	return $translation_type;
}

/**
 * @since 3.2
 * Accepts the ID and type of an element and returns its translation type.
 * Values will be one of these:
 *      WPML_ELEMENT_IS_NOT_TRANSLATED  = 0
 *      WPML_ELEMENT_IS_TRANSLATED      = 1
 *      WPML_ELEMENT_IS_DUPLICATED      = 2
 *      WPML_ELEMENT_IS_A_DUPLICATE     = 3
 *
 * @param mixed  $empty_value
 *
 * @see   \wpml_get_active_languages_filter
 *
 * @param int    $element_id    The element id to retrieve the information of. Use term_id for taxonomies, post_id for posts
 * @param string $element_type  Can be a post type: post, page, attachment, nav_menu_item, {custom post key}
 *                              or taxonomy: category, post_tag, nav_menu {custom taxonomy key}
 *
 * @return int
 * @use \SitePress::api_hooks
 */
function wpml_get_element_translation_type_filter( $empty_value, $element_id, $element_type ) {
	$translation_type = WPML_ELEMENT_IS_NOT_TRANSLATED;

	$element_has_translations = apply_filters( 'wpml_element_has_translations', null, $element_id, $element_type );
	$element_is_master        = apply_filters( 'wpml_master_post_from_duplicate', $element_id );
	$element_is_duplicate     = apply_filters( 'wpml_post_duplicates', $element_id );

	if ( $element_has_translations ) {
		$translation_type = WPML_ELEMENT_IS_TRANSLATED;
		if ( $element_is_master ) {
			$translation_type = WPML_ELEMENT_IS_A_DUPLICATE;
		} elseif ( $element_is_duplicate ) {
			$translation_type = WPML_ELEMENT_IS_DUPLICATED;
		}
	}

	return $translation_type;
}

/**
 * Accepts the ID of a post and returns its translation type.
 * Values will be one of these:
 *      WPML_ELEMENT_IS_NOT_TRANSLATED  = 0
 *      WPML_ELEMENT_IS_TRANSLATED      = 1
 *      WPML_ELEMENT_IS_DUPLICATED      = 2
 *      WPML_ELEMENT_IS_A_DUPLICATE     = 3
 *
 * @param int $post_id The ID of the post from which to get translation information
 *
 * @return int
 * @internal   param string $post_type
 * @since      3.2
 * @deprecated 3.2 use 'wpml_element_translation_type' filter instead
 */
function wpml_get_post_translation_type( $post_id ) {
	$translation_type = WPML_ELEMENT_IS_NOT_TRANSLATED;

	$post_type = get_post_type( $post_id );
	if ( wpml_post_has_translations( $post_id, $post_type ) ) {
		$translation_type = WPML_ELEMENT_IS_TRANSLATED;
		if ( wpml_get_master_post_from_duplicate( $post_id ) ) {
			$translation_type = WPML_ELEMENT_IS_A_DUPLICATE;
		} elseif ( wpml_get_post_duplicates( $post_id ) ) {
			$translation_type = WPML_ELEMENT_IS_DUPLICATED;
		}
	}

	return $translation_type;
}

/**
 * @param int    $post_id
 * @param string $post_type
 *
 * @return bool
 * @since      3.2
 * @deprecated 3.2 use 'wpml_element_has_translations' filter instead
 */
function wpml_post_has_translations( $post_id, $post_type = 'post' ) {
	$has_translations = false;
	global $sitepress;
	if ( isset( $sitepress ) ) {
		$trid         = $sitepress->get_element_trid( $post_id, 'post_' . $post_type );
		$translations = $sitepress->get_element_translations( $trid );
		if ( $translations && count( $translations ) > 1 ) {
			$has_translations = true;
		}
	}

	return $has_translations;
}

/**
 * Checks if an element has translations
 * A translation can be a manual translation or a duplication.
 * @since 3.2
 *
 * @param mixed  $empty_value
 *
 * @see   \wpml_get_active_languages_filter
 *
 * @param int    $element_id    Use term_id for taxonomies, post_id for posts
 * @param string $element_type  Can be a post type: post, page, attachment, nav_menu_item, {custom post key}
 *                              or taxonomy: category, post_tag, nav_menu {custom taxonomy key}
 *
 * @return bool
 * @use \SitePress::api_hooks
 */
function wpml_element_has_translations_filter( $empty_value, $element_id, $element_type = 'post' ) {
	$has_translations = false;
	global $sitepress;
	if ( isset( $sitepress ) ) {
		$wpml_element_type = apply_filters( 'wpml_element_type', $element_type );

		if ( strpos( $wpml_element_type, 'tax_' ) === 0 ) {
			global $wpml_term_translations;
			$term_language_code = $wpml_term_translations->lang_code_by_termid( $element_id );
			$element_id         = $wpml_term_translations->term_id_in( $element_id, $term_language_code );
		}

		$trid = $sitepress->get_element_trid( $element_id, $wpml_element_type );

		//$translations = $sitepress->get_element_translations($trid);
		$translations = apply_filters( 'wpml_get_element_translations_filter', '', $trid, $wpml_element_type );

		if ( $translations && count( $translations ) > 1 ) {
			$has_translations = true;
		}
	}

	return $has_translations;
}

function wpml_get_content_translations_filter( $empty, $post_id, $content_type = 'post' ) {
	global $sitepress;
	$translations = array();
	if ( isset( $sitepress ) ) {
		$wpml_element_type = apply_filters( 'wpml_element_type', $content_type );

		$trid = $sitepress->get_element_trid( $post_id, $wpml_element_type );

		$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $wpml_element_type );
	}

	return $translations;
}

/**
 * @param $post_id
 *
 * @return mixed
 * @since      3.2
 * @deprecated 3.2 use 'wpml_master_post_from_duplicate' filter instead
 */
function wpml_get_master_post_from_duplicate( $post_id ) {
	return get_post_meta( $post_id, '_icl_lang_duplicate_of', true );
}

/**
 * Get the original post from the duplicated post
 *
 * @param int $post_id The duplicated post ID
 *
 * @return int or empty string if there is nothing to return
 * @use \SitePress::api_hooks
 */
function wpml_get_master_post_from_duplicate_filter( $post_id ) {
	return get_post_meta( $post_id, '_icl_lang_duplicate_of', true );
}

/**
 * @param $master_post_id
 *
 * @return mixed
 * @since      3.2
 * @deprecated 3.2 use 'wpml_post_duplicates' filter instead
 */
function wpml_get_post_duplicates( $master_post_id ) {
	global $sitepress;
	if ( isset( $sitepress ) ) {
		return $sitepress->get_duplicates( $master_post_id );
	}

	return array();
}

/**
 * Get the duplicated post ids
 * Will return an associative array with language codes as indexes and post_ids as values
 *
 * @param int $master_post_id The original post id from which duplicates exist
 *
 * @return array
 * @use \SitePress::api_hooks
 */
function wpml_get_post_duplicates_filter( $master_post_id ) {
	global $sitepress;
	if ( isset( $sitepress ) ) {
		return $sitepress->get_duplicates( $master_post_id );
	}

	return array();
}

/**
 * Filters a WordPress element by adding the WPML prefix 'post_', 'tax_', or nothing for 'comment' as used in icl_translations db table
 * @since 3.2
 *
 * @param string $element_type Accepts comment, post, page, attachment, nav_menu_item, {custom post key},
 *                                nav_menu, category, post_tag, {custom taxonomy key}
 *
 * @return string
 * @use \SitePress::api_hooks
 */
function wpml_element_type_filter( $element_type ) {
	global $wp_post_types, $wp_taxonomies;

	$post_types = array_keys( (array) $wp_post_types );
	$taxonomies = array_keys( (array) $wp_taxonomies );

	if ( in_array( $element_type, $taxonomies ) ) {
		$wpml_element_type = 'tax_' . $element_type;
	} elseif ( in_array( $element_type, $post_types ) ) {
		$wpml_element_type = 'post_' . $element_type;
	} else {
		$wpml_element_type = $element_type;
	}

	return $wpml_element_type;
}

/**
 * Retrieves language information for a translatable element
 * Checks icl_translations db table and returns an object with the element's
 * trid, source language code and language code
 * @since                             3.2.2
 *
 * @param mixed $element_object       A WordPress object. Defaults to null
 * @param array $args                 {
 *                                    Required An array of arguments to be used
 *
 * @type int    $element_id           Use term_taxonomy_id for taxonomies, post_id for posts
 * @type string $element_type         Can be a post type: post, page, attachment, nav_menu_item, {custom post key}
 *                                    or taxonomy: category, post_tag, nav_menu {custom taxonomy key}
 *                                    }
 * @return object
 * @use \SitePress::api_hooks
 */
function wpml_element_language_details_filter($element_object = null, $args) {
	global $sitepress;
	if(isset($sitepress)) {
		$element_type   = apply_filters( 'wpml_element_type', $args[ 'element_type' ] );
		$element_object = $sitepress->get_element_language_details( $args[ 'element_id' ], $element_type );
	}

	return $element_object;
}

/**
 * Retrieves the language code for a translatable element
 * Checks icl_translations db table and returns the element's language code
 * @since                             3.2.2
 *
 * @param mixed $language_code        A 2-letter language code. Defaults to null
 * @param array $args                 {
 *                                    Required An array of arguments to be used
 *
 * @type int    $element_id           Use term_taxonomy_id for taxonomies, post_id for posts
 * @type string $element_type         Can be a post type: post, page, attachment, nav_menu_item, {custom post key}
 *                                    or taxonomy: category, post_tag, nav_menu {custom taxonomy key}
 *                                    }
 * @return string
 * @use \SitePress::api_hooks
 */
function wpml_element_language_code_filter($language_code = null, $args) {
	global $sitepress;
	if(isset($sitepress)) {
		$element_type = apply_filters( 'wpml_element_type', $args[ 'element_type' ] );

		$language_code =  $sitepress->get_language_for_element( $args[ 'element_id' ], $element_type );
	}

	return $language_code;
}

/**
 * Retrieves the elements without translations
 * Queries the database and returns an array with ids
 * @since                             3.2.2
 *
 * @param array $element_ids          An array of element ids. Defaults to an empty array
 * @param array $args                 {
 *                                    Required An array of arguments to be used
 *
 * @type string $target_language      The target language code
 * @type string $source_language      The source language code
 * @type string $element_type         Can be a post type: post, page, attachment, nav_menu_item, {custom post key}
 *                                    or taxonomy: category, post_tag, nav_menu {custom taxonomy key}
 *                                    }
 * @return array
 * @use \SitePress::api_hooks
 */
function wpml_elements_without_translations_filter($element_ids = array(), $args) {
	global $sitepress;
	if(isset($sitepress)) {
		$element_type = apply_filters( 'wpml_element_type', $args[ 'element_type' ] );
		$element_ids  = $sitepress->get_elements_without_translations( $element_type, $args[ 'target_language' ], $args[ 'source_language' ] );
	}
	return $element_ids;
}

/**
 * Filters a WordPress permalink and converts it to a language specific permalink based on plugin settings
 * @since            3.2.2
 * @type string      $url The WordPress generated url to filter
 * @type null|string $language_code
 *                   (if null, it falls back to default language for root page, or current language in all other cases)
 * @return string
 * @use \SitePress::api_hooks
 */
function wpml_permalink_filter($permalink, $language_code = null) {
	global $sitepress;
	if(isset($sitepress)) {
		$permalink = $sitepress->convert_url( $permalink, $language_code );
	}
	return $permalink;
}

/**
 * Switches WPML's query language
 * @since                           3.2.2
 * @type null|string $language_code The language code to switch  to
 *                                  If set to null it restores the original language
 *                                  If set to 'all' it will query content from all active languages
 *                                  Defaults to null
 * @use \SitePress::api_hooks
 */
function wpml_switch_language_action($language_code = null) {
	global $sitepress;

	$sitepress->switch_lang( $language_code, true );
}

