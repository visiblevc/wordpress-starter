<?php

interface IWPML_String_Translation_Job_Notification {
	/**
	 * @param string $source_lang
	 * @param string $target_lang
	 * @param int|null $translator_id
	 */
	public function notify( $source_lang, $target_lang, $translator_id = null );
}