<?php

class WPML_Ajax_Route {

	const ACTION_PREFIX = 'wp_ajax_';
	const ACTION_PREFIX_LENGTH = 8;

	/** @var  WPML_Ajax_Factory $factory */
	private $factory;

	public function __construct( WPML_Ajax_Factory $factory ) {
		$this->factory = $factory;
		$this->factory->add_route( $this );
	}

	public function add( $class_name ) {
		add_action( self::ACTION_PREFIX . $class_name, array( $this, 'do_ajax' ) );
	}

	public function do_ajax() {
		$action = current_filter();
		$class_name = substr( $action, self::ACTION_PREFIX_LENGTH );
		$ajax_handler = $this->factory->create( $class_name );
		$ajax_response = $ajax_handler->run();

		$ajax_response->send_json();
	}
}