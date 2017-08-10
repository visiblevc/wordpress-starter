<?php

class WPML_Package_TM_Jobs {
	/**
	 * @var WPML_Package
	 */
	protected $package;

	protected function __construct( $package ) {
		$this->package = $package;
	}

	final public function validate_translations( $create_if_missing = true ) {
		$trid = $this->get_trid( $create_if_missing );

		return $trid != false;
	}

	final protected function get_trid($create_if_missing = true) {
		global $sitepress;
		$package   = $this->package;
		$post_trid = $sitepress->get_element_trid( $package->ID, $package->get_translation_element_type() );
		if ( ! $post_trid && $create_if_missing ) {
			$this->set_language_details();
			$post_trid = $sitepress->get_element_trid( $package->ID, $package->get_translation_element_type() );
		}

		return $post_trid;
	}

	final public function set_language_details( $language_code = null ) {
		global $sitepress;
		$package = $this->package;
		$post_id = $package->ID;
		$post    = $this->get_translatable_item( $post_id );
		$post_id = $post->ID;
		$element_type = $package->get_translation_element_type();
		if ( ! $language_code ) {
			$language_code = icl_get_default_language();
		}
		$sitepress->set_element_language_details( $post_id, $element_type, false, $language_code, null, false );
	}

	final public function get_translatable_item( $package ) {
		//for TranslationManagement::send_jobs
		if ( ! is_a( $package, 'WPML_Package' ) ) {
			$package = new WPML_Package( $package );
		}

		return $package;
	}

	final public function delete_translation_jobs() {
		global $wpdb;

		$deleted = false;

		$post_translations = $this->get_post_translations();
		foreach ( $post_translations as $lang => $translation ) {
			$rid_query   = "SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d";
			$rid_prepare = $wpdb->prepare( $rid_query, array( $translation->translation_id ) );
			$rid         = $wpdb->get_var( $rid_prepare );
			if ( $rid ) {
				$job_id_query   = "SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d";
				$job_id_prepare = $wpdb->prepare( $job_id_query, array( $rid ) );
				$job_id         = $wpdb->get_var( $job_id_prepare );

				if ( $job_id ) {
					$wpdb->delete($wpdb->prefix . 'icl_translate_job', array('job_id' => $job_id) );
					$wpdb->delete($wpdb->prefix . 'icl_translate', array('job_id' => $job_id) );
					$deleted = true;
				}
			}
			
			$wpdb->delete($wpdb->prefix . 'icl_translations', array('translation_id' => $translation->translation_id));
		}

		return $deleted;
	}

	final public function delete_translations() {
		global $sitepress;
		$package = $this->package;

		$translation_element_type = $package->get_translation_element_type();
		$trid                     = $this->get_trid();

		$deleted = false;
		if($trid) {
			$deleted = $sitepress->delete_element_translation($trid, $translation_element_type);
		}
		return $deleted;
	}

	final public function get_post_translations() {
		global $sitepress;
		$package = $this->package;

		$translation_element_type = $package->get_translation_element_type();
		$trid                     = $this->get_trid();

		return $sitepress->get_element_translations( $trid, $translation_element_type );
	}

	final protected function update_translation_job_needs_update( $job_id ) {
		global $wpdb;
		$update_data  = array( 'translated' => 0 );
		$update_where = array( 'job_id' => $job_id );
		$wpdb->update( "{$wpdb->prefix}icl_translate_job", $update_data, $update_where );
	}

	final function update_translation_job( $rid, $post ) {

		$updated = $this->update_translation_job_fields( $rid, $post );
		$deleted = $this->delete_translation_job_fields( $rid, $post );

		return ( $updated || $deleted );
	}

	private function delete_translation_job_fields( $rid, $post ) {
		$deleted            = false;
		$job_id             = $this->get_translation_job_id( $rid );
		$translation_fields = $this->get_translations_job_fields( $job_id );
		foreach ( $translation_fields as $field_type => $el ) {
			//delete fields that are no longer present
			if ( $el->field_translate && ! isset( $post->string_data[ $field_type ] ) ) {
				$this->delete_translation_field( $el->tid );
				if ( ! $deleted ) {
					$deleted = true;
				}
			}
		}

		return $deleted;
	}

	/**
	 * @param int          $rid
	 * @param WPML_Package $post
	 *
	 * @return bool
	 */
	private function update_translation_job_fields( $rid, $post ) {
		$updated_any = false;

		$job_id             = $this->get_translation_job_id( $rid );
		$translation_fields = $this->get_translations_job_fields( $job_id );

		if ( isset($post->string_data) && is_array($post->string_data) ) {
			foreach ( $post->string_data as $field_type => $field_value ) {
				$updated = $this->insert_update_translation_job_field( $field_value, $translation_fields, $field_type, $job_id );
				if ( ! $updated_any && $updated ) {
					$updated_any = true;
				}
			}
		}

		return $updated_any;
	}

	protected function get_translation_job_id( $rid ) {
		global $wpdb;
		$job_id_query   = "SELECT MAX(job_id) FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d GROUP BY rid";
		$job_id_prepare = $wpdb->prepare( $job_id_query, $rid );
		$job_id         = $wpdb->get_var( $job_id_prepare );

		return $job_id;
	}

	private function get_translations_job_fields( $job_id ) {
		global $wpdb;
		$translation_fields_query = "SELECT field_type, field_data, tid, field_translate
							FROM {$wpdb->prefix}icl_translate
							WHERE job_id=%d";
		$translation_fields_query = $wpdb->prepare( $translation_fields_query, $job_id );
		$translation_fields       = $wpdb->get_results( $translation_fields_query, OBJECT_K );

		return $translation_fields;
	}

	private function delete_translation_field( $tid ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'icl_translate', array( 'tid' => $tid ), array( '%d' ) );
	}

	private function insert_update_translation_job_field( $field_value, $translation_fields, $field_type, $job_id ) {
		$updated    = false;
		$field_data = base64_encode( $field_value );
		if ( ! isset( $translation_fields[ $field_type ] ) ) {
			$this->insert_translation_field( $job_id, $field_type, $field_data );
			$updated = true;
		} elseif ( $translation_fields[ $field_type ]->field_data != $field_data ) {
			$this->update_translation_field( $field_data, $translation_fields, $field_type );
			$updated = true;
		}

		return $updated;
	}

	private function update_translation_field( $field_data, $translation_fields, $field_type ) {
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'icl_translate', array( 'field_data' => $field_data, 'field_finished' => 0 ), array( 'tid' => $translation_fields[ $field_type ]->tid ) );
	}

	private function insert_translation_field( $job_id, $field_type, $field_data ) {
		global $wpdb;
		$data = array(
			'job_id'                => $job_id,
			'content_id'            => 0,
			'field_type'            => $field_type,
			'field_format'          => 'base64',
			'field_translate'       => 1,
			'field_data'            => $field_data,
			'field_data_translated' => 0,
			'field_finished'        => 0
		);

		$wpdb->insert( $wpdb->prefix . 'icl_translate', $data );
	}
}