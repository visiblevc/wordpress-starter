<?php

/**
 * Use this class as parent class for translatable elements in WPML,
 * to have a common approach for retrieving and setting translation information.
 * @author OnTheGo Systems
 */
abstract class WPML_Translation_Element extends WPML_SP_User {
	/** @var int */
	protected $id;

	/** @var stdClass */
	private $languages_details;
	/** @var array */
	private $element_translations;
	/** @var WPML_WP_Cache */
	protected $wpml_cache;

	/**
	 * WPML_Translation_Element constructor.
	 *
	 * @param int           $id
	 * @param SitePress     $sitepress
	 * @param WPML_WP_Cache $wpml_cache
	 */
	public function __construct( $id, SitePress $sitepress, WPML_WP_Cache $wpml_cache = null ) {
		if ( ! is_numeric( $id ) || $id <= 0 ) {
			throw new InvalidArgumentException( 'Argument ID must be numeric and greater than 0.' );
		}
		$this->id = (int) $id;
		$this->wpml_cache = $wpml_cache ? $wpml_cache : new WPML_WP_Cache( WPML_ELEMENT_TRANSLATIONS_CACHE_GROUP );
		parent::__construct( $sitepress );
	}

	public function get_id() {
		return $this->id;
	}

	public function get_source_language_code() {
		$source_language_code = null;
		if ( $this->get_language_details() ) {
			$source_language_code = $this->get_language_details()->source_language_code;
		}

		return $source_language_code;
	}

	/**
	 * @return stdClass
	 * @throws \UnexpectedValueException
	 */
	protected function get_language_details() {
		$this->init_language_details();
		return $this->languages_details;
	}

	abstract function get_element_id();

	abstract function get_wpml_element_type();

	/**
	 * @return array
	 */
	private function get_element_translations() {
		return $this->sitepress->get_element_translations( $this->get_trid(), $this->get_wpml_element_type() );
	}

	/**
	 * @param string $language_code
	 *
	 * @return WPML_Translation_Element|null
	 * @throws \InvalidArgumentException
	 */
	public function get_translation( $language_code ) {
		if ( ! $language_code ) {
			throw new InvalidArgumentException( 'Argument $language_code must be a non empty string.' );
		}
		$this->maybe_init_translations();

		$translation = null;
		if ( $this->element_translations && array_key_exists( $language_code, $this->element_translations ) ) {
			$translation = $this->element_translations[ $language_code ];
		}

		return $translation;
	}

	/**
	 * @return array
	 */
	public function maybe_init_translations() {
		if ( ! $this->element_translations ) {
			$this->element_translations = array();
			$translations               = $this->get_element_translations();
			foreach ( $translations as $language_code => $element_data ) {
				$instance                                     = $this->get_new_instance( $element_data );
				$this->element_translations[ $language_code ] = $instance;
			}
		}

		return $this->element_translations;
	}

	public function get_trid() {
		$trid = false;
		if ( $this->get_language_details() ) {
			$trid = $this->get_language_details()->trid;
		}

		return $trid;
	}

	function get_wp_element_type() {
		$element = $this->get_wp_object();
		if ( is_wp_error( $element ) ) {
			return $element;
		}
		if ( false === (bool) $element ) {
			return new WP_Error( 1, 'Element does not exists.' );
		}

		return $this->get_type( $element );
	}

	/**
	 * @return mixed|WP_Error
	 */
	abstract function get_wp_object();

	/**
	 * @param mixed $element
	 *
	 * @return string
	 */
	abstract function get_type( $element = null );

	/**
	 * @param null|stdClass $element_data null, or a standard object containing at least the `translation_id`, `language_code`, `element_id`, `source_language_code`, `element_type`, and `original` properties.
	 *
	 * @return WPML_Translation_Element
	 */
	abstract function get_new_instance( $element_data );

	/**
	 * @return null|WPML_Translation_Element
	 */
	public function get_source_element() {
		$this->maybe_init_translations();

		$source_element       = null;
		$source_language_code = $this->get_source_language_code();
		if ( $this->element_translations && $source_language_code && array_key_exists( $source_language_code, $this->element_translations ) ) {
			$source_element = $this->element_translations[ $source_language_code ];
		}

		return $source_element;
	}

	public function get_language_code() {
		$language_code = null;
		if ( $this->get_language_details() ) {
			$language_code = $this->get_language_details()->language_code;
		}

		return $language_code;
	}

	protected function init_language_details() {
		if ( ! $this->languages_details ) {
			$this->languages_details = $this->sitepress->get_element_language_details( $this->get_element_id(), $this->get_wpml_element_type() );
		}
	}

	public function flush_cache() {
		$this->languages_details    = null;
		$this->element_translations = null;
		$this->wpml_cache->flush_group_cache();
	}

	abstract function is_translatable();

}
