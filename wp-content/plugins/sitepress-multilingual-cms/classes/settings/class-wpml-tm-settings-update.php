<?php

class WPML_TM_Settings_Update extends WPML_SP_User {

	private $index_singular;
	private $index_ro;
	private $index_sync;
	private $index_plural;
	/** @var  TranslationManagement $tm_instance */
	private $tm_instance;
	/** @var WPML_Settings_Helper $settings_helper */
	private $settings_helper;

	/**
	 * @param string                $index_singular
	 * @param string                $index_plural
	 * @param TranslationManagement $tm_instance
	 * @param SitePress             $sitepress
	 * @param WPML_Settings_Helper  $settings_helper
	 */
	public function __construct( $index_singular, $index_plural, &$tm_instance, &$sitepress, &$settings_helper ) {
		parent::__construct( $sitepress );
		$this->tm_instance     = &$tm_instance;
		$this->index_singular  = $index_singular;
		$this->index_plural    = $index_plural;
		$this->index_ro        = $index_plural . '_readonly_config';
		$this->index_sync      = $index_plural . '_sync_option';
		$this->settings_helper = $settings_helper;
	}

	/**
	 * @param array $config
	 */
	public function update_from_config( array $config ) {
		$config[ $this->index_plural ] = isset( $config[ $this->index_plural ] ) ? $config[ $this->index_plural ] : array();
		$this->update_tm_settings( $config[ $this->index_plural ] );
	}

	private function sync_settings( array $config ) {
		$section_singular = $this->index_singular;
		$section_plural   = $this->index_plural;

		if ( ! empty( $config[ $section_singular ] ) ) {
			$sync_option = $this->sitepress->get_setting( $this->index_sync, array() );
			if ( ! is_numeric( key( current( $config ) ) ) ) {
				$cf[0] = $config[ $section_singular ];
			} else {
				$cf = $config[ $section_singular ];
			}
			foreach ( $cf as $c ) {
				$val                                                    = $c['value'];
				$sync_existing_setting                                  = isset( $sync_option[ $val ] )
					? $sync_option[ $val ] : false;
				$translate                                              = intval( $c['attr']['translate'] );
				$this->tm_instance->settings[ $this->index_ro ][ $val ] = $translate;
				$sync_option[ $val ]                                    = $translate;
				if ( $translate && $translate != $sync_existing_setting ) {
					if ( $section_plural === 'taxonomies' ) {
						$this->sitepress->verify_taxonomy_translations( $val );
					} else {
						$this->sitepress->verify_post_translations( $val );
					}
					$this->tm_instance->save_settings();
				}
			}

			$this->sitepress->set_setting( $this->index_sync, $sync_option );
			$this->settings_helper->maybe_add_filter( $section_plural );
		}
	}

	private function update_tm_settings( array $config ) {
		$section_singular            = $this->index_singular;
		$config                      = array_filter( $config );
		$config[ $section_singular ] = isset( $config[ $section_singular ] ) ? $config[ $section_singular ] : array();
		$this->sync_settings( $config );

		// taxonomies - check what's been removed
		if ( ! empty( $this->tm_instance->settings[ $this->index_ro ] ) ) {
			$config_values = array();
			foreach ( $config[ $section_singular ] as $config_value ) {
				$config_values[ $config_value['value'] ] = $config_value['attr']['translate'];
			}
			foreach ( $this->tm_instance->settings[ $this->index_ro ] as $key => $translation_option ) {
				if ( ! isset( $config_values[ $key ] ) ) {
					unset( $this->tm_instance->settings[ $this->index_ro ][ $key ] );
				}
			}

			$this->tm_instance->save_settings();
		}
	}
}