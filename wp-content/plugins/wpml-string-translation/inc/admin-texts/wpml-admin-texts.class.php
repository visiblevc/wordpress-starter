<?php
require_once dirname( __FILE__ ) . '/wpml-admin-text-functionality.class.php';

class WPML_Admin_Texts extends WPML_Admin_Text_Functionality{

	private $icl_st_cache = array();

	/** @var  TranslationManagement $tm_instance */
	private $tm_instance;

	/** @var  WPML_String_Translation $st_instance */
	private $st_instance;

	/**
	 * @param TranslationManagement $tm_instance
	 * @param WPML_String_Translation $st_instance
	 */
	function __construct( &$tm_instance, &$st_instance ) {
		add_action( 'plugins_loaded', array( $this, 'icl_st_set_admin_options_filters' ), 10 );
		add_filter( 'wpml_unfiltered_admin_string', array( $this, 'unfiltered_admin_string_filter' ), 10, 2 );
		$this->tm_instance = &$tm_instance;
		$this->st_instance = &$st_instance;
	}

	function icl_register_admin_options( $array, $key = "", $option = array() ) {
		if(is_object($option)) {
			$option = object_to_array($option);
		}
		foreach ( $array as $k => $v ) {
			$option = $key === '' ? array( $k => maybe_unserialize( $this->get_option_without_filtering( $k ) ) ) : $option;
			if ( is_array( $v ) ) {
				$this->icl_register_admin_options( $v, $key . '[' . $k . ']', $option[ $k ] );
			} else {
				$context = $this->get_context( $key, $k );
				if ( $v === '' ) {
					icl_unregister_string( $context, $key . $k );
				} elseif ( isset( $option[ $k ] ) && ( $key === '' || preg_match_all( '#\[([^\]]+)\]#',
				                                                                      (string) $key,
				                                                                      $opt_key_matches ) > 0 )
				) {
					icl_register_string( $context, $key . $k, $option[ $k ] );
					$vals     = array( $k => 1 );
					$opt_keys = isset( $opt_key_matches ) ? array_reverse( $opt_key_matches[1] ) : array();
					foreach ( $opt_keys as $opt ) {
						$vals = array( $opt => $vals );
					}
					update_option( '_icl_admin_option_names',
					               array_merge_recursive( (array) get_option( '_icl_admin_option_names' ), $vals ) );

				}
			}
		}
	}

	function icl_st_render_option_writes( $option_name, $option_value, $option_key = '' ) {
		$sub_key = $option_key . '[' . $option_name . ']';
		if ( is_array( $option_value ) || is_object( $option_value ) ) {
			$output = '<h4><a class="icl_stow_toggler" href="#">+ ' . $option_name
			          . '</a></h4><ul class="icl_st_option_writes" style="display: none">';
			foreach ( $option_value as $key => $value ) {
				$output .= '<li>' . $this->icl_st_render_option_writes( $key, $value, $sub_key ) . '</li>';
			}
			$output .= '</ul>';
		} elseif ( is_string( $option_value ) || is_numeric( $option_value ) ) {
			$fixed            = $this->is_sub_key_fixed( $sub_key );
			$string_name      = $option_key . $option_name;
			$context          = $this->get_context( $option_key, $option_name );
			$checked          = icl_st_is_registered_string( $context, $string_name ) ? ' checked="checked"' : '';
			$has_translations = ! $fixed && $checked === ''
			                    && icl_st_string_has_translations( $context, $string_name )
				? ' class="icl_st_has_translations" ' : '';

			$input_val            = ' value="' . htmlspecialchars( $option_value ) . '" ';
			$option_key_name      = ' name="icl_admin_options' . $sub_key . '" ';
			$input_open           = '<input' . ( $fixed ? ' disabled="disabled"' : '' );
			$read_only_input_open = '<input type="text" readonly="readonly"';
			$output               = '<div class="icl_st_admin_string icl_st_' . ( is_numeric( $option_value ) ? 'numeric' : 'string' ) . '">'
			                        . $input_open . ' type="hidden" ' . $option_key_name . ' value="" />'
			                        . $input_open . $has_translations . ' type="checkbox" ' . $option_key_name . $input_val . $checked . ' />'
			                        . $read_only_input_open . ' value="' . $option_name . '" size="32" />'
			                        . $read_only_input_open . $input_val . ' size="48" /></div><br clear="all" />';
		}

		return isset( $output ) ? $output : '';
	}

	private function is_sub_key_fixed( $sub_key ) {
		if ( $fixed = ( preg_match_all( '#\[([^\]]+)\]#', $sub_key, $matches ) > 0 ) ) {

			$fixed_settings = $this->tm_instance->admin_texts_to_translate;
			foreach ( $matches[1] as $m ) {
				if ( $fixed = isset( $fixed_settings[ $m ] ) ) {
					$fixed_settings = $fixed_settings[ $m ];
				} else {
					break;
				}
			}
		}

		return $fixed;
	}

	private function get_context( $option_key, $option_name ) {

		return 'admin_texts_' . ( preg_match( '#\[([^\]]+)\]#', (string) $option_key, $matches ) === 1 ? $matches[1] : $option_name );
	}

	function icl_st_scan_options_strings() {
		$options = wp_load_alloptions();
		foreach ( $options as $name => $value ) {
			if ( $this->is_blacklisted( $name ) ) {
				unset( $options[ $name ] );
			} else {
				$options[ $name ] = maybe_unserialize( $value );
			}
		}

		return $options;
	}

	function icl_st_set_admin_options_filters() {
		static $option_names;
		if ( empty( $option_names ) ) {
			$option_names = get_option( '_icl_admin_option_names' );
		}

		if ( is_array( $option_names ) ) {
			foreach ( $option_names as $option_key => $option ) {
				if ( $this->is_blacklisted( $option_key ) ) {
					unset( $option_names[ $option_key ] );
					update_option( '_icl_admin_option_names', $option_names );
				}
				elseif ( $option_key != 'theme' && $option_key != 'plugin' ) { // theme and plugin are an obsolete format before 3.2
					add_filter( 'option_' . $option_key, array( $this, 'icl_st_translate_admin_string' ) );
					add_action( 'update_option_' . $option_key, array( $this, 'clear_cache_for_option' ), 10, 0);
				}
			}
		}
	}

	function icl_st_translate_admin_string( $option_value, $key = "", $name = "", $rec_level = 0 ) {
		$lang        = $this->st_instance->get_current_string_language( $name );
		$option_name = substr( current_filter(), 7 );
		$name        = $name === '' ? $option_name : $name;
		if ( isset( $this->icl_st_cache[ $lang ][ $name ] ) ) {

			return $this->icl_st_cache[ $lang ][ $name ];
		}

		$serialized   = is_serialized( $option_value );
		$option_value = $serialized ? unserialize( $option_value ) : $option_value;
		if ( is_array( $option_value ) || is_object( $option_value ) ) {
			foreach ( $option_value as $k => &$value ) {
				$value = $this->icl_st_translate_admin_string( $value,
				                                               $key . '[' . $name . ']',
				                                               $k,
				                                               $rec_level + 1 );
			}
		} else {
			
			if ( $this->is_admin_text( $key , $name ) ) {
				$tr           = icl_t( 'admin_texts_' . $option_name, $key . $name, $option_value, $hast, true );
				$option_value = $hast ? $tr : $option_value;
			}
		}
		$option_value = $serialized ? serialize( $option_value ) : $option_value;

		if ( $rec_level === 0 ) {
			$this->icl_st_cache[ $lang ][ $name ] = $option_value;
		}

		return $option_value;
	}

	private function is_admin_text( $key , $name ) {
		static $option_names;
		$option_names = empty( $option_names ) ? get_option( '_icl_admin_option_names' ) : $option_names;

		if ( $key ) {
			$key = ltrim( $key, '[' );
			$key = rtrim( $key, ']' );
			
			$keys = explode( '][', $key );
			
			$test_option_names = $option_names;
			
			foreach ( $keys as $key ) {
				if ( isset( $test_option_names[ $key ] ) ) {
					$test_option_names = $test_option_names[ $key ];
				} else {
					return false;
				}
			}
			
			return isset( $test_option_names[ $name ] );
		} else {
			return isset( $option_names[ $name ] );
		}
	}
	
	function clear_cache_for_option() {
		$option_name = substr( current_filter(), 14 );
		foreach ( array_keys( $this->icl_st_cache ) as $lang_code ) {
			if ( isset( $this->icl_st_cache[ $lang_code ][ $option_name ] ) ) {
				unset ( $this->icl_st_cache[ $lang_code ][ $option_name ] );
			}
		}
	}

	/**
	 * @param mixed  $default_value Value to return in case the string does not exists
	 * @param string $option_name   Name of option to retrieve. Expected to not be SQL-escaped.
	 *
	 * @return mixed Value set for the option.
	 */
	function unfiltered_admin_string_filter( $default_value, $option_name ) {
		return $this->get_option_without_filtering( $option_name, $default_value );
	}
}
