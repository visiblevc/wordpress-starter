<?php

class VersionRequirementTest extends PHPUnit_Framework_TestCase {
	public function testNameAndVersionAreNotEmpty() {
		$requirement = new Whip_VersionRequirement( 'php', '5.2' );

		$this->assertNotEmpty($requirement->component());
		$this->assertNotEmpty($requirement->version());
	}

	/**
	 * @expectedException Whip_EmptyProperty
	 */
	public function testComponentCannotBeEmpty() {
		new Whip_VersionRequirement( '', '5.2' );
	}

	/**
	 * @expectedException Whip_EmptyProperty
	 */
	public function testVersionCannotBeEmpty() {
		new Whip_VersionRequirement( 'php', '' );
	}

	/**
	 * @expectedException Whip_InvalidType
	 */
	public function testComponentMustBeString() {
		new Whip_VersionRequirement( 123, '5.2' );
	}

	/**
	 * @expectedException Whip_InvalidType
	 */
	public function testVersionMustBeString() {
		new Whip_VersionRequirement( 'php', 123 );
	}

	/**
	 * @expectedException Whip_EmptyProperty
	 */
	public function testOperatorCannotBeEmpty() {
		new Whip_VersionRequirement( 'php', '5.6', '' );
	}

	/**
	 * @expectedException Whip_InvalidType
	 */
	public function testOperatorMustBeString() {
		new Whip_VersionRequirement( 'php', '5.2', 6 );
	}

	/**
	 * @expectedException Whip_InvalidOperatorType
	 */
	public function testOperatorMustBeValid() {
		new Whip_VersionRequirement( 'php', '5.2', '->' );
	}

	public function testGettingComponentProperties() {
		$requirement = new Whip_VersionRequirement( 'php', '5.6' );

		$this->assertEquals( 'php', $requirement->component() );
		$this->assertEquals( '5.6', $requirement->version() );
		$this->assertEquals( '=',   $requirement->operator() );
	}

	/**
	 * @dataProvider dataFromCompareString
	 */
	public function testFromCompareString( $expectation, $component, $compareString ) {
		$requirement = Whip_VersionRequirement::fromCompareString( $component, $compareString );

		$this->assertEquals( $expectation[0], $requirement->component() );
		$this->assertEquals( $expectation[1], $requirement->version() );
		$this->assertEquals( $expectation[2], $requirement->operator() );
	}

	public function dataFromCompareString() {
		return array(
			array( array( 'php', '5.5', '>' ), 'php', '>5.5' ),
			array( array( 'php', '5.5', '>=' ), 'php', '>=5.5' ),
			array( array( 'php', '5.5', '<' ), 'php', '<5.5' ),
			array( array( 'php', '5.5', '<=' ), 'php', '<=5.5' ),
			array( array( 'php', '7.3', '>' ), 'php', '>7.3' ),
			array( array( 'php', '7.3', '>=' ), 'php', '>=7.3' ),
			array( array( 'php', '7.3', '<' ), 'php', '<7.3' ),
			array( array( 'php', '7.3', '<=' ), 'php', '<=7.3' ),
		);
	}

	/**
	 * @expectedException Whip_InvalidVersionComparisonString
	 */
	public function testFromCompareStringException() {
		Whip_VersionRequirement::fromCompareString( 'php', '> 2.3' );
	}
}

