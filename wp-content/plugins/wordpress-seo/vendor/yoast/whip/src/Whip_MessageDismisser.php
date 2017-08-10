<?php

/**
 * A class to dismiss messages.
 */
class Whip_MessageDismisser {

	/** @var Whip_DismissStorage */
	protected $storage;

	/** @var string */
	protected $currentTime;

	/** @var int */
	protected $threshold;

	/**
	 * Whip_MessageDismisser constructor.
	 *
	 * @param int                 $currentTime The current time.
	 * @param int                 $threshold   The number of seconds the message will be dismissed.
	 * @param Whip_DismissStorage $storage     Storage object to manage the dismissal state.
	 */
	public function __construct( $currentTime, $threshold, Whip_DismissStorage $storage ) {
		$this->currentTime = $currentTime;
		$this->threshold   = $threshold;
		$this->storage     = $storage;
	}

	/**
	 * Saves the version number to the storage to indicate the message as being dismissed.
	 */
	public function dismiss() {
		$this->storage->set( $this->currentTime );
	}

	/**
	 * Checks if the current time is lower than the stored time extended by the threshold.
	 *
	 * @return bool True when current time is lower than stored value + threshold.
	 */
	public function isDismissed() {
		return ( $this->currentTime <= ( $this->storage->get() + $this->threshold ) );
	}
}
