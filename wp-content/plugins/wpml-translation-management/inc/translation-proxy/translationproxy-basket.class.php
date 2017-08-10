<?php
/**
 * @package    wpml-core
 * @subpackage wpml-core
 */

if ( ! class_exists( 'TranslationProxy_Basket' ) ) {
	/**
	 * TranslationProxy_basket collects all static methods to operate on
	 * translations basket (cart)
	 */
	class TranslationProxy_Basket {
		private static $item_types;
		private static $messages;
		private static $dashboard_select;

		private static $basket;

		//The name of the option stored in wp_options table and that
		//stores all the basket items
		const ICL_TRANSLATION_JOBS_BASKET = 'icl_translation_jobs_basket';
		private static $posts_ids;
		private static $translate_from;
		private static $translation_action;

		public static function add_message( $array ) {
			self::$messages[] = $array;
		}

		public static function remove_message( $text ) {
			if( is_array( self::$messages ) ) {
				foreach ( self::$messages as $key => $message ) {
					if ( array_key_exists( 'text', $message ) && $message['text'] === $text ) {
						unset( self::$messages[ $key ] );
					}
				}
			}
		}

		public static function get_basket( $force = false ) {
			if ( ! isset( self::$basket ) || $force ) {
				self::$basket = get_option( self::ICL_TRANSLATION_JOBS_BASKET );
			}

			return self::$basket;
		}

		public static function update_basket( $basket_portion = array() ) {
			if ( !empty( $basket_portion ) ) {
				if ( !self::$basket || self::get_basket_items_count() == 0 ) {
					self::$basket = $basket_portion;
				} else {
					self::$basket = self::merge_baskets( self::$basket, $basket_portion );
				}
			}
			if ( self::get_basket_items_count( true ) == 0 ) {
				self::$basket = false;
			}
			self::sync_target_languages();
			update_option( self::ICL_TRANSLATION_JOBS_BASKET, self::$basket );
			self::update_basket_notifications();
		}

		private static function merge_baskets($from, $to) {
			if ( function_exists( 'array_replace_recursive' ) ) {
				return array_replace_recursive( $from, $to );
			} else {
				return self::array_replace_recursive($from, $to);
			}
		}

		/**
		 * Return number of items in translation basket by key
		 *
		 * @param string $type
		 * @param bool   $skip_cache
		 *
		 * @return int number of items in translation basket
		 */
		public static function get_basket_items_type_count( $type, $skip_cache = false ) {

			$cache_key = $type;
			$cache_group = 'get_basket_items_type_count';
			$cache_found = false;

			if ( ! $skip_cache) {
				$basket_items_number = wp_cache_get( $cache_key, $cache_group, false, $cache_found );
			} else {
				$basket_items_number = 0;
			}

			if ( $cache_found ) {
				return $basket_items_number;
			}

			self::get_basket();

			if ( self::$basket ) {
				if ( isset( self::$basket[ $type ] ) ) {
					$posts = self::$basket[ $type ];
					$basket_items_number += count( $posts );
				}
			}

			if ( ! $skip_cache) {
				wp_cache_set( $cache_key, $basket_items_number, $cache_group );
			}

			return $basket_items_number;
		}

		/**
		 * Return number of items in translation basket
		 *
		 * @param bool $skip_cache
		 *
		 * @return int number of items in translation basket
		 */
		public static function get_basket_items_count( $skip_cache = false ) {

			$basket_items_number = 0;

			$basket_items_types = self::get_basket_items_types();
			foreach ( $basket_items_types as $item_type_name => $item_type ) {
				$basket_items_number += self::get_basket_items_type_count( $item_type_name, $skip_cache );
			}

			return $basket_items_number;
		}

		/**
		 * Register notification with number of items in basket and link to basket
		 */
		public static function update_basket_notifications() {
			$positions = self::get_basket_notification_positions();
			$basket_link = "admin.php?page=" . WPML_TM_FOLDER . "/menu/main.php&sm=basket";

			foreach ( $positions as $position => $group ) {
					ICL_AdminNotifier::remove_message_group( $position );
			}

			self::get_basket();
			$basket_items_count = self::get_basket_items_count(true);


			// if we have something in the basket
			if ( $basket_items_count > 0 && ( !isset($_GET['clear_basket']) || $_GET['clear_basket'] != 1 ) && ( !isset($_GET['action']) || $_GET['action'] != 'delete' ) ){

				$text =  __( 'The items you have selected are now in the translation basket &ndash;', 'wpml-translation-management' );
				$text .= ' ' . sprintf( __( '<a href="%s">Send to translation &raquo;</a>', 'wpml-translation-management' ), $basket_link );

				// translation management pages
				$message_args = array(
					'id'				=> $positions[ 'tm_dashboard_top' ],
					'text'				=> $text,
					'classes'			=> 'small',
					'type'				=> 'information small',
					'group'				=> $positions[ 'tm_dashboard_top' ],
					'admin_notice'		=> false,
					'hide_per_user'		=> false,
					'dismiss_per_user'	=> false,
					'capability'		=> 'manage_options',
				);
				ICL_AdminNotifier::add_message( $message_args );

			} else{
				ICL_AdminNotifier::remove_message( $positions[ 'tm_dashboard_top' ] );
			}

			$admin_basket_message_id = $positions[ 'admin_notice' ];
			if ( self::$messages || $basket_items_count > 0 ) {

				$additional_messages = array();
				if ( isset( self::$messages ) && is_array( self::$messages ) ) {
					foreach ( self::$messages as $message ) {
						$additional_messages[ ] = $message[ 'text' ];
					}
				}
				$additional_messages_text = '';
				if ( count( $additional_messages ) > 0 ) {
					$additional_messages_text = '<ul><li>' . implode( '</li><li>', $additional_messages ) . '</li></ul>';
				}
                
				$limit_to_page = array();
				$limit_to_page[] = WPML_TM_FOLDER . '/menu/main.php';

				$message_args = array(
					'id'               => $admin_basket_message_id,
					'text'             => $additional_messages_text,
					'classes'          => 'small',
					'type'             => 'information',
					'group'            => $admin_basket_message_id,
					'admin_notice'     => true,
					'hide_per_user'    => false,
					'dismiss_per_user' => false,
					'limit_to_page'    => $limit_to_page,
					'show_once'        => true
				);

				if ( trim($additional_messages_text) != '' ) {
					ICL_AdminNotifier::add_message( $message_args );
				}

			} else {
				ICL_AdminNotifier::remove_message( $admin_basket_message_id );
			}
		}

		/**
		 * Displays div with number of items in basket and link to basket
		 * Removes notification if basket is empty
		 */
		public static function display_basket_items_notification() {
			ICL_AdminNotifier::display_messages( 'translation-basket-notification' );
		}

		public static function is_in_basket( $post_id, $source_language, $target_language, $item_type = 'post' ) {
			self::get_basket();

			if ( ! self::$basket || ! isset( self::$basket[ $item_type ][ $post_id ] ) ) {
				return false;
			}

			$basket_item = self::$basket[ $item_type ][ $post_id ];

			return isset( $basket_item ) && $basket_item[ 'from_lang' ] == $source_language && isset( $basket_item[ 'to_langs' ][ $target_language ] ) && $basket_item[ 'to_langs' ][ $target_language ];
		}

		/**
		 * Checks if post with ID $post_id is in the basket for any language
		 *
		 * @param int    $post_id
		 * @param string $element_type
		 * @param array  $check_in_languages
		 * @param bool   $original_language_code
		 *
		 * @return bool
		 */
		public static function anywhere_in_basket( $post_id, $element_type = 'post', $check_in_languages = array(), $original_language_code = false ) {
			$basket = self::get_basket();

			if ( $post_id && isset( $basket[ $element_type ][ $post_id ] ) ) {
				if ( $check_in_languages ) {
					if ( !$original_language_code ) {
						$original_language_code = $basket[ 'source_language' ];
					}
					foreach ( $check_in_languages as $language_code => $language_data ) {
						if ( $language_code != $original_language_code && isset( $basket[ $element_type ][ $post_id ][ 'to_langs' ][ $language_code ] ) ) {
							return true;
						}
					}
					return false;
				} else {
					return true;
				}
			}

			return false;
		}

		public static function is_string_in_basket_anywhere( $string_id ) {
			return self::anywhere_in_basket($string_id, 'string');
		}

		public static function has_any_string() {
			return self::has_any_item_type('string');
		}

		public static function has_any_item_type($item_type) {
			self::get_basket();
			return isset( self::$basket[ $item_type ] ) && count( self::$basket[ $item_type ] );
		}

		/**** adding items to basket ****/

		/**
		 * Serves Translation Dashboard form submission and adds posts to basket
		 *
		 * @param array $data data submitted from form
		 *
		 * @return boolean
		 */
		public static function add_posts_to_basket( $data ) {
			self::get_basket();
			global $sitepress, $iclTranslationManagement, $wpml_translation_job_factory;

			extract( $data, EXTR_OVERWRITE );

			ICL_AdminNotifier::remove_message( 'the_basket_items_notification' );

			self::$translation_action = null;
			if (isset( $data[ 'tr_action' ] ) ) { //adapt new format
				self::$translation_action = $data[ 'tr_action' ];
			}
			if ( ! isset( $data[ 'tr_action' ] ) && isset( $data[ 'translate_to' ] ) ) { //adapt new format
				$data[ 'tr_action' ] = $data[ 'translate_to' ];
				self::$translation_action = $data[ 'tr_action' ];
				unset( $data[ 'translate_to' ] );
			}

			self::$posts_ids  = self::get_elements_ids( $data, 'post' );

			self::$translate_from = $data [ 'translate_from' ]; // language of the submitted posts transported by hidden field

			$data_is_valid = self::validate_data( $data );

			if(!$data_is_valid) {
				return false;
			}

			// check tr_action and do what user decided
			foreach ( self::$translation_action as $language_code => $status ) {

				$language_name = $sitepress->get_display_language_name( $language_code );
				// if he decided duplicate or not to translate for this particular language,
				// try to remove it from wp_options

				$basket_item_type = 'post';

				if ( $status == 2 ) {
					// iterate posts ids, check if they are in wp_options
					// if they are set to translate for this particular language
					// end then remove it
					foreach ( self::$posts_ids as $id ) {
						if ( isset( self::$basket[ $basket_item_type ][ $id ][ 'to_langs' ][ $language_code ] ) ) {
							unset( self::$basket[ $basket_item_type ][ $id ][ 'to_langs' ][ $language_code ] );
						}
						// if user want to duplicate this post, lets do this
						if ( $status == 2 ) {
							$iclTranslationManagement->make_duplicate( $id, $language_code );
						}
					}
				} elseif ( $status == 1 ) {
					foreach ( self::$posts_ids as $id ) {

						$send_to_basket = true;

						$post = self::get_post( $id );

						$post_type  = $post->post_type;
						$post_title = $post->post_title;
						global $wpdb;
						$source_language_code = $wpdb->get_var( $wpdb->prepare( "	SELECT source_language_code
																					FROM {$wpdb->prefix}icl_translations
																					WHERE element_type LIKE 'post_%%'
																					AND element_id = %d",
						                                                            $post->ID ) );

						if ( $source_language_code != $language_code ) {
							$trid   = $sitepress->get_element_trid( $id, 'post_' . $post_type );
							$job_id = $iclTranslationManagement->get_translation_job_id( $trid, $language_code );

							if ( $job_id ) {
								/** @var stdClass $job_details */
								$job_details = $wpml_translation_job_factory->get_translation_job( $job_id );
								if ( $job_details->status == ICL_TM_IN_PROGRESS ) {
									self::$messages[ ] = array(
										'type' => 'update',
										'text' => sprintf( __( 'Post "%s" will be ignored for %s, because translation is already in progress.',
										                       'wpml-translation-management' ),
										                   $post_title,
										                   $language_name )
									);
									$send_to_basket    = false;
								} elseif ( $job_details->status == ICL_TM_WAITING_FOR_TRANSLATOR ) {
									self::$messages[ ] = array(
										'type' => 'update',
										'text' => sprintf( __( 'Post "%s" will be ignored for %s, because translation is already waiting for translator.',
										                       'wpml-translation-management' ),
										                   $post_title,
										                   $language_name )
									);
									$send_to_basket    = false;
								}
							}
						} else {
							self::$messages[ ] = array(
								'type' => 'update',
								'text' => sprintf( __( 'Post "%s" will be ignored for %s, because it is an original post.',
								                       'wpml-translation-management' ),
								                   $post_title,
								                   $language_name )
							);
							$send_to_basket    = false;
						}

						if ( $send_to_basket ) {
							self::$basket[ $basket_item_type ][ $id ][ 'from_lang' ]                  = self::$translate_from;
							self::$basket[ $basket_item_type ][ $id ][ 'to_langs' ][ $language_code ] = 1;
							// set basket language if not already set
							if ( !isset( self::$basket[ 'source_language' ]  ) ) {
								self::$basket[ 'source_language' ] = self::$translate_from;
							}
						}
					}
				}
			}

			self::update_basket();

			return true;
		}

		/**
		 * Serves WPML > String translation form submission and adds strings to basket
		 *
		 * @param array $string_ids identifiers of strings
		 * @param       $source_language
		 * @param array $target_languages selected target languages
		 * @return bool
		 * @todo: [WPML 3.3] move to ST and handle with hooks
		 */
		public static function add_strings_to_basket( $string_ids, $source_language, $target_languages ) {
			global $wpdb, $sitepress;

			self::get_basket();
			ICL_AdminNotifier::remove_message( 'the_basket_items_notification' );

			/* structure of cart in get_option:
			* [posts]
			*  [element_id]
			*          [to_langs]
			*             [language_code]             fr | pl | de ... with value 1
			* [strings]
			*  [string_id]
			*          [to_langs]
			*             [language_code]
			*/


			// no post selected ?
			if ( empty( $string_ids ) ) {
				self::$messages[ ]      = array(
					'type' => 'error',
					'text' => __( 'Please select at least one document to translate.', 'wpml-translation-management' )
				);
				self::update_basket();
				return false;
			}

			// no language selected ?
			if ( empty( $target_languages ) ) {
				self::$messages[ ]      = array(
					'type' => 'error',
					'text' => __( 'Please select at least one language to translate into.', 'wpml-translation-management' )
				);
				self::update_basket();
				return false;
			}

			if ( self::get_basket() && self::get_source_language() ) {
			/*we do not add items that are not in the source language of the current basket
			  we cannot yet set its source language though since update_basket would set the basket
			  to false oso long as we do not have any elements in the basket*/
				if ( $source_language != self::get_source_language() ) {
					self::$messages[ ] = array(
						'type' => 'update',
						'text' => __( 'You cannot add strings  in this language to the basket since it already contains posts or strings of another source language!
						Either submit the current basket and then add the post or delete the posts of differing language in the current basket', 'wpml-translation-management' )
						);
					self::update_basket();
					return false;
				}
			}

			foreach ( $target_languages as $target_language => $selected ) {
				if ( $target_language == $source_language ) {
					continue;
				}
				$target_language_name = $sitepress->get_display_language_name( $target_language );

				foreach ( $string_ids as $id ) {

					$send_to_basket = true;
					$query = "	SELECT 	{$wpdb->prefix}icl_string_translations.status,
										{$wpdb->prefix}icl_strings.value
								FROM {$wpdb->prefix}icl_string_translations
								INNER JOIN {$wpdb->prefix}icl_strings
									ON {$wpdb->prefix}icl_string_translations.string_id = {$wpdb->prefix}icl_strings.id
								WHERE {$wpdb->prefix}icl_string_translations.string_id=%d
									AND {$wpdb->prefix}icl_string_translations.language=%s";

					$string_translation = $wpdb->get_row( $wpdb->prepare( $query, $id, $target_language ) );

					if ( ! is_null( $string_translation ) && $string_translation->status == ICL_TM_WAITING_FOR_TRANSLATOR ) {
						self::$messages[ ] = array(
							'type' => 'update',
							'text' => sprintf( __( 'String "%s" will be ignored for %s, because translation is already waiting for translator.',
							                       'wpml-translation-management' ),
							                   $string_translation->value,
							                   $target_language_name )
						);
						$send_to_basket    = false;
					}

					if ( $send_to_basket ) {
						self::$basket[ 'string' ][ $id ][ 'from_lang' ] = $source_language;
						self::$basket[ 'string' ][ $id ][ 'to_langs' ][ $target_language ] = 1;
						// set basket language if not already set
						if ( ! isset( self::$basket[ 'source_language' ] ) ) {
							self::$basket[ 'source_language' ] = $source_language;
						}
					}
				}
			}

			self::update_basket();

			return true;
		}

		/**
		 * Serves deletion of items from basket, triggered from WPML TM > Translation
		 * Jobs
		 *
		 * @param array $items Array of items ids, in two separate parts: ['post']
		 *                     and ['string']
		 */
		public static function delete_items_from_basket( $items ) {
			self::get_basket();

			$basket_items_types = self::get_basket_items_types();
			foreach ( $basket_items_types as $item_type_name => $item_type ) {
				if ( ! empty( $items[ $item_type_name ] ) ) {
					foreach ( $items[ $item_type_name ] as $id ) {
						self::delete_item_from_basket( $id, $item_type_name, false );
					}
				}
			}

			self::update_basket();
		}

		/**
		 * Removes one item from basket
		 *
		 * @param int    $id            Item ID
		 * @param string $type          Item type (strings | posts | ...)
		 * @param bool   $update_option do update_option('icl_translation_jobs_cart' ?
		 */
		public static function delete_item_from_basket( $id, $type = 'post', $update_option = true ) {
			self::get_basket();

			if ( isset( self::$basket[ $type ][ $id ] ) ) {
				unset( self::$basket[ $type ][ $id ] );
				if ( count( self::$basket[ $type ] ) == 0 ) {
					unset( self::$basket[ $type ] );
				}
			}

			if ( self::get_basket_items_count( true ) == 0 ) {
				self::$basket = array();
			}

			if ( $update_option ) {
				self::update_basket( self::$basket );
			} else {
				self::update_basket_notifications();
			}
		}

		//TODO: [WPML 3.3] implement this in the troubleshooting page
		public static function delete_all_items_from_basket() {
			self::$basket = false;
			delete_option( self::ICL_TRANSLATION_JOBS_BASKET );
			self::update_basket();
		}

		/**
		 * @param $batch TranslationProxy_Batch
		 */
		public static function set_batch_data( $batch ) {
			self::get_basket();
			self::$basket[ 'batch' ] = $batch;
			self::update_basket();
		}

		/**
		 * @return bool|TranslationProxy_Batch
		 */
		public static function get_batch_data() {
			self::get_basket();
			return isset( self::$basket[ 'batch' ] ) ? self::$basket[ 'batch' ] : false;
		}

		public static function set_basket_name( $basket_name ) {
			self::get_basket();
			self::$basket[ 'name' ] = $basket_name;
			self::update_basket();
		}

		public static function get_basket_name() {
			self::get_basket();

			return isset( self::$basket[ 'name' ] ) ? self::$basket[ 'name' ] : false;
		}
		
		public static function get_basket_extra_fields() {
			if (isset($_REQUEST['extra_fields'])) {
				$extra_fields_string = urldecode($_REQUEST['extra_fields']);
				if(strlen($extra_fields_string) > 0) {
					$extra_fields_rows = explode("|", $extra_fields_string);
					$result = array();
					foreach ($extra_fields_rows as $row) {
						$row_data = explode(":", $row);
						if (count($row_data) == 2) {
							$result[$row_data[0]] = $row_data[1];
						}
					}
				}
			}
			
			if (isset($result) && count($result) > 0) {
				return $result;
			}
				
			return false;
		}

		private static function array_replace_recursive( $array, $array1 ) {
			if ( function_exists( 'array_replace_recursive' ) ) {
				$array = array_replace_recursive( $array, $array1 );
			} else {
				// handle the arguments, merge one by one
				$args  = func_get_args();
				$array = $args[ 0 ];
				if ( ! is_array( $array ) ) {
					return $array;
				}
				for ( $i = 1; $i < count( $args ); $i ++ ) {
					if ( is_array( $args[ $i ] ) ) {
						$array = self::recurse( $array, $args[ $i ] );
					}
				}
			}
			return $array;
		}

		private static function recurse( $array, $array1 ) {
			foreach ( $array1 as $key => $value ) {
				// create new key in $array, if it is empty or not an array
				if ( ! isset( $array[ $key ] ) || ( isset( $array[ $key ] ) && ! is_array( $array[ $key ] ) ) ) {
					$array[ $key ] = array();
				}

				// overwrite the value in the base array
				if ( is_array( $value ) ) {
					$value = self::recurse( $array[ $key ], $value );
				}
				$array[ $key ] = $value;
			}

			return $array;
		}

		public static function get_basket_items_types() {
			self::$item_types = array(
				'post' => 'core',
				'string' => 'core',
				'package' => 'custom',
			);
			return apply_filters('wpml_tm_basket_items_types', self::$item_types);
		}

		/**
		 * @param $post_id
		 *
		 * @return mixed|null|void|WP_Post
		 */
		private static function get_post( $post_id ) {
			if (is_string($post_id) && strcmp(substr($post_id, 0, strlen('external_')), 'external_')===0) {
				$item = apply_filters('wpml_get_translatable_item', null, $post_id);
			} else {
				$item = get_post($post_id);
			}
			return $item;
		}

		/**
		 * @param array $selected_elements
		 *
		 * @param bool|string $type
		 * @return array[]|int[]
		 */
		public static function get_elements_ids( $selected_elements, $type = false ) {
			$element_ids = array();
			$legal_item_types = $type ? array( $type ) : array_keys( self::get_basket_items_types() );
			foreach ( $legal_item_types as $item_type ) {
				if ( !isset( $selected_elements[ $item_type ] ) ) {
					continue;
				}
				$element_ids[ $item_type ] = isset( $element_ids[ $item_type ] ) ? $element_ids[ $item_type ] : array();
				$items = $selected_elements[ $item_type ];
				foreach ( $items as $element_id => $action_data ) {
					if ( isset( $action_data[ 'checked' ] ) && $action_data[ 'checked' ] ) {
						$element_ids[ $item_type ][ ] = $element_id;
					}
				}
			}

			return $type && isset( $element_ids[ $type ] ) ? $element_ids[ $type ] : $element_ids;
		}

		public static function get_source_language() {
			self::get_basket();
			return isset( self::$basket[ 'source_language' ] ) ? self::$basket[ 'source_language' ] : false;
		}

		private static function sync_target_languages() {
			self::get_basket();
			if(!isset(self::$basket[ 'target_languages' ])) {
				self::$basket[ 'target_languages' ] = array();
			}
			
			$basket_items_types = self::get_basket_items_types();
			foreach ( $basket_items_types as $item_type_name => $item_type ) {
				if ( isset( self::$basket[ $item_type_name ] ) ) {
					$posts_in_basket = self::$basket[ $item_type_name ];
					foreach ( (array) $posts_in_basket as $post_in_basket ) {
						foreach ( (array) $post_in_basket[ 'to_langs' ] as $key => $target_language ) {
							if ( $target_language && ! in_array( $key, self::$basket[ 'target_languages' ] ) ) {
								self::$basket[ 'target_languages' ] [ ] = $key;
							}
						}
					}
				}
			}
		}

		/**
		 * @return bool|array
		 */
		public static function get_target_languages() {
			self::get_basket();
			self::sync_target_languages();
			return isset( self::$basket[ 'target_languages' ] ) ? self::$basket[ 'target_languages' ] : false;
		}


		/**
		 * Sets target languages for remote service
		 *
		 * @param $remote_target_languages
		 */
		public static function set_remote_target_languages( $remote_target_languages ) {
			self::get_basket();
			self::$basket[ 'remote_target_languages' ] = $remote_target_languages;
			self::update_basket();
		}


		/**
		 * Get target languages for remote service
		 *
		 * @return array | false
		 */
		public static function get_remote_target_languages() {
			self::get_basket();
			if  ( isset( self::$basket[ 'remote_target_languages' ] ) ){
				return self::$basket[ 'remote_target_languages' ];
			} else {
				return self::get_target_languages();
			}
		}

		/**
		 * @return array
		 */
		public static function get_basket_notification_positions() {
			return array(
				'admin_notice'        => 'basket_status_update',
				'tm_dashboard_top'    => 'translation-basket-notification',
				'st_dashboard_top'    => 'string-translation-top',
				'st_dashboard_bottom' => 'string-translation-under',
			);
		}

		public static function get_basket_extra_fields_section() {
			$extra_fields = TranslationProxy::get_extra_fields_local();

			$html = '';

			if ( $extra_fields ) {

				$html .= '<h3>3. ' . __( 'Select additional options', 'wpml-translation-management' ) . ' <a href="#" id="basket_extra_fields_refresh">(' . __( "Refresh", 'wpml-translation-management' ) . ')</a></h3>';

				$html .= '<div id="basket_extra_fields_list">';

				$html .= self::get_basket_extra_fields_inputs( $extra_fields, false );

				$html .= '</div>';
			}

			return $html;
		}

		public static function get_basket_extra_fields_inputs( array $extra_fields = array(), $force_refresh = false ) {
			if ( ! $extra_fields ) {
				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					$force_refresh = true;
				}
				$extra_fields = self::get_basket_extra_fields_array( $force_refresh );
			}

			return self::extra_fields_build_inputs( $extra_fields );
		}
		
		public static function get_basket_extra_fields_array($force_refresh = false) {
			if ($force_refresh) {
				$networking   = wpml_tm_load_tp_networking();
				$project      = TranslationProxy::get_current_project();
				$extra_fields = $networking->get_extra_fields_remote( $project );
			} else {
				$extra_fields = TranslationProxy::get_extra_fields_local();
			}

			return TranslationProxy::maybe_convert_extra_fields( $extra_fields );
		}

		public static function extra_fields_build_inputs( array $extra_fields ) {
			if ( ! $extra_fields ) {
				return '';
			}

			$rows = array();
			/** @var WPML_TP_Extra_Field $field */
			$field_diplay = new WPML_TP_Extra_Field_Display();
			foreach ( $extra_fields as $field ) {
				$rows[] = $field_diplay->render( $field );
			}

			$rows = array_filter($rows);

			$html = '';
			if ( $rows ) {
				$html = '<table class="form-table">';
				$html .= '<tbody>';
				$html .= implode( PHP_EOL, $rows );
				$html .= '</tbody>';
				$html .= '</table>';
			}

			return $html;
		}

		/**
		 * @param $data
		 *
		 * @return bool
		 */
		private static function validate_data( $data ) {
			$data_is_valid = true;
			if ( self::get_basket() && self::get_source_language() ) {
				/*we do not add items that are not in the source language of the current basket
				we cannot yet set its source language though since update_basket would set the basket
				to false as long as we do not have any elements in the basket*/
				if ( self::$translate_from != self::get_source_language() ) {
					self::$messages[ ] = array(
						'type' => 'update',
						'text' => __( 'You cannot add posts in this language to the basket since it already contains posts or strings of another source language!
						Either submit the current basket and then add the post or delete the posts of differing language in the current basket', 'wpml-translation-management' )
					);
					self::update_basket();

					$data_is_valid = false;
				}
			}

			// no language selected ?
			if ( ! isset( self::$translation_action ) || empty( self::$translation_action ) ) {
				self::$messages[ ]      = array(
					'type' => 'error',
					'text' => __( 'Please select at least one language to translate into.', 'wpml-translation-management' )
				);
				self::$dashboard_select = $data; // pre fill dashboard
				$data_is_valid          = false;
			}

			if($data_is_valid) {
				$data_is_valid = false;
				$basket_items_types = self::get_basket_items_types();
				// nothing selected ?
				foreach ( $basket_items_types as $basket_items_type => $basket_type ) {
					if ( isset( $data[ $basket_items_type ] ) && $data[ $basket_items_type ] ) {
						$data_is_valid = true;
						break;
					}
				}
			}

			if ( ! $data_is_valid ) {
				self::$messages[ ]      = array(
					'type' => 'error',
					'text' => __( 'Please select at least one document to translate.', 'wpml-translation-management' )
				);
				self::$dashboard_select = $data; // pre-populate dashboard
				$data_is_valid          = false;

				return $data_is_valid;
			}

			return $data_is_valid;
		}
	}
}
