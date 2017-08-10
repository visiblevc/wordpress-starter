<?php

class WPML_Translation_Basket {

	private $wpdb;

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Returns an array representation of the current translation basket
	 *
	 * @param bool|false $force if true reloads the baskets contents from the database
	 *
	 * @return array
	 */
	function get_basket( $force = false ) {
		$basket = TranslationProxy_Basket::get_basket( $force );
		$basket = $basket ? $basket : array();

		return $basket;
	}

	/**
	 * @return bool|TranslationProxy_Project
	 */
	public function get_project() {

		return TranslationProxy::get_current_project();
	}

	function get_item_types() {

		return TranslationProxy_Basket::get_basket_items_types();
	}

	/**
	 * Returns a batch instance by basket- or batch-name
	 *
	 * @param string $basket_name
	 *
	 * @return WPML_Translation_Batch
	 */
	function get_basket_batch( $basket_name ) {

		return new WPML_Translation_Batch( $this->wpdb, $this->get_batch_id_from_name( $basket_name ) );
	}

	/**
	 * Sets the remote target languages before committing the basket to a translation service.
	 *
	 * @param array $remote_languages
	 */
	function set_remote_target_languages( $remote_languages ) {

		TranslationProxy_Basket::set_remote_target_languages( $remote_languages );
	}

	/**
	 * Removes all items from the current translation basket.
	 */
	function delete_all_items() {

		TranslationProxy_Basket::delete_all_items_from_basket();
	}

	/**
	 * Returns the name of the current translation basket.
	 *
	 * @return bool|string
	 */
	function get_name() {

		return TranslationProxy_Basket::get_basket_name();
	}

	function set_name( $basket_name ) {

		TranslationProxy_Basket::set_basket_name( $basket_name );
	}

	/**
	 * @param string $basket_name
	 * @param int    $basket_name_max_length
	 *
	 * @return array
	 */
	function check_basket_name( $basket_name, $basket_name_max_length ) {

		$result              = array( 'modified' => false, 'valid' => true, 'message' => '', 'new_value' => '' );
		$old_value           = $basket_name;
		$basket_name         = strip_tags( $basket_name );
		$result['new_value'] = $old_value !== $basket_name ? $basket_name : $result['new_value'];

		if ( strlen( $basket_name ) > $basket_name_max_length ) {
			$result['valid']   = false;
			$result['message'] = sprintf(
				__( 'The length of the batch name exceeds the maximum length of %s', 'wpml-translation-management' ),
				$basket_name_max_length
			);
		} elseif ( $this->get_batch_id_from_name( $basket_name ) ) {
			$result['valid']     = true;
			$result['new_value'] = $this->get_unique_basket_name( $basket_name, $basket_name_max_length );
			$result['message']   = __(
				'This batch name already exists and was modified to ensure unique naming',
				'wpml-translation-management'
			);
		} elseif ( count( $basket_name_array = explode( '|', $basket_name ) ) === 1 ) {
			$result['valid']     = true;
			$result['new_value'] = $this->get_unique_basket_name( $basket_name, $basket_name_max_length );
			$result['message']   = __(
				'The batch name was appended with the source language of its elements.',
				'wpml-translation-management'
			);
		}
		$result['modified'] = $result['new_value'] !== '' && $result['new_value'] !== $old_value;
		$result['message']  = $result['modified'] || ! $result['valid'] ? $result['message'] : '';

		return $result;
	}

	/**
	 * Returns a unique name derived from an input name for a Translation Proxy Basket
	 *
	 * @param bool     $name
	 * @param bool|int $max_length
	 *
	 * @return bool|string
	 */
	function get_unique_basket_name( $name, $max_length ) {
		$basket_name_array = explode( '|', $name );
		$name              = count( $basket_name_array ) === 1
		                     || ( ! is_numeric( $basket_name_array[ count( $basket_name_array ) - 1 ] )
		                          && $basket_name_array[ count( $basket_name_array ) - 1 ] !== $this->get_source_language() )
		                     || ( is_numeric( $basket_name_array[ count( $basket_name_array ) - 1 ] )
		                          && $basket_name_array[ count( $basket_name_array ) - 2 ] !== $this->get_source_language() )
			? $name . '|' . $this->get_source_language() : $name;

		$name = strlen( $name ) > $max_length
			? $this->sanitize_basket_name( $name, $max_length ) : $name;

		if ( $this->get_batch_id_from_name( $name ) ) {
			$suffix = 2;
			$name   = $this->sanitize_basket_name( $name, $max_length - strlen( (string) $suffix ) - 1 );
			while ( $this->get_batch_id_from_name( $name . '|' . $suffix ) ) {
				$suffix ++;
				$name = $this->sanitize_basket_name( $name, $max_length - strlen( (string) $suffix ) - 1 );
			}
			$name .= '|' . $suffix;
		}

		return $name;
	}

	/**
	 * @return string
	 */
	public function get_source_language() {

		return TranslationProxy_Basket::get_source_language();
	}

	/**
	 * @param int[]    $string_ids
	 * @param string   $source_language
	 * @param string[] $target_languages
	 */
	public function add_strings_to_basket( $string_ids, $source_language, $target_languages ) {
		TranslationProxy_Basket::add_strings_to_basket( $string_ids, $source_language, $target_languages );
	}

	private function sanitize_basket_name( $basket_name, $max_length ) {
		//input basket name is separated by pipes so we explode it
		$to_trim = strlen( $basket_name ) - $max_length;
		if ( $to_trim <= 0 ) {
			return $basket_name;
		}
		$basket_name_array = explode( '|', $basket_name );
		$wpml_flag         = count( $basket_name_array ) < 3;

		if ( $wpml_flag === false && count( $basket_name_array ) < 2 ) {

			return substr( $basket_name, $max_length - 1 );
		}

		//first we trim the middle part holding the "WPML"
		if ( $wpml_flag ) {
			list( $basket_name_array, $to_trim ) = $this->shorten_basket_name( $basket_name_array, 1, $to_trim );
		}
		//then trim the site name first, if that's not enough move the array index and also trim the language
		for ( $i = 0; $i <= 1; $i ++ ) {
			if ( $to_trim > 0 ) {
				list( $basket_name_array, $to_trim ) = $this->shorten_basket_name( $basket_name_array, 0, $to_trim );
				$basket_name_array = array_filter( $basket_name_array );
			} else {
				break;
			}
		}
		$basket_name_array = array_filter( $basket_name_array );

		return implode( '|', $basket_name_array );
	}

	private function shorten_basket_name( $name_array, $index, $to_trim ) {
		if ( strlen( $name_array [ $index ] ) > $to_trim ) {
			$name_array[ $index ] = substr( $name_array[ $index ], 0, strlen( $name_array [ $index ] ) - $to_trim - 1 );
			$name_array           = array_filter( $name_array );
			$to_trim              = 0;
		} else {
			$to_trim = $to_trim - strlen( $name_array [ $index ] ) - 1; //subtract one here since we lose a downstroke
			unset( $name_array [ $index ] );
		}

		return array( $name_array, $to_trim );
	}

	/**
	 * Returns the batch id for a given basket or batch name
	 *
	 * @param string $basket_name
	 *
	 * @return int|bool
	 */
	private function get_batch_id_from_name( $basket_name ) {

		return TranslationProxy::get_batch_id_from_name( $basket_name );
	}
}