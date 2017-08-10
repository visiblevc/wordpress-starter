<?php

class WPML_TM_Dashboard_Display_Filter {

    private $active_languages = array();
    private $translation_filter;
    private $post_types;
    private $post_statuses;
    private $source_language_code;

    public function __construct(
        $active_languages,
        $source_language_code,
        $translation_filter,
        $post_types,
        $post_statuses
    ) {
        $this->active_languages     = $active_languages;
        $this->translation_filter   = $translation_filter;
        $this->source_language_code = $source_language_code;
        $this->post_types           = $post_types;
        $this->post_statuses        = $post_statuses;
    }

    private function from_lang_select() {
        ?>
        <label for="icl_language_selector">
            <strong><?php echo __( 'Show documents in:', 'wpml-translation-management' ) ?></strong>
        </label>
        <select id="icl_language_selector"
                name="filter[from_lang]" <?php if ( $this->source_language_code ): echo "disabled"; endif; ?> >
            <?php
            foreach ( $this->active_languages as $lang ) {
                $selected = '';
                if ( !$this->source_language_code && $lang[ 'code' ] == $this->translation_filter[ 'from_lang' ] ) {
                    $selected = 'selected="selected"';
                } elseif ( $this->source_language_code && $lang[ 'code' ] == $this->source_language_code ) {
                    $selected = 'selected="selected"';
                }
                ?>
                <option value="<?php echo $lang[ 'code' ] ?>" <?php echo $selected; ?>>
                    <?php
                    echo $lang[ 'display_name' ]; ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function to_lang_select() {
        ?>
        <label for="filter_to_lang">
            <strong><?php _e( 'Translated to:', 'wpml-translation-management' ); ?></strong>
        </label>
        <select id="filter_to_lang" name="filter[to_lang]">
            <option value=""><?php _e( 'All languages', 'wpml-translation-management' ) ?></option>
            <?php
            foreach ( $this->active_languages as $lang ) {
                $selected = selected( $this->translation_filter[ 'to_lang' ], $lang[ 'code' ], false );
                ?>
                <option value="<?php echo $lang[ 'code' ] ?>" <?php echo $selected; ?>>
                    <?php echo $lang[ 'display_name' ] ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function translation_status_select() {
        ?>
        <label for="filter_tstatus">
            <strong><?php echo __( 'Translation status:', 'wpml-translation-management' ) ?></strong>
        </label>
        <select id="filter_tstatus" name="filter[tstatus]">
            <?php
            $option_status = array(
                - 1 => __( 'All documents', 'wpml-translation-management' ),
                ICL_TM_NOT_TRANSLATED => __(
                    'Not translated or needs updating',
                    'wpml-translation-management'
                ),
                ICL_TM_NEEDS_UPDATE => __( 'Needs updating', 'wpml-translation-management' ),
                ICL_TM_IN_PROGRESS => __( 'Translation in progress', 'wpml-translation-management' ),
                ICL_TM_COMPLETE => __( 'Translation complete', 'wpml-translation-management' )
            );
            foreach ( $option_status as $status_key => $status_value ) {
                $selected = selected( $this->translation_filter[ 'tstatus' ], $status_key, false );

                ?>
                <option value="<?php echo $status_key ?>" <?php echo $selected; ?>><?php echo $status_value ?></option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function display_source_lang_locked_message() {
        if ( $this->source_language_code && isset( $this->active_languages[ $this->source_language_code ] ) ) {
            $language_name        = $this->active_languages[ $this->source_language_code ][ 'display_name' ];
            $basket_locked_string = '<p>';
            $basket_locked_string .= sprintf(
                __(
                    'Language filtering has been disabled because you already have items in %s in the basket.',
                    'wpml-translation-management'
                ),
                $language_name
            );
            $basket_locked_string .= '<br/>';
            $basket_locked_string .= __(
                'To re-enable it, please empty the basket or send it for translation.',
                'wpml-translation-management'
            );
            $basket_locked_string .= '</p>';

            ICL_AdminNotifier::display_instant_message( $basket_locked_string, 'information-inline' );
        }
    }

    private function display_basic_filters() {
        ?>
        <tr valign="top">
            <td colspan="2">
                <?php do_action( 'display_basket_notification', 'tm_dashboard_top' ); ?>
                <img id="icl_dashboard_ajax_working" align="right"
                     src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="display: none;" width="16"
                     height="16" alt="loading..."/>
                <br/>
                <?php $this->from_lang_select() ?>
                &nbsp;
                <?php $this->to_lang_select() ?>
                &nbsp;
                <?php $this->translation_status_select() ?>

	            <?php $this->number_of_ducuments_select() ?>
                <br/>
                <?php $this->display_source_lang_locked_message() ?>
            </td>
        </tr>
    <?php
    }

    private function display_post_type_select() {
        $selected_type = isset( $this->translation_filter[ 'type' ] ) ? $this->translation_filter[ 'type' ] : false;
        ?>
        <label for="filter_type"><?php _e( 'Type:', 'wpml-translation-management' ) ?></label>
        <select id="filter_type" name="filter[type]">
            <option value=""><?php _e( 'Any', 'wpml-translation-management' ) ?></option>
            <?php
            foreach ( $this->post_types as $post_type_key => $post_type ) {
                $filter_type_selected = selected( $selected_type, $post_type_key, false );
                ?>
                <option value="<?php echo $post_type_key ?>" <?php echo $filter_type_selected; ?>>
                    <?php echo $post_type->labels->singular_name != "" ? $post_type->labels->singular_name
                        : $post_type->labels->name; ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function filter_title_textbox() {
        ?>
        <label for="filter_title"><?php _e( 'Title:', 'wpml-translation-management' ) ?></label>
        <input type="text" id="filter_title" name="filter[title]"
               value="<?php echo isset( $this->translation_filter[ 'title' ] ) ? $this->translation_filter[ 'title' ]
                   : '' ?>"/>
        <?php if ( !empty( $this->translation_filter[ 'title' ] ) ): ?>
            <input class="button-secondary" type="button"
                   value="<?php esc_attr_e( 'clear', 'wpml-translation-management' ) ?>"
                   onclick="jQuery('#filter_title').val('');jQuery(this).fadeOut();"/>
        <?php endif; ?>
    <?php
    }

    private function display_post_statuses_select() {
        $filter_post_status = isset( $this->translation_filter[ 'status' ] ) ? $this->translation_filter[ 'status' ]
            : false;

        ?>
        <label for="filter_status"><?php _e( 'Status:', 'wpml-translation-management' ) ?></label>
        <select id="filter_status" name="filter[status]">
            <option value=""><?php _e( 'Any', 'wpml-translation-management' ) ?></option>
            <?php
            foreach ( $this->post_statuses as $post_status_k => $post_status ) {
                $post_status_selected = selected( $filter_post_status, $post_status_k, false );
                ?>
                <option value="<?php echo $post_status_k ?>" <?php echo $post_status_selected; ?>>
                    <?php echo $post_status ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function display_parent_filter_select() {
        ?>
        <label for="icl_parent_filter_control"><?php echo __( 'Parent:', 'wpml-translation-management' ) ?></label>
        <select id="icl_parent_filter_control" name="filter[parent_type]">
            <option value=""><?php _e( 'Any', 'wpml-translation-management' ) ?></option>
            <option value="page"
                    <?php if ($this->translation_filter[ 'parent_type' ] == 'page'): ?>selected="selected"<?php endif ?>><?php _e(
                    'Page',
                    'wpml-translation-management'
                ) ?></option>
            <option value="category"
                    <?php if ($this->translation_filter[ 'parent_type' ] == 'category'): ?>selected="selected"<?php endif ?>><?php _e(
                    'Category',
                    'wpml-translation-management'
                ) ?></option>
        </select>
        <?php
        $icl_tm_parent_id_value = isset( $this->translation_filter[ 'parent_id' ] )
            ? $this->translation_filter[ 'parent_id' ] : '';
        $icl_tm_parent_all      = isset( $this->translation_filter[ 'parent_all' ] )
            ? $this->translation_filter[ 'parent_all' ] : '';
        ?>
        <input type="hidden" id="icl_tm_parent_id" value="<?php echo $icl_tm_parent_id_value; ?>"/>
        <input type="hidden" id="icl_tm_parent_all" value="<?php echo $icl_tm_parent_all; ?>"/>
    <?php
    }

    private function display_advanced_filters() {
        ?>
        <tr id="icl_dashboard_advanced_filters" class="wpml-tm-dashboard-filters" valign="top">
            <td>
                <strong><?php echo __( 'Filters:', 'wpml-translation-management' ) ?></strong><br/>
                <fieldset>
                    <div class="fieldset_row">
                        <?php $this->display_post_statuses_select() ?>
                    </div>
                    <div class="fieldset_row">
                        <?php $this->display_post_type_select() ?>
                    </div>
                    <div class="fieldset_row">
                        <?php $this->filter_title_textbox() ?>
                    </div>
                    <div class="fieldset_row">
                        <?php $this->display_parent_filter_select() ?>
                    </div>
                    <div class="fieldset_row" id="icl_parent_filter">
                        <label for="icl_parent_filter_drop" id="icl_parent_filter_label"></label>
                        <select name="filter[parent_id]" id="icl_parent_filter_drop"></select>
                    </div>
                    <div class="fieldset_row">
                        <input id="translation_dashboard_filter" name="translation_dashboard_filter"
                               class="button-primary" type="submit"
                               value="<?php echo __( 'Display', 'wpml-translation-management' ) ?>"/>
                    </div>
                </fieldset>
                <?php // filters end */ ?>

            </td>
            <td class="wpml-tm-dashboard-promo">
							<?php do_action('wpml_tm_dashboard_promo'); ?>
            </td>
        </tr>
    <?php
    }

    public function display() {
        ?>
        <form method="post" name="translation-dashboard-filter"
              action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&amp;sm=dashboard">
            <input type="hidden" name="icl_tm_action" value="dashboard_filter"/>
            <table class="form-table widefat fixed">
                <thead>
                <tr>
                    <th colspan="2">
                        <strong><?php _e(
                                'Select which documents to display',
                                'wpml-translation-management'
                            ) ?></strong>
                    </th>
                </tr>
                </thead>
                <?php
                $this->display_basic_filters();
                $this->display_advanced_filters();
                ?>
            </table>
        </form>
        <br/>
    <?php
    }

	private function number_of_ducuments_select() {
		?>
        <label for="filter_limit_no">
            <strong><?php echo __( 'Number of documents:', 'wpml-translation-management' ) ?></strong>
        </label>
        <select id="filter_limit_no" name="filter[limit_no]">
			<?php
			foreach ( array( 10, 20, 50, 100 ) as $limit ) {
				$selected = selected( $this->translation_filter[ 'limit_no' ], $limit, false );
				?>
                <option value="<?php echo $limit ?>" <?php echo $selected; ?>><?php echo $limit ?></option>
				<?php
			}
			?>
        </select>
		<?php
	}
}