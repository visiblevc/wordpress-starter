<?php

/**
 * Represents the WordPress option for saving the dismissed messages.
 */
class Whip_WPDismissOption implements Whip_DismissStorage {

	/** @var string */
	protected $optionName = 'whip_dismiss_timestamp';

	/**
	 * Saves the value to the options.
	 *
	 * @param int $dismissedValue The value to save.
	 *
	 * @return bool True when successful.
	 */
	public function set( $dismissedValue ) {
		return update_option( $this->optionName, $dismissedValue );
	}

	/**
	 * Returns the value of the whip_dismissed option.
	 *
	 * @return int Returns the value of the option or an empty string when not set.
	 */
	public function get() {
		$dismissedOption = get_option( $this->optionName );
		if ( ! $dismissedOption ) {
			return 0;
		}

		return (int) $dismissedOption;
	}

}
