<?php

class WPML_LS_Templates {

	const CONFIG_FILE    = 'config.json';
	const TRANSIENT_NAME = 'wpml_language_switcher_template_objects';

	/** @var  @var string $uploads_path */
	private $uploads_path;

	/**
	 * @var WPML_File
	 */
	private $wpml_file;

	/* @var array $templates Collection of WPML_LS_Template */
	private $templates = false;

	/* @var string $ds */
	private $ds = DIRECTORY_SEPARATOR;

	public function __construct( WPML_File $wpml_file = null ) {
		if ( ! $wpml_file ) {
			$wpml_file = new WPML_File();
		}

		$this->wpml_file = $wpml_file;
	}

	public function init_hooks() {
		add_action( 'after_setup_theme',  array( $this, 'after_setup_theme_action' ) );
		add_action( 'activated_plugin',   array( $this, 'activated_plugin_action' ) );
		add_action( 'deactivated_plugin', array( $this, 'activated_plugin_action' ) );
		add_action( 'switch_theme',       array( $this, 'activated_plugin_action' ) );
	}

	/**
	 * @return array
	 */
	public function after_setup_theme_action() {
		return $this->init_available_templates();
	}

	public function activated_plugin_action() {
		delete_site_transient( self::TRANSIENT_NAME );
	}

	/**
	 * @param mixed|bool|array $in_array
	 *
	 * @return mixed
	 */
	public function get_templates( $in_array = false ) {
		if ( ! $in_array ) {
			$ret = $this->templates;
		} else {
			$in_array = array_combine( $in_array, $in_array );
			$ret = array_intersect_key( $this->templates, $in_array );
		}
		return $ret;
	}

	/**
	 * @param string $template_slug
	 *
	 * @return WPML_LS_Template
	 */
	public function get_template( $template_slug ) {
		$ret = new WPML_LS_Template( array() );
		if ( array_key_exists( $template_slug, $this->templates ) ) {
			$ret = $this->templates[ $template_slug ];
		}

		return $ret;
	}

	public function get_all_templates_data() {
		$ret = array();

		foreach ( $this->get_templates() as $slug => $template ) {
			/* @var WPML_LS_Template $template */
			$ret[ $slug ] = $template->get_template_data();
		}

		return $ret;
	}

	/**
	 * @return array
	 */
	private function init_available_templates() {
		$is_admin_ui_page = isset( $_GET['page'] ) && WPML_LS_Admin_UI::get_page_hook() === $_GET['page'];

		if ( ! $is_admin_ui_page ) {
			$this->templates = $this->get_templates_from_transient();
		}

		if ( $this->templates === false ) {

			$this->templates = array();
			$dirs_to_scan = array();

			/**
			 * Filter the directories to scan
			 *
			 * @param array $dirs_to_scan
			 */
			$dirs_to_scan = apply_filters( 'wpml_ls_directories_to_scan', $dirs_to_scan );

			$sub_dir          = $this->ds . 'templates' . $this->ds . 'language-switchers';
			$wpml_core_path   = ICL_PLUGIN_PATH . $sub_dir;
			$theme_path       = get_template_directory() . $this->ds . 'wpml' . $sub_dir;
			$child_theme_path = get_stylesheet_directory() . $this->ds . 'wpml' . $sub_dir;
			$uploads_path     = $this->get_uploads_path() . $this->ds . 'wpml' . $sub_dir;

			array_unshift( $dirs_to_scan, $wpml_core_path, $theme_path, $child_theme_path, $uploads_path );

			$templates_paths = $this->scan_template_paths( $dirs_to_scan );

			foreach ( $templates_paths as $template_path ) {
				$template_path = $this->wpml_file->fix_dir_separator( $template_path );
				if ( file_exists( $template_path . $this->ds . WPML_LS_Template::FILENAME ) ) {
					$tpl    = array();
					$config = $this->parse_template_config( $template_path );

					$tpl['path']     = $template_path;
					$tpl['version']  = isset( $config['version'] ) ? $config['version'] : '1';
					$tpl['name']     = isset( $config['name'] ) ? $config['name'] : null;
					$tpl['name']     = $this->get_unique_name( $tpl['name'], $template_path );
					$tpl['slug']     = sanitize_title_with_dashes( $tpl['name'] );
					$tpl['base_uri'] = trailingslashit( $this->wpml_file->get_uri_from_path( $template_path ) );
					$tpl['css']      = $this->get_files( 'css', $template_path, $config );
					$tpl['js']       = $this->get_files( 'js', $template_path, $config );

					$tpl['flags_base_uri'] = isset( $config['flags_dir'] ) // todo: check with ../
						? $this->wpml_file->get_uri_from_path( $template_path . $this->ds . $config['flags_dir'] ) : null;
					$tpl['flags_base_uri'] = ! isset( $tpl['flags_base_uri'] ) && file_exists( $template_path . $this->ds . 'flags' )
						? $this->wpml_file->get_uri_from_path( $template_path . $this->ds . 'flags' ) : $tpl['flags_base_uri'];

					$tpl['flag_extension'] = isset( $config['flag_extension'] )
						?  $config['flag_extension'] : null;

					if ( $this->is_core_template( $template_path ) ) {
						$tpl['is_core'] = true;
						$tpl['slug']    = isset( $config['slug'] ) ? $config['slug'] : $tpl['slug'];
					}

					$tpl['for'] = isset( $config['for'] )
						? $config['for'] : array( 'menus', 'sidebars', 'footer', 'post_translations', 'shortcode_actions' );
					$tpl['force_settings'] = isset( $config['settings'] )
						? $config['settings'] : array();

					$this->templates[ $tpl['slug'] ] = new WPML_LS_Template( $tpl );
				}
			}
			set_site_transient( self::TRANSIENT_NAME, $this->templates, 30 * DAY_IN_SECONDS );
		}

		return $this->templates;
	}

	/**
	 * @param array $dirs_to_scan
	 *
	 * @return array
	 */
	private function scan_template_paths( $dirs_to_scan ) {
		$templates_paths = array();

		foreach ( $dirs_to_scan as $dir ) {
			if ( !is_dir( $dir ) ) {
				continue;
			}
			$files = scandir( $dir );
			$files = array_diff( $files, array( '..', '.' ) );
			if ( count( $files ) > 0 ) {
				foreach ( $files as $file ) {
					$template_path = $dir . '/' . $file;
					if ( is_dir( $template_path )
					     && file_exists( $template_path . $this->ds . WPML_LS_Template::FILENAME )
						 && file_exists( $template_path . $this->ds . self::CONFIG_FILE )
					) {
						$templates_paths[] = $template_path;
					}
				}
			}
		}

		return $templates_paths;
	}

	/**
	 * @param string $template_path
	 *
	 * @return array
	 */
	private function parse_template_config( $template_path ) {
		$config = array();
		$configuration_file = $template_path . $this->ds . self::CONFIG_FILE;
		if ( file_exists( $configuration_file ) ) {
			$json_content = file_get_contents( $configuration_file );
			$config       = json_decode( $json_content, true );
		}

		return $config;
	}

	/**
	 * @param string $ext
	 * @param string $template_path
	 * @param array $config
	 *
	 * @return array|null
	 */
	private function get_files( $ext, $template_path, $config ) {
		$resources = array();

		if( isset( $config[ $ext ] ) ) {
			$config[ $ext ] = is_array( $config[ $ext ] ) ? $config[ $ext ] : array( $config[ $ext ] );
			foreach ( $config[ $ext ] as $file ) {
				$file = untrailingslashit( $template_path ) .$this->ds . $file;
				$resources[] = $this->wpml_file->get_uri_from_path( $file );
			}
		} else {
			$search_path = $template_path . $this->ds . '*.' . $ext;
			if ( glob( $search_path ) ) {
				foreach ( glob( $search_path ) as $file ) {
					$resources[] = $this->wpml_file->get_uri_from_path( $file );
				}
			}
		}

		return $resources;
	}

	/**
	 * @param mixed|string|null $name
	 * @param string $path
	 *
	 * @return string
	 */
	private function get_unique_name( $name, $path ) {
		if ( is_null( $name ) ) {
			$name = basename( $path );
		}

		if ( strpos( $path, $this->wpml_file->fix_dir_separator( get_template_directory() ) ) === 0 ) {
			$theme = wp_get_theme();
			$name  = $theme . ' - ' . $name;
		} elseif ( strpos( $path, $this->wpml_file->fix_dir_separator( $this->get_uploads_path() ) ) === 0 ) {
			$name = __( 'Uploads', 'sitepress' ) . ' - ' . $name;
		} elseif (
			strpos( $path, $this->wpml_file->fix_dir_separator( WP_PLUGIN_DIR ) ) === 0
			&& ! $this->is_core_template( $path )
		) {
			$plugin_dir = $this->wpml_file->fix_dir_separator( WP_PLUGIN_DIR );
			$plugin_dir = preg_replace( '#' . preg_quote( $plugin_dir ) . '#' , '', $path, 1 );
			$plugin_dir = ltrim( $plugin_dir, $this->ds );
			$plugin_dir = explode( $this->ds, $plugin_dir );

			if ( isset( $plugin_dir[0] ) ) {
				$require = ABSPATH . 'wp-admin' . $this->ds . 'includes' . $this->ds . 'plugin.php';
				require_once( $require );
				foreach ( get_plugins() as $slug => $plugin ) {
					if ( strpos( $slug, $plugin_dir[0] ) === 0 ) {
						$name = $plugin['Name'] . ' - ' . $name;
						break;
					}
				}
			} else {
				$name = substr( md5( $path ), 0, 8 ) . ' - ' . $name;
			}
		}

		return $name;
	}

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	private function is_core_template( $path ) {
		return strpos( $path, ICL_PLUGIN_PATH ) === 0;
	}

	private function get_templates_from_transient() {
		$templates = get_site_transient( self::TRANSIENT_NAME );
		if ( $templates && ! $this->are_template_paths_valid( $templates ) ) {
			$templates = false;
		}
		return $templates;
	}

	/**
	 * @param WPML_LS_Template[] $templates
	 *
	 * @return bool
	 */
	private function are_template_paths_valid( $templates ) {
		$paths_are_valid = true;
		foreach ( $templates as $template ) {
			if ( ! $template->is_path_valid() ) {
				$paths_are_valid = false;
				break;
			}
		}
		return $paths_are_valid;
	}

	/**
	 * @return null|string
	 */
	private function get_uploads_path() {
		if ( ! $this->uploads_path ) {
			$uploads = wp_get_upload_dir();

			if ( isset( $uploads['basedir'] ) ) {
				$this->uploads_path = $uploads['basedir'];
			}
		}

		return $this->uploads_path;
	}
}
