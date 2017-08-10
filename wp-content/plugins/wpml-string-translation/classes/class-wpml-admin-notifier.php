<?php

class WPML_Admin_Notifier {

	public function display_instant_message( $message, $type = 'information', $class = false, $return = false, $fadeout = false ) {

		return ICL_AdminNotifier::display_instant_message( $message, $type, $class, $return, $fadeout );
	}
}