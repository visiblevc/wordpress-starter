<?php

class WPML_Translation_Selector extends WPML_SP_User {

    private $default_language_code;
    private $source_language_code;
    private $element_id;

    public function __construct( &$sitepress, $default_language, $source_language, $element_id ) {
        $this->default_language_code = $default_language;
        $this->source_language_code = $source_language;
        $this->element_id = $element_id;
        parent::__construct( $sitepress );
    }

    function add_translation_of_selector_to_page( $trid, $current_language, $selected_language, $untranslated_ids ) {
        $default_language = $this->default_language_code;
        $source_language = $this->source_language_code;
        ?>
        <input type="hidden" name="icl_trid" value="<?php echo $trid ?>"/>

        <?php
        if ( $selected_language !== $default_language && 'all' !== $current_language ) {
            ?>
            <br/><br/>
            <?php echo __ ( 'This is a translation of', 'sitepress' ); ?><br/>
            <select name="icl_translation_of"
                    id="icl_translation_of"<?php if ( ! $this->sitepress->get_wp_api()->is_term_edit_page() && $trid ) {
                echo " disabled";
            } ?>>
                <?php
                    if ( $trid ) {
                        ?>
                        <option value="none"><?php echo __ ( '--None--', 'sitepress' ) ?></option>
                        <?php
                        $src_term = $this->get_original_name_by_trid($trid);
                        if ( $src_term !== null ) {
                            ?>
                            <option value="<?php echo $src_term->ttid; ?>"
                                    selected="selected"><?php echo $src_term->name; ?></option>
                        <?php
                        }
                    } else {
                        ?>
                        <option value="none" selected="selected"><?php echo __ ( '--None--', 'sitepress' ); ?></option>
                    <?php
                    }
                if ( !$source_language || $source_language === $default_language ) {
                    foreach ( $untranslated_ids as $translation_of_id ) {
                        $title = $this->get_name_by_ttid ( $translation_of_id );
                        if ( $title !== null ) {
                            ?>
                            <option value="<?php echo $translation_of_id; ?>"><?php echo $title; ?></option>
                        <?php
                        }
                    }
                }
                ?>
            </select>
        <?php
        }
    }

    private function get_name_by_ttid( $ttid ) {
        global $wpdb;

        return $wpdb->get_var (
            $wpdb->prepare (
                " SELECT t.name
                  FROM {$wpdb->terms} t
                  JOIN {$wpdb->term_taxonomy} tt
                    ON t.term_id = tt.term_id
                  WHERE tt.term_taxonomy_id = %d
                  LIMIT 1",
                $ttid
            )
        );
    }

    /**
     * @param int $trid
     * @return null|object
     */
    private function get_original_name_by_trid( $trid ) {
        global $wpdb;

        $src_snippet = $this->source_language_code ? $wpdb->prepare (
            " AND language_code = %s LIMIT 1",
            $this->source_language_code
        ) : '';

        $all_translations = $wpdb->get_results (
            $wpdb->prepare (
                " SELECT t.name, i.element_id as ttid, i.language_code
                  FROM {$wpdb->terms} t
                  JOIN {$wpdb->term_taxonomy} tt
                    ON t.term_id = tt.term_id
                  JOIN {$wpdb->prefix}icl_translations i
                    ON i.element_type = CONCAT('tax_', tt.taxonomy)
                      AND i.element_id = tt.term_taxonomy_id
                  WHERE i.trid = %d
                    AND i.element_id != %d
                  {$src_snippet}",
                $trid,
                $this->element_id
            )
        );
        $res = null;
        foreach ( $all_translations as $translation ) {
            $res = $res === null ? $translation : $res;
            if ( $translation->language_code === $this->default_language_code ) {
                $res = $translation;
                break;
            }
        }

        return $res;
    }
}