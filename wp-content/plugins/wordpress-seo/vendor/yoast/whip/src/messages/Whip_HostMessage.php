<?php

/**
 * Class Whip_HostMessage
 */
class Whip_HostMessage implements Whip_Message {

	/**
	 * @var string
	 */
	private $textdomain;

	/**
	 * @var string
	 */
	private $messageKey;

	/**
	 * @var string
	 */
	private $filterKey;

	/**
	 * Whip_Message constructor.
	 *
	 * @param string $messageKey The environment key to use to retrieve the message from.
	 * @param string $textdomain The text domain to use for translations.
	 */
	public function __construct( $messageKey, $textdomain ) {
		$this->textdomain = $textdomain;
		$this->messageKey = $messageKey;
	}

	/**
	 * Renders the message body.
	 *
	 * @return string The message body.
	 */
	public function body() {
		$message = array();

		$message[] = Whip_MessageFormatter::strong( $this->title() ) . '<br />';
		$message[] = Whip_MessageFormatter::paragraph( Whip_Host::message( $this->messageKey, $this->filterKey ) );

		return implode( $message, "\n" );
	}

	/**
	 * Renders the message title.
	 *
	 * @return string The message title.
	 */
	public function title() {
		return sprintf( __( 'A message from %1$s', $this->textdomain ), Whip_Host::name() );
	}
}
