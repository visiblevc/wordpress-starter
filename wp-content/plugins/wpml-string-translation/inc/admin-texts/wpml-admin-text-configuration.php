<?php
require_once dirname( __FILE__ ) . '/wpml-admin-text-functionality.class.php';

class WPML_Admin_Text_Configuration extends WPML_Admin_Text_Functionality {

	/** @var  array $config */
	private $config;

	/**
	 * @param string|object $file
	 */
	function __construct( $file = "" ) {
		if ( is_object( $file ) ) {
			$config             = $file->config;
			$type               = $file->type;
			$admin_text_context = $file->admin_text_context;
		} elseif ( is_string( $file ) && $file !== "" && file_exists( $file ) ) {
			$config             = icl_xml2array( file_get_contents( $file ) );
			$type               = ( dirname( $file ) == get_template_directory() || dirname( $file ) == get_stylesheet_directory() ) ? 'theme' : 'plugin';
			$admin_text_context = basename( dirname( $file ) );
		}

		$admin_text_config = isset( $config['wpml-config']['admin-texts'] ) ? $config['wpml-config']['admin-texts'] : array();
		$wpml_config_all   = array();
		if ( isset( $type ) && isset( $admin_text_context ) && isset( $admin_text_config['key'] ) ) {
			if ( isset( $admin_text_config['key']['attr'] ) ) { //single
				$admin_text_config['key']['type']    = $type;
				$admin_text_config['key']['context'] = $admin_text_context;
				$wpml_config_all[]                   = $admin_text_config['key'];
			} else {
				foreach ( (array) $admin_text_config['key'] as $cf ) {
					$cf['type']        = $type;
					$cf['context']     = $admin_text_context;
					$wpml_config_all[] = $cf;
				}
			}
		}

		$this->config = $this->fill_wildcards( $wpml_config_all );
	}

	function get_config_array() {

		return $this->config;
	}

	function get_wpml_config_file( $data ) {

		return "<wpml-config>\n\t<admin-texts>\n" . $this->output_xml( $data, 0 )
		       . "\t</admin-texts>\n</wpml-config>\n";
	}

	private function output_xml( $data, $level ) {
		$output = '';

		foreach ( $data as $key => $value ) {
			$tabs = str_repeat( "\t", $level + 2 );
			$output .= $tabs . '<key name="' . $key . '"'
			           . ( is_array( $value ) && ! empty( $value )
					? ">\n" . $this->output_xml( $value, $level + 1 ) . $tabs . '</key' : '/' )
			           . ">\n";
		}

		return $output;
	}

	private function fill_wildcards( array $config_array ) {

		return ( ! isset( $config_array['attr']['name'] ) || $config_array['attr']['name'] !== '*' )
		       && ( ! isset( $config_array[0]['attr']['name'] ) || $config_array[0]['attr']['name'] !== '*' )
			? $this->remove_unmatched( $config_array,
			                           $this->all_strings_array( $this->get_top_level_filters( $config_array ) ) )
			: array();
	}

	private function get_top_level_filters( array $config_array ) {
		$ret          = array();
		$config_array = isset( $config_array['attr']['name'] ) ? array( $config_array ) : $config_array;
		foreach ( $config_array as $option ) {
			if ( isset( $option['attr']['name'] ) ) {
				$ret[] = $option['attr']['name'];
			}
		}

		return $ret;
	}

	private function remove_unmatched( array $input, array $all_possibilities ) {
		$ret   = array();
		$input = isset( $input['attr'] ) ? array( $input ) : $input;
		foreach ( $input as $val ) {
			$name_matcher = $this->wildcard_to_matcher( $val['attr']['name'] );
			foreach ( $all_possibilities as $a_val ) {
				if ( preg_match( $name_matcher, $a_val['attr']['name'] ) === 1 ) {
					$match                 = $val;
					$match['attr']['name'] = $a_val['attr']['name'];
					$has_sub_filter        = ! empty( $val['key'] );
					$match['key']          = $has_sub_filter
						? $this->remove_unmatched( $val['key'], $a_val['key'] )
						: $a_val['key'];
					if ( $has_sub_filter === false || ! empty( $match['key'] ) ) {
						$ret[] = $match;
					}
				}
			}
		}

		return $ret;
	}

	/**
	 * Creates a regex matcher from a wildcard string name definition
	 *
	 * @param string $wildcard
	 *
	 * @return string
	 */
	private function wildcard_to_matcher( $wildcard ) {

		return '#^' . str_replace( '*', '.+', $wildcard ) . '$#';
	}

	private function all_strings_array( array $top_level_filters ) {
		global $wpdb;

		if ( (bool) $top_level_filters === false ) {
			return array();
		}

		foreach ( $top_level_filters as $key => $filter ) {
			$like                      = strpos( $filter, '*' ) !== false;
			$comparator                = $like ? ' LIKE ' : '=';
			$top_level_filters[ $key ] = $wpdb->prepare( ' option_name ' . $comparator . ' %s ',
			                                             $like
				                                             ? $wpdb->esc_like( str_replace( '*', '%', $filter ) )
				                                             : $filter );
		}

		$where = ' AND ( ' . join( ' OR ', $top_level_filters ) . ' )';

		$strings     = $wpdb->get_results( "SELECT option_name, option_value
											FROM {$wpdb->options}
											WHERE option_name NOT LIKE '_transient%'
											AND option_name NOT LIKE '_site_transient%' {$where}
											AND LENGTH(option_value) < 1000000" );
		$all_options = array();
		foreach ( $strings as $data_pair ) {
			if ( $this->is_blacklisted( $data_pair->option_name ) === false ) {
				$all_options[ $data_pair->option_name ] = maybe_unserialize( $data_pair->option_value );
			}
		}

		return $this->reformat_array( $all_options );
	}

	private function reformat_array( $option_value ) {
		$ret = array();
		if ( is_array( $option_value ) ) {
			foreach ( $option_value as $key => $value ) {
				$ret[] = array(
					'attr' => array( 'name' => $key ),
					'key'  => $this->reformat_array( $value )
				);
			}
		}

		return $ret;
	}
}