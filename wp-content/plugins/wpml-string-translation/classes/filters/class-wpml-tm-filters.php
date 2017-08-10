<?php

class WPML_TM_Filters extends WPML_WPDB_And_SP_User {

	/**
	 * Filters the active languages to include all languages in which strings exist.
	 *
	 * @param array[] $source_langs
	 *
	 * @return array[]
	 */
	public function filter_tm_source_langs( $source_langs ) {

		$string_lang_codes = $this->wpdb->get_col( "SELECT DISTINCT(s.language)
													FROM {$this->wpdb->prefix}icl_strings s" );

		foreach ( $string_lang_codes as $lang_code ) {
			$language = $this->sitepress->get_language_details( $lang_code );
			if ( (bool) $language === true ) {
				$source_langs[ $lang_code ] = $language;
			}
		}

		return $source_langs;
	}

	/**
	 * This filters the check whether or not a job is assigned to a specific translator for local string jobs.
	 * It is to be used after assigning a job, as it will update the assignment for local string jobs itself.
	 *
	 * @param bool       $assigned_correctly
	 * @param string|int $string_translation_id
	 * @param int        $translator_id
	 * @param string|int $service
	 *
	 * @return bool
	 */
	public function job_assigned_to_filter( $assigned_correctly, $string_translation_id, $translator_id, $service ) {
		if ( ( ! $service || $service === 'local' ) && strpos( $string_translation_id, 'string|' ) !== false ) {
			$string_translation_id = preg_replace( '/[^0-9]/', '', $string_translation_id );
			$this->wpdb->update(
				$this->wpdb->prefix . 'icl_string_translations',
				array( 'translator_id' => $translator_id ),
				array( 'id' => $string_translation_id )
			);
			$assigned_correctly = true;
		}

		return $assigned_correctly;
	}
}