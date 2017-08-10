<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Notice_Action {
	private $dismiss;
	private $display_as_button;
	private $hide;
	private $text;
	private $url;
	private $group_to_dismiss;
	private $js_callback;
	private $dismiss_different_text;

	/**
	 * WPML_Admin_Notice_Action constructor.
	 *
	 * @param string      $text
	 * @param string      $url
	 * @param bool        $dismiss
	 * @param bool        $hide
	 * @param bool|string $display_as_button
	 * @param bool        $dismiss_different_text
	 */
	public function __construct( $text, $url = '#', $dismiss = false, $hide = false, $display_as_button = false, $dismiss_different_text = true ) {
		$this->text                   = $text;
		$this->url                    = $url;
		$this->dismiss                = $dismiss;
		$this->hide                   = $hide;
		$this->display_as_button      = $display_as_button;
		$this->dismiss_different_text = $dismiss_different_text;
	}

	public function get_text() {
		return $this->text;
	}

	public function get_url() {
		return $this->url;
	}

	public function can_dismiss() {
		return $this->dismiss;
	}

	public function can_dismiss_different_text() {
		return $this->dismiss_different_text;
	}

	public function can_hide() {
		return $this->hide;
	}

	public function must_display_as_button() {
		return $this->display_as_button;
	}

	public function set_group_to_dismiss( $group_name ) {
		$this->group_to_dismiss = $group_name;
	}

	public function get_group_to_dismiss() {
		return $this->group_to_dismiss;
	}

	public function set_js_callback( $js_callback ) {
		$this->js_callback = $js_callback;
	}

	public function get_js_callback() {
		return $this->js_callback;
	}
}
