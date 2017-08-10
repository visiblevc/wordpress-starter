<?php

/**
 * A message presenter to show a WordPress notice.
 */
class Whip_WPMessagePresenter implements Whip_MessagePresenter {

	/** @var string The string to show to dismiss the message. */
	private $dismissMessage;

	/** @var Whip_Message The message to be displayed */
	private $message;

	/** @var Whip_MessageDismisser */
	private $dismisser;

	/**
	 * Whip_WPMessagePresenter constructor.
	 *
	 * @param Whip_Message          $message        The message to use in the presenter.
	 * @param Whip_MessageDismisser $dismisser      Dismisser object.
	 * @param string                $dismissMessage The copy to show to dismiss the message.
	 */
	public function __construct( Whip_Message $message, Whip_MessageDismisser $dismisser, $dismissMessage ) {
		$this->message        = $message;
		$this->dismisser      = $dismisser;
		$this->dismissMessage = $dismissMessage;
	}

	/**
	 * Registers hooks to WordPress. This is a separate function so you can
	 * control when the hooks are registered.
	 *
	 */
	public function register_hooks() {
		global $whip_admin_notices_added;

		if ( null === $whip_admin_notices_added || ! $whip_admin_notices_added ) {
			add_action( 'admin_notices', array( $this, 'renderMessage' ) );
			$whip_admin_notices_added = true;
		}
	}

	/**
	 * Renders the messages present in the global to notices.
	 */
	public function renderMessage() {
		$dismissListener = new Whip_WPMessageDismissListener( $this->dismisser );
		$dismissListener->listen();

		if ( $this->dismisser->isDismissed() ) {
			return;
		}

		$dismissButton = sprintf(
			'<a href="%2$s">%1$s</a>',
			$this->dismissMessage,
			$dismissListener->getDismissURL()
		);

		printf( '<div class="error"><p>%1$s</p><p>%2$s</p></div>', $this->kses( $this->message->body() ), $dismissButton );
	}

	/**
	 * Removes content from the message that we don't want to show.
	 *
	 * @param string $message The message to clean.
	 *
	 * @return string The cleaned message.
	 */
	public function kses( $message ) {
		return wp_kses( $message, array(
			'a'      => array(
				'href'   => true,
				'target' => true,
			),
			'strong' => true,
			'p'      => true,
			'ul'     => true,
			'li'     => true,
		) );
	}
}
