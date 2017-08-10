<?php

class WPML_ST_Admin_Option_Translation extends WPML_SP_User {

	/** @var  WPML_String_Translation $st_instance */
	private $st_instance;
	/** @var  string $option_name */
	private $option_name;
	/** @var  string $option_name */
	private $language;

	/**
	 * WPML_ST_Admin_Option constructor.
	 *
	 * @param SitePress               $sitepress
	 * @param WPML_String_Translation $st_instance
	 * @param string                  $option_name
	 * @param string                  $language
	 */
	public function __construct(
		&$sitepress,
		&$st_instance,
		$option_name,
		$language = ''
	) {
		if ( ! $option_name || ! is_scalar( $option_name ) ) {
			throw new InvalidArgumentException( 'Not a valid option name, received: ' . serialize( $option_name ) );
		}

		parent::__construct( $sitepress );
		$this->st_instance = &$st_instance;
		$this->option_name = $option_name;
		$this->language    = $language ? $language : $this->st_instance->get_current_string_language( $option_name );
	}

	/**
	 *
	 * @param string   $option_name
	 * @param string   $new_value
	 * @param int|bool $status
	 * @param int      $translator_id
	 * @param int      $rec_level
	 *
	 * @return boolean|mixed
	 */
	public function update_option(
		$option_name = '',
		$new_value = null,
		$status = false,
		$translator_id = null,
		$rec_level = 0
	) {
		$option_name = $option_name ? $option_name : $this->option_name;
		$new_value   = (array) $new_value;
		$updated     = array();

		foreach ( $new_value as $index => $value ) {
			if ( is_array( $value ) ) {
				$name      = "[" . $option_name . "][" . $index . "]";
				$result    = $this->update_option( $name, $value, $status,
					$translator_id, $rec_level + 1 );
				$updated[] = array_sum( explode( ",", $result ) );
			} else {
				if ( is_string( $index ) ) {
					$name = ( $rec_level == 0 ? "[" . $option_name . "]" : $option_name ) . $index;
				} else {
					$name = $option_name;
				}
				$string    = $this->st_instance->string_factory()->find_admin_by_name( $name );
				$string_id = $string->string_id();
				if ( $string_id ) {
					if ( $this->language !== $string->get_language() ) {
						$updated[] = $string->set_translation( $this->language,
							$value, $status,
							$translator_id );
					} else {
						$string->update_value( $value );
					}
				}
			}
		}

		return array_sum( $updated ) > 0 ? join( ",", $updated ) : false;
	}
}