<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Notice_Render {
	private $dismiss_html_added;
	private $hide_html_added;
	private $collapse_html_added;

	public function render( WPML_Notice $notice ) {
		echo $this->get_html( $notice );
	}

	/**
	 * @param WPML_Notice $notice
	 *
	 * @return string
	 */
	public function get_html( WPML_Notice $notice ) {
		$result = '';

		if ( $this->must_display_notice( $notice ) ) {
			$actions_html = $this->get_actions_html( $notice );

			$temp_types = $notice->get_css_class_types();
			foreach ( $temp_types as $temp_type ) {
				if ( strpos( $temp_type, 'notice-' ) === false ) {
					$temp_types[] = 'notice-' . $temp_type;
				}
				if ( strpos( $temp_type, 'notice-' ) === 0 ) {
					$temp_types[] = substr( $temp_type, 0, strlen( 'notice-' ) );
				}
			}
			$temp_classes = $notice->get_css_classes();

			$classes = array_merge( $temp_classes, $temp_types );

			if ( $this->hide_html_added || $this->dismiss_html_added || $notice->can_be_hidden() || $notice->can_be_dismissed() ) {
				$classes[] = 'is-dismissible';
			}
			$classes[] = 'notice';
			$classes[] = 'otgs-notice';

			$classes = array_unique( $classes );

			$class = implode( ' ', $classes );

			$result .= '<div class="' . $class . '" data-id="' . esc_attr( $notice->get_id() ) . '" data-group="' . esc_attr( $notice->get_group() ) . '"';
			$result .= $this->get_data_nonce_attribute();

			if ( $this->hide_html_added || $notice->can_be_hidden() ) {
				$result .= ' data-hide-text="' . __( 'Hide', 'sitepress' ) . '" ';
			}
			$result .= '>';

			if ( $notice->can_be_collapsed() ) {
				$result .= $this->sanitize_and_format_text( $this->get_collapsed_html( $notice ) );
			} else {
				$result .= '<p>' . $this->sanitize_and_format_text( $notice->get_text() ) . '</p>';
			}

			$this->dismiss_html_added  = false;
			$this->hide_html_added     = false;
			$this->collapse_html_added = false;

			$result .= $actions_html;

			if ( $notice->can_be_hidden() ) {
				$result .= $this->get_hide_html();
			}

			if ( $notice->can_be_dismissed() ) {
				$result .= $this->get_dismiss_html();
			}

			if ( $notice->can_be_collapsed() ) {
				$result .= $this->get_collapse_html();
			}

			$result .= '</div>';
		}

		return $result;
	}

	public function must_display_notice( WPML_Notice $notice ) {
		$current_page = array_key_exists( 'page', $_GET ) ? $_GET['page'] : null;

		$exclude_from_pages = $notice->get_exclude_from_pages();
		$page_excluded      = $exclude_from_pages && in_array( $current_page, $exclude_from_pages, true );

		$restrict_to_pages = $notice->get_restrict_to_pages();
		$allow_this_page   = ! $restrict_to_pages || in_array( $current_page, $restrict_to_pages, true );

		$allow_by_callback = true;
		$display_callbacks = $notice->get_display_callbacks();
		if ( $display_callbacks ) {
			$allow_by_callback = false;
			foreach ( $display_callbacks as $callback ) {
				if ( call_user_func( $callback ) ) {
					$allow_by_callback = true;
					break;
				}
			}
		}

		return ! $page_excluded && $allow_this_page && $allow_by_callback;
	}

	/**
	 * @param WPML_Notice $notice
	 *
	 * @return string
	 */
	private function get_actions_html( WPML_Notice $notice ) {
		$actions_html = '';
		if ( $notice->get_actions() ) {
			/** @var WPML_Notice_Action $action */
			$actions_html .= '<div class="otgs-notice-actions">';
			foreach ( $notice->get_actions() as $action ) {
				$actions_html .= $this->get_action_html( $action );
			}

			$actions_html .= '</div>';

			return $actions_html;
		}

		return $actions_html;
	}

	private function sanitize_and_format_text( $text ) {
		$backticks_pattern = '|`(.*)`|U';
		preg_match_all( $backticks_pattern, $text, $matches );

		$sanitized_notice = $text;
		if ( 2 === count( $matches ) ) {
			$matches_to_sanitize = $matches[1];

			/** @var array $matches_to_sanitize */
			foreach ( $matches_to_sanitize as &$match_to_sanitize ) {
				$match_to_sanitize = '<pre>' . esc_html( $match_to_sanitize ) . '</pre>';
			}
			unset( $match_to_sanitize );

			$sanitized_notice = str_replace( $matches[0], $matches_to_sanitize, $sanitized_notice );
		}

		return stripslashes( $sanitized_notice );
	}

	/**
	 * @param null|string $localized_text
	 *
	 * @return string
	 */
	private function get_hide_html( $localized_text = null ) {
		$hide_html = '';
		$hide_html .= '<span class="otgs-notice-hide notice-hide"><span class="screen-reader-text">';
		if ( $localized_text ) {
			$hide_html .= esc_html( $localized_text );
		} else {
			$hide_html .= esc_html__( 'Hide this notice.', 'sitepress' );
		}
		$hide_html .= '</span></span>';

		return $hide_html;
	}

	/**
	 * @param null|string $localized_text
	 *
	 * @return string
	 */
	private function get_dismiss_html( $localized_text = null ) {
		$dismiss_html = '';
		$dismiss_html .= '<span class="otgs-notice-dismiss notice-dismiss">';
		$dismiss_html .= '<span class="screen-reader-text"><input class="otgs-notice-dismiss-check" type="checkbox" value="1" />';
		if ( $localized_text ) {
			$dismiss_html .= esc_html( $localized_text );
		} else {
			$dismiss_html .= esc_html__( 'Dismiss this notice.', 'sitepress' );
		}
		$dismiss_html .= '</span></span>';

		return $dismiss_html;
	}

	/**
	 * @param string|null $localized_text
	 *
	 * @return string
	 */
	private function get_collapse_html( $localized_text = null ) {
		$hide_html = '<span class="otgs-notice-collapse-hide"><span class="screen-reader-text">';
		if ( $localized_text ) {
			$hide_html .= esc_html( $localized_text );
		} else {
			$hide_html .= esc_html__( 'Hide this notice.', 'sitepress' );
		}
		$hide_html .= '</span></span>';

		return $hide_html;
	}

	/**
	 * @param WPML_Notice $notice
	 * @param string|null $localized_text
	 *
	 * @return string
	 */
	private function get_collapsed_html( WPML_Notice $notice, $localized_text = null ) {
		$content = '
			<div class="otgs-notice-collapsed-text">
				<p>%s 
					<span class="otgs-notice-collapse-show notice-collapse"><span class="screen-reader-text">
					%s
					</span></span>
				</p>
			</div>
			<div class="otgs-notice-collapse-text">
				%s
			</div>
		';

		$content = sprintf(
			$content,
			$notice->get_collapsed_text(),
			$localized_text ? esc_html( $localized_text ) : esc_html__( 'Show this notice.', 'sitepress' ),
			$notice->get_text()
		);

		return $content;
	}

	/**
	 * @param WPML_Notice_Action $action
	 *
	 * @return string
	 */
	private function get_action_html( $action ) {
		$action_html = '';
		if ( $action->can_hide() ) {
			$action_html .= $this->get_hide_html( $action->get_text() );
			$this->hide_html_added = true;
		} elseif ( $action->can_dismiss() ) {
			$action_html .= $this->get_dismiss_html( $action->get_text() );
			$this->dismiss_html_added = true;
		} else {
			if ( $action->get_url() ) {
				$action_html .= $this->get_action_anchor( $action );;
			} else {
				$action_html .= $action->get_text();
			}
		}

		return $action_html;
	}

	/**
	 * @param WPML_Notice_Action $action
	 *
	 * @return string
	 */
	private function get_action_anchor( WPML_Notice_Action $action ) {
		$action_url = '<a href="' . esc_url_raw( $action->get_url() ) . '"';

		$action_url_classes = array( 'notice-action' );
		if ( $action->must_display_as_button() ) {
			$button_style = 'button-secondary';
			if ( is_string( $action->must_display_as_button() ) ) {
				$button_style = $action->must_display_as_button();
			}
			$action_url_classes[] = esc_attr( $button_style );
			$action_url_classes[] = 'notice-action-' . esc_attr( $button_style );
		} else {
			$action_url_classes[] = 'notice-action-link';
		}
		$action_url .= ' class="' . implode( ' ', $action_url_classes ) . '"';

		if ( $action->get_group_to_dismiss() ) {
			$action_url .= ' data-dismiss-group="' . esc_attr( $action->get_group_to_dismiss() ) . '"';
		}
		if ( $action->get_js_callback() ) {
			$action_url .= ' data-js-callback="' . esc_attr( $action->get_js_callback() ) . '"';
		}

		$action_url .= $this->get_data_nonce_attribute();
		$action_url .= '>';
		$action_url .= $action->get_text();
		$action_url .= '</a>';

		return $action_url;
	}

	/**
	 * @return string
	 */
	private function get_data_nonce_attribute() {
		return ' data-nonce="' . wp_create_nonce( WPML_Notices::NONCE_NAME ) . '"';
	}
}
