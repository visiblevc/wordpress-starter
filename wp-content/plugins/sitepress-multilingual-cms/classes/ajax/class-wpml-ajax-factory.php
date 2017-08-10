<?php

abstract class WPML_Ajax_Factory {

	public function add_route( WPML_Ajax_Route $route ) {
		$class_names = $this->get_class_names();
		foreach ( $class_names as $class_name ) {
			$route->add( $class_name );
		}
	}

	abstract function get_class_names();
	abstract function create( $class_name );
}