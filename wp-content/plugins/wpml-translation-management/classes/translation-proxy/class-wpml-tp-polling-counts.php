<?php

class WPML_TP_Polling_Counts {

	/**
	 * @var int $cancelled
	 */
	private $cancelled;

	/**
	 * @var int $completed
	 */
	private $completed;

	/**
	 * WPML_TP_Polling_Counts constructor.
	 *
	 * @param int $completed
	 * @param int $cancelled
	 */
	public function __construct( $completed, $cancelled ) {
		if ( ! ( is_int( $completed ) && is_int( $cancelled ) ) ) {
			throw  new InvalidArgumentException( 'Counts need to be integers! I received completed: ' . serialize( $completed ) . ' cancelled: ' . serialize( $cancelled ) );
		}
		$this->completed = $completed;
		$this->cancelled = $cancelled;
	}

	/**
	 * Increments cancelled job count by 1
	 */
	public function cancel_job() {
		$this->cancelled ++;
	}

	/**
	 * Increments completed job count by 1
	 */
	public function complete_job() {
		$this->completed ++;
	}

	/**
	 * @return int number of cancelled jobs
	 */
	public function cancelled() {

		return $this->cancelled;
	}

	/**
	 * @return int number of completed jobs
	 */
	public function completed() {

		return $this->completed;
	}
}