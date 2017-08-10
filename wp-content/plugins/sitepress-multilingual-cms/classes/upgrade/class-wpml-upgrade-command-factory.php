<?php

class WPML_Upgrade_Command_Factory {

	/** @var SitePress $sitepress */
	protected $sitepress;
	/** @var wpdb $wpdb */
	protected $wpdb;

	/**
	 * @param wpdb      $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	/**
	 * @param string $class_name
	 * @param array  $args
	 *
	 * @return IWPML_Upgrade_Command
	 */
	public function create( $class_name, $args ) {
		return new $class_name( $args );
	}
}
