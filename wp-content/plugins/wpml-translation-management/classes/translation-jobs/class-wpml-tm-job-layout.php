<?php

class WPML_TM_Job_Layout extends WPML_WPDB_User {

	private $layout = array();
	private $custom_fields = array();
	private $grouped_custom_fields = array();
	private $terms = array();
	private $wp_api;

	public function __construct( &$wpdb, $wp_api ) {
		parent::__construct( $wpdb );
		$this->wp_api = $wp_api;
	}

	public function run( $fields, $tm_instance = null ) {

		foreach ( $fields as $field ) {
			$this->layout[] = $field['field_type'];
		}

		$this->order_main_fields();
		$this->extract_terms();
		$this->extract_custom_fields( $tm_instance );
		$this->append_terms();
		$this->append_grouped_custom_fields();
		$this->append_custom_fields();

		return apply_filters( 'wpml_tm_job_layout', array_values( $this->layout ) );
	}

	private function order_main_fields() {
		$ordered_elements = array();

		foreach ( array( 'title', 'body', 'excerpt' ) as $type ) {
			foreach ( $this->layout as $key => $element ) {
				if ( $element === $type ) {
					unset( $this->layout[ $key ] );
					$ordered_elements[] = $type;
				}
			}
		}
		$this->layout = array_merge( $ordered_elements, $this->layout );
	}

	private function extract_custom_fields( $tm_instance ) {

		foreach ( $this->layout as $key => $field ) {
			if ( $this->is_a_custom_field( $field ) ) {
				$group = $this->get_group_custom_field_belongs_to( $field, $tm_instance );
				if ( $group ) {
					if ( ! isset( $this->grouped_custom_fields[ $group ] ) ) {
						$this->grouped_custom_fields[ $group ] = array();
					}
					$this->grouped_custom_fields[ $group ][] = $field;
				} else {
					$this->custom_fields[] = $field;
				}
				unset( $this->layout[ $key ] );
			}
		}
	}

	private function get_group_custom_field_belongs_to( $field, $tm_instance ) {
		$group = '';
		if ( $tm_instance ) {
			$unfiltered_type = WPML_TM_Field_Type_Sanitizer::sanitize( $field );
			$settings        = new WPML_Custom_Field_Editor_Settings( $unfiltered_type, $tm_instance );
			$group           = $settings->get_group();
		}

		return $group;
	}

	private function extract_terms() {

		foreach ( $this->layout as $key => $field ) {
			if ( $this->is_a_term( $field ) ) {
				$this->terms[] = $field;
				unset( $this->layout[ $key ] );
			}
		}
	}

	private function append_grouped_custom_fields() {

		foreach ( $this->grouped_custom_fields as $group => $fields ) {
			$data           = array(
				'field_type'    => 'tm-section',
				'title'         => $group,
				'fields'        => $fields,
				'empty'         => false,
				'empty_message' => '',
				'sub_title'     => ''
			);
			$this->layout[] = $data;
		}
	}

	private function append_custom_fields() {

		if ( count( $this->custom_fields ) ) {
			$data           = array(
				'field_type'    => 'tm-section',
				'title'         => __( 'Custom Fields', 'wpml-translation-management' ),
				'fields'        => $this->custom_fields,
				'empty'         => false,
				'empty_message' => '',
				'sub_title'     => ''
			);
			$this->layout[] = $data;
		}
	}

	private function append_terms() {

		if ( count( $this->terms ) ) {
			$taxonomy_fields = array();

			foreach ( $this->terms as $term ) {
				$term_id  = substr( $term, 2 );
				$query    = $this->wpdb->prepare( "SELECT taxonomy FROM {$this->wpdb->term_taxonomy} WHERE term_taxonomy_id = %d", $term_id );
				$taxonomy = $this->wpdb->get_var( $query );
				if ( ! isset( $taxonomy_fields[ $taxonomy ] ) ) {
					$taxonomy_fields[ $taxonomy ] = array();
				}
				$taxonomy_fields[ $taxonomy ][] = $term;
			}

			foreach ( $taxonomy_fields as $taxonomy => $fields ) {
				$taxonomy       = $this->wp_api->get_taxonomy( $taxonomy );
				$data           = array(
					'field_type'    => 'tm-section',
					'title'         => $taxonomy->labels->name,
					'fields'        => $fields,
					'empty'         => false,
					'empty_message' => '',
					'sub_title'     => __( 'Changes in these translations will affect terms in general! (Not only for this post)', 'wpml-translation-management' )
				);
				$this->layout[] = $data;
			}
		}
	}

	private function is_a_custom_field( $field ) {
		return ( 0 === strpos( $field, 'field-' ) );
	}

	private function is_a_term( $field ) {
		return preg_match( '/^t_/', $field );
	}

}