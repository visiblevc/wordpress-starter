<?php

class WPML_LS_Menu_Item {

    /**
     * @see wp_setup_nav_menu_item() to decorate the object
     */
    public $ID;                           // The term_id if the menu item represents a taxonomy term.
    public $attr_title;                   // The title attribute of the link element for this menu item.
    public $classes = array();            // The array of class attribute values for the link element of this menu item.
    public $db_id;                        // The DB ID of this item as a nav_menu_item object, if it exists (0 if it doesn't exist).
    public $description;                  // The description of this menu item.
    public $menu_item_parent;             // The DB ID of the nav_menu_item that is this item's menu parent, if any. 0 otherwise.
    public $object = 'wpml_ls_menu_item'; // The type of object originally represented, such as "category," "post", or "attachment."
    public $object_id;                    // The DB ID of the original object this menu item represents, e.g. ID for posts and term_id for categories.
    public $post_parent;                  // The DB ID of the original object's parent object, if any (0 otherwise).
    public $post_title;                   // A "no title" label if menu item represents a post that lacks a title.
    public $target;                       // The target attribute of the link element for this menu item.
    public $title;                        // The title of this menu item.
    public $type = 'wpml_ls_menu_item';   // The family of objects originally represented, such as "post_type" or "taxonomy."
    public $type_label;                   // The singular label used to describe this type of menu item.
    public $url;                          // The URL to which this menu item points.
    public $xfn;                          // The XFN relationship expressed in the link of this menu item.
    public $_invalid = false;             // Whether the menu item represents an object that no longer exists.

    public $post_type = 'nav_menu_item';  // * Extra property => see [wpmlcore-3855]

    /**
     * WPML_LS_Menu_Item constructor.
     * @param array  $language
     * @param string $item_content
     */
    public function __construct( $language, $item_content ) {
        $this->decorate_object( $language, $item_content );
    }

    /**
     * @param array  $lang
     * @param string $item_content
     */
    private function decorate_object($lang, $item_content ) {
        $this->ID               = isset( $lang['db_id'] ) ? $lang['db_id'] : null;
        $this->object_id        = isset( $lang['db_id'] ) ? $lang['db_id'] : null;
        $this->db_id            = isset( $lang['db_id'] ) ? $lang['db_id'] : null;
        $this->menu_item_parent = isset( $lang['menu_item_parent'] ) ? $lang['menu_item_parent'] : null;
        $this->attr_title       = isset( $lang['display_name'] )
            ? $lang['display_name'] : (  isset( $lang['native_name'] ) ? $lang['native_name'] : '' );
        $this->title            = $item_content;
        $this->url              = isset( $lang['url'] ) ? $lang['url'] : null;

        if ( isset( $lang['css_classes'] ) ) {
            $this->classes = $lang['css_classes'];
            if ( is_string( $lang['css_classes'] ) ) {
                $this->classes = explode( ' ', $lang['css_classes'] );
            }
        }
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function __get( $property ) {
        return isset( $this->{$property} ) ? $this->{$property} : null;
    }
}