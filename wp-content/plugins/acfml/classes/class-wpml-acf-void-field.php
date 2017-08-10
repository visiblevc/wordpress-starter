<?php

class WPML_ACF_Void_Field extends WPML_ACF_Field {
	public function convert_ids() {
		return $this->meta_value;
	}
}
