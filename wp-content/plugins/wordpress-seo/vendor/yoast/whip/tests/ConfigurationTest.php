<?php

class ConfigurationTest extends PHPUnit_Framework_TestCase {
	/**
	 * @expectedException Whip_InvalidType
	 */
	public function testItThrowsAnErrorIfAFaultyConfigurationIsPassed() {
		$configuration = new Whip_Configuration( 'Invalid configuration' );
	}

	public function testItReturnsANegativeNumberIfRequirementCannotBeFound() {
		$configuration = new Whip_Configuration( array( 'php' => '5.6' ) );
		$requirement = $this->getMockBuilder( 'Whip_Requirement' )
							->setMethods( array( 'component' ) )
							->getMock();

		$requirement
			->expects( $this->any() )
			->method( 'component' )
			->will( $this->returnValue( 'mysql' ) );

		$this->assertEquals( -1, $configuration->configuredVersion( $requirement ) );
	}

	public function testItReturnsAnEntryIfRequirementIsFound() {
		$configuration = new Whip_Configuration( array( 'php' => '5.6' ) );
		$requirement = $this->getMockBuilder( 'Whip_Requirement' )
		                    ->setMethods( array( 'component' ) )
		                    ->getMock();

		$requirement
			->expects( $this->any() )
			->method( 'component' )
			->will( $this->returnValue( 'php' ) );

		$this->assertEquals( '5.6', $configuration->configuredVersion( $requirement ) );
	}

	public function testIfRequirementIsConfigured() {
		$configuration = new Whip_Configuration( array( 'php' => '5.6' ) );
		$requirement = $this->getMockBuilder( 'Whip_Requirement' )
		                    ->setMethods( array( 'component' ) )
		                    ->getMock();

		$requirement
			->expects( $this->any() )
			->method( 'component' )
			->will( $this->returnValue( 'php' ) );

		$falseRequirement = $this->getMockBuilder( 'Whip_Requirement' )
		                         ->setMethods( array( 'component' ) )
		                         ->getMock();

		$falseRequirement
			->expects( $this->any() )
			->method( 'component' )
			->will( $this->returnValue( 'mysql' ) );

		$this->assertTrue( $configuration->hasRequirementConfigured( $requirement ) );
		$this->assertFalse( $configuration->hasRequirementConfigured( $falseRequirement ) );
	}
}
