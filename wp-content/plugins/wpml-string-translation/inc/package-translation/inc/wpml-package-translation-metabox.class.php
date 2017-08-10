<?php

class WPML_Package_Translation_Metabox {
	private $args;
	private $active_languages;
	private $container_attributes_html;
	private $dashboard_link;
	private $strings_link;
	private $default_language;
	private $main_container_attributes;
	private $show_description;
	private $show_link;
	private $show_status;
	private $show_title;
	private $status_container_attributes;
	private $status_container_attributes_html;
	private $status_container_tag;
	private $status_element_tag;
	private $title_tag;

	public $metabox_data;

	/**
	 * @var SitePress
	 */
	private $sitepress;
	private $translation_statuses;
	/**
	 * @var WPDB
	 */
	private $wpdb;

	public function __construct( $package, $wpdb, $sitepress, $args = array() ) {

		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
		$this->package   = new WPML_Package( $package );
		$this->args      = $args;

		if ( $this->got_package() ) {
			$this->dashboard_link = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=dashboard&type=' . $this->package->kind_slug . '&lang=' . $this->package->get_package_language() );
			$this->strings_link   = admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . $this->package->get_string_context_from_package() );
		}

		$this->parse_arguments( $args );
		$this->init_metabox_data();
	}

	private function init_metabox_data() {
		$this->metabox_data     = array();
		$this->active_languages = $this->sitepress->get_active_languages();
		$this->default_language = $this->sitepress->get_default_language();
		$this->package_language = $this->package->get_package_language();
		$this->package_language = $this->package_language ? $this->package_language : $this->default_language;

		$this->metabox_data[ 'title' ]                  = __( 'WPML Translation', 'wpml-string-translation' );
		if ( $this->is_package_language_active( ) ) {
			$this->metabox_data[ 'translate_title' ]        = __( 'Send to translation', 'wpml-string-translation' );
		} else {
			$this->metabox_data[ 'translate_title' ]        = __( 'Translate strings', 'wpml-string-translation' );
		}

		if ( $this->got_package() ) {
			$this->metabox_data[ 'statuses_title' ] = __( 'Translation status:', 'wpml-string-translation' );
			$this->init_translation_statuses();
			$this->metabox_data[ 'package_language_title' ] = sprintf( __( 'Language of this %s is %s', 'wpml-string-translation' ), $this->package->kind, $this->get_lang_selector( ) );
		} else {
			$this->metabox_data[ 'package_language_title' ] = '';
			$this->metabox_data[ 'statuses_title' ] = __( 'There is nothing to translate.', 'wpml-string-translation' );
		}
	}

	private function get_lang_selector( ) {

		$disable = $this->check_if_language_change_is_ok() ? '' : 'disabled="disabled" ';
		
		ob_start( );
		
		$languages = $this->active_languages;
		if ( ! $this->is_package_language_active( ) ) {
			$languages = array_merge( array( $this->sitepress->get_language_details( $this->package_language ) ), $languages );
		} else {
			$languages = $this->active_languages;
		}
		
		$selector = new WPML_Simple_Language_Selector( $this->sitepress );
		$selector->render( array(
								'id'                 => 'icl_package_language',
								'name'               => 'icl_package_language',
								'show_please_select' => false,
								'languages'          => $languages,
								'selected'           => $this->package_language,
								'disabled'           => ! $this->check_if_language_change_is_ok(),
								'echo'               => true
								)
							  );
		?>
			<span class="spinner"></span>
		<?php
		
		return ob_get_clean();
	}
	
	public function get_package_language_name() {
		if ( $this->is_package_language_active ( ) ) {
			return $this->active_languages[ $this->package_language ][ 'display_name' ];
		} else {
			$all_languages = $this->sitepress->get_languages();
			return $all_languages[ $this->package_language ]['display_name'];
		}
	}
	
	private function get_lang_switcher_js( ) {
		ob_start();
		
		?>
			<script type="text/javascript">
				
				var WPML_Package_Translation = WPML_Package_Translation || {};
				
				WPML_Package_Translation.MetaBox = function () {
					var self = this;
					
					self.init = function () {
						jQuery('#icl_package_language').off('change');
						jQuery('#icl_package_language').on('change', self.switch_lang);
					};
					
					self.switch_lang = function() {
						self.disable_controls(true);
						var data = {
							action:          'wpml_change_package_lang',
							wpnonce:         jQuery('#wpml_package_nonce').val(),
							package_id:      jQuery('#wpml_package_id').val(),
							args:            jQuery('#wpml_package_args').val(),
							package_lang:    jQuery(this).val()
						};

						jQuery.ajax(
							{
								url:     ajaxurl,
								type:    'post',
								data:    data,
								dataType: 'json',
								success: function (response) {
									var position = jQuery('#wpml_package_status').prev();
									jQuery('#wpml_package_status').remove();
									jQuery(response.metabox).insertAfter(position);
									
									jQuery('#icl_package_language').trigger('wpml-package-language-changed', [response.lang]);
									
									self.disable_controls(false);
								}
							});
					};
					
					self.disable_controls = function(state) {
						var lang_switcher = jQuery('#icl_package_language');
						if (state) {
							lang_switcher.next('.spinner').show();
							lang_switcher.prop('disabled', true);
							lang_switcher.closest('div').find('a').prop('disabled', true);
						} else {
							lang_switcher.next('.spinner').hide();
							lang_switcher.prop('disabled', false);
							lang_switcher.closest('div').find('a').prop('disabled', false);
						}
					};
					
					self.init();
				};
				
				jQuery(document).ready(
					function () {
						WPML_Package_Translation.meta_box = new WPML_Package_Translation.MetaBox();
					}
				);
				
			</script>
		
		<?php
		
		return ob_get_clean();
	}
	
	function get_metabox() {
		$result = '';
		$result .= '<div ' . $this->container_attributes_html . '>';
		if ( $this->show_title ) {
			if ( $this->title_tag ) {
				$result .= $this->get_tag( $this->title_tag );
			}
			$result .= $this->metabox_data[ 'title' ];
			if ( $this->title_tag ) {
				$result .= $this->get_tag( $this->title_tag, 'closed' );
			}
		}
		if ( $this->show_description ) {
			$result .= '<p>' . $this->metabox_data[ 'package_language_title' ] . '</p>';
		}
		if ( $this->show_status ) {
			$result .= '<p>' . $this->metabox_data[ 'statuses_title' ] . '</p>';
		}

		$result .= $this->get_metabox_status();

		$result .= '</div>';
		$result .= wp_nonce_field( 'wpml_package_nonce', 'wpml_package_nonce', true, false );
		$result .= '<input type="hidden" id="wpml_package_id" value="' . $this->package->ID . '" />';
		$result .= '<input type="hidden" id="wpml_package_args" value="' . base64_encode( wp_json_encode( $this->args ) ) . '" />';
		
		if ( ! defined( 'DOING_AJAX' ) ) {
			$result .= $this->get_lang_switcher_js();
		}

		return $result;
	}

	public function get_metabox_status() {
		$result = '';
		if ( $this->got_package() ) {
			$result .= '<div id="wpml_package_status">';
			if ( $this->show_status && $this->metabox_data[ 'statuses' ] ) {
				if ( $this->status_container_tag ) {
					$result .= $this->get_tag( $this->status_container_tag . ' ' . $this->status_container_attributes_html );
				}
				foreach ( $this->metabox_data[ 'statuses' ] as $code => $status ) {
					$result .= $this->get_tag( $this->status_element_tag );
					$result .= '<img src="' . $this->sitepress->get_flag_url( $code ) . '"> ' . $status[ 'name' ] . ' : ' . $status[ 'status' ];
					$result .= $this->get_tag( $this->status_element_tag, 'closed' );
				}
				if ( $this->status_container_tag ) {
					$result .= $this->get_tag( $this->status_container_tag, 'closed' );
				}
			}
			if ( $this->show_link ) {
				if ( $this->is_package_language_active() ) {
					$result .= '<p><a style="float:right" class="button-secondary" href="' . $this->dashboard_link . '" target="_blank">' . $this->metabox_data[ 'translate_title' ] . '</a></p>';
				} else {
					$result .= '<p><a style="float:right" class="button-secondary" href="' . $this->strings_link . '" target="_blank">' . $this->metabox_data[ 'translate_title' ] . '</a></p>';
				}
			}
			$result .= '<br /><br /></div>';
		}
		
		return $result;
	}
	
	private function check_if_language_change_is_ok() {
		
		$ok = true;
		
		foreach ( $this->translation_statuses as $status ) {
			if ( isset( $status->status_code ) && $status->status_code != 0 ) {
				$ok = false;
				break;
			}
		}
		
		if ( $ok ) {
			$translations = $this->package->get_translated_strings( array() );
			foreach ( $translations as $string ) {
				foreach ( $string as $lang => $data ) {
					if ( $data[ 'status'] == ICL_STRING_TRANSLATION_COMPLETE ) {
						$ok = false;
						break;
					}
				}
				if ( ! $ok ) {
					break;
				}
			}
		}
		
		return $ok;
	}
	
	/**
	 * @param $attributes
	 *
	 * @return string
	 */
	private function attributes_to_string( $attributes ) {
		$result = '';
		foreach ( $attributes as $key => $value ) {
			if ( $result ) {
				$result .= ' ';
			}
			$result .= esc_html( $key ) . '="' . esc_attr( $value ) . '"';
		}

		return $result;
	}

	function get_post_translations() {
		
		$element_type = $this->package->get_package_element_type();
		$trid         = $this->sitepress->get_element_trid( $this->package->ID, $element_type );

		return $this->sitepress->get_element_translations( $trid, $element_type );
	}
	
	private function get_tag( $tag, $closed = false ) {
		$result = '<';
		if ( $closed ) {
			$result .= '/';
		}
		$result .= $tag . '>';

		return $result;
	}

	/**
	 * @param $args
	 */
	private function parse_arguments( $args ) {
		$default_args = array(
			'show_title'                  => true,
			'show_description'            => true,
			'show_status'                 => true,
			'show_link'                   => true,
			'title_tag'                   => 'h2',
			'status_container_tag'        => 'ul',
			'status_element_tag'          => 'li',
			'main_container_attributes'   => array(),
			'status_container_attributes' => array( 'style' => 'padding-left: 10px' ),
		);

		$args = array_merge( $default_args, $args );

		$this->show_title                  = $args[ 'show_title' ];
		$this->show_description            = $args[ 'show_description' ];
		$this->show_status                 = $args[ 'show_status' ];
		$this->show_link                   = $args[ 'show_link' ];
		$this->title_tag                   = $args[ 'title_tag' ];
		$this->status_container_tag        = $args[ 'status_container_tag' ];
		$this->status_element_tag          = $args[ 'status_element_tag' ];
		$this->main_container_attributes   = $args[ 'main_container_attributes' ];
		$this->status_container_attributes = $args[ 'status_container_attributes' ];

		$this->container_attributes_html        = $this->attributes_to_string( $this->main_container_attributes );
		$this->status_container_attributes_html = $this->attributes_to_string( $this->status_container_attributes );
	}

	/**
	 * @return bool
	 */
	private function got_package() {
		return $this->package && $this->package->ID;
	}

	private function get_translation_statuses() {
		$post_translations = $this->get_post_translations();
		$status            = array();
		foreach ( $post_translations as $language => $translation ) {
			$res_query   = "SELECT status as status_code, needs_update FROM {$this->wpdb->prefix}icl_translation_status WHERE translation_id=%d";
			$res_args    = array( $translation->translation_id );
			$res_prepare = $this->wpdb->prepare( $res_query, $res_args );
			$res         = $this->wpdb->get_row( $res_prepare );
			if ( $res ) {
				$res->status = $res->status_code;
				switch ( $res->status ) {
					case ICL_TM_WAITING_FOR_TRANSLATOR:
						$res->status = __( 'Waiting for translator', 'wpml-string-translation' );
						break;
					case ICL_TM_IN_PROGRESS:
						$res->status = __( 'In progress', 'wpml-string-translation' );
						break;
					case ICL_TM_NEEDS_UPDATE:
						$res->status = '';
						break;
					case ICL_TM_COMPLETE:
						$res->status = __( 'Complete', 'wpml-string-translation' );
						break;
					default:
						$res->status = __( 'Not translated', 'wpml-string-translation' );
						break;
				}

				if ( $res->needs_update ) {
					if ( $res->status ) {
						$res->status .= ' - ';
					}
					$res->status .= __( 'Needs update', 'wpml-string-translation' );
				}
				$status[ $language ] = $res;
			}
		}

		return $status;
	}

	private function init_translation_statuses() {
		$this->metabox_data[ 'statuses' ] = array();
		$this->translation_statuses = $this->get_translation_statuses();
		foreach ( $this->active_languages as $language_data ) {
			if ( $language_data[ 'code' ] != $this->package_language ) {
				$display_name = $language_data[ 'display_name' ];

				$this->metabox_data[ 'statuses' ][ $language_data[ 'code' ] ] = array( 'name'   => $display_name,
																					   'status' => $this->get_status_value( $language_data )
																					 );
			}
		}
	}

	private function get_status_value( $language_data ) {
		if ( isset( $this->translation_statuses[ $language_data[ 'code' ] ] ) ) {
			$status_value = $this->translation_statuses[ $language_data[ 'code' ] ]->status;
		} else {
			$tm = new WPML_Package_TM( $this->package );
			if ( $tm->is_in_basket( $language_data[ 'code' ] ) ) {
				$status_value = __( 'In translation basket', 'wpml-string-translation' );
			} else {
				$status_value = __( 'Not translated', 'wpml-string-translation' );
			}
		}

		return $status_value;
	}
	
	private function is_package_language_active( ) {
		return in_array( $this->package_language, array_keys( $this->active_languages ) );
	}
}