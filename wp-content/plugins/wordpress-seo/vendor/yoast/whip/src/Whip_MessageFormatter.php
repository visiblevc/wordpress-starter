<?php

/**
 * A helper class to format messages
 */
final class Whip_MessageFormatter {

	/**
	 * Wraps a piece of text in HTML strong tags
	 *
	 * @param string $toWrap The text to wrap.
	 * @return string The wrapped text.
	 */
	public static function strong( $toWrap ) {
		return '<strong>' . $toWrap . '</strong>';
	}

	/**
	 * Wraps a piece of text in HTML p tags
	 *
	 * @param string $toWrap The text to wrap.
	 * @return string The wrapped text.
	 */
	public static function paragraph( $toWrap ) {
		return '<p>' . $toWrap . '</p>';
	}

	/**
	 * Wraps a piece of text in HTML p and strong tags
	 *
	 * @param string $toWrap The text to wrap.
	 * @return string The wrapped text.
	 */
	public static function strongParagraph( $toWrap ) {
		return self::paragraph( self::strong( $toWrap ) );
	}
}
