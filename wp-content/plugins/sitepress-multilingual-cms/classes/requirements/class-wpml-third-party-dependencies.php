<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Third_Party_Dependencies {
	private $integrations;
	private $requirements;

	/**
	 * WPML_Third_Party_Dependencies constructor.
	 *
	 * @param WPML_Integrations $integrations
	 * @param WPML_Requirements $requirements
	 */
	public function __construct( WPML_Integrations $integrations, WPML_Requirements $requirements ) {
		$this->integrations = $integrations;
		$this->requirements = $requirements;
	}

	public function get_issues() {
		$issues = array(
			'causes'       => array(),
			'requirements' => array(),
		);

		$components = $this->integrations->get_results();
		foreach ( (array) $components as $slug => $component_data ) {
			$issue = $this->get_issue( $component_data, $slug );
			if ( $issue ) {
				$issues['causes'][] = $issue['cause'];

				foreach ( $issue['requirements'] as $requirement ) {
					$issues['requirements'][] = $requirement;
				}
			}
		}

		sort( $issues['causes'] );
		sort( $issues['requirements'] );

		$issues['causes']       = array_unique( $issues['causes'], SORT_REGULAR );
		$issues['requirements'] = array_unique( $issues['requirements'], SORT_REGULAR );

		if ( ! $issues || ! $issues['causes'] || ! $issues['requirements'] ) {
			return array();
		}

		return $issues;
	}

	private function get_issue( $component_data, $slug ) {
		$requirements = $this->requirements->get_requirements( $component_data['type'], $slug );
		if ( ! $requirements ) {
			return null;
		}

		return array(
			'cause'        => $component_data,
			'requirements' => $requirements,
		);
	}
}
