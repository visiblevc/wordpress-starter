<?php

/**
 * Class Whip_Message
 */
class Whip_NullMessage implements Whip_Message {
	/**
	 * @return string
	 */
	public function body() {
		return '';
	}
}
