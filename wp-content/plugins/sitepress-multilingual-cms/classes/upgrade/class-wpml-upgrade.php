<?php

class WPML_Upgrade {
	const SCOPE_ADMIN = 'admin';
	const SCOPE_AJAX = 'ajax';
	const SCOPE_FRONT_END = 'front-end';

	/** @var array */
	private $commands;
	const UPDATE_STATUSES_KEY = 'wpml_update_statuses';

	/** @var SitePress */
	private $sitepress;

	/** @var WPML_Upgrade_Command_Factory */
	private $command_factory;

	/**
	 * WPML_Upgrade constructor.
	 *
	 * @param array $commands
	 * @param SitePress $sitepress
	 * @param WPML_Upgrade_Command_Factory $command_factory
	 */
	public function __construct( array $commands, SitePress $sitepress, WPML_Upgrade_Command_Factory $command_factory ) {
		foreach ( $commands as $command ) {
			if ( $command instanceof WPML_Upgrade_Command_Definition ) {
				$this->commands[] = $command;
			}
		}

		$this->sitepress       = $sitepress;
		$this->command_factory = $command_factory;
	}

	public function run() {
		if ( $this->sitepress->get_wp_api()->is_admin() ) {
			if ( $this->sitepress->get_wp_api()->is_ajax() ) {
				return $this->run_ajax();
			} else {
				return $this->run_admin();
			}
		} elseif ( $this->sitepress->get_wp_api()->is_front_end() ) {
			return $this->run_front_end();
		}
	}

	private function get_commands_by_scope( $scope ) {
		$results = array();
		/** @var WPML_Upgrade_Command_Definition $command */
		foreach ( $this->commands as $command ) {
			if ( in_array( $scope, $command->get_scopes(), true ) ) {
				$results[] = $command;
			}
		}

		return $results;
	}

	private function get_admin_commands() {
		return $this->get_commands_by_scope( self::SCOPE_ADMIN );
	}

	private function get_ajax_commands() {
		return $this->get_commands_by_scope( self::SCOPE_AJAX );
	}

	private function get_front_end_commands() {
		return $this->get_commands_by_scope( self::SCOPE_FRONT_END );
	}

	private function run_admin() {
		return $this->run_commands( $this->get_admin_commands(), 'maybe_run_admin' );
	}

	private function run_ajax() {
		return $this->run_commands( $this->get_ajax_commands(), 'maybe_run_ajax' );
	}

	private function run_front_end() {
		return $this->run_commands( $this->get_front_end_commands(), 'maybe_run_front_end' );
	}

	private function run_commands( $commands, $default ) {
		$results = array();
		/** @var WPML_Upgrade_Command_Definition $command */
		foreach ( $commands as $command ) {
			$results[] = $this->run_command( $command, $default );
		}

		return array_unique( $results );
	}

	private function run_command( WPML_Upgrade_Command_Definition $command, $default ) {
		$method = $default;
		if ( $command->get_method() ) {
			$method = $command->get_method();
		}

		$upgrade = $this->command_factory->create( $command->get_class_name(), $command->get_dependencies() );
		if ( ! $this->has_been_command_executed( $upgrade ) ) {
			return $this->$method( $upgrade );
		}

		return null;
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @param IWPML_Upgrade_Command $upgrade
	 *
	 * @return null
	 */
	private function maybe_run_admin( IWPML_Upgrade_Command $upgrade ) {
		if ( $upgrade->run_admin() ) {
			$this->mark_command_as_executed( $upgrade );
		}

		return $upgrade->get_results();
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @param IWPML_Upgrade_Command $upgrade
	 *
	 * @return null
	 */
	private function maybe_run_front_end( IWPML_Upgrade_Command $upgrade ) {
		if ( $upgrade->run_frontend() ) {
			$this->mark_command_as_executed( $upgrade );
		}

		return $upgrade->get_results();
	}

	/** @noinspection PhpUnusedPrivateMethodInspection
	 * @param IWPML_Upgrade_Command $upgrade
	 *
	 * @return null
	 */
	private function maybe_run_ajax( IWPML_Upgrade_Command $upgrade ) {
		if ( $this->nonce_ok( $upgrade ) && $upgrade->run_ajax() ) {
			$this->mark_command_as_executed( $upgrade );
			$this->sitepress->get_wp_api()->wp_send_json_success( '' );
		}

		return $upgrade->get_results();
	}

	private function nonce_ok( $class ) {
		$ok = false;

		$class_name = strtolower( get_class( $class ) );
		$class_name = str_replace( '_', '-', $class_name );
		if ( isset( $_POST['action'] ) && $_POST['action'] === $class_name ) {
			$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( $this->sitepress->get_wp_api()->wp_verify_nonce( $nonce, $class_name . '-nonce' ) ) {
				$ok = true;
			}
		}

		return $ok;
	}

	/**
	 * @param IWPML_Upgrade_Command $class
	 *
	 * @return bool
	 */
	private function has_been_command_executed( IWPML_Upgrade_Command $class ) {
		return (bool) $this->get_update_option_value( $class->get_command_id() );
	}

	/**
	 * @param IWPML_Upgrade_Command $class
	 */
	private function mark_command_as_executed( IWPML_Upgrade_Command $class ) {
		$this->set_update_status( $class->get_command_id(), true );
		wp_cache_flush();
	}

	private function get_update_option_value( $id ) {
		$update_options = get_option( self::UPDATE_STATUSES_KEY, array() );

		if ( $update_options && array_key_exists( $id, $update_options ) ) {
			return $update_options[ $id ];
		}

		return null;
	}

	private function set_update_status( $id, $value ) {
		$update_options        = get_option( self::UPDATE_STATUSES_KEY, array() );
		$update_options[ $id ] = $value;
		update_option( self::UPDATE_STATUSES_KEY, $update_options, true );
	}
}

