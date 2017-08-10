<?php

require dirname( __FILE__ ) . '/wpml-taxonomy-translation-sync-display.class.php';
new WPML_Taxonomy_Translation_Sync_Display();

class WPML_Taxonomy_Translation_Table_Display {

	private static function get_strings_translation_array() {

		$labels = array(
			"Show"                                  => __( "Show", "sitepress" ),
			"untranslated"                          => __( "untranslated", "sitepress" ),
			"all"                                   => __( "all", "sitepress" ),
			"in"                                    => __( "in", "sitepress" ),
			"to"                                    => __( "to", "sitepress" ),
			"of"                                    => __( "of", "sitepress" ),
			"taxonomy"                              => __( "Taxonomy", "sitepress" ),
			"anyLang"                               => __( "any language", "sitepress" ),
			"apply"                                 => __( "Refresh", "sitepress" ),
			"synchronizeBtn"                        => __( "Update Taxonomy Hierarchy", "sitepress" ),
			"searchPlaceHolder"                     => __( "Search", "sitepress" ),
			"selectParent"                          => __( "select parent", "sitepress" ),
			"taxToTranslate"                        => __( "Select the taxonomy to translate: ", "sitepress" ),
			"translate"                             => __( "%taxonomy% Translation", "sitepress" ),
			"Synchronize"                           => __( "Hierarchy Synchronization", "sitepress" ),
			"lowercaseTranslate"                    => __( "translate", "sitepress" ),
			"copyToAllLanguages"                    => __( "Copy to all languages", "sitepress" ),
			"copyToAllMessage"                      => __( "Copy this term from original: %language% to all other languages?" ),
			"copyAllOverwrite"                      => __( "Overwrite existing translations", "sitepress" ),
			"willBeRemoved"                         => __( "Will be removed", "sitepress" ),
			"willBeAdded"                           => __( "Will be added", "sitepress" ),
			"legend"                                => __( "Legend:", "sitepress" ),
			"refLang"                               => __( "Synchronize taxonomy hierarchy according to: %language% language.", "sitepress" ),
			"targetLang"                            => __( "Target Language", "sitepress" ),
			"termPopupDialogTitle"                  => __( "Term translation", "sitepress" ),
			"originalTermPopupDialogTitle"          => __( "Original term", "sitepress" ),
			"labelPopupDialogTitle"                 => __( "Label translation", "sitepress" ),
			"copyFromOriginal"                      => __( "Copy from original", "sitepress" ),
			"original"                              => __( "Original:", "sitepress" ),
			"translationTo"                         => __( "Translation to:", "sitepress" ),
			"Name"                                  => __( "Name", "sitepress" ),
			"Slug"                                  => __( "Slug", "sitepress" ),
			"Description"                           => __( "Description", "sitepress" ),
			"Ok"                                    => __( "OK", "sitepress" ),
			"save"                                  => __( "Save", "sitepress" ),
			"Singular"                              => __( "Singular", "sitepress" ),
			"Plural"                                => __( "Plural", "sitepress" ),
			"cancel"                                => __( "Cancel", "sitepress" ),
			"loading"                               => __( "loading", "sitepress" ),
			"Save"                                  => __( "Save", "sitepress" ),
			"currentPage"                           => __( "Current page", "sitepress" ),
			"goToPreviousPage"                      => __( "Go to previous page", "sitepress" ),
			"goToNextPage"                          => __( "Go to the next page", "sitepress" ),
			"goToFirstPage"                         => __( "Go to the first page", "sitepress" ),
			"goToLastPage"                          => __( "Go to the last page", "sitepress" ),
			"hieraSynced"                           => __( "The taxonomy hierarchy is now synchronized.", "sitepress" ),
			"hieraAlreadySynced"                    => __( "The taxonomy hierarchy is already synchronized.", "sitepress" ),
			"noTermsFound"                          => __( "No %taxonomy% found.", "sitepress" ),
			"items"                                 => __( "items", "sitepress" ),
			"item"                                  => __( "item", "sitepress" ),
			"summaryTerms"                          => __( "Translation of %taxonomy%", "sitepress" ),
			"summaryLabels"                         => __(
				"Translations of taxonomy %taxonomy% labels - appearing in WordPress admin menu",
				"sitepress"
			),
			"preparingTermsData"                    => __( "Loading ...", "sitepress" ),
			"firstColumnHeading"                    => __( "%taxonomy% terms (in original language)", "sitepress" ),
			"wpml_save_term_nonce"                  => wp_create_nonce( 'wpml_save_term_nonce' ),
			"wpml_tt_save_labels_translation_nonce" => wp_create_nonce( 'wpml_tt_save_labels_translation_nonce' ),
			"wpml_tt_sync_hierarchy_nonce"          => wp_create_nonce( 'wpml_tt_sync_hierarchy_nonce' ),

			"addTranslation"   => __( "Add translation", "sitepress" ),
			"editTranslation"  => __( "Edit translation", "sitepress" ),
			"originalLanguage" => __( "Original language", "sitepress" ),
			"termMetaLabel"    => __( "This term has additional meta fields:", "sitepress" ),
		);

		if ( defined( 'WPML_ST_FOLDER' ) ) {
			$changeLabelLanguage_url = admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=WordPress' );
			$changeLabelLanguage     = __( 'You can change the language of this label from the <a href="%s">string translation page</a>.', "sitepress" );
		} else {
			$changeLabelLanguage_url = 'https://wpml.org/account/downloads/#wpml-string-translation';
			$changeLabelLanguage     = __( 'You can change the language of this label if you install and activate <a href="%s">WPML String Translation</a>.', "sitepress" );
		}
		$labels['changeLabelLanguage'] = sprintf( $changeLabelLanguage, $changeLabelLanguage_url );

		return $labels;
	}

	public static function enqueue_taxonomy_table_js( $sitepress ) {

		$core_dependencies = array( "jquery", "jquery-ui-dialog", "backbone", 'wpml-underscore-template-compiler' );
		wp_register_script( "templates-compiled",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/templates-compiled.js',
		                    $core_dependencies, '1.2.4' );
		$core_dependencies[ ] = "templates-compiled";
		wp_register_script( "main-util", ICL_PLUGIN_URL . '/res/js/taxonomy-translation/util.js', $core_dependencies );

		wp_register_script( "main-model", ICL_PLUGIN_URL . '/res/js/taxonomy-translation/main.js', $core_dependencies );
		$core_dependencies[ ] = "main-model";

		$dependencies = $core_dependencies;
		wp_register_script( "term-rows-collection",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/collections/term-rows.js',
		                    array_merge( $core_dependencies, array( "term-row-model" ) ) );
		$dependencies[ ] = "term-rows-collection";
		wp_register_script( "term-model",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/models/term.js',
		                    $core_dependencies );
		$dependencies[ ] = "term-model";
		wp_register_script( "taxonomy-model",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/models/taxonomy.js',
		                    $core_dependencies );
		$dependencies[ ] = "taxonomy-model";
		wp_register_script( "term-row-model",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/models/term-row.js',
		                    $core_dependencies );
		$dependencies[ ] = "term-row-model";
		
		foreach ( array(
						"filter-view",
						"nav-view",
						"table-view",
						"taxonomy-view",
						"term-popup-view",
						"original-term-popup-view",
						"label-popup-view",
						"term-row-view",
						"label-row-view",
						"term-rows-view",
						"term-view",
						"term-original-view",
						"copy-all-popup-view",
						) as $script ) {
			
			wp_register_script(
				$script,
				ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/' . $script . '.js',
				$core_dependencies,
				'1.2.4'
			);
			$dependencies[ ] = $script;
		}

		wp_localize_script( "main-model", "labels", self::get_strings_translation_array() );
		wp_localize_script( "main-model", "wpml_taxonomies", self::wpml_get_table_taxonomies( $sitepress ) );

		$need_enqueue    = $dependencies;
		$need_enqueue[ ] = "main-model";
		$need_enqueue[ ] = "main-util";
		$need_enqueue[ ] = "templates";

		foreach ( $need_enqueue as $handle ) {
			wp_enqueue_script( $handle );
		}
	}

	public static function wpml_get_table_taxonomies( SitePress $sitepress ) {
		$taxonomies = $sitepress->get_wp_api()->get_taxonomies( array(), 'objects' );

		$result = array( "taxonomies" => array(), "activeLanguages" => array(), "allLanguages" => array() );
		$sitepress->set_admin_language();
		$active_langs = $sitepress->get_active_languages();
		$default_lang = $sitepress->get_default_language();

		$result[ "activeLanguages" ][ $default_lang ] = array( "label" => $active_langs[ $default_lang ]['display_name'],
													  "flag" => $sitepress->get_flag_url( $default_lang ) );
		foreach ( $active_langs as $code => $lang ) {
			if ( $code !== $default_lang ) {
				$result[ "activeLanguages" ][ $code ] = array( "label" => $lang[ 'display_name' ],
															  "flag" => $sitepress->get_flag_url( $code ) );
			}
		}
		
		$all_languages = $sitepress->get_languages();
		foreach ( $all_languages as $code => $lang ) {
			$result[ "allLanguages" ][ $code ] = array( "label" => $lang[ 'display_name' ],
														  "flag" => $sitepress->get_flag_url( $code ) );
		}

		foreach ( $taxonomies as $key => $tax ) {
			if ( $sitepress->is_translated_taxonomy( $key ) ) {
				$result[ "taxonomies" ][ $key ] = array(
					"label"         => $tax->label,
					"singularLabel" => $tax->labels->singular_name,
					"hierarchical"  => $tax->hierarchical,
					"name"          => $key
				);
			}
		}

		return $result;
	}

	public static function wpml_get_terms_and_labels_for_taxonomy_table() {
		global $sitepress;
		$args     = array();
		$taxonomy = false;

		$request_post_page = filter_input( INPUT_POST,
										   'page',
										   FILTER_SANITIZE_FULL_SPECIAL_CHARS,
										   FILTER_NULL_ON_FAILURE );
		if ( $request_post_page ) {
			$args[ 'page' ] = $request_post_page;
		}

		$request_post_perPage = filter_input( INPUT_POST,
											  'perPage',
											  FILTER_SANITIZE_FULL_SPECIAL_CHARS,
											  FILTER_NULL_ON_FAILURE );
		if ( $request_post_perPage ) {
			$args[ 'per_page' ] = $request_post_perPage;
		}

		$request_post_taxonomy = filter_input( INPUT_POST,
											   'taxonomy',
											   FILTER_SANITIZE_FULL_SPECIAL_CHARS,
											   FILTER_NULL_ON_FAILURE );
		if ( $request_post_taxonomy ) {
			$taxonomy = html_entity_decode( $request_post_taxonomy );
		}

		do_action( 'wpml_st_load_label_menu' );

		if ( $taxonomy ) {
			$terms_data     = new WPML_Taxonomy_Translation_Screen_Data( $sitepress, $taxonomy );
			$labels         = apply_filters( 'wpml_label_translation_data', false, $taxonomy );
			$def_lang       = $sitepress->get_default_language();
			$bottom_content = apply_filters( 'wpml_taxonomy_translation_bottom', $html = '', $taxonomy, get_taxonomy( $taxonomy ) );
			wp_send_json( array(
							  "terms"                => $terms_data->terms(),
							  "taxLabelTranslations" => $labels,
							  "defaultLanguage"      => $def_lang,
							  "bottomContent"		 => $bottom_content
						  ) );
		} else {
			wp_send_json_error();
		}
	}
}
