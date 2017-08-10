<?php

/**
 * Class WPML_Links_Fixed_Status
 *
 * @package wpml-translation-management
 */
abstract class WPML_Links_Fixed_Status {

	abstract public function set( $status );
	
	abstract public function are_links_fixed();
	
}