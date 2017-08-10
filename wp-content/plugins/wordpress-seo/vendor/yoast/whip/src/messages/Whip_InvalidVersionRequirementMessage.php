<?php

/**
 * Class Whip_InvalidVersionMessage
 */
class Whip_InvalidVersionRequirementMessage implements Whip_Message {

	private $requirement;
	/**
	 * @var
	 */
	private $detected;

	/**
	 * Whip_InvalidVersionRequirementMessage constructor.
	 *
	 * @param Whip_Requirement $requirement
	 * @param                  $detected
	 */
	public function __construct( Whip_VersionRequirement $requirement, $detected )
	{
	    $this->requirement = $requirement;
		$this->detected = $detected;
	}

	/**
	 * @return string
	 */
	public function body() {
		return sprintf(
			'Invalid version detected for %s. Found %s but expected %s.',
			$this->requirement->component(),
			$this->detected,
			$this->requirement->version()
		);
	}
}
