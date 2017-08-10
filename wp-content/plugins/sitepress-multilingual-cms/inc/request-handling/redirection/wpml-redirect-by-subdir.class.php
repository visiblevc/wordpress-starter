<?php

class WPML_Redirect_By_Subdir extends WPML_Redirection {

	/**
	 * @return bool|string
	 */
	public function get_redirect_target() {

		return $this->redirect_hidden_home();
	}
}