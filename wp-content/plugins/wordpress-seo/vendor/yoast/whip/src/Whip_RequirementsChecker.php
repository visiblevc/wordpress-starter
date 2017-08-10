<?php

/**
 * Main controller class to require a certain version of software.
 */
class Whip_RequirementsChecker {

	/**
	 * @var array
	 */
	private $requirements;

	/**
	 * @var string
	 */
	private $textdomain;

	/**
	 * Whip_RequirementsChecker constructor.
	 *
	 * @param array  $configuration The configuration to check.
	 * @param string $textdomain The text domain to use for translations.
	 */
	public function __construct( $configuration = array(), $textdomain = 'wordpress-seo' ) {
		$this->requirements     = array();
		$this->configuration    = new Whip_Configuration( $configuration );
		$this->messageMananger  = new Whip_MessagesManager();
		$this->textdomain       = $textdomain;
	}

	/**
	 * Adds a requirement to the list of requirements if it doesn't already exist.
	 *
	 * @param Whip_Requirement $requirement The requirement to add.
	 */
	public function addRequirement( Whip_Requirement $requirement ) {
		// Only allow unique entries to ensure we're not checking specific combinations multiple times
		if ( $this->requirementExistsForComponent( $requirement->component() ) ) {
			return;
		}

		$this->requirements[] = $requirement;
	}

	/**
	 * Determines whether or not there are requirements available.
	 *
	 * @return bool Whether or not there are requirements.
	 */
	public function hasRequirements() {
		return $this->totalRequirements() > 0;
	}

	/**
	 * Gets the total amount of requirements.
	 *
	 * @return int The total amount of requirements.
	 */
	public function totalRequirements() {
		return count( $this->requirements );
	}

	/**
	 * Determines whether or not a requirement exists for a particular component.
	 *
	 * @param string $component The component to check for.
	 *
	 * @return bool Whether or not the component has a requirement defined.
	 */
	public function requirementExistsForComponent( $component ) {
		foreach ( $this->requirements as $requirement ) {
			if ( $requirement->component() === $component ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines whether a requirement has been fulfilled.
	 *
	 * @param Whip_Requirement $requirement The requirement to check.
	 *
	 * @return bool Whether or not the requirement is fulfilled.
	 */
	private function requirementIsFulfilled( Whip_Requirement $requirement ) {
		$available_version = $this->configuration->configuredVersion( $requirement );
		$required_version = $requirement->version();

		if ( in_array( $requirement->operator(), array( '=', '==', '===' ), true ) ) {
			return -1 !== version_compare( $available_version, $required_version );
		}

		return version_compare( $available_version, $required_version, $requirement->operator() );
	}

	/**
	 * Checks if all requirements are fulfilled and adds a message to the message manager if necessary.
	 */
	public function check() {
		foreach ( $this->requirements as $requirement ) {
			// Match against config
			$requirement_fulfilled = $this->requirementIsFulfilled( $requirement );

			if ( $requirement_fulfilled ) {
				continue;
			}

			$this->addMissingRequirementMessage( $requirement );
		}
	}

	/**
	 * Adds a message to the message manager for requirements that cannot be fulfilled.
	 *
	 * @param Whip_Requirement $requirement The requirement that cannot be fulfilled.
	 */
	private function addMissingRequirementMessage( Whip_Requirement $requirement ) {
		switch ( $requirement->component() ) {
			case 'php':
				$this->messageMananger->addMessage(	new Whip_UpgradePhpMessage( $this->textdomain ) );
				break;
			default:
				$this->messageMananger->addMessage(	new Whip_InvalidVersionRequirementMessage( $requirement, $this->configuration->configuredVersion( $requirement ) ) );
				break;
		}
	}

	/**
	 * Determines whether or not there are messages available.
	 *
	 * @return bool Whether or not there are messages to display.
	 */
	public function hasMessages() {
		return $this->messageMananger->hasMessages();
	}

	/**
	 * Gets the most recent message from the message manager.
	 *
	 * @return Whip_Message The latest message.
	 */
	public function getMostRecentMessage() {
		return $this->messageMananger->getLatestMessage();
	}

}
