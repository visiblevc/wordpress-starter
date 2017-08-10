<?php

/**
 * Listener for dismissing a message.
 */
class Whip_WPMessageDismissListener implements Whip_Listener {

	const ACTION_NAME = 'whip_dismiss';

	/**
	 * @var Whip_MessageDismisser
	 */
	protected $dismisser;

	/**
	 * Sets the dismisser attribute.
	 *
	 * @param Whip_MessageDismisser $dismisser The object for dismissing a message.
	 */
	public function __construct( Whip_MessageDismisser $dismisser ) {
		$this->dismisser = $dismisser;
	}

	/**
	 * Listens to a GET request to fetch the required attributes.
	 *
	 * @return void
	 */
	public function listen() {
		$action = filter_input( INPUT_GET, 'action' );
		$nonce  = filter_input( INPUT_GET, 'nonce' );

		if ( $action === self::ACTION_NAME && wp_verify_nonce( $nonce, self::ACTION_NAME ) ) {
			$this->dismisser->dismiss();
		}
	}

	/**
	 * Creates an url for dismissing the notice.
	 *
	 * @return string The url for dismissing the message.
	 */
	public function getDismissURL() {
		return sprintf(
			admin_url( 'index.php?action=%1$s&nonce=%2$s' ),
			self::ACTION_NAME,
			wp_create_nonce( self::ACTION_NAME )
		);
	}

}
