<?php

class WPML_Sync_Term_Meta_Action extends WPML_SP_User {

	/** @var  int $term_id */
	private $term_taxonomy_id;

	/**
	 * WPML_Sync_Term_Meta_Action constructor.
	 *
	 * @param SitePress $sitepress
	 * @param int       $term_taxonomy_id just saved term's term_taxonomy_id
	 */
	public function __construct( &$sitepress, $term_taxonomy_id ) {
		parent::__construct( $sitepress );
		$this->term_taxonomy_id = $term_taxonomy_id;
	}

	/**
	 * Copies to be synchronized term meta data to the translations of the term.
	 */
	public function run() {
		$translations = $this->sitepress
			->term_translations()
			->get_element_translations( $this->term_taxonomy_id, false, true );
		if ( ! empty( $translations ) ) {
			foreach ( $translations as $ttid ) {
				$this->copy_custom_fields( $ttid );
			}
		}
	}

	/**
	 * @param int $target_ttid
	 */
	private function copy_custom_fields( $target_ttid ) {
		$cf_copy = array();

		$setting_factory = $this->sitepress->core_tm()->settings_factory();
		$meta_keys       = $setting_factory->get_term_meta_keys();

		foreach ( $meta_keys as $meta_key ) {
			if ( $setting_factory->term_meta_setting( $meta_key )->status() === WPML_COPY_CUSTOM_FIELD ) {
				$cf_copy[] = $meta_key;
			}
		}

		$term_id_to   = $this->sitepress->term_translations()->adjust_ttid_for_term_id( $target_ttid );
		$term_id_from = $this->sitepress->term_translations()->adjust_ttid_for_term_id( $this->term_taxonomy_id );
		foreach ( $cf_copy as $meta_key ) {
			$meta_from = $this->sitepress->get_wp_api()->get_term_meta( $term_id_from,
				$meta_key );
			$meta_to   = $this->sitepress->get_wp_api()->get_term_meta( $term_id_to,
				$meta_key );
			if ( $meta_from || $meta_to ) {
				$this->sync_custom_field( $term_id_from, $term_id_to,
					$meta_key );
			}
		}
	}

	private function sync_custom_field(
		$term_id_from,
		$term_id_to,
		$meta_key
	) {
		$wpdb        = $this->sitepress->wpdb();
		$sql         = "SELECT meta_value FROM {$wpdb->termmeta} WHERE term_id=%d AND meta_key=%s";
		$values_from = $wpdb->get_col( $wpdb->prepare( $sql,
			array( $term_id_from, $meta_key ) ) );
		$values_to   = $wpdb->get_col( $wpdb->prepare( $sql,
			array( $term_id_to, $meta_key ) ) );

		$removed = array_diff( $values_to, $values_from );
		foreach ( $removed as $v ) {
			$delete_prepared = $wpdb->prepare( "DELETE FROM {$wpdb->termmeta}
												WHERE term_id=%d
												AND meta_key=%s
												AND meta_value=%s",
				array( $term_id_to, $meta_key, $v ) );
			$wpdb->query( $delete_prepared );
		}

		$added = array_diff( $values_from, $values_to );
		foreach ( $added as $v ) {
			$insert_prepared = $wpdb->prepare( "INSERT INTO {$wpdb->termmeta}(term_id, meta_key, meta_value)
												VALUES(%d, %s, %s)",
				array( $term_id_to, $meta_key, $v ) );
			$wpdb->query( $insert_prepared );
		}
		wp_cache_init();
	}
}