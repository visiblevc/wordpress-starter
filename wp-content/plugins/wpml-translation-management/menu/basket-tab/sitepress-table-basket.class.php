<?php
require_once WPML_TM_PATH . '/menu/sitepress-table.class.php';

class SitePress_Table_Basket extends SitePress_Table {

    public static function enqueue_js() {
        wp_enqueue_script(
            'wpml-tm-translation-basket-and-options',
            WPML_TM_URL . '/res/js/translation-basket-and-options.js',
            array( 'wpml-tm-scripts', 'jquery-ui-progressbar' ),
            WPML_TM_VERSION
        );

	    $message = esc_html_x( 'You are about to translate duplicated posts.', '1/2 Confirm to disconnect duplicates', 'sitepress' ) . "\n";
	    $message .= esc_html_x( 'These items will be automatically disconnected from originals, so translation is not lost when you update the originals.', '2/2 Confirm to disconnect duplicates', 'sitepress' );

        $tm_basket_data = array(
            'nonce' => array(),
            'strings' => array(
                'done_msg' => __( "Done! ", 'wpml-translation-management' ),
                'jobs_committed' => sprintf(
                    __(
                        "<p>Jobs committed...</p><p>You can check current status of this job in  <a href='%s'>Translation Jobs tab</a>.</p>",
                        'wpml-translation-management'
                    ),
                    admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=jobs' )
                ),
                'jobs_committing' => __( 'Committing jobs...', 'wpml-translation-management' ),
                'error_occurred' => __( 'An error occurred:', 'wpml-translation-management' ),
                'error_not_allowed' => __( 'You are not allowed to run this action.', 'wpml-translation-management' ),
                'batch' => __( 'Batch', 'wpml-translation-management' ),
                'error_no_translators' => __( 'No selected translators!', 'wpml-translation-management' ),
                'rollbacks' => __( 'Rollback jobs...', 'wpml-translation-management' ),
                'rolled' => __( 'Batch rolled back', 'wpml-translation-management' ),
                'errors' => __( 'Errors:', 'wpml-translation-management' ),
            ),
	        'tmi_message' => $message,
        );

        $tm_basket_data = apply_filters( 'translation_basket_and_options_js_data', $tm_basket_data );
        wp_localize_script(
            'wpml-tm-translation-basket-and-options',
            'tm_basket_data',
            $tm_basket_data
        );

        wp_enqueue_script( 'wpml-tm-translation-basket-and-options' );
    }

	function prepare_items() {
		$this->action_callback();

		$this->get_data();
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		if ( $this->items ) {
			usort( $this->items, array( &$this, 'usort_reorder' ) );
		}
	}

	function get_columns() {
		$columns = array(
			'title'     => __( 'Title', 'wpml-translation-management' ),
			'type'      => __( 'Type', 'wpml-translation-management' ),
			'status'    => __( 'Status', 'wpml-translation-management' ),
			'languages' => __( 'Languages', 'wpml-translation-management' ),
		);

		return $columns;
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'title':
			case 'notes':
				return $item[ $column_name ];
				break;
			case 'type':
				return $this->get_post_type_label( $item[ $column_name ] );
				break;
			case 'status':
				return $this->get_post_status_label( $item[ $column_name ] );
				break;
			case 'languages':
				$target_languages_data = $item[ 'target_languages' ];
				$source_language_data  = $item[ 'source_language' ];
				$target_languages      = explode( ',', $target_languages_data );
				$languages             = sprintf( __( '%s to %s', 'wpml-translation-management' ),
				                                  $source_language_data,
				                                  $target_languages_data );
				if ( count( $target_languages ) > 1 ) {
					$last_target_language   = $target_languages[ count( $target_languages ) - 1 ];
					$first_target_languages = array_slice( $target_languages, 0, count( $target_languages ) - 1 );
					$languages              = sprintf( __( '%s to %s and %s', 'wpml-translation-management' ),
					                                   $source_language_data,
					                                   implode( ',', $first_target_languages ),
					                                   $last_target_language );
				}

				return $languages;
				break;
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	function column_title( $item ) {
		$qs                = $_GET;
		$qs[ 'page' ]      = $_REQUEST[ 'page' ];
		$qs[ 'action' ]    = 'delete';
		$qs[ 'id' ]        = $item[ 'ID' ];
		$qs[ 'item_type' ] = $item[ 'item_type' ];

		$new_qs = esc_attr( http_build_query( $qs ) );

		$actions = array(
			'delete' => sprintf( '<a href="?%s">%s</a>', $new_qs, __( 'Remove', 'wpml-translation-management' ) ),
		);

		return sprintf( '%1$s %2$s', esc_html($item[ 'title' ]), $this->row_actions( $actions ) );
	}

	function no_items() {
		_e( 'The basket is empty', 'wpml-translation-management' );
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'title'     => array( 'title', true ),
			'type'      => array( 'type', false ),
			'status'    => array( 'status', false ),
			'languages' => array( 'languages', false ),
		);

		return $sortable_columns;
	}

	/**
	 * @param $post_id
	 * @param $data
	 * @param $item_type
	 */
	private function build_basket_item( $post_id, $data, $item_type ) {
		$this->items[ $item_type . '|' . $post_id ][ 'ID' ]               = $post_id;
		$this->items[ $item_type . '|' . $post_id ][ 'title' ]            = $data[ 'post_title' ];
		$this->items[ $item_type . '|' . $post_id ][ 'notes' ]            = isset( $data[ 'post_notes' ] ) ? $data[ 'post_notes' ] : "";
		$this->items[ $item_type . '|' . $post_id ][ 'type' ]             = $data[ 'post_type' ];
		$this->items[ $item_type . '|' . $post_id ][ 'status' ]           = isset( $data[ 'post_status' ] ) ? $data[ 'post_status' ] : "";
		$this->items[ $item_type . '|' . $post_id ][ 'source_language' ]  = $data[ 'from_lang_string' ];
		$this->items[ $item_type . '|' . $post_id ][ 'target_languages' ] = $data[ 'to_langs_string' ];
		$this->items[ $item_type . '|' . $post_id ][ 'item_type' ]        = $item_type;
	}

	/**
	 * @param $cart_items
	 * @param $item_type
	 *
	 * @return array
	 */
	private function build_basket_items( $cart_items, $item_type ) {
		if ( $cart_items ) {
			foreach ( $cart_items as $post_id => $data ) {
				$this->build_basket_item( $post_id, $data, $item_type );
			}
		}
	}

	private function usort_reorder( $a, $b ) {
		$sortable_columns_keys = array_keys( $this->get_sortable_columns() );
		$first_column_key      = $sortable_columns_keys[ 0 ];
		// If no sort, default to first column
		$orderby = ( ! empty( $_GET[ 'orderby' ] ) ) ? $_GET[ 'orderby' ] : $first_column_key;
		// If no order, default to asc
		$order = ( ! empty( $_GET[ 'order' ] ) ) ? $_GET[ 'order' ] : 'asc';
		// Determine sort order
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : - $result;
	}

	/**
	 * @param $post_status
	 *
	 * @return string
	 */
	private function get_post_status_label( $post_status ) {
		static $post_status_object;
		//Get and store the post status "as verb", if available
		if ( ! isset( $post_status_object[ $post_status ] ) ) {
			$post_status_object[ $post_status ] = get_post_status_object( $post_status );
		}
		$post_status_label = ucfirst( $post_status );
		if ( isset( $post_status_object[ $post_status ] ) ) {
			$post_status_object_item = $post_status_object[ $post_status ];
			if ( isset( $post_status_object_item->label ) && $post_status_object_item->label ) {
				$post_status_label = $post_status_object_item->label;
			}
		}

		return $post_status_label;
	}

	private function get_post_type_label( $post_type ) {
		static $post_type_object;

		if ( ! isset( $post_type_object[ $post_type ] ) ) {
			$post_type_object[ $post_type ] = get_post_type_object( $post_type );
		}
		$post_type_label = ucfirst( $post_type );

		if ( isset( $post_type_object[ $post_type ] ) ) {
			$post_type_object_item = $post_type_object[ $post_type ];
			if ( isset( $post_type_object_item->labels->singular_name ) && $post_type_object_item->labels->singular_name ) {
				$post_type_label = $post_type_object_item->labels->singular_name;
			}
		}

		return $post_type_label;
	}

	private function action_callback() {
		if ( isset( $_GET[ 'clear_basket' ] ) && isset( $_GET[ 'clear_basket_nonce' ] ) && $_GET[ 'clear_basket' ] == 1 ) {
			if ( wp_verify_nonce( $_GET[ 'clear_basket_nonce' ], 'clear_basket' ) ) {
				TranslationProxy_Basket::delete_all_items_from_basket();
			}
		}
		if ( $this->current_action() == 'delete_selected' ) {
			//Delete basket items from post action
			TranslationProxy_Basket::delete_items_from_basket( $_POST[ 'icl_translation_basket_delete' ] );
		} elseif ( $this->current_action() == 'delete' && isset( $_GET[ 'id' ] ) && isset( $_GET[ 'item_type' ] ) ) {
			//Delete basket item from post action
			$delete_basket_item_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			$delete_basket_item_type = filter_input( INPUT_GET, 'item_type', FILTER_SANITIZE_STRING );
			if ( $delete_basket_item_id && $delete_basket_item_type ) {
				TranslationProxy_Basket::delete_item_from_basket( $delete_basket_item_id,
				                                                  $delete_basket_item_type,
				                                                  true );
			}
		}
	}

	private function get_data() {
		global $iclTranslationManagement;

		$translation_jobs_basket = TranslationProxy_Basket::get_basket();

		$basket_items_types = TranslationProxy_Basket::get_basket_items_types();
		foreach ( $basket_items_types as $item_type_name => $item_type ) {
			$translation_jobs_cart[ $item_type_name ] = false;
			if ( $item_type == 'core' ) {
				if ( ! empty( $translation_jobs_basket[ $item_type_name ] ) ) {
					$basket_type_items = $translation_jobs_basket[ $item_type_name ];
					if ( $item_type_name == 'string' ) {
						$translation_jobs_cart[ $item_type_name ] = $iclTranslationManagement->get_translation_jobs_basket_strings( $basket_type_items );
					} else {
						$translation_jobs_cart[ $item_type_name ] = $iclTranslationManagement->get_translation_jobs_basket_posts( $basket_type_items );
					}
					$this->build_basket_items( $translation_jobs_cart[ $item_type_name ], $item_type_name );
				}
			} elseif ( $item_type == 'custom' ) {
				$translation_jobs_cart_externals = apply_filters( 'wpml_tm_translation_jobs_basket',
				                                                  array(),
				                                                  $translation_jobs_basket,
				                                                  $item_type_name );
				$this->build_basket_items( $translation_jobs_cart_externals, $item_type_name );
			}
		}
	}

	function display() {
		parent::display();
		$clear_basket_nonce = wp_create_nonce( 'clear_basket' );
		?>
		<a href="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&sm=basket&clear_basket=1&clear_basket_nonce=<?php echo $clear_basket_nonce; ?>"
		   class="button-secondary" name="clear-basket"><?php _e( 'Clear Basket',
		                                                          'wpml-translation-management' ); ?></a>
	<?php
	}

}