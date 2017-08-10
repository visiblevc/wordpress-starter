<?php
if ( ! class_exists( 'WPML_Auto_Loader' ) ) {
	/**
	 * Class WPML_Auto_Loader
	 * This class is designed to be either included or instantiated (as singleton and as long as it's loaded somewhere else) to handle class auto loading.
	 * Call `WPML_Auto_Loader::get_instance()->register( dirname( __FILE__ ) . '/' );` from the main plugin file to register the auto loading for each plugin.
	 * Call `WPML_Auto_Loader::get_instance()->register( $class, $file );` to register "known" class files (in case they don't match the auto loading convention, but you want to load them automatically anyway.
	 * See PHPDoc for more details.
	 * @package wpml-auto-loader
	 */
	class WPML_Auto_Loader {
		private        $accepted_prefixes    = array();
		private        $base_dirs            = array();
		private        $classes_base_folder;
		private        $include_root;
		private static $instance;
		private        $known_classes        = array();
		private        $glob_cache           = array();


		/**
		 * @return WPML_Auto_Loader
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new WPML_Auto_Loader();
			}

			return self::$instance;
		}

		public function add_known_class( $class, $file ) {
			$this->known_classes[ $class ] = $file;
		}

		public function autoload( $class ) {
			$file = $this->get_file( $class );

			if ( $file ) {
				/** @noinspection PhpIncludeInspection */
				require_once $file;
			}
		}

		/**
		 * @param $class
		 *
		 * @return mixed|null|string
		 */
		private function get_file( $class ) {
			$file = null;
			if ( $this->is_accepted_class( $class ) ) {
				$file = $this->get_file_from_known_classes( $class );
				if ( null === $file ) {
					$file = $this->get_file_from_name( $class, 'class' );
				}
			}
			if ( $this->is_accepted_interface( $class ) ) {
				$file = $this->get_file_from_name( $class, 'interface' );
			}			

			return $file;
		}

		private function is_accepted_class( $class ) {
			foreach ( $this->accepted_prefixes as $accepted_prefix ) {
				if ( 0 === strpos( $class, $accepted_prefix ) ) {
					return true;
				}
			}

			return false;
		}
		
		private function is_accepted_interface( $class ) {
			foreach ( $this->accepted_prefixes as $accepted_prefix ) {
				if ( 0 === strpos( $class, 'I' . $accepted_prefix ) ) {
					return true;
				}
			}
	
			return false;
		}		

		/**
		 * @param string $class
		 *
		 * @return string|null
		 */
		private function get_file_from_known_classes( $class ) {
			$file = null;
			if ( array_key_exists( $class, $this->known_classes ) && is_file( $this->known_classes[ $class ] ) ) {
				$file = $this->known_classes[ $class ];
			}

			return $file;
		}

		/**
		 * @param string $class
		 * @param string $prefix
		 *
		 * @return null|string
		 */
		private function get_file_from_name( $class, $prefix ) {
			$file      = null;
			$file_name = $prefix . '-' . strtolower( str_replace( array( '_', "\0" ), array( '-', '' ), $class ) . '.php' );

			if ( $this->include_root ) {
				$base_dirs = $this->get_base_dirs();
				foreach ( $base_dirs as $base_dir ) {
					$current_dir        = $this->build_dir( $base_dir, null, false );
					$possible_full_path = $current_dir . $file_name;
					if ( is_file( $possible_full_path ) ) {
						$file = $possible_full_path;
					}
				}
			}

			if ( ! $file ) {
				$possible_file = $this->get_file_from_path( $file_name, null, false );
				if ( is_file( $possible_file ) ) {
					$file = $possible_file;
				}
			}

			return $file;
		}
		
		private function build_dir( $base_dir, $path = null, $with_base_folder = true ) {
			if ( $with_base_folder ) {
				$base_dir .= $this->classes_base_folder;
			}
			if ( $path ) {
				$base_dir .= $path;
			}

			return $base_dir;
		}

		private function get_file_from_path( $file_name, $path = null, $deep = false ) {
			$file       = null;
			if ( $path ) {
				$base_dirs = array( $path );
				$path = null;
			} else {
				$base_dirs  = $this->get_base_dirs();
			}
			$found_file = false;

			foreach ( $base_dirs as $base_dir ) {
				$current_dir = $base_dir;
				if ( ! $deep ) {
					$current_dir = $this->build_dir( $base_dir, $path, true );
				}
				$possible_full_path = $current_dir . '/' . $file_name;
				if ( is_file( $possible_full_path ) ) {
					$file = $possible_full_path;
				} else {
					$current_dir = $this->escape_path( $current_dir );
					if ( ! isset( $this->glob_cache[ $current_dir ] ) ) {
						$this->glob_cache[ $current_dir ] = glob( $current_dir . '/*', GLOB_ONLYDIR );
						$this->glob_cache[ $current_dir ] = false === $this->glob_cache[ $current_dir ] ? array() : $this->glob_cache[ $current_dir ];
					}
					foreach ( (array) $this->glob_cache[ $current_dir ] as $sub_folder_path ) {
						$found_file = $this->get_file_from_path( $file_name, $sub_folder_path, true );
						if ( null !== $found_file ) {
							$file = $found_file;
							break;
						}
					}
				}
				if ( $found_file ) {
					break;
				}
			}

			return $file;
		}

		public function register( $base_dir, array $accepted_prefixes = array( 'WPML' ), $classes_base_folder = 'classes', $include_root = false, $prepend = false ) {
			$this->add_base_dir( $base_dir );
			$this->accepted_prefixes   = $accepted_prefixes;
			$this->classes_base_folder = $classes_base_folder;
			$this->known_classes       = array();
			$this->include_root        = $include_root;

			if ( version_compare( phpversion(), '5.3.0', '>=' ) ) {
				spl_autoload_register( array( $this, 'autoload' ), true, $prepend );
			} else {
				spl_autoload_register( array( $this, 'autoload' ) );
			}
		}

		private function add_base_dir( $base_dir ) {
			if ( $this->is_base_dir_registered( $base_dir ) ) {
				$this->base_dirs[] = $base_dir;
			}
		}

		private function get_base_dirs() {
			return $this->base_dirs;
		}

		/**
		 * @param $base_dir
		 *
		 * @return bool
		 */
		private function is_base_dir_registered( $base_dir ) {
			return ! in_array( $base_dir, $this->base_dirs, true );
		}

		/**
		 * @param string $current_dir
		 *
		 * @return string
		 */
		private function escape_path( $current_dir ) {
			return preg_replace( '/([\[\]<>?|+;="])/', '\\\\$1', $current_dir );
		}
	}
}
