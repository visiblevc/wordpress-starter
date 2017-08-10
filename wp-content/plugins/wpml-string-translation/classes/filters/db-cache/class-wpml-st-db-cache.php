<?php

class WPML_ST_DB_Cache {
	/**
	 * @var IWPML_ST_Page_Translations_Persist
	 */
	private $translations_persist;

	/**
	 * @var WPML_ST_DB_Translation_Retrieve
	 */
	private $single_translation_retriever;

	/**
	 * @var WPML_ST_Page_URL_Preprocessor
	 */
	private $page_url_preprocessor;

	/**
	 * @var WPML_ST_Page_Translations
	 */
	private $translations;

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var string
	 */
	private $page_url;

	/**
	 * @param string $language
	 * @param IWPML_ST_Page_Translations_Persist $translations_persist
	 * @param WPML_ST_DB_Translation_Retrieve $single_translation_retriever
	 * @param WPML_ST_Page_URL_Preprocessor $page_url_preprocessor
	 */
	public function __construct(
		$language,
		IWPML_ST_Page_Translations_Persist $translations_persist,
		WPML_ST_DB_Translation_Retrieve $single_translation_retriever,
		WPML_ST_Page_URL_Preprocessor $page_url_preprocessor
	) {
		$this->page_url_preprocessor        = $page_url_preprocessor;
		$this->page_url                     = $this->get_page_url();
		$this->language                     = $language;
		$this->translations_persist         = $translations_persist;
		$this->single_translation_retriever = $single_translation_retriever;

		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	public function clear_cache() {
		$this->single_translation_retriever->clear_cache();
		$this->translations_persist->clear_cache();
		$this->translations = new WPML_ST_Page_Translations( array() );
	}

	public function shutdown() {
		$is_reseting_wpml_single = ( array_key_exists( 'icl-reset-all', $_POST ) && $_POST['icl-reset-all'] === 'on' );
		$is_reseting_wpml_multi = ( array_key_exists( 'action', $_GET ) && $_GET['action'] === 'resetwpml' );
		if ( $this->is_404() || $is_reseting_wpml_single || $is_reseting_wpml_multi ) {
			return;
		}

		if ( $this->translations && $this->translations->has_new_translations() ) {
			$this->translations_persist->store_new_translations(
				$this->language,
				$this->page_url,
				$this->translations->get_new_translations()
			);
		}
	}

	/**
	 * @return bool
	 */
	private function is_404() {
		return is_404() || is_home() && '/' !== $_SERVER['REQUEST_URI'];
	}

	/**
	 * @param string $name
	 * @param string $context
	 * @param string $original_value
	 * @param string $gettext_context
	 *
	 * @return WPML_ST_Page_Translation|null
	 */
	public function get_translation( $name, $context, $original_value, $gettext_context = '' ) {
		if ( md5( '' ) == $name && empty( $original_value ) && empty( $gettext_context ) ) {
			return $original_value;
		}

		if ( null === $this->translations ) {
			$this->translations = $this->translations_persist->get_translations_for_page( $this->language, $this->page_url );
		}

		$translation = $this->translations->get_translation( $name, $context, $gettext_context );

		if ( $translation ) {
			return $translation;
		}

		$translation = $this->single_translation_retriever->get_translation( $this->language, $name, $context, $gettext_context );

		if ( $translation ) {
			$this->translations->add_translation( $translation );
		}

		return $translation;
	}

	/**
	 * @return string
	 */
	private function get_page_url() {
		return $this->page_url_preprocessor->process_url( $_SERVER['REQUEST_URI'] );
	}
}
