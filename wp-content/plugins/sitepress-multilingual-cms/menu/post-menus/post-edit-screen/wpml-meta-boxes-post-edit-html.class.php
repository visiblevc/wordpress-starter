<?php

/**
 * Class WPML_Meta_Boxes_Post_Edit_HTML
 */
class WPML_Meta_Boxes_Post_Edit_HTML extends WPML_SP_And_PT_User {

	private $translation_of_options;
	/** @var  array $allowed_languages */
	private $allowed_languages;
	/** @var  bool $can_translate_post */
	private $can_translate_post;
	/** @var  bool $is_original */
	private $is_original;
	/** @var  WP_Post $post */
	private $post;
	/** @var  string $post_type_label */
	private $post_type_label;
	/** @var  string $selected_language */
	private $selected_language;
	/** @var  string $source_language */
	private $source_language;
	/** @var  array $translations */
	private $translations;
	/** @var  int $trid */
	private $trid;

	/**
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translation
	 */
	function __construct( &$sitepress, &$post_translation ) {
		parent::__construct( $post_translation, $sitepress );
		add_action( 'wpml_post_edit_languages', array( $this, 'render_languages' ), 10, 1 );
	}

	/**
	 * @param null|WP_Post $post
	 */
	public function render_languages( $post = null ) {
		if ( ! $post || ! is_post_type_translated( $post->post_type ) ) {
			return;
		}
		$this->set_post( $post );
		$this->init_post_data();
		$this->post_edit_languages_duplicate_of();

		ob_start();
		$this->post_edit_languages_dropdown();
		$this->connect_translations();
		$this->translation_of();
		$this->languages_actions();
		$this->copy_from_original( $post );
		do_action( 'icl_post_languages_options_after' );
		$contents = ob_get_clean();

		echo $this->is_a_duplicate() ? '<span style="display:none">' . $contents . '</span>' : $contents;
	}

	private function post_edit_languages_duplicate_of() {
		$duplicate_original_id = $this->is_a_duplicate();
		if ( $duplicate_original_id ) {
			?>
			<div class="icl_cyan_box"><?php
				printf( esc_html__( 'This document is a duplicate of %s and it is maintained by WPML.', 'sitepress' ), '<a href="' . esc_url( get_edit_post_link( $duplicate_original_id ) ) . '">' . esc_html( get_the_title( $duplicate_original_id ) ) . '</a>' );
				?>
				<p><input id="icl_translate_independent" class="button-secondary" type="button" value="<?php esc_html_e( 'Translate independently', 'sitepress' ) ?>"/></p>
				<?php wp_nonce_field( 'reset_duplication_nonce', '_icl_nonce_rd' ) ?>
				<i><?php printf( esc_html__( 'WPML will no longer synchronize this %s with the original content.', 'sitepress' ), $this->post->post_type ); ?></i>
			</div>
		<?php
		}
	}

	private function post_edit_languages_dropdown() {
		?>
		<div id="icl_document_language_dropdown" class="icl_box_paragraph">
			<p>
				<label for="icl_post_language">
                    <strong><?php printf( esc_html__( 'Language of this %s', 'sitepress' ), esc_html( $this->post_type_label ) ); ?></strong>
                </label>
			</p>

			<?php
			$disabled_language = disabled( false, $this->can_translate_post, false );
			?>
			<select name="icl_post_language" id="icl_post_language" <?php echo $disabled_language; ?>>
				<?php
				$active_langs = $this->sitepress->get_active_languages();
				$active_langs = apply_filters( 'wpml_active_languages_access', $active_langs, array( 'action'=>'edit' ) );
				$translations = $this->get_translations();
				foreach ( $active_langs as $code => $lang ) {
					if ( ( $code != $this->selected_language && ! in_array( $code, $this->allowed_languages ) )
					     || ( isset( $translations[ $code ] ) && $translations[ $code ] != $this->post->ID )
					) {
						continue;
					}
					?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( true, $this->is_selected_lang( $code, $this->selected_language ), true ); ?>>
						<?php echo esc_html( $lang['display_name'] ); ?>
					</option>
					<?php
				}
				?>
			</select>
			<input type="hidden" name="icl_trid" value="<?php echo esc_attr( $this->get_trid() ); ?>"/>
		</div>
		<?php
	}

	private function connect_translations() {
		if ( 'auto-draft' !== $this->post->post_status ) {
			$trid             = $this->get_trid();
			$current_language = $this->sitepress->get_current_language();
			if ( count( $this->get_translations() ) === 1 && count( $this->sitepress->get_orphan_translations( $trid, $this->post->post_type, $current_language ) ) > 0 ) {
				$args                  = array();
				$args['language_code'] = $this->selected_language;
				$args['display_code']  = $this->sitepress->get_default_language();
				$language_name         = apply_filters( 'wpml_display_single_language_name', null, $args );
				?>
                <div id="icl_document_connect_translations_dropdown" class="icl_box_paragraph">
                    <p>
                        <a class="js-set-post-as-source" href="#">
							<?php esc_html_e( 'Connect with translations', 'sitepress' ); ?>
                        </a>
                    </p>
                    <input type="hidden" id="icl_connect_translations_post_id" name="icl_connect_translations_post_id"
                           value="<?php echo esc_attr( $this->post->ID ); ?>"/>
                    <input type="hidden" id="icl_connect_translations_trid" name="icl_connect_translations_trid"
                           value="<?php echo esc_attr( $trid ); ?>"/>
                    <input type="hidden" id="icl_connect_translations_post_type"
                           name="icl_connect_translations_post_type" value="<?php echo esc_attr( $this->post->post_type ); ?>"/>
                    <input type="hidden"
                           id="icl_connect_translations_language"
                           name="icl_connect_translations_language"
                           value="<?php echo esc_attr( $current_language ); ?>"/>
                    <?php wp_nonce_field( 'get_orphan_posts_nonce', '_icl_nonce_get_orphan_posts' ); ?>
                </div>

                <div class="hidden">
                    <div id="connect_translations_dialog"
                         title="<?php esc_attr_e( 'Choose a post to assign', 'sitepress' ); ?>"
                         data-set_as_source-text="<?php echo esc_attr( sprintf( __( 'Make %s the original language for this %s', 'sitepress' ), $language_name, $this->post->post_type ) ); ?>"
                         data-alert-text="<?php esc_attr_e( "Please make sure to save your post, if you've made any change, before proceeding with this action!", 'sitepress' ); ?>"
                         data-cancel-label="<?php esc_attr_e( 'Cancel', 'sitepress' ); ?>"
                         data-ok-label="<?php esc_attr_e( 'Ok', 'sitepress' ); ?>">
                        <p class="js-ajax-loader ajax-loader">
							<?php esc_html_e( 'Loading', 'sitepress' ); ?>&hellip; <span class="spinner"></span>
                        </p>

                        <div class="posts-found js-posts-found">
                            <label id="post-label" for="post_search">
								<?php esc_html_e( 'Type a post title', 'sitepress' ); ?>: </label>
                            <input id="post_search" type="text">
                        </div>
                        <p class="js-no-posts-found no-posts-found"><?php esc_html_e( 'No posts found', 'sitepress' ) ?></p>
                        <input type="hidden" id="assign_to_trid">
                    </div>
                    <div id="connect_translations_dialog_confirm"
                         title="<?php esc_attr_e( 'Connect this post?', 'sitepress' ); ?>"
                         data-cancel-label="<?php esc_attr_e( 'Cancel', 'sitepress' ); ?>"
                         data-assign-label="<?php esc_attr_e( 'Assign', 'sitepress' ); ?>">
                        <p>
                            <span class="ui-icon ui-icon-alert"></span> <?php esc_html_e( 'You are about to connect the current post with these following posts', 'sitepress' ); ?>
                            : </p>
                        <div id="connect_translations_dialog_confirm_list">
                            <p class="js-ajax-loader ajax-loader">
	                            <?php esc_html_e( 'Loading', 'sitepress' ); ?>&hellip; <span class="spinner"></span>
                            </p>
                        </div> <?php wp_nonce_field( 'get_posts_from_trid_nonce', '_icl_nonce_get_posts_from_trid' ); ?>
						<?php wp_nonce_field( 'connect_translations_nonce', '_icl_nonce_connect_translations' ); ?>
                    </div>
                </div>
				<?php
			}
		}
	}

	private function translation_of() {
		?>
		<div id="translation_of_wrap">
			<?php
			if ( $this->is_a_translation() || ! $this->has_translations() ) {
				$disabled = disabled( false, $this->is_edit_action() && $this->get_trid(), false );
				?>

				<div id="icl_translation_of_panel" class="icl_box_paragraph">
					<label for="icl_translation_of"><?php esc_html_e( 'This is a translation of', 'sitepress' ); ?></label>&nbsp;
					<select name="icl_translation_of" id="icl_translation_of" <?php echo $disabled; ?>>
						<?php
						$this->render_translation_of_options();
						?>
					</select>
					<?php //Add hidden value when the dropdown is hidden ?>
					<?php
					if ( $disabled && ! empty( $source_element_id ) ) {
						?>
						<input type="hidden" name="icl_translation_of" id="icl_translation_of_hidden" value="<?php echo esc_attr( $source_element_id ); ?>">
					<?php
					}
					?>
				</div>
			<?php
			}
			?>
		</div><!--//translation_of_wrap--><?php // don't delete this html comment ?>

		<br clear="all"/>
	<?php
	}

	private function languages_actions() {
		$status_display = new WPML_Post_Status_Display( $this->sitepress->get_active_languages() );

		if ( $this->can_translate() ) {

			do_action( 'icl_post_languages_options_before', $this->post->ID );

			list( $translated_posts, $untranslated_posts ) = $this->count_untranslated_posts();
			?>

			<div id="icl_translate_options">
				<?php
				if ( $untranslated_posts ) {
					$this->languages_table( $status_display );
				}

				if ( $translated_posts > 0 ) {
					$this->translation_summary( $status_display );
				}
				?>

			</div>
		<?php
		}
	}

	private function is_a_translation() {
		return ! $this->is_original && ( $this->selected_language != $this->source_language || ( isset( $_GET[ 'lang' ] ) && $this->source_language !== $_GET[ 'lang' ] ) ) && 'all' !== $this->sitepress->get_current_language();
	}

	private function has_translations() {
		return (bool) $this->get_translations();
	}

	private function render_translation_of_options() {
		$this->init_translation_of_options();
		foreach ( $this->translation_of_options as $option_value => $option_data ) {
			echo sprintf( '<option value="%s" %s>%s</option>', esc_attr( $option_value ), selected( true, $option_data[ 'selected' ], false ), esc_html( $option_data[ 'label' ] ) );
		}
	}

	/**
	 * @return bool
	 */
	private function can_translate() {
		$trid = $this->get_trid();
		$can_translate_args = array(
			'trid'         => $trid,
			'translations' => $this->get_translations(),
		);

		$can_translate_aaa = $trid && ( $this->is_edit_action() || apply_filters( 'wpml_post_edit_can_translate', false, $can_translate_args ) );

		return $can_translate_aaa;
	}

	private function count_untranslated_posts() {
		$untranslated_found = 0;
		$translations_found = 0;
		$active_langs       = $this->sitepress->get_active_languages();
		$translations       = $this->get_translations();
		foreach ( $active_langs as $lang ) {
			if ( $this->selected_language == $lang['code'] ) {
				continue;
			}
			if ( isset( $translations[ $lang['code'] ] ) ) {
				$translations_found += 1;
			} else {
				$untranslated_found += 1;
			}
		}

		return array( $translations_found, $untranslated_found );
	}

	private function languages_table( $status_display ) {
		?>
		<p style="clear:both;"><b><?php esc_html_e( 'Translate this Document', 'sitepress' ); ?></b></p>
		<table width="100%" id="icl_untranslated_table" class="icl_translations_table">
			<tr>
				<th>&nbsp;</th>
				<th align="right"><?php esc_html_e( 'Translate', 'sitepress' ) ?></th>
				<th align="right" width="10" style="padding-left:8px;"><?php echo esc_html__( 'Duplicate', 'sitepress' ) ?></th>
			</tr>
			<?php
			$active_langs = $this->sitepress->get_active_languages();
			$active_langs = apply_filters( 'wpml_active_languages_access', $active_langs, array( 'action' => 'edit', 'post_type' => $this->post_type_label, 'post_id' => $this->post->ID ) );
			foreach ( $active_langs as $lang ) {
				$this->translate_option( $lang, $status_display );
			}
			?>
			<tr>
				<td colspan="3" align="right">
					<input id="icl_make_duplicates" type="button" class="button-secondary" value="<?php echo esc_attr__( 'Duplicate', 'sitepress' ) ?>" disabled="disabled" style="display:none;"/>
					<?php wp_nonce_field( 'make_duplicates_nonce', '_icl_nonce_mdup' ); ?>
				</td>
			</tr>
		</table>
	<?php
	}

	/**
	 * @param WPML_Post_Status_Display $status_display
	 */
	private function translation_summary( $status_display ) {
		$dupes          = $this->sitepress->get_duplicates( $this->post->ID );
		$not_show_flags = ! apply_filters( 'wpml_setting', false, 'show_translations_flag' );
		?>
        <div class="icl_box_paragraph">
            <b><?php esc_html_e( 'Translations', 'sitepress' ) ?></b>
            (<a class="icl_toggle_show_translations" href="#"
			    <?php if ( $not_show_flags ) : ?>style="display:none;"<?php endif; ?>><?php esc_html_e( 'hide', 'sitepress' ); ?></a>
            <a class="icl_toggle_show_translations" href="#"
			   <?php if ( ! $not_show_flags ) : ?>style="display:none;"<?php endif; ?>><?php esc_html_e( 'show', 'sitepress' ) ?></a>)
			<?php wp_nonce_field( 'toggle_show_translations_nonce', '_icl_nonce_tst' ) ?>
            <table width="100%" class="icl_translations_table" id="icl_translations_table"
			       <?php
			       if ( $not_show_flags ) : ?>style="display:none;"<?php endif; ?>>
				<?php
				$odd_even     = 1;
				$active_langs = $this->sitepress->get_active_languages();
				$translations = $this->get_translations();
				?>
				<?php foreach ( $active_langs as $lang ) :
					if ( $this->selected_language === $lang['code'] ) {
						continue;
					} ?>
                    <tr <?php if ( $odd_even < 0 ) : ?>class="icl_odd_row"<?php endif; ?>>
						<?php if ( isset( $translations[ $lang['code'] ] ) ) : ?>
							<?php $odd_even = $odd_even * - 1; ?>
                            <td style="padding-left: 4px;">
								<?php echo esc_html( $lang['display_name'] ); ?>
								<?php if ( isset( $dupes[ $lang['code'] ] ) ) {
									echo ' (' . esc_html__( 'duplicate', 'sitepress' ) . ')';
								} ?>
                            </td>
                            <td align="right">
								<?php echo $status_display->get_status_html( $this->post->ID, $lang['code'] ); ?>
                            </td>

						<?php endif; ?>
                    </tr>
				<?php endforeach; ?>
            </table>
        </div>
		<?php
	}

	private function init_translation_of_options() {
		$this->translation_of_options = array();
		$element_id                   = null;
		if ( $this->trid ) {
			$this->fix_source_language();
			$element_id = $this->post_translation->get_element_id( $this->source_language, $this->trid );
		}
		$this->add_translation_of_option( 'none', __( '--None--', 'sitepress' ), false );
		if ( $element_id && ! isset( $_GET['icl_ajx'] ) ) {
			$element_title = $this->get_element_title( $element_id );
			$this->add_translation_of_option( $element_id, $element_title, true );
		}

		if ( $this->handle_as_original() && apply_filters( 'wpml_language_is_active', null, $this->selected_language )
		) {
			$untranslated = $this->get_untranslated_posts();
			foreach ( $untranslated as $translation_of_id => $translation_of_title ) {
				$this->add_translation_of_option( $translation_of_id, $translation_of_title, false );
			}
		}
	}

	/**
	 * @param string                   $lang
	 * @param WPML_Post_Status_Display $status_display
	 */
	private function translate_option( $lang, $status_display ) {

		static $row = 0;

		if ( $this->selected_language == $lang[ 'code' ] ) {
			return;
		}

		$row_class = 0 === $row % 2 ? 'class="icl_odd_row"' : '';
		?>
		<tr <?php echo $row_class; ?>>
			<?php
			$translations = $this->get_translations();
			if ( ! isset( $translations[ $lang['code'] ] ) ) {
				$row ++;
				?>
				<td style="padding-left: 4px;">
					<?php echo esc_html( $lang['display_name'] ); ?>
				</td>
				<td align="right">
					<?php echo $status_display->get_status_html( $this->post->ID, $lang[ 'code' ] ); ?>
				</td>
				<td align="right">
					<?php
					$disabled_duplication       = false;
					$disabled_duplication_title = esc_attr__( 'Create duplicate', 'sitepress' );
					$element_key                = array( 'trid' => $this->trid, 'language_code' => $lang['code'] );
					$translation_status         = apply_filters( 'wpml_tm_translation_status', null, $element_key );
					echo PHP_EOL . '<!-- $translation_status = ' . $translation_status . ' -->' . PHP_EOL;

					if ( $translation_status && $translation_status < ICL_TM_COMPLETE ) {
						$disabled_duplication       = true;
						if ( ICL_TM_DUPLICATE === (int) $translation_status ) {
							$disabled_duplication_title = esc_attr__( 'This post is already duplicated.', 'sitepress' );
						} else {
							$disabled_duplication_title = esc_attr__( "Can't create a duplicate. A translation is in progress.", 'sitepress' );
						}
					}

					?>
					<input<?php disabled( true, $disabled_duplication ); ?> type="checkbox" name="icl_dupes[]" value="<?php echo esc_attr( $lang['code'] ); ?>" title="<?php echo $disabled_duplication_title ?>"/>
				</td>

			<?php
			}
			?>
		</tr>
	<?php
	}

	private function handle_as_original() {

		return $this->is_original || ! $this->source_language || $this->source_language === $this->selected_language;
	}

	private function add_translation_of_option( $value, $label, $selected ) {
		if ( ! isset( $this->translation_of_options[ $value ] ) ) {
			if ( trim( $label ) == '' ) {
				$label = '{' . __( 'Post without a title', 'sitepress' ) . '}';
			}
			$this->translation_of_options[ $value ] = array( 'label' => $label, 'selected' => $selected );
		}
	}

	private function fix_source_language() {
		if ( ! $this->source_language ) {
			if ( $this->post ) {
				$this->source_language = $this->post_translation->get_source_lang_code( $this->post->ID );
			} else {
				$this->source_language = $this->sitepress->get_default_language();
			}
		}
	}

	/**
	 * @return bool
	 */
	private function is_edit_action() {
		return isset( $_GET['action'] ) && 'edit' === $_GET['action'];
	}

	/**
	 * Helper function to tell if $lang_code should be marked as selected in post language chooser
	 *
	 * @param string     $lang_code         2 letters language code
	 * @param string     $selected_language 2 letters language code
	 *
	 * @return boolean
	 */
	private function is_selected_lang( $lang_code, $selected_language ) {

		return $lang_code === $selected_language
		       || ( ! $this->sitepress->is_active_language( $selected_language )
		            && $lang_code === $this->sitepress->get_default_language() );
	}

	/**
	 * Renders the "Copy From" and "Overwrite With" buttons on the post edit screen.
	 *
	 * @param WP_Post $post
	 *
	 * @hook icl_post_languages_options_after
	 */
	private function copy_from_original( $post ) {
		$trid        = $this->get_trid();
		$source_lang = filter_var( isset( $_GET['source_lang'] ) ? $_GET['source_lang'] : '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$source_lang = 'all' === $source_lang ? $this->sitepress->get_default_language() : $source_lang;
		$lang        = filter_var( isset( $_GET['lang'] ) ? $_GET['lang'] : '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$source_lang = ! $source_lang && isset( $_GET['post'] ) && $lang !== $this->sitepress->get_default_language()
				? $this->post_translation->get_source_lang_code( $post->ID ) : $source_lang;

		if ( $source_lang && $source_lang !== $lang ) {
			$_lang_details    = $this->sitepress->get_language_details( $source_lang );
			$source_lang_name = $_lang_details['display_name'];
			$this->display_copy_from_button( $source_lang, $source_lang_name, $post, $trid );
			$this->display_set_as_dupl_btn( $post,
                                            $source_lang_name,
                                            $this->post_translation->get_element_id( $source_lang, $trid ),
                                            $lang );
		}
	}

	/**
	 * Renders the button for copying the original posts content to the currently edited post on the post edit screen.
	 *
	 * @param string  $source_lang
	 * @param string  $source_lang_name
	 * @param WP_Post $post
	 * @param int     $trid
	 */
	private function display_copy_from_button( $source_lang, $source_lang_name, $post, $trid ) {
		$disabled = trim( $post->post_content ) ? ' disabled="disabled"' : '';
		wp_nonce_field( 'copy_from_original_nonce', '_icl_nonce_cfo_' . $trid );
		echo '<input id="icl_cfo" class="button-secondary" type="button" value="' . sprintf(
		        esc_html__( 'Copy content from %s', 'sitepress' ),
                $source_lang_name
            ) . '"
				onclick="icl_copy_from_original(\'' . esc_js( $source_lang ) . '\', \'' . esc_js( $trid ) . '\')"'
		     . $disabled . ' style="white-space:normal;height:auto;line-height:normal;"/>';
		icl_pop_info(
				esc_html__(
						"This operation copies the content from the original language onto this translation. It's meant for when you want to start with the original content, but keep translating in this language. This button is only enabled when there's no content in the editor.",
						'sitepress'
				),
				'question'
		);
		echo '<br clear="all" />';
	}

	/**
	 * Renders the "Overwrite" button on the post edit screen that allows setting the post as a duplicate of its
	 * original.
	 *
	 * @param WP_Post $post
	 * @param string  $source_lang_name
	 * @param int     $original_post_id
	 * @param string  $post_lang
	 */
	private function display_set_as_dupl_btn( $post, $source_lang_name, $original_post_id, $post_lang ) {
		wp_nonce_field( 'set_duplication_nonce', '_icl_nonce_sd' ) ?>
		<input id="icl_set_duplicate" type="button" class="button-secondary"
		       value="<?php printf( esc_html__( 'Overwrite with %s content.', 'sitepress' ), $source_lang_name ) ?>"
		       style="white-space:normal;height:auto;line-height:normal;"
		       data-wpml_original_post_id="<?php echo absint( $original_post_id ); ?>"
		       data-post_lang="<?php echo esc_attr( $post_lang ); ?>"/>
		<span style="display: none;"><?php echo esc_js(
					sprintf(
							__(
									'The current content of this %s will be permanently lost. WPML will copy the %s content and replace the current content.',
									'sitepress'
							),
							$post->post_type,
							esc_html( $source_lang_name )
					)
			); ?></span>
		<?php icl_pop_info(
				esc_html__(
						"This operation will synchronize this translation with the original language. When you edit the original, this translation will update immediately. It's meant when you want the content in this language to always be the same as the content in the original language.",
						'sitepress'
				),
				'question'
		); ?>
		<br clear="all"/>
		<?php
	}

	private function get_untranslated_posts() {
		$untranslated = array();
		if ( $this->selected_language != $this->sitepress->get_default_language() ) {
			$args['element_type']    = 'post_' . $this->post->post_type;
			$args['target_language'] = $this->selected_language;
			$args['source_language'] = $this->sitepress->get_default_language();
			$untranslated_ids        = apply_filters( 'wpml_elements_without_translations', null, $args );
			foreach ( $untranslated_ids as $id ) {
				$untranslated[ $id ] = get_the_title( $id );
			}
		}

		return $untranslated;
	}


	/**
	 * Wrapper for \WPML_Post_Translation::get_element_translations that retrieves all translations of the currently
	 * edited post.
	 *
	 * @uses \WPML_Post_Translation::get_element_translations
	 *
	 * @return int[]
	 */
	private function get_translations() {

		return $this->post_translation->get_element_translations( false, $this->get_trid() );
	}

	/**
	 * @return int|false
	 */
	private function get_trid() {
		$post_id     = isset( $this->post->ID ) ? $this->post->ID : 0;
		$post_status = isset( $this->post->post_status ) ? $this->post->post_status : '';

		return $this->post_translation->get_save_post_trid( $post_id, $post_status );
	}

	/**
	 * Returns the post title for a given post or a placeholder if no title exists
	 *
	 * @param int $source_element_id
	 *
	 * @return string
	 */
	private function get_element_title( $source_element_id ) {
		$element_title = '';
		if ( $source_element_id && $source_element_id != $this->post->ID ) {
			$element_title = get_the_title( $source_element_id );
			if ( trim( $element_title ) === '' ) {
				$element_title = '{' . esc_html__( 'Post without a title', 'sitepress' ) . '}';
			}
		}

		return $element_title;
	}

	private function init_post_data() {
		global $wp_post_types;

		$this->init_trid_and_selected_language();
		$this->init_source_element_data();

		//globalize some variables to make them available through hooks
		global $icl_meta_box_globals;
		$icl_meta_box_globals = array(
				'active_languages'  => $this->sitepress->get_active_languages(),
				'translations'      => $this->get_translations(),
				'selected_language' => $this->selected_language
		);

		$this->post_type_label = wpml_mb_strtolower( $wp_post_types[ $this->post->post_type ]->labels->singular_name
		                                             != "" ? $wp_post_types[ $this->post->post_type ]->labels->singular_name : $wp_post_types[ $this->post->post_type ]->labels->name );
	}

	/**
	 * Returns the id of the master post in case the currently edited post is a duplicate.
	 *
	 * @return int|bool|false
	 */
	private function is_a_duplicate() {

		return get_post_meta( $this->post->ID, '_icl_lang_duplicate_of', true );
	}

	private function set_post( &$post ) {
		$this->allowed_languages  = $this->get_allowed_target_langs( $post );
		$this->can_translate_post = ! empty( $this->allowed_languages );
		$this->selected_language  = null;
		$this->source_language    = null;
		$this->post_type_label    = null;
		$this->trid               = null;
		$this->translations       = array();
		$this->is_original        = false;
		$this->post               = $post;
	}

	/**
	 * Returns the languages for which a post is missing translations and can be translated to
	 *
	 * @param WP_Post $post
	 *
	 * @return string[] language codes
	 */
	private function get_allowed_target_langs( $post ) {
		$active_languages = $this->sitepress->get_active_languages();
		$can_translate    = array_keys( $active_languages );
		$can_translate    = array_diff(
			$can_translate,
			array( $this->post_translation->get_element_lang_code( $post->ID ) )
		);

		return apply_filters( 'wpml_allowed_target_langs', $can_translate, $post->ID, 'post' );
	}

	private function init_trid_and_selected_language() {
		$current_lang            = $this->sitepress->get_current_language();
		$this->selected_language = $current_lang;
		if ( $this->post->ID && $this->post->post_status !== 'auto-draft' ) {
			$this->trid = $this->post_translation->get_element_trid( $this->post->ID );
			if ( $this->trid ) {
				$this->selected_language = $this->post_translation->get_element_lang_code( $this->post->ID );
			} else {
				$this->sitepress->set_element_language_details( $this->post->ID,
				                                                'post_' . $this->post->post_type,
				                                                null,
				                                                $current_lang );
				$this->trid              = $this->post_translation->get_element_trid( $this->post->ID );
				$this->selected_language = $current_lang;
			}
		} else {
			$this->trid              = isset( $_GET['trid'] ) ? intval( $_GET['trid'] ) : false;
			$this->selected_language = isset( $_GET['lang'] ) ? strip_tags( $_GET['lang'] ) : $current_lang;
		}
		if ( isset( $_GET['lang'] ) ) {
			$this->selected_language = strip_tags( $_GET['lang'] );
		}
	}

	private function init_source_element_data() {
		$this->source_language = isset( $_GET['source_lang'] ) ? filter_var( $_GET['source_lang'],
		                                                                     FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : false;
		$this->is_original     = false;
		if ( ! $this->source_language ) {
			$translations = $this->get_translations();
			if ( isset( $translations[ $this->selected_language ] ) ) {
				$selected_content_translation = $translations[ $this->selected_language ];
				$this->is_original            = (bool) $this->post_translation->get_source_lang_code( $selected_content_translation ) === false;
				if ( ! $this->is_original ) {
					$selected_content_language_details = $this->sitepress->get_element_translations( $selected_content_translation,
					                                                                                 'post_' . $this->post->post_type );
					if ( isset( $selected_content_language_details ) && isset( $selected_content_language_details->source_language_code ) ) {
						$this->source_language = $selected_content_language_details->source_language_code;
					}
				}
			}
		}
	}
}
