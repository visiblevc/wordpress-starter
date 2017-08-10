<?php

class WPML_MO_File_Search {
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var array
	 */
	private $settings;

	/**
	 * @var WP_Filesystem_Direct
	 */
	private $filesystem;

	/**
	 * @var array
	 */
	private $locales;

	/**
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress, WP_Filesystem_Direct $filesystem = null ) {
		$this->sitepress = $sitepress;

		if ( ! $filesystem ) {
			$filesystem = $sitepress->get_wp_api()->get_wp_filesystem_direct();
		}
		$this->filesystem = $filesystem;

		$this->settings = $this->sitepress->get_settings();
		$this->locales = $this->sitepress->get_locale_file_names();
	}

	/**
	 * @param array $active_languages
	 *
	 * @return bool
	 */
	public function has_mo_file_for_any_language( $active_languages ) {
		foreach ( $active_languages as $lang ) {
			if ( $this->can_find_mo_file( $lang['code'] ) ) {
				return true;
			}
		}

		return false;
	}

	public function reload_theme_dirs() {
		$type = isset( $this->settings['theme_localization_type'] ) ? $this->settings['theme_localization_type'] : WPML_Theme_Localization_Type::USE_MO_FILES;
		if ( WPML_Theme_Localization_Type::USE_MO_FILES === $type ) {
			$dirs = $this->find_theme_mo_dirs();
			$this->save_mo_dirs( $dirs );
			$this->settings['theme_language_folders'] = $dirs;
		}
	}

	/**
	 * @param string $lang_code
	 *
	 * @return bool
	 */
	public function can_find_mo_file( $lang_code ) {
		if ( ! isset( $this->locales[ $lang_code ] ) ) {
			return false;
		}

		$file_names = $this->locales[ $lang_code ];

		if ( isset( $this->settings['theme_language_folders']['parent'] ) ) {
			$files[] = $this->settings['theme_language_folders']['parent'] . '/' . $file_names . '.mo';
		}
		if ( isset( $this->settings['theme_language_folders']['child'] ) ) {
			$files[] = $this->settings['theme_language_folders']['child'] . '/' . $file_names . '.mo';
		}

		$files[] = $this->get_template_path() . '/' . $file_names . '.mo';

		foreach ( $files as $file ) {
			if ( $this->filesystem->is_readable( $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string
	 */
	protected function get_template_path() {
		return TEMPLATEPATH;
	}

	/**
	 * @return array
	 */
	public function find_theme_mo_dirs() {
		$parent_theme = get_template_directory();
		$child_theme = get_stylesheet_directory();
		$languages_folders = null;

		if ( $found_folder = $this->determine_mo_folder( $parent_theme ) ) {
			$languages_folders['parent'] = $found_folder;
		}
		if ( $parent_theme != $child_theme && $found_folder = $this->determine_mo_folder( $child_theme ) ) {
			$languages_folders['child'] = $found_folder;
		}

		return $languages_folders;
	}

	/**
	 * @param string $folder
	 * @param int $rec
	 *
	 * @return bool
	 */
	public function determine_mo_folder( $folder, $rec = 0 ) {
		$lfn = $this->sitepress->get_locale_file_names();
		$files = $this->filesystem->dirlist( $folder, false, false );

		foreach ( $files as $file => $data ) {
			if ( 0 === strpos( $file, '.' ) ) {
				continue;
			}
			if ( $this->filesystem->is_file( $folder . '/' . $file ) && preg_match( '#\.mo$#i', $file )
			     && in_array( preg_replace( '#\.mo$#i', '', $file ), $lfn )
			) {
				return $folder;
			} elseif ( $this->filesystem->is_dir( $folder . '/' . $file ) && $rec < 5 ) {
				if ( $f = $this->determine_mo_folder( $folder . '/' . $file, $rec + 1 ) ) {
					return $f;
				};
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function get_dir_names() {
		$dirs = array();

		if ( isset( $this->settings['theme_language_folders']['parent'] ) ) {
			$dirs[] = $this->settings['theme_language_folders']['parent'];
		}

		if ( isset( $this->settings['theme_language_folders']['child'] ) ) {
			$dirs[] = $this->settings['theme_language_folders']['child'];
		}

		if ( empty( $dirs ) ) {
			$template = get_option( 'template' );
			$dirs[] = get_theme_root( $template ) . '/' . $template;
		}

		return $dirs;
	}

	/**
	 * @param array $dirs
	 */
	public function save_mo_dirs( $dirs ) {
		$sitepress_settings = $this->sitepress->get_settings();
		$sitepress_settings['theme_language_folders'] = $dirs;
		$this->sitepress->save_settings( $sitepress_settings );
	}
}