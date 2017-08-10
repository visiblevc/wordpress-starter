<?php

/**
 * @deprecated This file should be removed in WPML 3.8.0: it has been kept to allow error-less updates from pre 3.6.2.
 * @since 3.6.2
 * @author OnTheGo Systems
 */
class WPML_Notice {
	private $display_callbacks = array();
	private $id;
	private $text;
	private $collapsed_text;
	private $group             = 'default';

	private $actions            = array();
	/**
	 * @see \WPML_Notice::set_css_class_types
	 * @var array
	 */
	private $css_class_types = array();
	private $css_classes        = array();
	private $dismissible        = false;
	private $exclude_from_pages = array();
	private $hideable           = false;
	private $collapsable = false;
	private $restrict_to_pages  = array();

	private $default_group_name = 'default';

	/**
	 * WPML_Admin_Notification constructor.
	 *
	 * @param int    $id
	 * @param string $text
	 * @param string $group
	 */
	public function __construct( $id, $text, $group = 'default' ) {
		$this->id    = $id;
		$this->text  = $text;
		$this->group = $group ? $group : $this->default_group_name;
	}

	public function add_action( WPML_Notice_Action $action ) {
		$this->actions[] = $action;

		if ( $action->can_dismiss() ) {
			$this->dismissible = true;
		}
		if ( $action->can_hide() ) {
			$this->hideable = true;
		}
	}

	public function add_exclude_from_page( $page ) {
		$this->exclude_from_pages[] = $page;
	}

	public function add_restrict_to_page( $page ) {
		$this->restrict_to_pages[] = $page;
	}

	public function can_be_dismissed() {
		return $this->dismissible;
	}

	public function can_be_hidden() {
		return $this->hideable;
	}

	/**
	 * @return bool
	 */
	public function can_be_collapsed() {
		return $this->collapsable;
	}

	public function add_display_callback( $callback ) {
		if ( ! is_callable( $callback ) ) {
			throw new UnexpectedValueException( '\WPML_Notice::add_display_callback expects a callable', 1 );
		}
		$this->display_callbacks[] = $callback;
	}

	public function get_display_callbacks() {
		return $this->display_callbacks;
	}

	public function get_actions() {
		return $this->actions;
	}

	public function get_css_classes() {
		return $this->css_classes;
	}

	/**
	 * @param string|array $css_classes
	 */
	public function set_css_classes( $css_classes ) {
		if ( ! is_array( $css_classes ) ) {
			$css_classes = explode( ' ', $css_classes );
		}
		$this->css_classes = $css_classes;
	}

	public function get_exclude_from_pages() {
		return $this->exclude_from_pages;
	}

	/**
	 * @return string
	 */
	public function get_group() {
		return $this->group;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	public function get_restrict_to_pages() {
		return $this->restrict_to_pages;
	}

	/**
	 * @return string
	 */
	public function get_text() {
		return $this->text;
	}

	public function get_css_class_types() {
		return $this->css_class_types;
	}

	/**
	 * @return string
	 */
	public function get_collapsed_text() {
		return $this->collapsed_text;
	}

	/**
	 * Use this to set the look of the notice.
	 * WordPress recognize these values:
	 * - notice-error
	 * - notice-warning
	 * - notice-success
	 * - notice-info
	 * You can use the above values with or without the "notice-" prefix:
	 * the prefix will be added automatically in the HTML, if missing.
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices for more details
	 *
	 * @param string|array $types Accepts either a space separated values string, or an array of values.
	 */
	public function set_css_class_types( $types ) {
		if ( ! is_array( $types ) ) {
			$types = explode( ' ', $types );
		}
		$this->css_class_types = $types;
	}

	/**
	 * @param bool $dismissible
	 */
	public function set_dismissible( $dismissible ) {
		$this->dismissible = $dismissible;
	}

	public function set_exclude_from_pages( array $pages ) {
		$this->exclude_from_pages = $pages;
	}

	/**
	 * @param bool $hideable
	 */
	public function set_hideable( $hideable ) {
		$this->hideable = $hideable;
	}

	/**
	 * @param bool $collapsable
	 */
	public function set_collapsable( $collapsable ) {
		$this->collapsable = $collapsable;
	}

	/**
	 * @param string $collapsed_text
	 */
	public function set_collapsed_text( $collapsed_text ) {
		$this->collapsed_text = $collapsed_text;
	}

	public function set_restrict_to_pages( array $pages ) {
		$this->restrict_to_pages = $pages;
	}
}
