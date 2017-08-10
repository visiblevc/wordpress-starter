<?php

/**
 * Class WPML_Update_Term_Action
 *
 * This class holds the functionality for creating or editing a taxonomy term.
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 */
class WPML_Update_Term_Action extends WPML_WPDB_And_SP_User {

	/**
	 * TRUE if this object represents valid data for the update or creation of a term, false otherwise.
	 * @var bool
	 */
	private $is_valid = true;
	/**
	 * TRUE if this object represents term update action, false if it represents a term creation action.
	 * @var bool
	 */
	private $is_update;
	/**
	 * Argument array containing arguments in a format that can and is used as input to \wp_update_term or
	 * \wp_insert_term
	 * @var array
	 */
	private $wp_new_term_args = array();
	/**
	 * The taxonomy in which this action takes place.
	 * @var string
	 */
	private $taxonomy;
	/**
	 * Trid value in the icl_translations table to which this action is to be written.
	 * @var int
	 */
	private $trid;
	/**
	 * Language of the term that is to result from this action.
	 * @var string
	 */
	private $lang_code;
	/**
	 * Source language of the term that is to result from this action.
	 * @var string|null
	 */
	private $source_lang_code = null;
	/**
	 * Array holding translations of the term created by this object prior to it's creation.
	 * @var array
	 */
	private $existing_translations = array();
	/**
	 * The term id of the term to be updated or resulting from this action.
	 * @var int
	 */
	private $term_id;
	/**
	 * This only gets set for update actions. In this case the new slug has to be compared with the old slug,
	 * to decide whether any slug name sanitation has to happen.
	 * @var string
	 */
	private $old_slug;

	/**
	 * @param wpdb      $wpdb
	 * @param SitePress $sitepress
	 * @param array     $args
	 */
	public function __construct( &$wpdb, &$sitepress, $args ) {
		parent::__construct($wpdb, $sitepress);
		/**
		 * Actual name of the term. Same as the name input argument to \wp_update_term or \wp_insert_term
		 * @var string|bool
		 */
		$term     = false;
		$slug     = '';
		$taxonomy = '';
		/** @var string $lang_code */
		$lang_code = '';
		$trid      = null;
		/** @var int|bool $original_tax_id */
		$original_tax_id = false;
		/**
		 * Taxonomy_term_id of the parent element
		 * @var int
		 */
		$parent          = 0;
		$description     = false;
		$term_group      = false;
		$source_language = null;

		extract( $args, EXTR_OVERWRITE );

		// We cannot create a term unless we at least know its name
		if ( (string)$term !== "" && $taxonomy ) {
			$this->wp_new_term_args[ 'name' ] = $term;
			$this->taxonomy                   = $taxonomy;
		} else {
			$this->is_valid = false;

			return;
		}
		if ( $parent ) {
			$this->wp_new_term_args[ 'parent' ] = $parent;
		}
		if ( $description ) {
			$this->wp_new_term_args[ 'description' ] = $description;
		}
		if ( $term_group ) {
			$this->wp_new_term_args[ 'term_group' ] = $term_group;
		}
		$this->wp_new_term_args[ 'term_group' ] = $term_group;
		$this->is_valid = $this->set_language_information( $trid, $original_tax_id, $lang_code, $source_language );
		$this->set_action_type();
		if ( ! $this->is_update || ( $this->is_update && $slug != $this->old_slug && ! empty( $slug ) ) ) {
			if ( trim( $slug ) == '' ) {
				$slug = sanitize_title( $term );
			}
			$slug = WPML_Terms_Translations::term_unique_slug( $slug, $taxonomy, $lang_code );
			$this->wp_new_term_args[ 'slug' ] = $slug;
		}
	}

	/**
	 * Writes the term update or creation action saved in this object to the database.
	 * @return array|false
	 * Returns either an array containing the term_id and term_taxonomy_id of the term resulting from this database
	 * write or false on error.
	 */
	public function execute() {
		global $sitepress;

		$switch_lang = new WPML_Temporary_Switch_Language( $sitepress, $this->lang_code );

		remove_action( 'create_term', array( $sitepress, 'create_term' ), 1 );
		remove_action( 'edit_term', array( $sitepress, 'create_term' ), 1 );
		add_action( 'create_term', array( $this, 'add_term_language_action' ), 1, 3 );
		$new_term = false;

		if ( $this->is_valid ) {
			if ( $this->is_update && $this->term_id ) {
				$new_term = wp_update_term( $this->term_id, $this->taxonomy, $this->wp_new_term_args );
			} else {
				$new_term = wp_insert_term( $this->wp_new_term_args[ 'name' ], $this->taxonomy, $this->wp_new_term_args );
			}
		}
		add_action( 'create_term', array( $sitepress, 'create_term' ), 1, 3 );
		add_action( 'edit_term', array( $sitepress, 'create_term' ), 1, 3 );
		remove_action( 'create_term', array( $this, 'add_term_language_action' ), 1, 3 );

		if ( ! is_array( $new_term ) ) {
			$new_term = false;
		}

		unset( $switch_lang );
		return $new_term;
	}

	/**
	 * This action is to be hooked to the WP create_term and edit_term hooks.
	 * It sets the correct language information after a term is saved.
	 *
	 * @param int|string $term_id
	 * @param int|string $term_taxonomy_id
	 * @param string     $taxonomy
	 */
	public function add_term_language_action( $term_id, $term_taxonomy_id, $taxonomy ) {
		if ( $this->is_valid && ! $this->is_update && $this->taxonomy == $taxonomy ) {
			$this->sitepress->set_element_language_details( $term_taxonomy_id,
			                                                'tax_' . $taxonomy,
			                                                $this->trid,
			                                                $this->lang_code,
			                                                $this->source_lang_code );
		}
	}

	/**
	 * Sets the language variables for this object.
	 * @param bool|int    $trid
	 * @param bool|int    $original_tax_id
	 * @param string      $lang_code
	 * @param bool|string $source_language
	 * @return bool True if the given language parameters allowed for determining valid language information, false
	 *              otherwise.
	 */
	private function set_language_information( $trid, $original_tax_id, $lang_code, $source_language ) {
		if ( ! $lang_code || ! $this->sitepress->is_active_language( $lang_code ) ) {
			return false;
		} else {
			$this->lang_code = $lang_code;
		}
		if ( ! $trid && $original_tax_id ) {
			$trid = $this->sitepress->get_element_trid( $original_tax_id, 'tax_' . $this->taxonomy );
		}
		if ( $trid ) {
			$this->trid                  = $trid;
			$this->existing_translations = $this->sitepress->get_element_translations( $trid,
			                                                                           'tax_' . $this->taxonomy );
			// Only set valid source languages
			if ( $source_language && isset( $translations[ $source_language ] ) ) {
				$this->source_lang_code = $source_language;
			} else {
				foreach ( $this->existing_translations as $lang => $translation ) {
					if ( $original_tax_id && isset( $translation->element_id ) && $translation->element_id == $original_tax_id && isset( $translation->language_code ) && $translation->language_code ) {
						$this->source_lang_code = $translation->language_code;
						break;
					} elseif ( isset( $translation->language_code ) && $translation->language_code && ! $translation->source_language_code ) {
						$this->source_lang_code = $translation->language_code;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Sets the action type of this object.
	 * In case of this action being an update the is_update flag is set true.
	 * Also the term_id of the existing term is saved in $this->term_id.
	 */
	private function set_action_type() {
		if ( ! $this->trid ) {
			$this->is_update = false;
		} elseif ( isset( $this->existing_translations[ $this->lang_code ] ) ) {
			$existing_db_entry = $this->existing_translations[ $this->lang_code ];
			if ( isset( $existing_db_entry->element_id ) && $existing_db_entry->element_id ) {
				// Term update actions need information about the term_id, not the term_taxonomy_id saved in the element_id column of icl_translations.
				$term = $this->wpdb->get_row( $this->wpdb->prepare(
						"SELECT t.term_id, t.slug FROM {$this->wpdb->terms} AS t
						 JOIN {$this->wpdb->term_taxonomy} AS tt ON t.term_id=tt.term_id
						 WHERE term_taxonomy_id=%d", $existing_db_entry->element_id
					)
				);
				if ( $term->term_id && $term->slug ) {
					$this->is_update = true;
					$this->term_id   = $term->term_id;
					$this->old_slug  = $term->slug;
				} else {
					$this->is_update = false;
				}
			} else {
				$this->sitepress->delete_element_translation( $this->trid, 'tax_' . $this->taxonomy, $this->lang_code );
				$this->is_update = false;
			}
		} else {
			$this->is_update = false;
		}
	}
}