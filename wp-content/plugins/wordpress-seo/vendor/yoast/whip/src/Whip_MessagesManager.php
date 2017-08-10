<?php

/**
 * Manages messages using a global to prevent duplicate messages.
 */
class Whip_MessagesManager {

	/**
	 * Whip_MessagesManager constructor.
	 */
	public function __construct()
	{
		if ( ! array_key_exists( 'whip_messages', $GLOBALS ) ) {
			$GLOBALS['whip_messages'] = array();
		}
	}

	/**
	 * Adds a message to the Messages Manager.
	 *
	 * @param Whip_Message $message The message to add.
	 */
	public function addMessage( Whip_Message $message ) {
		$whipVersion = require dirname( __FILE__ ) . '/configs/version.php';

		$GLOBALS[ 'whip_messages' ][$whipVersion] = $message;
	}

	/**
	 * Determines whether or not there are messages available.
	 *
	 * @return bool Whether or not there are messages available.
	 */
	public function hasMessages() {
		return isset( $GLOBALS['whip_messages'] ) && count( $GLOBALS['whip_messages'] ) > 0;
	}

	/**
	 * Lists the messages that are currently available.
	 *
	 * @return array The messages that are currently set.
	 */
	public function listMessages() {
		return $GLOBALS[ 'whip_messages' ];
	}

	/**
	 * Deletes all messages.
	 */
	public function deleteMessages() {
		unset( $GLOBALS[ 'whip_messages' ] );
	}

	/**
	 * Gets the latest message.
	 *
	 * @return Whip_Message The message. Returns a NullMessage if none is found.
	 */
	public function getLatestMessage() {
		if ( ! $this->hasMessages() ) {
			return new Whip_NullMessage();
		}

		$messages = $this->sortByVersion( $this->listMessages() );

		$this->deleteMessages();

		return array_pop( $messages );
	}

	/**
	 * Sorts the list of messages based on the version number.
	 *
	 * @param array $messages The list of messages to sort.
	 *
	 * @return array The sorted list of messages.
	 */
	private function sortByVersion( array $messages ) {
		uksort( $messages, 'version_compare' );

		return $messages;
	}
}
