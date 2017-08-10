<?php

/**
 * Created by OnTheGo Systems
 */
class WPML_Translations_Queue_Jobs_Model extends WPML_TM_User {

	private $translation_jobs;
	private $tm_api;
	private $post_link_factory;
	private $post_types;
	private $post_type_names = array();

	/**
	 * WPML_Translations_Queue_Jobs_Model constructor.
	 *
	 * @param SitePress                 $sitepress
	 * @param TranslationManagement     $tm_instance
	 * @param WPML_TM_API               $tm_api
	 * @param WPML_TM_Post_Link_Factory $post_link_factory
	 * @param array                     $translation_jobs
	 */
	public function __construct( $sitepress, &$tm_instance, &$tm_api, &$post_link_factory, array $translation_jobs ) {
		parent::__construct( $tm_instance );

		$this->translation_jobs  = $translation_jobs;
		$this->tm_api            = &$tm_api;
		$this->post_link_factory = &$post_link_factory;

		$this->post_types       = $sitepress->get_translatable_documents( true );
		$this->post_types       = apply_filters( 'wpml_get_translatable_types', $this->post_types );
	}

	public function get() {
		$model = array();

		$model[ 'strings' ] = array( 'job_id'    => __( 'Job ID', 'wpml-translation-management' ),
									 'title'     => __( 'Title', 'wpml-translation-management' ),
									 'type'      => __( 'Type', 'wpml-translation-management' ),
									 'language'  => __( 'Language', 'wpml-translation-management' ),
									 'status'    => __( 'Translation status', 'wpml-translation-management' ),
									 'check_all' => __( 'Check all', 'wpml-translation-management' ),
									 'confirm'   => __('Are you sure you want to resign from this job?', 'wpml-translation-management')
								   );

		$model[ 'jobs' ] = array();


		foreach( $this->translation_jobs as $job ) {
			$job->post_title   = apply_filters( 'the_title', $job->post_title );
			$job->tm_post_link = $this->get_post_link( $job );
			$job->post_type    = $this->get_post_type( $job );
			$job->icon         = $this->tm_instance->status2icon_class( $job->status, $job->needs_update );
			$job->status_text  = $this->get_status_text( $job );
			$job->edit_url     = $this->get_edit_url( $job );
			$job->button_text  = $this->get_button_text( $job );
			$job->is_doing_job = $this->is_doing_job( $job );
			$job->resign_text  = $this->get_resign_text( $job );
			$job->resign_url   = $this->get_resign_url( $job );

			$model[ 'jobs' ][] = $job;
		}

		return $model;
	}

	private function get_post_link( $job ) {
		$view_original_text = __( 'View original', 'wpml-translation-management' );
		$tm_post_link       = $this->post_link_factory->view_link_anchor( $job->original_doc_id, $view_original_text );

		$element_type_prefix = $this->tm_instance->get_element_type_prefix_from_job( $job );
		if ( $this->tm_instance->is_external_type( $element_type_prefix ) ) {
			$url          = apply_filters( 'wpml_external_item_url', '', $job->original_doc_id );
			$tm_post_link = '<a href="' . $url . '">' . $view_original_text . '</a>';
		}

		$original_element_type = $job->original_post_type;
		$original_element_type = explode( '_', $original_element_type );
		if ( count( $original_element_type ) > 1 ) {
			unset( $original_element_type[ 0 ] );
		}
		$original_element_type = join( '_', $original_element_type );

		$tm_post_link = apply_filters( 'wpml_document_view_item_link', $tm_post_link, $view_original_text, $job, $element_type_prefix, $original_element_type );

		return $tm_post_link;
	}

	private function get_post_type( $job ) {
		if ( ! isset( $this->post_type_names[ $job->original_post_type ] ) ) {
			$type = $job->original_post_type;
			$name = $type;
			switch ( $job->element_type_prefix ) {
				case 'post':
					$type = substr( $type, 5 );
					break;

				case 'package':
					$type = substr( $type, 8 );
					break;
			}

			if ( isset( $this->post_types[ $type ]) ) {
				$name = $this->post_types[ $type ]->labels->singular_name;
			}

			$this->post_type_names [ $job->original_post_type ] = $name;
		}

		return $this->post_type_names[ $job->original_post_type ];
	}

	private function get_status_text( $job ) {
		$status = $this->tm_api->get_translation_status_label( $job->status );
		if($job->needs_update) {
			$status .= __(' - (needs update)', 'wpml-translation-management');
		}

		return $status;
	}

	private function get_edit_url( $job ) {
		$edit_url = '';
		if ( $job->original_doc_id ) {
			$translation_queue_page = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $job->job_id );
			$edit_url               = apply_filters( 'icl_job_edit_url', $translation_queue_page, $job->job_id );
		}

		return $edit_url;
	}

	private function get_button_text( $job ) {

		$needs_edit  = in_array( $job->status, array( ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS, ICL_TM_COMPLETE ) );
		$is_editable = $job->translator_id > 0 && $needs_edit;
		if ( $is_editable ) {
			if ( $job->status == ICL_TM_COMPLETE ) {
				$button_text = __( 'Edit', 'wpml-translation-management' );
			} else {
				$button_text = __( 'Translate', 'wpml-translation-management' );
			}
		} else {
			$button_text = __( 'Take and translate', 'wpml-translation-management' );
		}

		return $button_text;
	}

	private function get_resign_text( $job ) {
		return $this->is_doing_job( $job ) ? __('Resign', 'wpml-translation-management') : '';
	}

	private function is_doing_job( $job	) {
        return $job->translator_id > 0 && ( $job->status == ICL_TM_WAITING_FOR_TRANSLATOR || $job->status == ICL_TM_IN_PROGRESS );
	}

	private function get_resign_url( $job ) {
		return admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&icl_tm_action=save_translation&resign=1&job_id=' . $job->job_id );
	}

}


