<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Notices {

	const NOTICES_OPTION_KEY   = 'wpml_notices';
	const DISMISSED_OPTION_KEY = '_wpml_dismissed_notices';
	const NONCE_NAME           = 'wpml-notices';
	const DEFAULT_GROUP        = 'default';

	/**
	 * @var array
	 */
	private $notices = array();
	private $notices_to_remove  = array();
	private $dismissed = array();

	/**
	 * WPML_Notices constructor.
	 *
	 * @param WPML_Notice_Render     $notice_render
	 */
	public function __construct( WPML_Notice_Render $notice_render ) {
		$this->current_user_id   = get_current_user_id();
		$this->notice_render     = $notice_render;
		$this->notices           = $this->get_all_notices();
		$this->dismissed         = $this->get_all_dismissed();
	}

	/**
	 * @return int
	 */
	public function count() {
		$all_notices = $this->get_all_notices();
		$count       = 0;
		foreach ( $all_notices as $group => $group_notices ) {
			$count += count( $group_notices );
		}

		return $count;
	}

	/**
	 * @return array
	 */
	public function get_all_notices() {
		$all_notices = get_option( self::NOTICES_OPTION_KEY );
		if ( ! is_array( $all_notices ) ) {
			$all_notices = array();
		}
		return $all_notices;
	}

	/**
	 * @return array
	 */
	private function get_all_dismissed() {
		$dismissed = get_option( self::DISMISSED_OPTION_KEY );
		if ( ! is_array( $dismissed ) ) {
			$dismissed = array();
		}
		return $dismissed;
	}

	public function add_notice( WPML_Notice $notice, $force_update = false ) {
		$existing_notice = $this->notice_exists( $notice ) ? $this->notices[ $notice->get_group() ][ $notice->get_id() ] : null;

		$new_notice_is_different = $existing_notice && serialize( $existing_notice ) !== serialize( $notice );

		if ( ! $existing_notice || ( $new_notice_is_different || $force_update ) ) {
			$this->notices[ $notice->get_group() ][ $notice->get_id() ] = $notice;
			$this->save_notices();
		}
	}

	/**
	 * @param string $id
	 * @param string $text
	 * @param string $group
	 *
	 * @return WPML_Notice
	 */
	public function get_new_notice( $id, $text, $group = 'default' ) {
		return new WPML_Notice( $id, $text, $group );
	}

	/**
	 * @param string $text
	 * @param string $url
	 * @param bool   $dismiss
	 * @param bool   $hide
	 * @param bool   $display_as_button
	 *
	 * @return WPML_Notice_Action
	 */
	public function get_new_notice_action( $text, $url = '#', $dismiss = false, $hide = false, $display_as_button = false ) {
		return new WPML_Notice_Action( $text, $url, $dismiss, $hide, $display_as_button );
	}

	/**
	 * @param WPML_Notice $notice
	 *
	 * @return bool
	 */
	private function notice_exists( WPML_Notice $notice ) {
		$notice_id    = $notice->get_id();
		$notice_group = $notice->get_group();

		return $this->group_and_id_exist( $notice_group, $notice_id );
	}

	private function get_notices_for_group( $group ) {
		if ( array_key_exists( $group, $this->notices ) ) {
			return $this->notices[ $group ];
		}

		return array();
	}

	private function save_notices() {
		$this->remove_notices();
		update_option( self::NOTICES_OPTION_KEY, $this->notices, false );
	}

	private function save_dismissed() {
		update_option( self::DISMISSED_OPTION_KEY, $this->dismissed, false );
	}

	public function remove_notices() {
		if ( $this->notices_to_remove ) {
			foreach ( $this->notices_to_remove as $group => &$group_notices ) {
				/** @var array $group_notices */
				foreach ( $group_notices as $id ) {
					if ( array_key_exists( $group, $this->notices ) && array_key_exists( $id, $this->notices[ $group ] ) ) {
						unset( $this->notices[ $group ][ $id ] );
						$group_notices = array_diff( $this->notices_to_remove[ $group ], array( $id ) );
					}
				}
				if ( array_key_exists( $group, $this->notices_to_remove ) && ! $this->notices_to_remove[ $group ] ) {
					unset( $this->notices_to_remove[ $group ] );
				}
				if ( array_key_exists( $group, $this->notices ) && ! $this->notices[ $group ] ) {
					unset( $this->notices[ $group ] );
				}
			}
		}
	}

	function admin_enqueue_scripts() {
		if ( $this->must_display_notices() ) {
			wp_enqueue_style( 'otgs-notices', ICL_PLUGIN_URL . '/res/css/otgs-notices.css', array( 'sitepress-style' ) );
			wp_enqueue_script( 'otgs-notices', ICL_PLUGIN_URL . '/res/js/otgs-notices.js', array( 'underscore' ) );
			do_action( 'wpml-notices-scripts-enqueued' );
		}
	}

	private function must_display_notices() {
		if ( $this->notices ) {
			/**
			 * @var string $group
			 */
			foreach ( $this->notices as $group => $notices ) {
				/**
				 * @var array       $notices
				 * @var WPML_Notice $notice
				 */
				foreach ( $notices as $notice ) {
					if ( $this->notice_render->must_display_notice( $notice ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	function admin_notices() {
		if ( $this->notices && $this->must_display_notices() ) {
			foreach ( $this->notices as $group => $notices ) {
				/**
				 * @var array       $notices
				 * @var WPML_Notice $notice
				 */
				foreach ( $notices as $notice ) {
					if ( $notice instanceof WPML_Notice && ! $this->is_notice_dismissed( $notice ) ) {
						$this->notice_render->render( $notice );
					}
				}
			}
		}
	}

	function wp_ajax_hide_notice() {
		list( $notice_group, $notice_id ) = $this->parse_group_and_id();

		if ( ! $notice_group ) {
			$notice_group = self::DEFAULT_GROUP;
		}

		if ( $this->has_valid_nonce() && $this->group_and_id_exist( $notice_group, $notice_id ) ) {
			$this->remove_notice( $notice_group, $notice_id );
			wp_send_json_success( true );
		}

		wp_send_json_error( __( 'Notice does not exists.', 'sitepress' ) );
	}

	function wp_ajax_dismiss_notice() {
		list( $notice_group, $notice_id ) = $this->parse_group_and_id();

		if ( ! $notice_group ) {
			$notice_group = self::DEFAULT_GROUP;
		}

		if ( $this->has_valid_nonce() && $this->group_and_id_exist( $notice_group, $notice_id ) ) {
			$notice_text = $this->get_notice_text( $notice_group, $notice_id );
			$this->dismiss_notice( $notice_group, $notice_id, $notice_text, false );
			$this->remove_notice( $notice_group, $notice_id );
			$this->save_dismissed();

			wp_send_json_success( true );
		}

		wp_send_json_error( __( 'Notice does not exist.', 'sitepress' ) );
	}

	/**
	 * @param string $notice_group
	 * @param string $notice_id
	 *
	 * @return bool|string
	 */
	private function get_notice_text( $notice_group, $notice_id ) {
		$notices = $this->get_all_notices();
		$message = true;

		if ( array_key_exists( $notice_group, $notices ) && array_key_exists( $notice_id, $notices[ $notice_group ] ) ) {
			/**	@var WPML_Notice $notice */
			$notice  = $notices[ $notice_group ][ $notice_id ];
			$message = $notice->get_text();
		}

		return $message;
	}

	function wp_ajax_dismiss_group() {
		list( $notice_group ) = $this->parse_group_and_id();

		if ( $this->has_valid_nonce() && $notice_group ) {
			$notices = $this->get_notices_for_group( $notice_group );

			if ( $notices ) {

				/** @var WPML_Notice $notice */
				foreach ( $notices as $notice ) {
					$this->dismiss_notice( $notice_group, $notice->get_id(), $notice->get_text(), false );
					$this->remove_notice( $notice_group, $notice->get_id() );
				}

				$this->save_dismissed();

				wp_send_json_success( true );
			}
		}
		wp_send_json_error( __( 'Group does not exist.', 'sitepress' ) );
	}

	/**
	 * @return array
	 */
	private function parse_group_and_id() {
		$group = isset( $_POST['group'] ) ? sanitize_text_field( $_POST['group'] ) : false;
		$id    = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : false;

		return array( $group, $id );
	}

	/**
	 * @return false|int
	 */
	private function has_valid_nonce() {
		$nonce          = isset( $_POST['nonce'] ) ? $_POST['nonce'] : null;
		return wp_verify_nonce( $nonce, self::NONCE_NAME );
	}

	private function group_and_id_exist( $group, $id ) {
		return array_key_exists( $group, $this->notices ) && array_key_exists( $id, $this->notices[ $group ] );
	}

	/**
	 * @param string $group
	 * @param string $id
	 */
	public function remove_notice( $group, $id ) {
		if ( $group && $id ) {
			$this->notices_to_remove[ $group ][] = $id;
			$this->notices_to_remove[ $group ]   = array_unique( $this->notices_to_remove[ $group ] );
			$this->save_notices();
		}
		if ( ! is_array( $this->notices_to_remove ) ) {
			$this->notices_to_remove = array();
		}
	}

	/**
	 * @param string $group
	 * @param string $id
	 * @param string $message
	 * @param bool   $persist
	 */
	private function dismiss_notice( $group, $id, $message, $persist = true ) {
		$this->dismissed[ $group ][ $id ] = md5( $message );

		if ( $persist ) {
			$this->save_dismissed();
		}
	}

	/**
	 * @param WPML_Notice $notice
	 *
	 * @return bool
	 */
	public function is_notice_dismissed( WPML_Notice $notice ) {
		$group = $notice->get_group();
		$id    = $notice->get_id();

		$is_dismissed = (bool) isset( $this->dismissed[ $group ][ $id ] ) && $this->dismissed[ $group ][ $id ];

		if ( $is_dismissed && method_exists( $notice, 'can_be_dismissed_for_different_text' )
			 && ! $notice->can_be_dismissed_for_different_text() ) {
			$is_dismissed = md5( $notice->get_text() ) === $this->dismissed[ $group ][ $id ];
		}

		return $is_dismissed;
	}

	public function init_hooks() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_otgs-hide-notice', array( $this, 'wp_ajax_hide_notice' ) );
		add_action( 'wp_ajax_otgs-dismiss-notice', array( $this, 'wp_ajax_dismiss_notice' ) );
		add_action( 'wp_ajax_otgs-dismiss-group', array( $this, 'wp_ajax_dismiss_group' ) );
	}
}
