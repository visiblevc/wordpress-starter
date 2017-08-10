<?php

abstract class WPML_TM_Update_Translation_Data_Action extends WPML_Translation_Job_Helper_With_API {

	function get_prev_job_data( $rid ) {
		global $wpdb;

		// if we have a previous job_id for this rid mark it as the top (last) revision
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT job_id, translated
							 FROM {$wpdb->prefix}icl_translate_job
							 WHERE rid=%d
							 	AND revision IS NULL
						     LIMIT 1",
				$rid ),
			ARRAY_N );
	}

	/**
	 * Adds a translation job record in icl_translate_job
	 *
	 * @param mixed $rid
	 * @param mixed $translator_id
	 * @param       $translation_package
	 *
	 * @return bool|int
	 */
	function add_translation_job( $rid, $translator_id, array $translation_package ) {
		global $wpdb, $current_user;

		$translation_status = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}icl_translation_status WHERE rid=%d", $rid ) );
		$prev_translation   = $this->get_translated_field_values( $rid, $translation_package );
		if ( ! $current_user->ID ) {
			$manager_id = $wpdb->get_var( $wpdb->prepare( "SELECT manager_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d ORDER BY job_id DESC LIMIT 1",
			                                              $rid ) );
		} else {
			$manager_id = $current_user->ID;
		}

		$translate_job_insert_data = array(
			'rid' => $rid,
			'translator_id' => $translator_id,
			'translated' => 0,
			'manager_id' => (int)$manager_id
		);
		$wpdb->insert( $wpdb->prefix . 'icl_translate_job', $translate_job_insert_data );
		$job_id = $wpdb->insert_id;

		$this->package_helper->save_package_to_job( $translation_package, $job_id, $prev_translation );
		$this->fire_notification_actions( $job_id, $translation_status, $translator_id );

		return $job_id;
	}

	/**
	 * @param int   $prev_id
	 * @param array $package
	 *
	 * @return mixed
	 */
	abstract protected function populate_prev_translation( $prev_id, array $package );

	/**
	 * @param int   $rid
	 * @param array $package
	 *
	 * @return mixed
	 */
	protected function get_translated_field_values( $rid, array $package ) {
		global $wpdb;

		$prev_translations = $this->populate_prev_translation( $rid, $package );

		if ( ! $prev_translations ) {
			return array();
		}

		// if we have a previous job_id for this rid mark it as the top (last) revision
		list( $prev_job_id, $prev_job_translated ) = $this->get_prev_job_data( $rid );

		if ( ! is_null( $prev_job_id ) ) {
			$last_rev_prepare = $wpdb->prepare( "
				SELECT MAX(revision)
				FROM {$wpdb->prefix}icl_translate_job
				WHERE rid=%d
					AND ( revision IS NOT NULL OR translated = 1 )
			", $rid );
			$last_rev         = $wpdb->get_var( $last_rev_prepare );
			$wpdb->update( $wpdb->prefix . 'icl_translate_job', array( 'revision' => $last_rev + 1 ), array( 'job_id' => $prev_job_id ) );
		}

		return $prev_translations;
	}

	protected function fire_notification_actions( $job_id, $translation_status, $translator_id ) {
		global $wpml_translation_job_factory;

		$job = $wpml_translation_job_factory->get_translation_job( $job_id, false, 0, true );
		if ( $translation_status->translation_service === 'local' ) {
			if ( $this->get_tm_setting( array( 'notification', 'new-job' ) ) == ICL_TM_NOTIFICATION_IMMEDIATELY
			) {
				if ( $job_id ) {
					if ( empty( $translator_id ) ) {
						do_action( 'wpml_tm_new_job_notification', $job );
					} else {
						do_action( 'wpml_tm_assign_job_notification', $job, $translator_id );
					}
				}
			}
			do_action( 'wpml_added_local_translation_job', $job_id );
		}
	}
}