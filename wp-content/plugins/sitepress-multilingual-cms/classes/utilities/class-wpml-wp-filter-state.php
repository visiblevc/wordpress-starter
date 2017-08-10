<?php

/**
 * Class WPML_WP_Filter_State
 *
 * This class records the state of the WordPress $wp_filter and allows you to restore its state
 *
 * It's needed when are doing an action or filter and we call a function that causes the filter
 * to run again.
 *
 * eg.
 *
 * If you call wp_update_post while processing 'save_post' action it causes the 'save_post' action
 * to be run again and the current state of the filter is lost when returning from the wp_update_post call
 * This means that any other 'save_post' actions are not run
 *
 * NOTE: It's a bit of a hack because it manipulates the global $wp_filter. It should be save
 * because we have a unit test for it and so we should pickup any problems if WP changes the way the $wp_filter is
 * used or accessed.
 */

/**
 * NOTE: This is no longer needed for WP 4.7 and above as WP now handles the states correctly.
 * Check whether $wp_filter[ $key ] is an array or not to decide whether this is still needed or not.
 */

class WPML_WP_Filter_State {

	private $tag;
	private $pointer = null;

	/**
	 * @param string $tag     The name of the filter or action.
	 */
	public function __construct( $tag ) {
		global $wp_filter;

		$this->tag = $tag;
		if ( isset( $wp_filter[ $tag ] ) && is_array( $wp_filter[ $tag ] ) ) {
			$this->pointer = current( $wp_filter[ $tag ] );
		}
	}

	public function restore() {
		global $wp_filter;
		if ( $this->pointer && isset( $wp_filter[ $this->tag ] ) && is_array( $wp_filter[ $this->tag ] ) ) {
			reset( $wp_filter[ $this->tag ] );
			while( $this->pointer != current( $wp_filter[ $this->tag ] ) ) {
				if ( next( $wp_filter[ $this->tag ] ) === false ) {
					break;
				}
			}
		}
	}
}