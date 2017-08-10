<?php

/**
 * Class WPML_Translation_Tree
 *
 * @package    wpml-core
 * @subpackage taxonomy-term-translation
 */

class WPML_Translation_Tree extends WPML_SP_User {

	private $taxonomy;
	private $tree = array();
	private $trid_levels;
	private $language_order;
	private $term_ids;

	/**
	 * @param SitePress $sitepress
	 * @param string    $element_type
	 * @param object[]  $terms
	 */
	public function __construct( &$sitepress, $element_type, $terms ) {
		if ( ! $terms ) {
			throw new InvalidArgumentException( 'No terms to order given given' );
		}
		parent::__construct( $sitepress );
		$this->taxonomy       = $element_type;
		$this->language_order = $this->get_language_order( $terms );
		$this->tree           = $this->get_tree_from_terms_array( $terms );
	}

	/**
	 * Returns all terms in the translation tree, ordered by hierarchy and as well as alphabetically within a level and/or parent term relationship.
	 *
	 * @return array
	 */
	public function get_alphabetically_ordered_list() {
		$root_list = $this->sort_trids_alphabetically( $this->tree );
		$ordered_list_flattened = array();
		foreach ( $root_list as $root_trid_group ) {
			$ordered_list_flattened = $this->get_children_recursively( $root_trid_group, $ordered_list_flattened );
		}

		return $ordered_list_flattened;
	}

	/**
	 * @param array $terms
	 *
	 * Generates a tree representation of an array of terms objects
	 *
	 * @return array|bool
	 */
	private function get_tree_from_terms_array( $terms ) {
		$trids     = $this->generate_trid_groups( $terms );
		$trid_tree = $this->parse_tree( $trids, false, 0 );

		return $trid_tree;
	}

	/**
	 * Groups an array of terms objects by their trid and language_code
	 *
	 * @param array $terms
	 *
	 * @return array
	 */
	private function generate_trid_groups ( $terms ) {
		$trids = array();
		foreach ( $terms as $term ) {
			$trids [ $term->trid ] [ $term->language_code ] = array(
				'ttid'    => $term->term_taxonomy_id,
				'parent'  => $term->parent,
				'term_id' => $term->term_id,
			);
			if ( isset( $term->name ) ) {
				$trids [ $term->trid ] [ $term->language_code ][ 'name' ] = $term->name;
			}

			$this->term_ids[ ] = $term->term_id;
		}

		return $trids;
	}

	/**
	 * @param            array $trids
	 * @param array|bool|false $root_trid_group
	 * @param int              $level current depth in the tree
	 *                                Recursively turns an array of unordered trid objects into a tree.
	 *
	 * @return array|bool
	 */
	private function parse_tree( $trids, $root_trid_group, $level ) {
		$return = array();

		foreach ( $trids as $trid => $trid_group ) {
			if ( $this->is_root ( $trid_group, $root_trid_group ) ) {
				unset( $trids[ $trid ] );
				if ( !isset( $this->trid_levels[ $trid ] ) ) {
					$this->trid_levels[ $trid ] = 0;
				}
				$this->trid_levels[ $trid ] = max ( array( $level, $this->trid_levels[ $trid ] ) );
				$return [ $trid ] = array(
					'trid'     => $trid,
					'elements' => $trid_group,
					'children' => $this->parse_tree ( $trids, $trid_group, $level + 1 )
				);
			}
		}

		return empty( $return ) ? false : $return;
	}

	/**
	 * @param array|bool $parent
	 * @param array      $children
	 *                   Checks if one trid is the root of another. This is the case if at least one parent child
	 *                   relationship between both trids exists.
	 *
	 * @return bool
     */
    private function is_root( $children, $parent ) {
        $root  = !(bool)$parent;
        foreach ( $this->language_order as $c_lang ) {
            if(!isset($children[ $c_lang ])){
                continue;
            }
            $child_in_lang = $children[ $c_lang ];
            if ( $parent === false ) {
                $root =!( $child_in_lang[ 'parent' ] != 0 || in_array( $child_in_lang[ 'parent' ], $this->term_ids ) );
                break;
            } else {
                foreach ( (array) $parent as $p_lang => $parent_in_lang ) {
                    if ( $c_lang == $p_lang && $child_in_lang[ 'parent' ] == $parent_in_lang[ 'term_id' ] ) {
                        $root = true;
                        break;
                    }
                }
            }
        }

        return $root;
    }

	private function sort_trids_alphabetically( $trid_groups ) {
		$terms_in_trids = array();
		$ordered_trids = array();
		foreach ( $trid_groups as $trid_group ) {
			foreach ( $trid_group[ 'elements' ] as $lang => $term ) {
				if ( ! isset( $terms_in_trids[ $lang ] ) ) {
					$terms_in_trids[ $lang ] = array();
				}
				$trid                                = $trid_group[ 'trid' ];
				$term[ 'trid' ]                      = $trid;
				$terms_in_trids[ $lang ][ $trid ][ ] = $term;
			}
		}

		$sorted = array();
		foreach ( $this->language_order as $lang ) {
			if ( isset( $terms_in_trids[ $lang ] ) ) {
				$terms_in_lang_and_trids = $terms_in_trids[ $lang ];
				$term_names              = array();
				$term_names_numerical    = array();
				foreach ( $terms_in_lang_and_trids as $trid => $terms_in_lang_and_trid ) {
					if ( in_array( $trid, $sorted ) ) {
						continue;
					}
					$term_in_lang_and_trid     = array_pop( $terms_in_lang_and_trid );
					$term_name                 = $term_in_lang_and_trid[ 'name' ];
					$term_names [ $term_name ] = $trid;
					$term_names_numerical [ ]  = $term_name;
				}
				natsort( $term_names_numerical );
				foreach ( $term_names_numerical as $name ) {
					$ordered_trids [ ] = $trid_groups[ $term_names[ $name ] ];
					$sorted[ ]         = $term_names[ $name ];
				}
			}
		}

		return $ordered_trids;
	}

	/**
	 * @param array $trid_group
	 * @param array $existing_list
	 *
	 * Reads in a trid array and appends it and its children to the input array.
	 * This is done in the order parent->alphabetically ordered children -> ( alphabetically ordered children's children) ...
	 *
	 * @return array
	 */
	private function get_children_recursively(
		$trid_group,
		$existing_list = array()
	) {
		$children = $trid_group['children'];
		unset( $trid_group['children'] );
		$existing_list [] = $this->add_level_information_to_terms( $trid_group );
		if ( is_array( $children ) ) {
			$children = $this->sort_trids_alphabetically( $children );
			foreach ( $children as $child ) {
				$existing_list = $this->get_children_recursively( $child,
					$existing_list );
			}
		}

		return $existing_list;
	}

	/**
	 * Adds the hierarchical depth as a variable to all terms.
	 * 0 means, that the term has no parent.
	 *
	 * @param array $tridgroup
	 *
	 * @return array
	 */
	private function add_level_information_to_terms ( $tridgroup ) {
		foreach ( $tridgroup[ 'elements' ] as $lang => &$term ) {
			$level = 0;
			$term_id = $term[ 'term_id' ];
			while ( ( $term_id = wp_get_term_taxonomy_parent_id( $term_id, $this->taxonomy ) ) ) {
				$level ++;
			}
			$term[ 'level' ] = $level;
		}

		return $tridgroup;
	}

	/**
	 * Counts the number of terms per language and returns an array of language codes,
	 * that is ordered by the number of terms in every language.
	 *
	 * @param array $terms
	 *
	 * @return array
	 */
	private function get_language_order( $terms ) {
		$langs        = array();
		$default_lang = $this->sitepress->get_default_language();
		foreach ( $terms as $term ) {
			$term_lang = $term->language_code;
			if ( $term_lang === $default_lang ) {
				continue;
			}
			if ( isset( $langs[ $term_lang ] ) ) {
				$langs[ $term_lang ] += 1;
			} else {
				$langs[ $term_lang ] = 1;
			}
		}
		natsort( $langs );
		$return    = array_keys( $langs );
		$return [] = $default_lang;
		$return    = array_reverse( $return );

		return $return;
	}
}
