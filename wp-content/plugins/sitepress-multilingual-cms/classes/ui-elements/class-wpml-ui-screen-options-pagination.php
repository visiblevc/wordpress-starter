<?php

/**
 * @package wpml-core
 */
class WPML_UI_Screen_Options_Pagination {
	/**
	 * @var string $option_name
	 */
	private $option_name;
	/**
	 * @var int $default_per_page
	 */
	private $default_per_page;

	/**
	 * WPML_UI_Screen_Options_Pagination constructor.
	 *
	 * @param string $option_name
	 * @param int $default_per_page
	 */
	public function __construct( $option_name, $default_per_page ) {
		$this->option_name      = $option_name;
		$this->default_per_page = $default_per_page;
	}
	
	public function init_hooks() {
		add_action( 'admin_head', array( $this, 'add_screen_options' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_options_filter' ), 10, 3 );
	}
	
	public function add_screen_options() {
		add_screen_option( 'per_page', array( 'default' => $this->default_per_page, 'option' => $this->option_name ) );
	}
	
	public function set_screen_options_filter( $value, $option, $set_value ) {

		return $option === $this->option_name ? $set_value : $value;
	}
	
	public function get_items_per_page() {
		$page_size = (int) get_user_option( $this->option_name );
		if ( ! $page_size ) {
			$page_size = $this->default_per_page;
		}
		
		return $page_size;
	}
}
