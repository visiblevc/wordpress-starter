<?php

class WPML_Cache_Directory {

	const DIR_PERMISSIONS      = 0775;
	const MAIN_DIRECTORY_NAME  = 'wpml';
	const NOTICE_GROUP         = 'wpml-cache-directory';
	const NOTICE_INVALID_CACHE = 'invalid-cache';
	private $cache_disabled = false;

	/**
	 * @var WPML_WP_API
	 */
	private $wp_api;

	/**
	 * @var WP_Filesystem_Direct
	 */
	private $filesystem;

	/**
	 * WPML_Cache_Directory constructor.
	 *
	 * @param WPML_WP_API $wp_api
	 */
	public function __construct( WPML_WP_API $wp_api ) {
		$this->wp_api     = $wp_api;
		$this->filesystem = $wp_api->get_wp_filesystem_direct();
	}

	/**
	 * @return string
	 */
	private function get_main_directory_path() {
		$main_directory_path = null;
		$cache_path_root     = $this->wp_api->constant( 'WPML_CACHE_PATH_ROOT' );

		if ( $cache_path_root ) {
			$main_directory_path = trailingslashit( $cache_path_root ) . self::MAIN_DIRECTORY_NAME;
			return trailingslashit( $main_directory_path );
		}else {
			$upload_dir = wp_upload_dir();

			if ( empty( $upload_dir['error'] ) ) {
				$base_dir            = $upload_dir['basedir'];
				$main_directory_path = trailingslashit( $base_dir ) . 'cache/' . self::MAIN_DIRECTORY_NAME;
				return trailingslashit( $main_directory_path );
			}
		}

		return null;
	}

	/**
	 * The function `wp_mkdir_p` will create directories recursively
	 *
	 * @param string $absolute_path
	 *
	 * @return string|bool absolute path or false if we can't have a writable and readable directory
	 */
	private function maybe_create_directory( $absolute_path ) {
		$result = true;

		if ( ! $this->filesystem->is_dir( $absolute_path ) ) {
			$result = wp_mkdir_p( $absolute_path );
		}

		return $result ? $absolute_path : false;
	}

	/**
	 * @param string $relative_path
	 *
	 * @return string|bool absolute path or false if we can't have a writable and readable directory
	 */
	public function get( $relative_path = '' ) {
		$absolute_path       = false;
		$main_directory_path = $this->maybe_create_directory( $this->get_main_directory_path() );

		if ( $main_directory_path ) {
			$absolute_path = trailingslashit( $main_directory_path . ltrim( $relative_path, '/\\' ) );
			$absolute_path = $this->maybe_create_directory( $absolute_path );
		}

		return $absolute_path;
	}

	/**
	 * @param string $relative_path
	 */
	public function remove( $relative_path = '' ) {
		$main_directory_path = $this->get_main_directory_path();
		if ( $main_directory_path ) {
			$absolute_path = trailingslashit( $main_directory_path . ltrim( $relative_path, '/\\' ) );
			$this->filesystem->delete( $absolute_path, true );
		}
	}
}
