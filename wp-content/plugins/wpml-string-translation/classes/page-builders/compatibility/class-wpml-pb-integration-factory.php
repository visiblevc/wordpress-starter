<?php

class WPML_PB_Integration_Factory implements IWPML_PB_Integration_Factory {

	public function create( $class_name ) {
		switch( $class_name ) {
			case 'WPML_Beaver_Builder_Integration':
				$nodes = new WPML_Beaver_Builder_Translatable_Nodes();
				$register_strings = new WPML_Beaver_Builder_Register_Strings( $nodes );
				$update_translation = new WPML_Beaver_Builder_Update_Translation( $nodes );
				return new WPML_Beaver_Builder_Integration( $register_strings, $update_translation );

			default:
				return null;
		}
	}
}