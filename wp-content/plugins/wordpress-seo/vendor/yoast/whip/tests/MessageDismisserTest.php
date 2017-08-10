<?php

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 60 * 60 * 24 * 7 );
}


class Whip_DismissStorageMock implements Whip_DismissStorage {

	/** @var int */
	protected $dismissed = 0;

	/**
	 * Saves the value.
	 *
	 * @param int $dismissedValue The value to save.
	 *
	 * @return boolean
	 */
	public function set( $dismissedValue ) {
		$this->dismissed = $dismissedValue;

		return true;
	}

	/**
	 * Returns the value.
	 *
	 * @return int
	 */
	public function get() {
		return $this->dismissed;
	}
}

class MessageDismisserTest extends PHPUnit_Framework_TestCase {

	/**
	 * @covers Whip_MessageDismisser::__construct()
	 * @covers Whip_MessageDismisser::dismiss()
	 */
	public function testDismiss() {
		$currentTime = time();
		$storage     = new Whip_DismissStorageMock();
		$dismisser   = new Whip_MessageDismisser( $currentTime, WEEK_IN_SECONDS * 4, $storage );

		$this->assertEquals( 0, $storage->get() );

		$dismisser->dismiss();

		$this->assertEquals( $currentTime, $storage->get() );
	}

	/**
	 * @dataProvider versionNumbersProvider
	 *
	 * @param int $savedTime   The saved time.
	 * @param int $currentTime The current time.
	 * @param bool   $expected The expected value.
	 *
	 * @covers Whip_MessageDismisser::__construct()
	 * @covers Whip_MessageDismisser::isDismissed()
	 */
	public function testIsDismissibleWithVersions( $savedTime, $currentTime, $expected ) {
		$storage = new Whip_DismissStorageMock();
		$storage->set( $savedTime );
		$dismisser = new Whip_MessageDismisser( $currentTime, WEEK_IN_SECONDS * 4, $storage );

		$this->assertEquals( $expected, $dismisser->isDismissed() );
	}

	/**
	 * Provides array with test values.
	 *
	 * @return array
	 */
	public function versionNumbersProvider() {
		return array(
			array( strtotime( "-2weeks" ), time(), true ),
			array( strtotime( "-4weeks" ), time(), true ),
			array( strtotime( "-6weeks" ), time(), false ),
			array( time(), time(), true ),
		);
	}

}
