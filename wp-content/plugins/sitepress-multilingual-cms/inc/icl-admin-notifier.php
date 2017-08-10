<?php
/**
 * @package wpml-core
 */

/**
 * Admin Notifier Class
 *
 * Manages Admin Notices
 *
 *
 */

add_action ( 'init', array('ICL_AdminNotifier', 'init') );

if(!class_exists('ICL_AdminNotifier')) {
	class ICL_AdminNotifier {
		public static function init() {
			if ( is_admin() ) {
				add_action( 'wp_ajax_icl-hide-admin-message', array( __CLASS__, 'hide_message' ) );
				add_action( 'wp_ajax_icl-show-admin-message', array( __CLASS__, 'show_message' ) );
				if ( ! defined( 'DOING_AJAX' ) ) {
					add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_script' ) );

					add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
				}

				add_filter( 'troubleshooting_js_data', array( __CLASS__, 'troubleshooting_js_data' ) );
				add_action( 'wpml_troubleshooting_cleanup', array( __CLASS__, 'troubleshooting' ) );
				add_action( 'wp_ajax_icl_restore_notifications', array( __CLASS__, 'restore_notifications' ) );
				add_action( 'wp_ajax_icl_remove_notifications', array( __CLASS__, 'remove_notifications' ) );
			}
		}

		public static function add_script() {
			wp_enqueue_script( 'icl-admin-notifier', ICL_PLUGIN_URL . '/res/js/icl-admin-notifier.js', array( 'jquery' ), ICL_SITEPRESS_VERSION );
		}

		/**
		 * @param string $message
		 * @param string $type
		 */
		public static function add_instant_message( $message, $type = '' ) {
			$messages                       = self::get_messages();
			$messages['instant_messages'][] = array(
				'text' => $message,
				'type' => $type
			);
			self::save_messages( $messages );
		}

		/**
		 * @param $message_id
		 *
		 * @return bool|array
		 */
		public static function get_message( $message_id ) {
			$messages = self::get_messages();

			return isset( $messages['messages'][ $message_id ] ) ? $messages['messages'][ $message_id ] : false;
		}

		public static function message_id_exists( $message_id ) {
			$message = self::get_message( $message_id );

			return $message !== false;
		}

		private static function get_messages() {
			$messages = get_option( 'icl_admin_messages' );
			if ( ! ( isset( $messages ) && $messages != false ) ) {
				return array( 'messages' => array(), 'instant_messages' => array() );
			}
			if ( ! isset( $messages['messages'] ) || ! isset( $messages['instant_messages'] ) ) {
				$messages = array( 'messages' => array(), 'instant_messages' => array() );
			}

			return (array) $messages;
		}

		private static function save_messages( $messages ) {
			if ( isset( $messages ) ) {
				update_option( 'icl_admin_messages', (array) $messages );
			}
			self::get_messages();
		}

		/**
		 * @param $args
		 *    Args attributes:
		 *    string        id - An unique identifier for the message
		 *    string        msg - The actual message
		 *    string        type (optional) - Any string: it will be used as css class fro the message container. A typical value is 'error', but the following strings can be also used: icl-admin-message-information, icl-admin-message-warning
		 *    array         classes (optional) - Display the notice only on specific url(s)
		 *    bool          hide (optional) - Enable the toggle link to permanently hide the notice
		 *    bool          hide_per_user (optional) - Enable the toggle link per user basis (overrides hide option)
		 *    bool          dismiss (optional) - Enable the dismiss option
		 *    bool          dismiss_per_user (optional) - Enable the dismiss option per user basis (overrides dismiss option)
		 *    bool|string   fallback_text (optional) - A message to show when the notice gets hidden
		 *    bool|string   fallback_type (optional) - The message type to use in the fallback message (@see $type)
		 *    array         fallback_classes (optional) - The message type to use in the fallback message (@see $type)
		 *    bool|string   group (optional) - A way to group messages: when displaying messages stored with this method, it's possible to filter them by group (@see ICL_AdminNotifier::displayMessages)
		 *    bool          admin_notice (optional) - Hook the rendering to the 'admin_notice' action
		 *    string|array  limit_to_page (optional) - Display the notice only on specific page(s)
		 */
		public static function add_message( $args ) {
			$defaults = array(
				'type'             => '',
				'classes'          => array(),
				'hide'             => false,
				'hide_per_user'    => false,
				'dismiss'          => false,
				'dismiss_per_user' => false,
				'fallback_text'    => false,
				'fallback_type'    => false,
				'fallback_classes' => array(),
				'group'            => false,
				'admin_notice'     => false,
				'hidden'           => false,
				'dismissed'        => false,
				'limit_to_page'    => false,
				'show_once'        => false,
				'capability'       => '',
			);

			$args = self::sanitize_message_args( $args );

			$args = array_merge( $defaults, $args );

			$id = $args['id'];

			//Check if existing message has been set as dismissed or hidden
			if ( self::message_id_exists( $id ) ) {
				$temp_msg = self::get_message( $id );

				if ( $temp_msg ) {
					$current_user_id   = get_current_user_id();
					$message_user_data = isset( $temp_msg['users'][ $current_user_id ] ) ? $temp_msg['users'][ $current_user_id ] : false;

					if ( self::is_user_dismissed( $temp_msg ) || self::is_globally_dismissed( $temp_msg ) || self::is_globally_hidden( $temp_msg ) ) {
						return;
					}

					$args['hidden'] = $message_user_data['hidden'] ? false : $args['hidden'];
				}
			}

			$id       = $id ? $id : md5( wp_json_encode( $args ) );
			$messages = self::get_messages();

			$message = array(
				'id'               => $id,
				'text'             => $args['text'],
				'type'             => $args['type'],
				'classes'          => $args['classes'],
				'hide'             => $args['hide'],
				'hide_per_user'    => $args['hide_per_user'],
				'dismiss'          => $args['dismiss'],
				'dismiss_per_user' => $args['dismiss_per_user'],
				'fallback_text'    => $args['fallback_text'],
				'fallback_type'    => $args['fallback_type'],
				'fallback_classes' => $args['classes'],
				'group'            => $args['group'],
				'admin_notice'     => $args['admin_notice'],
				'hidden'           => false,
				'dismissed'        => false,
				'limit_to_page'    => $args['limit_to_page'],
				'show_once'        => $args['show_once'],
				'capability'       => $args['capability'],
			);

			$message_md5 = md5( wp_json_encode( $message ) );

			if ( isset( $messages['messages'][ $id ] ) ) {
				$existing_message_md5 = md5( wp_json_encode( $messages['messages'][ $id ] ) );
				if ( $message_md5 != $existing_message_md5 ) {
					unset( $messages['messages'][ $id ] );
				}
			}

			if ( ! isset( $messages['messages'][ $id ] ) ) {
				$messages['messages'][ $id ] = $message;
				self::save_messages( $messages );
			}
		}

		public static function is_user_dismissed( $message_data ) {
			$current_user_id   = get_current_user_id();
			$message_user_data = isset( $message_data['users'][ $current_user_id ] ) ? $message_data['users'][ $current_user_id ] : false;

			return ! empty( $message_data['dismiss_per_user'] ) && ! empty( $message_user_data['dismissed'] );
		}

		public static function is_globally_dismissed( $message_data ) {
			return ! empty( $message_data['dismiss'] ) && $message_data['dismissed'];
		}

		public static function is_globally_hidden( $message_data ) {
			return ! empty( $message_data['hide'] ) && $message_data['hidden'];
		}

		public static function hide_message() {

			$message_id = self::get_message_id();
			$dismiss    = isset( $_POST['dismiss'] ) ? $_POST['dismiss'] : false;
			if ( ! self::message_id_exists( $message_id ) ) {
				exit;
			}

			self::set_message_display( $message_id, false, 'hide', 'hidden', 'hide_per_user' );

			if ( $dismiss ) {
				self::set_message_display( $message_id, false, 'dismiss', 'dismissed', 'dismiss_per_user' );
			} else {
				$messages = self::get_messages();
				$message  = $messages['messages'][ $message_id ];
				if ( $message && isset( $message['fallback_text'] ) && $message['fallback_text'] ) {
					echo self::display_message( $message_id, $message['fallback_text'], $message['fallback_type'], $message['fallback_classes'], false, false, true, true );
				}
			}
			exit;
		}

		public static function get_message_id() {
			$message_id = '';
			if ( isset( $_POST['icl-admin-message-id'] ) ) {
				$message_id = filter_var( $_POST['icl-admin-message-id'], FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			}
			$message_id = $message_id ? $message_id : '';
			$message_id = preg_replace( '/^icl-id-/', '', $message_id );

			return $message_id;
		}

		public static function show_message() {
			$message_id = self::get_message_id();
			if ( ! self::message_id_exists( $message_id ) ) {
				exit;
			}

			self::set_message_display( $message_id, true, 'hide', 'hidden', 'hide_per_user' );

			$messages = self::get_messages();
			$message  = $messages['messages'][ $message_id ];
			if ( $message ) {
				echo self::display_message( $message_id, $message['text'], $message['type'], $message['classes'], $message['hide'] || $message['hide_per_user'], $message['dismiss'] || $message['dismiss_per_user'], true, true );
			}
			exit;
		}

		public static function engage_message() {
			$message_id = self::get_message_id();
			if ( ! self::message_id_exists( $message_id ) ) {
				exit;
			}

			self::set_message_display( $message_id, true, 'dismiss', 'dismissed', 'dismiss_per_user' );
		}

		private static function set_message_display( $message_id, $show, $action, $action_past, $action_user ) {
			if ( $message_id === null ) {
				return;
			}

			$messages = self::get_messages();

			if ( ! isset( $messages['messages'][ $message_id ] ) ) {
				return;
			}
			$message         = $messages['messages'][ $message_id ];
			$current_user_id = get_current_user_id();
			if ( $message[ $action_user ] && $current_user_id ) {
				$message['users'][ $current_user_id ][ $action_past ] = ! $show;
			} elseif ( $message[ $action ] ) {
				$message[ $action_past ] = ! $show;
			}
			$messages['messages'][ $message_id ] = $message;
			self::save_messages( $messages );
		}

		public static function remove_message( $message_id ) {
			if ( $message_id === null || ! isset( $message_id ) ) {
				return false;
			}

			$messages = self::get_messages();

			if ( ! isset( $messages['messages'][ $message_id ] ) ) {
				return false;
			}

			unset( $messages['messages'][ $message_id ] );

			self::save_messages( $messages );

			return false;
		}

		public static function remove_message_group( $message_group ) {
			if ( $message_group === null || ! isset( $message_group ) ) {
				return;
			}

			$all_messages = self::get_messages();

			if ( ! isset( $all_messages['messages'] ) ) {
				return;
			}

			$messages = $all_messages['messages'];

			$ids_to_remove = array();
			foreach ( $messages as $id => $message_data ) {
				if ( isset( $message_data['group'] ) && $message_data['group'] == $message_group ) {
					$ids_to_remove[] = $id;
				}
			}

			foreach ( $ids_to_remove as $id_to_remove ) {
				self::remove_message( $id_to_remove );
			}
		}

		public static function display_messages( $group = false ) {
			if ( is_admin() ) {
				$messages = self::get_messages();

				foreach ( $messages['messages'] as $id => $msg ) {
					if ( ! $group || ( isset( $msg['group'] ) && $msg['group'] == $group ) ) {
						if ( isset( $msg['admin_notice'] ) && ! $msg['admin_notice'] ) {
							if ( ! isset( $msg['capability'] ) || ( $msg['capability'] == '' ) || current_user_can( $msg['capability'] ) ) {
								self::display_message( $id, $msg['text'], $msg['type'], $msg['classes'], $msg['hide'] || $msg['hide_per_user'], $msg['dismiss'] || $msg['dismiss_per_user'], true );
							}
						}
					}
				}

				foreach ( $messages['instant_messages'] as $msg ) {
					self::display_instant_message( $msg['text'], $msg['type'] );
				}
				// delete instant messages
				$messages['instant_messages'] = array();
				self::save_messages( $messages );
			}
		}

		/**
		 * @deprecated deprecated @since version 3.2. Use ICL_AdminNotifier::display_message()
		 *
		 * @param bool $group
		 */
		public static function displayMessages( $group = false ) {
			self::display_messages( $group );
		}

		public static function admin_notices() {
			$messages = self::get_messages();
			if ( isset( $messages['messages'] ) ) {
				foreach ( $messages['messages'] as $id => $msg ) {

					if ( isset( $msg['limit_to_page'] ) && $msg['limit_to_page'] ) {
						if ( ! is_array( $msg['limit_to_page'] ) ) {
							$msg['limit_to_page'] = (array) $msg['limit_to_page'];
						}
						if ( ! isset( $_REQUEST['page'] ) || ! in_array( $_REQUEST['page'], $msg['limit_to_page'] ) ) {
							continue;
						}
					}

					if ( $msg['admin_notice'] ) {
						$current_user_id  = get_current_user_id();
						$display          = true;
						$display_fallback = false;

						$message_user_data = isset( $msg['users'][ $current_user_id ] ) ? $msg['users'][ $current_user_id ] : false;

						if ( $msg['dismiss_per_user'] && isset( $message_user_data['dismissed'] ) && $message_user_data['dismissed'] ) {
							$display = false;
						} elseif ( $msg['dismiss'] && isset( $msg['dismissed'] ) && $msg['dismissed'] ) {
							$display = false;
						}

						if ( $display ) {
							if ( $msg['hide_per_user'] && isset( $message_user_data['hidden'] ) && $message_user_data['hidden'] ) {
								$display          = false;
								$display_fallback = $msg['fallback_text'];
							} elseif ( $msg['hide'] && isset( $msg['hidden'] ) && $msg['hidden'] ) {
								$display          = false;
								$display_fallback = $msg['fallback_text'];
							}
						}

						$msg['classes']          = isset( $msg['classes'] ) ? $msg['classes'] : array();
						$msg['fallback_classes'] = isset( $msg['fallback_classes'] ) ? $msg['fallback_classes'] : array();
						if ( $display ) {
							self::display_message( $id, $msg['text'], $msg['type'], $msg['classes'], $msg['hide'] || $msg['hide_per_user'], $msg['dismiss'] || $msg['dismiss_per_user'], true );
							if ( $msg['show_once'] && ! $display_fallback ) {
								self::remove_message( $msg['id'] );
							}
						} elseif ( $display_fallback ) {
							self::display_message( $id, $msg['fallback_text'], $msg['fallback_type'], $msg['fallback_classes'], false, false, true );
							if ( $msg['show_once'] ) {
								self::remove_message( $msg['id'] );
							}
						}
					}
				}
			}
		}

		/**
		 * @param string       $id
		 * @param string       $message
		 * @param string       $type
		 * @param string|array $classes
		 * @param bool         $hide
		 * @param bool         $dismiss
		 * @param bool         $admin_notice
		 * @param bool         $echo
		 *
		 * @return string
		 */
		private static function display_message( $id, $message, $type = '', $classes = array(), $hide = true, $dismiss = false, $admin_notice = false, $echo = false ) {
			$result       = '';
			$temp_classes = array();
			if ( strpos( $type, 'icl-admin-message' ) ) {
				$type = str_replace( 'icl-admin-message-', '', $type );
			}
			if ( $admin_notice ) {
				$temp_classes[] = 'icl-admin-message';
			}
			$temp_types = explode( ' ', $type );
			$temp_types = array_unique( $temp_types );
			foreach ( $temp_types as $temp_type ) {
				if ( $admin_notice ) {
					$temp_classes[] = 'icl-admin-message-' . $temp_type;
				}
				$temp_classes[] = $temp_type;
			}

			if ( $classes ) {
				if ( ! is_array( $classes ) ) {
					$classes = explode( ' ', $classes );
				}
				foreach ( $classes as $class ) {
					$temp_classes[] = $class;
				}
			}
			if ( $hide OR $dismiss ) {
				$temp_classes[] = 'otgs-is-dismissible';
			}

			$temp_classes = array_unique( $temp_classes );

			$class = implode( ' ', $temp_classes );

			$result .= '<div class="' . $class . '" id="icl-id-' . $id . '"';

			if ( $hide ) {
				$result .= ' data-hide-text="' . __( 'Hide', 'sitepress' ) . '" ';
			}
			$result .= '>';

			$result .= '<p>' . self::sanitize_and_format_message( $message ) . '</p>';
			if ( $hide ) {
				$result .= ' <span class="icl-admin-message-hide notice-dismiss"><span class="screen-reader-text">' . __( 'Hide this notice.', 'sitepress' ) . '</span></span>';
			}

			if ( $dismiss ) {
				$result .= ' <span class="icl-admin-message-dismiss notice-dismiss">';
				$result .= '<span class="screen-reader-text"><input class="icl-admin-message-dismiss-check" type="checkbox" value="1" />';
				$result .= __( 'Dismiss this notice.', 'sitepress' );
				$result .= '</span></span>';
			}

			$result .= '</div>';

			if ( ! $echo ) {
				echo $result;
			}

			return $result;
		}

		public static function display_instant_message( $message, $type = 'information', $class = false, $return = false, $fadeout = false ) {
			$classes = array();
			if ( ! $class && $type ) {
				$classes[] = $type;
			}
			$classes[] = 'instant-message';
			$classes[] = 'message';
			$classes[] = 'message-' . $type;

			foreach ( $classes as $class ) {
				$classes[] = 'icl-admin-' . $class;
			}

			if ( $fadeout ) {
				$classes[] = 'js-icl-fadeout';
			}

			$classes = array_unique( $classes );

			if ( in_array( 'error', $classes ) ) {
				$key = array_search( 'error', $classes );
				if ( $key !== false ) {
					unset( $classes[ $key ] );
				}
			}

			$result = '<div class="' . implode( ' ', $classes ) . '">';
			$result .= self::sanitize_and_format_message( $message );
			$result .= '</div>';

			if ( ! $return ) {
				echo $result;
			}

			return $result;
		}

		/**
		 * @param $args
		 *
		 * @return mixed
		 */
		private static function sanitize_message_args( $args ) {
			if ( isset( $args['msg'] ) ) {
				$args['text'] = $args['msg'];
				unset( $args['msg'] );
			}

			if ( isset( $args['fallback'] ) ) {
				$args['fallback_message'] = $args['fallback'];
				unset( $args['fallback'] );
			}

			if ( isset( $args['message_fallback'] ) ) {
				$args['fallback_message'] = $args['message_fallback'];
				unset( $args['message_fallback'] );
			}

			if ( isset( $args['type_fallback'] ) ) {
				$args['fallback_type'] = $args['type_fallback'];
				unset( $args['type_fallback'] );

				return $args;
			}

			if ( ! isset( $args['classes'] ) ) {
				$args['classes'] = array();
			} elseif ( ! is_array( $args['classes'] ) ) {
				$args['classes'] = (array) $args['classes'];
			}

			if ( ! isset( $args['limit_to_page'] ) ) {
				$args['limit_to_page'] = array();
			} elseif ( ! is_array( $args['limit_to_page'] ) ) {
				$args['limit_to_page'] = (array) $args['limit_to_page'];
			}

			return $args;
		}

		static function troubleshooting_js_data( $data ) {
			$data['nonce']['icl_restore_notifications'] = wp_create_nonce( 'icl_restore_notifications' );
			$data['nonce']['icl_remove_notifications']  = wp_create_nonce( 'icl_remove_notifications' );

			return $data;
		}

		static function has_hidden_messages() {
			$messages           = self::get_messages();
			$no_hidden_messages = true;
			foreach ( $messages as $group => $message_group ) {
				foreach ( $message_group as $id => $msg ) {
					if ( ( isset( $msg['hidden'] ) && $msg['hidden'] ) || ( isset( $msg['dismissed'] ) && $msg['dismissed'] ) ) {
						$no_hidden_messages = false;
					} else {
						$current_user_id   = get_current_user_id();
						$message_user_data = isset( $msg['users'][ $current_user_id ] ) ? $msg['users'][ $current_user_id ] : false;
						if ( $message_user_data && $msg['dismiss_per_user'] && isset( $message_user_data['dismissed'] ) && $message_user_data['dismissed'] ) {
							$no_hidden_messages = false;
						} elseif ( $message_user_data && $msg['dismiss'] && isset( $msg['dismissed'] ) && $msg['dismissed'] ) {
							$no_hidden_messages = false;
						}

						if ( $no_hidden_messages ) {
							if ( isset( $msg['hide_per_user'] ) && $msg['hide_per_user'] && isset( $message_user_data['hidden'] ) && $message_user_data['hidden'] ) {
								$no_hidden_messages = ! $msg['fallback_text'];
							} elseif ( $msg['hide'] && isset( $msg['hidden'] ) && $msg['hidden'] ) {
								$no_hidden_messages = ! $msg['fallback_text'];
							}
						}
					}

					if ( ! $no_hidden_messages ) {
						return true;
					}
				}
			}

			return false;
		}

		static function troubleshooting() {
			?>
					<h4><?php _e( 'Messages and notifications', 'sitepress' ) ?></h4>
			<?php
			if ( self::has_hidden_messages() ) {

				?>
							<p>
								<input id="icl_restore_notifications" type="button" class="button-secondary" value="<?php echo __( 'Restore messages and notification', 'sitepress' ); ?>"/>
								<br/>
								<br/>
								<input id="icl_restore_notifications_all_users" name="icl_restore_notifications_all_users" type="checkbox" value="1"/><label for="icl_restore_notifications_all_users"><?php echo __( 'Apply to all users', 'sitepress' ); ?></label>
								<br/>
								<br/>
								<small style="margin-left:10px;"><?php echo __( 'Restore dismissed and hidden messages and notifications.', 'sitepress' ); ?></small>
							</p>
				<?php
			}
			?>
					<p>
						<input id="icl_remove_notifications" type="button" class="button-secondary" value="<?php echo __( 'Remove all messages and notifications', 'sitepress' ); ?>"/>
						<br/>
						<small style="margin-left:10px;"><?php echo __( 'Remove all messages and notifications, for all users.', 'sitepress' ); ?></small>
					</p>
			<?php
		}

		static function remove_notifications() {
			self::save_messages( array() );
			echo wp_json_encode( array( 'errors' => 0, 'message' => __( 'Done', 'sitepress' ), 'cont' => 0, 'reload' => 1 ) );
			die();
		}

		static function restore_notifications() {
			$all_users = $_POST['all_users'];

			$messages = self::get_messages();
			$dirty    = 0;
			foreach ( $messages as $group => $message_group ) {
				foreach ( $message_group as $id => $msg ) {
					if ( $msg['hidden'] ) {
						$msg['hidden'] = false;
						$dirty ++;
					}
					if ( $msg['dismissed'] ) {
						$msg['dismissed'] = false;
						$dirty ++;
					}

					$current_user_id = get_current_user_id();
					foreach ( $msg['users'] as $user_id => $message_user_data ) {
						if ( $current_user_id == $user_id || $all_users ) {
							if ( $message_user_data['hidden'] ) {
								$message_user_data['hidden'] = false;
								$dirty ++;
							}
							if ( $message_user_data['dismissed'] ) {
								$message_user_data['dismissed'] = false;
								$dirty ++;
							}
						}
						$msg['users'][ $user_id ] = $message_user_data;
					}

					$message_group[ $id ] = $msg;
				}
				$messages[ $group ] = $message_group;
			}

			if ( $dirty ) {
				self::save_messages( $messages );
			}

			echo wp_json_encode( array( 'errors' => 0, 'message' => __( 'Done', 'sitepress' ), 'cont' => $dirty, 'reload' => 1 ) );
			die();
		}

		/** Deprecated methods */

		/**
		 * @deprecated deprecated @since version 3.2. Use ICL_AdminNotifier::remove_message()
		 *
		 * @param $message_id
		 *
		 * @return bool
		 */
		public static function removeMessage( $message_id ) {
			return self::remove_message( $message_id );
		}

		/**
		 * @deprecated deprecated @since version 3.2
		 */
		public static function hideMessage() {
			self::hide_message();
		}

		/**
		 * @deprecated deprecated @since version 3.2
		 *
		 * @param string $message
		 * @param string $type
		 */
		public static function addInstantMessage( $message, $type = '' ) {
			self::add_instant_message( $message, $type );
		}

		/**
		 * @deprecated deprecated @since version 3.2
		 */
		public static function addScript() {
			self::add_script();
		}

		/**
		 * @deprecated deprecated @since version 3.2
		 *
		 * @param string $id               An unique identifier for the message
		 * @param string $msg              The actual message
		 * @param string $type             (optional) Any string: it will be used as css class fro the message container. A typical value is 'error', but the following strings can be also used: icl-admin-message-information, icl-admin-message-warning
		 * @param bool   $hide             (optional) Enable the toggle link to permanently hide the notice
		 * @param bool   $fallback_message (optional) A message to show when the notice gets hidden
		 * @param bool   $fallback_type    (optional) The message type to use in the fallback message (@see $type)
		 * @param bool   $group            (optional) A way to group messages: when displaying messages stored with this method, it's possible to filter them by group (@see ICL_AdminNotifier::displayMessages)
		 * @param bool   $admin_notice     (optional) Hook the rendering to the 'admin_notice' action
		 */
		public static function addMessage( $id, $msg, $type = '', $hide = true, $fallback_message = false, $fallback_type = false, $group = false, $admin_notice = false ) {
			$args = array(
				'id'               => $id,
				'msg'              => $msg,
				'type'             => $type,
				'hide'             => $hide,
				'message_fallback' => $fallback_message,
				'fallback_type'    => $fallback_type,
				'group'            => $group,
				'admin_notice'     => $admin_notice,
			);

			self::add_message( $args );
		}

		/**
		 * @deprecated deprecated @since version 3.2. Use ICL_AdminNotifier::display_instant_message()
		 *
		 * @param        $message
		 * @param string $type
		 * @param bool   $class
		 * @param bool   $return
		 *
		 * @return string
		 */
		public static function displayInstantMessage( $message, $type = 'information', $class = false, $return = false ) {
			return self::display_instant_message( $message, $type, $class, $return );
		}

		/**
		 * @param $message
		 *
		 * @return string
		 */
		public static function sanitize_and_format_message( $message ) {
			//		return preg_replace( '/`(.*?)`/s', '<pre>$1</pre>', stripslashes( $message ) );
			$backticks_pattern = '|`(.*)`|U';
			preg_match_all( $backticks_pattern, $message, $matches );

			$sanitized_message = $message;
			if ( 2 === count( $matches ) ) {
				$matches_to_sanitize = $matches[1];

				foreach ( $matches_to_sanitize as &$match_to_sanitize ) {
					$match_to_sanitize = '<pre>' . esc_html( $match_to_sanitize ) . '</pre>';
				}
				unset( $match_to_sanitize );

				$sanitized_message = str_replace( $matches[0], $matches_to_sanitize, $sanitized_message );
			}

			return stripslashes( $sanitized_message );
		}
	}
}
