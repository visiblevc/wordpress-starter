<?php
class WPML_Translation_Proxy_API {
	public function get_current_service_name() {
		return TranslationProxy::get_current_service_name();
	}

	public function has_preferred_translation_service() {
		return TranslationProxy::has_preferred_translation_service();
	}
}