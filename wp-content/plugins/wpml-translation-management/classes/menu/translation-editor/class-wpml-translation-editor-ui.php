<?php

class WPML_Translation_Editor_UI extends WPML_WPDB_And_SP_User {
	private $all_translations;
	/**
	 * @var WPML_Translation_Editor
	 */
	private $editor_object;
	private $job;
	private $original_post;
	private $rtl_original;
	private $rtl_original_attribute_object;
	private $rtl_translation;
	private $rtl_translation_attribute;
	private $is_duplicate = false;
	/**
	 * @var TranslationManagement $tm_instance
	 */
	private $tm_instance;

	/** @var  WPML_Element_Translation_Job $job_instance */
	private $job_instance;

	private $job_factory;
	private $job_layout;
	private $fields;

	/**
	 * @param WPDB $wpdb
	 * @param SitePress $sitepress
	 * @param TranslationManagement $iclTranslationManagement
	 * @param WPML_Element_Translation_Job $job_instance
	 * @param WPML_TM_Job_Action_Factory $job_factory
	 * @param WPML_TM_Job_Layout $job_layout
	 */
	function __construct( &$wpdb, &$sitepress, &$iclTranslationManagement, $job_instance, $job_factory, $job_layout ) {
		parent::__construct( $wpdb, $sitepress );
		$this->tm_instance  = $iclTranslationManagement;
		$this->job_instance = $job_instance;
		$this->job          = $job_instance->get_basic_data();
		$this->job_factory  = $job_factory;
		$this->job_layout   = $job_layout;
		if ( $job_instance->get_translator_id() <= 0 ) {
			$job_instance->assign_to( $sitepress->get_wp_api()->get_current_user_id(), 'local' );
		}
		$job_instance->maybe_load_terms_from_post_into_job( $sitepress->get_setting( 'tm_block_retranslating_terms' ) );

	}

	function render() {
		list( $this->rtl_original, $this->rtl_translation ) = $this->init_rtl_settings();

		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		?>
		<div class="wrap icl-translation-editor wpml-dialog-translate">
			<h1 id="wpml-translation-editor-header" class="wpml-translation-title"></h1>
			<?php
			do_action( 'icl_tm_messages' );
			$this->init_original_post();
			$this->init_editor_object();

			$this->output_model();
			$this->output_wysiwyg_editors();
			$this->output_copy_all_dialog();
			if ( $this->is_duplicate ) {
				$this->output_edit_independently_dialog();
			}
			$this->output_editor_form();
			?>
		</div>
	<?php
	}

	/**
	 * @return array
	 */
	private function init_rtl_settings() {
		$this->rtl_original                  = $this->sitepress->is_rtl( $this->job->source_language_code );
		$this->rtl_translation               = $this->sitepress->is_rtl( $this->job->language_code );
		$this->rtl_original_attribute_object = $this->rtl_original ? ' dir="rtl"' : ' dir="ltr"';
		$this->rtl_translation_attribute     = $this->rtl_translation ? ' dir="rtl"' : ' dir="ltr"';

		return array( $this->rtl_original, $this->rtl_translation );
	}

	private function init_original_post() {
		// we do not need the original document of the job here
		// but the document with the same trid and in the $this->job->source_language_code
		$this->all_translations = $this->sitepress->get_element_translations( $this->job->trid,
			$this->job->original_post_type );
		$this->original_post    = false;
		foreach ( $this->all_translations as $t ) {
			if ( $t->language_code === $this->job->source_language_code ) {
				$this->original_post = $this->tm_instance->get_post( $t->element_id, $this->job->element_type_prefix );
				//if this fails for some reason use the original doc from which the trid originated
				break;
			}
		}
		$this->original_post = $this->original_post
			? $this->original_post
			: $this->tm_instance->get_post( $this->job_instance->get_original_element_id(),
				$this->job->element_type_prefix );

		if ( isset( $this->all_translations[ $this->job->language_code ] ) ) {
			$post_status        = new WPML_Post_Status( $this->wpdb, $this->sitepress->get_wp_api() );
			$this->is_duplicate = $post_status->is_duplicate( $this->all_translations[ $this->job->language_code ]->element_id );
		}

		return $this->original_post;
	}

	private function init_editor_object() {
		global $wpdb;

		$this->editor_object = new WPML_Translation_Editor( $this->sitepress,
			$wpdb,
			$this->job_instance );
	}

	private function output_model() {

		$model = array(
			'requires_translation_complete_for_each_field' => true,
			'hide_empty_fields'                            => true,
			'translation_is_complete'                      => $this->job->status == ICL_TM_COMPLETE,
			'show_media_button'                            => false,
			'is_duplicate'                                 => $this->is_duplicate
		);

		if ( isset( $_GET['return_url'] ) ) {
			$model['return_url'] = filter_var( $_GET['return_url'], FILTER_SANITIZE_URL );
		} else {
			$model['return_url'] = 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php';
		}

		$languages          = new WPML_Translation_Editor_Languages( $this->sitepress, $this->job );
		$model['languages'] = $languages->get_model();

		$header          = new WPML_Translation_Editor_Header( $this->job_instance );
		$model['header'] = $header->get_model();

		$model['note'] = $this->sitepress->get_wp_api()->get_post_meta( $this->job_instance->get_original_element_id(),
			'_icl_translator_note',
			true );

		$this->fields             = $this->job_factory->field_contents( (int) $this->job_instance->get_id() )->run();
		$this->fields             = $this->add_titles_and_adjust_styles( $this->fields );
		$this->fields             = $this->add_rtl_attribues( $this->fields );
		$model['fields']          = $this->fields;
		$model['layout']          = $this->job_layout->run( $model['fields'], $this->tm_instance );
		$model['rtl_original']    = $this->rtl_original;
		$model['rtl_translation'] = $this->rtl_translation;

		$model = $this->filter_the_model( $model );
		?>
		<script type="text/javascript">
			var WpmlTmEditorModel = <?php echo json_encode( $model ); ?>;
		</script>
	<?php
	}

	private function output_wysiwyg_editors() {
		echo '<div style="display: none">';

		foreach ( $this->fields as $field ) {
			if ( $field['field_style'] == 2 ) {
				$this->editor_object->output_editors( $field );
			}
		}

		echo '</div>';
	}

	private function output_copy_all_dialog() {
		?>
		<div id="wpml-translation-editor-copy-all-dialog" class="wpml-dialog" style="display:none"
		     title="<?php echo __( 'Copy all fields from original', 'wpml-translation-management' ); ?>">
			<p class="wpml-dialog-cols-icon">
				<i class="otgs-ico-copy wpml-dialog-icon-xl"></i>
			</p>

			<div class="wpml-dialog-cols-content">
				<p>
					<strong><?php _e( 'Some fields in translation are already filled!', 'wpml-translation-management' ); ?></strong>
					<br/>
					<?php _e( 'You have two ways to copy content from the original language:', 'wpml-translation-management' ); ?>
				</p>
				<ul>
					<li><?php _e( 'copy to empty fields only', 'wpml-translation-management' ); ?></li>
					<li><?php _e( 'copy and overwrite all fields', 'wpml-translation-management' ); ?></li>
				</ul>
			</div>

			<div class="wpml-dialog-footer">
				<div class="alignleft">
					<button
						class="cancel wpml-dialog-close-button js-copy-cancel"><?php echo __( 'Cancel', 'wpml-translation-management' ); ?></button>
				</div>
				<div class="alignright">
					<button
						class="button-secondary js-copy-not-translated"><?php echo __( 'Copy to empty fields only', 'wpml-translation-management' ); ?></button>
					<button
						class="button-secondary js-copy-overwrite"><?php echo __( 'Copy & Overwrite all fields', 'wpml-translation-management' ); ?></button>
				</div>
			</div>

		</div>
	<?php
	}

	private function output_edit_independently_dialog() {
		?>
		<div id="wpml-translation-editor-edit-independently-dialog" class="wpml-dialog" style="display:none"
		     title="<?php echo __( 'Edit independently', 'wpml-translation-management' ); ?>">
			<p class="wpml-dialog-cols-icon">
				<i class="otgs-ico-unlink wpml-dialog-icon-xl"></i>
			</p>

			<div class="wpml-dialog-cols-content">
				<p><?php esc_html_e( 'This document is a duplicate of:', 'wpml-translation-management' ); ?>
					<span class="wpml-duplicated-post-title">
							<img class="wpml-title-flag"
							     src="<?php echo $this->sitepress->get_flag_url( $this->job->source_language_code ); ?>">
						<?php echo esc_html( $this->job_instance->get_title() ); ?>
						</span>
				</p>

				<p>
					<?php echo esc_html( sprintf( __( 'WPML will no longer synchronize this %s with the original content.', 'wpml-translation-management' ), $this->job_instance->get_type_title() ) ); ?>
				</p>
			</div>

			<div class="wpml-dialog-footer">
				<div class="alignleft">
					<button
						class="cancel wpml-dialog-close-button js-edit-independently-cancel"><?php echo __( 'Cancel', 'wpml-translation-management' ); ?></button>
				</div>
				<div class="alignright">
					<button
						class="button-secondary js-edit-independently"><?php echo __( 'Edit independently', 'wpml-translation-management' ); ?></button>
				</div>
			</div>
		</div>
	<?php
	}

	private function output_editor_form() {
		?>
		<form id="icl_tm_editor" method="post" action="">
			<input type="hidden" name="job_post_type" value="<?php echo esc_attr( $this->job->original_post_type ) ?>"/>
			<input type="hidden" name="job_post_id" value="<?php echo esc_attr( $this->job->original_doc_id ) ?>"/>
			<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_instance->get_id() ) ?>"/>

			<div id="wpml-translation-editor-wrapper"></div>
		</form>
	<?php
	}

	private function add_titles_and_adjust_styles( $fields ) {
		foreach ( $fields as &$field ) {
			$field['title'] = $field['field_type'];
			if ( $this->is_external_element() ) {
				// Get human readable string Title and editor style from the WPML string package.
				$field['title']       = apply_filters( 'wpml_tm_editor_string_name', $field['field_type'], $this->original_post );
				$field['field_style'] = (string) apply_filters( 'wpml_tm_editor_string_style', $field['field_style'], $field['field_type'], $this->original_post );
			} else if ( $this->is_a_custom_field( $field ) ) {
				$custom_field_data    = $this->custom_field_data( (object) $field );
				$field                = (array) $custom_field_data[2];
				$field['title']       = $custom_field_data[0];
				$field['field_style'] = (string) $custom_field_data[1];
			} else if ( $this->is_a_term( $field ) ) {
				$field['title'] = '';
			} else {
				switch ( $field['field_type'] ) {
					case 'title':
						$field['title'] = __( 'Title', 'wpml-translation-management' );
						break;

					case 'body':
						$field['title'] = __( 'Body', 'wpml-translation-management' );
						break;

					case 'excerpt':
						$field['title']       = __( 'Excerpt', 'wpml-translation-management' );
						$field['field_style'] = '1';
						break;
				}
			}
		}

		return apply_filters( 'wpml_tm_adjust_translation_fields', $fields, $this->job );
	}

	private function add_rtl_attribues( $fields ) {
		foreach ( $fields as &$field ) {
			$field['original_direction']    = $this->rtl_original ? 'dir="rtl"' : 'dir="ltr"';
			$field['translation_direction'] = $this->rtl_translation ? 'dir="rtl"' : 'dir="ltr"';
		}

		return $fields;
	}

	private function filter_the_model( $model ) {
		$job_details = array(
			'job_type' => $this->job->original_post_type,
			'job_id'   => $this->job->original_doc_id,
			'target'   => $model['languages']['target']
		);
		$job         = apply_filters( 'wpml-translation-editor-fetch-job', null, $job_details );
		if ( $job ) {
			$model['requires_translation_complete_for_each_field'] = $job->requires_translation_complete_for_each_field();
			$model['hide_empty_fields']                            = $job->is_hide_empty_fields();
			$model['show_media_button']                            = $job->show_media_button();

			$model['fields'] = $this->add_rtl_attribues( $job->get_all_fields() );
			$this->fields    = $model['fields'];

			$model['layout'] = $job->get_layout_of_fields();
		}

		return $model;
	}

	private function is_external_element() {

		return $this->tm_instance->is_external_type( $this->job->element_type_prefix );
	}

	private function is_a_custom_field( $field ) {
		return ( 0 === strpos( $field['field_type'], 'field-' ) );
	}

	/**
	 * Applies filters to a custom field job element.
	 * Custom fields that were named with numeric suffixes are stripped of these suffixes.
	 *
	 * @param object $element
	 *
	 * @return array
	 */
	private function custom_field_data( $element ) {
		$unfiltered_type    = WPML_TM_Field_Type_Sanitizer::sanitize( $element->field_type );
		$element_field_type = $unfiltered_type;
		/**
		 * @deprecated Use `wpml_editor_custom_field_name` filter instead
		 * @since 3.2
		 */
		$element_field_type = apply_filters( 'icl_editor_cf_name',
			$element_field_type );
		$element_field_type = apply_filters( 'wpml_editor_custom_field_name',
			$element_field_type );

		$element_field_style = 0;
		/**
		 * @deprecated Use `wpml_editor_custom_field_style` filter instead
		 * @since 3.2
		 */
		$element_field_style = apply_filters( 'icl_editor_cf_style',
			$element_field_style,
			$unfiltered_type );
		$element_field_style = apply_filters( 'wpml_editor_custom_field_style',
			$element_field_style,
			$unfiltered_type );

		$element = apply_filters( 'wpml_editor_cf_to_display', $element,
			$this->job_instance );

		$settings            = new WPML_Custom_Field_Editor_Settings( $unfiltered_type, $this->tm_instance );
		$element_field_type  = $settings->filter_name( $element_field_type );
		$element_field_style = $settings->filter_style( $element_field_style );

		return array( $element_field_type, $element_field_style, $element );
	}

	private function is_a_term( $field ) {
		return preg_match( '/^t_/', $field['field_type'] );
	}
}
