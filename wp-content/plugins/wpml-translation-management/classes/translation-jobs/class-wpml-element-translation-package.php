<?php

/**
 * Class WPML_Element_Translation_Package
 *
 * @package wpml-core
 */
class WPML_Element_Translation_Package extends WPML_Translation_Job_Helper{

	const CUSTOM_FIELD_KEY_SEPARATOR = ':::';

	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function __construct( WPML_WP_API $wp_api = null ) {
		global $sitepress;
		if ( $wp_api ) {
			$this->wp_api = $wp_api;
		} else {
			$this->wp_api = $sitepress->get_wp_api();
		}
	}
	/**
	 * create translation package
	 *
	 * @param object|int $post
	 *
	 * @return array
	 */
	public function create_translation_package( $post ) {
		global $sitepress;

		$package   = array();
		$post      = is_numeric( $post ) ? get_post( $post ) : $post;
		$post_type = $post->post_type;
		if ( apply_filters( 'wpml_is_external', false, $post ) ) {
			/** @var stdClass $post */
			$post_contents = (array) $post->string_data;
			$original_id   = isset( $post->post_id ) ? $post->post_id : $post->ID;
			$type          = 'external';
		} else {
			$home_url       = get_home_url();
			$package['url'] = htmlentities( $home_url . '?' . ( $post_type === 'page' ? 'page_id' : 'p' ) . '=' . ( $post->ID ) );

			$post_contents = array(
				'title'   => $post->post_title,
				'body'    => $post->post_content,
				'excerpt' => $post->post_excerpt
			);

			if ( wpml_get_setting_filter( false, 'translated_document_page_url' ) === 'translate' ) {
				$post_contents['URL'] = $post->post_name;
			}

			$original_id             = $post->ID;
			$cf_translation_settings = $this->get_tm_setting( array( 'custom_fields_translation' ) );
			if ( ! empty( $cf_translation_settings ) ) {
				$package = $this->add_custom_field_contents( $package,
				                                             $post,
				                                             $cf_translation_settings );
			}

			foreach ( (array) $sitepress->get_translatable_taxonomies( true, $post_type ) as $taxonomy ) {
				$terms = get_the_terms( $post->ID, $taxonomy );
				if ( is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						$post_contents[ 't_' . $term->term_taxonomy_id ] = $term->name;
					}
				}
			}
			$type = 'post';
		}
		$package['contents']['original_id'] = array( 'translate' => 0, 'data' => $original_id );
		$package['type']                    = $type;
		foreach ( $post_contents as $key => $entry ) {
			$package['contents'][ $key ] = array(
				'translate' => 1,
				'data'      => base64_encode( $entry ),
				'format'    => 'base64'
			);
		}

		return apply_filters( 'wpml_tm_translation_job_data', $package, $post );
	}

	/**
	 * @param array $translation_package
	 * @param array $prev_translation
	 * @param int $job_id
	 */
	public function save_package_to_job( array $translation_package, $job_id, $prev_translation ) {
		global $wpdb;

		$show = $wpdb->hide_errors();

		foreach ( $translation_package['contents'] as $field => $value ) {
			$job_translate = array(
				'job_id'                => $job_id,
				'content_id'            => 0,
				'field_type'            => $field,
				'field_format'          => isset( $value['format'] ) ? $value['format'] : '',
				'field_translate'       => $value['translate'],
				'field_data'            => $value['data'],
				'field_data_translated' => '',
				'field_finished'        => 0,
			);

			if ( array_key_exists( $field, $prev_translation ) ) {
				$job_translate['field_data_translated'] = $prev_translation[ $field ]->get_translation();
				$job_translate['field_finished']        = $prev_translation[ $field ]->is_finished( $value['data'] );
			}

			$wpdb->insert( $wpdb->prefix . 'icl_translate', $job_translate );
		}

		$wpdb->show_errors( $show );
	}

	/**
	 * @param object $job
	 * @param int    $post_id
	 * @param array  $fields
	 */
	function save_job_custom_fields( $job, $post_id, $fields ) {
		$field_names = array();
		foreach ( $fields as $field_name => $val ) {
			if ( '' === (string) $field_name ) {
				continue;
			}

			// find it in the translation
			foreach ( $job->elements as $el_data ) {
				if ( strpos( $el_data->field_data, (string) $field_name ) === 0
				     && 1 === preg_match( '/field-(.*?)-name/', $el_data->field_type, $match )
				     && 1 === preg_match( '/field-' . $field_name . '-.*?-name/', $el_data->field_type )
				) {
					$field_names[ $field_name ] = isset( $field_names[ $field_name ] )
							? $field_names[ $field_name ] : array();
					$field_id_string            = $match[1];
					$field_translation          = false;
					foreach ( $job->elements as $v ) {
						if ( $v->field_type === 'field-' . $field_id_string ) {
							$field_translation = $this->decode_field_data(
								$v->field_data_translated,
								$v->field_format
							);
						}
						if ( $v->field_type === 'field-' . $field_id_string . '-type' ) {
							$field_type = $v->field_data;
							break;
						}
					}
					if ( false !== $field_translation && isset( $field_type ) && 'custom_field' === $field_type ) {
						$field_translation = str_replace( '&#0A;', "\n", $field_translation );
						// always decode html entities  eg decode &amp; to &
						$field_translation = html_entity_decode( $field_translation );
						$meta_keys = explode( '-', preg_replace( '#' . $field_name . '-?#', '', $field_id_string ) );
						$meta_keys = array_map( array( $this, 'replace_separator' ), $meta_keys );
						$field_names       = $this->insert_under_keys(
							array_merge( array( $field_name ), $meta_keys ), $field_names, $field_translation
						);
					}
				}
			}
		}

		$this->save_custom_field_values( $field_names, $post_id );
	}

	private function replace_separator( $el ) {
		return str_replace( self::CUSTOM_FIELD_KEY_SEPARATOR, '-', $el );
	}

	/**
	 * Inserts an element into an array, nested by keys.
	 * Input ['a', 'b'] for the keys, an empty array for $array and $x for the value would lead to
	 * [ 'a' => ['b' => $x ] ] being returned.
	 *
	 * @param array $keys indexes ordered from highest to lowest level
	 * @param array $array array into which the value is to be inserted
	 * @param mixed $value to be inserted
	 *
	 * @return array
	 */
	private function insert_under_keys( $keys, $array, $value ) {
		$array[ $keys[0] ] = count( $keys ) === 1
			? $value
			: $this->insert_under_keys(
				array_slice($keys, 1),
				( isset( $array[ $keys[0] ] ) ? $array[ $keys[0] ] : array() ),
				$value );

		return $array;
	}

	private function save_custom_field_values( $fields_in_job, $post_id ) {
		foreach ( $fields_in_job as $name => $contents ) {
			$this->wp_api->delete_post_meta( $post_id, $name );
			$single   = count( $contents ) === 1;
			$contents = (array) $contents;
			foreach ( $contents as $val ) {
				$this->wp_api->add_post_meta( $post_id, $name, $val, $single );
			}
		}
	}

	/**
	 * @param array $package
	 * @param object $post
	 * @param array $fields
	 *
	 * @return array
	 */
	private function add_custom_field_contents( $package, $post, $fields ) {
		$fields_to_translate = array_keys( $fields, 2 );
		foreach ( $fields_to_translate as $key ) {
			$custom_fields_values = array_values( array_filter( get_post_meta( $post->ID, $key ) ) );
			foreach ( $custom_fields_values as $index => $custom_field_val ) {
				$package          = $this->add_single_field_content( $package, $key, $index, $custom_field_val );
			}
		}

		return $package;
	}

	/**
	 * Uses the wpml_translation_job_post_meta_value_translated to exclude certain fields from being translated.
	 * For array valued custom fields cf is given in the form field-{$field_name}-join('-', $indicies)
	 *
	 * @param array $package
	 * @param string $key
	 * @param string $custom_field_index
	 * @param array|stdClass|string $custom_field_val
	 *
	 * @return array
	 */
	private function add_single_field_content( $package, $key, $custom_field_index, $custom_field_val ) {
		if ( is_scalar( $custom_field_val ) ) {
			$key_index = $key . '-' . $custom_field_index;
			$cf        = 'field-' . $key_index;
			$package['contents'][ $cf ] = array(
				'translate' => apply_filters( 'wpml_translation_job_post_meta_value_translated', 1, $cf ),
				'data'      => base64_encode( $custom_field_val ),
				'format'    => 'base64',
			);
			foreach ( array( 'name' => $key_index, 'type' => 'custom_field' ) as $field_key => $setting ) {
				$package['contents'][ $cf . '-' . $field_key ] = array(
					'translate' => 0,
					'data'      => $setting,
				);
			}
		} else {
			foreach ( (array) $custom_field_val as $ind => $value ) {
				$package = $this->add_single_field_content( $package, $key, $custom_field_index . '-' . str_replace( '-', self::CUSTOM_FIELD_KEY_SEPARATOR, $ind ), $value );
			}
		}

		return $package;
	}
}
