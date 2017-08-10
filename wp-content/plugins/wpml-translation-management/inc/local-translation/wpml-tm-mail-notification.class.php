<?php
require_once WPML_TM_PATH . '/inc/local-translation/wpml-tm-blog-translators.class.php';

/**
 * Class WPML_TM_Mail_Notification
 */
class WPML_TM_Mail_Notification extends WPML_WPDB_And_SP_User {

	private $mail_cache = array();
	private $process_mail_queue;

	/** @var  array $notification_settings */
	private $notification_settings;

	/** @var  WPML_TM_Blog_Translators */
	private $blog_translators;

	/** @var WPML_Translation_Job_Factory $job_factory */
	private $job_factory;

	/**
	 * @param SitePress                    $sitepress
	 * @param wpdb                         $wpdb
	 * @param WPML_Translation_Job_Factory $job_factory
	 * @param WPML_TM_Blog_Translators     $blog_translators
	 * @param array                        $notification_settings
	 */
	public function __construct( &$sitepress, &$wpdb, &$job_factory, $blog_translators, array $notification_settings ) {
		parent::__construct( $wpdb, $sitepress );
		$this->job_factory           = &$job_factory;
		$this->blog_translators      = $blog_translators;
		$this->notification_settings = array_merge( array( 'resigned' => 0, 'completed' => 0 ),
		                                            $notification_settings );
	}

	public function init() {
		add_action( 'wpml_tm_empty_mail_queue', array( $this, 'send_queued_mails' ), 10, 0 );
		add_action( 'wpml_tm_new_job_notification', array( $this, 'new_job_notification_mail' ), 10, 1 );
		add_action( 'wpml_tm_complete_job_notification', array( $this, 'wpml_tm_job_complete_mail' ), 10, 2 );
		add_action( 'wpml_tm_assign_job_notification', array( $this, 'new_job_translator_mail' ), 10, 2 );
		add_action( 'wpml_tm_remove_job_notification', array( $this, 'translator_removed_mail' ), 10, 2 );
		add_action( 'wpml_tm_resign_job_notification', array( $this, 'translator_resign_mail' ), 10, 2 );
		add_action( 'icl_ajx_custom_call', array( $this, 'send_queued_mails' ), 10, 0 );
		add_action( 'icl_pro_translation_completed', array( $this, 'send_queued_mails' ), 10, 0 );
	}

	public function send_queued_mails() {
		$tj_url = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php' );
		foreach ( $this->mail_cache as $type => $mail_to_send ) {
			foreach ( $mail_to_send as $to => $subjects ) {
				$body_to_send = '';
				foreach ( $subjects as $subject => $content ) {
					$body = $content['body'];
					$body_to_send .= $body_to_send . "\n\n" . implode( "\n\n\n\n", $body ) . "\n\n\n\n";
					$home_url = get_home_url();
					if ( $type === 'translator' ) {
						$footer = sprintf(
							          __( 'You can view your other translation jobs here: %s', 'sitepress' ),
							          $tj_url
						          ) . "\n\n--\n";
						$footer .= sprintf(
							__(
								"This message was automatically sent by Translation Management running on %s. To stop receiving these notifications contact the system administrator at %s.\n\nThis email is not monitored for replies.",
								'sitepress'
							),
							get_bloginfo( 'name' ),
							$home_url
						);
					} else {
						$footer = "\n--\n" . sprintf(
								__(
									"This message was automatically sent by Translation Management running on %s. To stop receiving these notifications, go to Notification Settings, or contact the system administrator at %s.\n\nThis email is not monitored for replies.",
									'sitepress'
								),
								get_bloginfo( 'name' ),
								$home_url
							);
					}
					$body_to_send .= $footer;
					$attachments  = isset( $content['attachment'] ) ? $content['attachment'] : array();
					$attachments  = apply_filters( 'wpml_new_job_notification_attachments', $attachments );

					/**
					 * @deprecated Use 'wpml_new_job_notification_attachments' instead
					 */
					$attachments  = apply_filters( 'WPML_new_job_notification_attachments', $attachments );
					$this->sitepress->get_wp_api()->wp_mail( $to, $subject, $body_to_send, '', $attachments );
				}
			}
		}
		$this->mail_cache         = array();
		$this->process_mail_queue = false;
	}

	/**
	 * Sends an email to all potential translators for the given job_id
	 *
	 * @param WPML_Translation_Job|int $job
	 *
	 * @return array
	 */
	public function new_job_notification_mail( $job ) {
		$mails = array();
		list( $manager_id, $job ) = $this->get_mail_elements( $job );
		if ( ! $job ) {
			return $mails;
		}
		$job_id      = $job->get_id();
		$translators = $this->blog_translators->get_blog_translators( array( 'to' => $job->get_language_code() ) );

		/** @var WPML_Element_Translation_Job $job */
		$edit_url = strtolower( $job->get_type() ) !== 'string' ? apply_filters(
			'icl_job_edit_url',
			admin_url(
				'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $job_id
			),
			$job_id
		) : "";

		foreach ( $translators as $t ) {
			if ( $manager_id == $t->ID || ! $job->user_can_translate( $t ) ) {
				continue;
			}
			$user_language = $this->sitepress->get_user_admin_language( $t->ID );
			$this->sitepress->switch_locale( $user_language );
			list( $lang_from, $lang_to ) = $this->get_lang_to_from( $job, $user_language );
			if ( strtolower( $job->get_type() ) !== 'string' ) {
				/** @var WPML_Element_Translation_Job $job */
				$post_link = $job->get_url( true );
				$post_link = $post_link != "" ? sprintf( __( "\nView the original document in %s here: %s\n",
				                                             'sitepress' ),
				                                         $lang_from,
				                                         $post_link ) : "";
			} else {
				$post_link = "";
			}
			$mail['to']      = $t->display_name . ' <' . $t->user_email . '>';
			$mail['subject'] = sprintf( __( 'New translation job from %s', 'sitepress' ), get_bloginfo( 'name' ) );
			$mail['body']    = sprintf(
				__( "New job available from %s to %s.\n%s\nStart editing: %s", 'sitepress' ),
				$lang_from,
				$lang_to,
				$post_link,
				$edit_url
			);
			$mail['type']    = 'translator';
			$this->enqueue_mail( $mail );
			$this->sitepress->switch_locale();
			$mails[] = $mail;
		}

		return $mails;
	}

	/**
	 * @param WPML_Translation_Job|int $job
	 * @param bool|false               $update
	 *
	 * @return array representation of the email to be sent
	 */
	public function wpml_tm_job_complete_mail( $job, $update = false ) {
		/** @var WPML_Translation_Job $job */
		list( $manager_id, $job ) = $this->get_mail_elements( $job );
		if ( ! $job || ( $manager_id && $manager_id == $job->get_translator_id() ) ) {
			return false;
		}
		$manager       = new WP_User( $manager_id );
		$translator    = new WP_User( $job->get_translator_id() );
		$user_language = $this->sitepress->get_user_admin_language( $manager->ID );
		$this->sitepress->switch_locale( $user_language );
		list( $lang_from, $lang_to ) = $this->get_lang_to_from( $job, $user_language );
		$jobs_links = "";
		if ( strtolower( $job->get_type() ) !== 'string' ) {
			/** @var WPML_Element_Translation_Job $job */
			$tj_url     = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=jobs' );
			$doc_url    = $job->get_url( false );
			$jobs_links = sprintf( "\n%s\n\nView translation jobs: %s",
			                       $doc_url,
			                       $tj_url );
		}
		$doc_title = $job->get_title();
		$mail      = array();
		if ( $this->notification_settings['completed'] == ICL_TM_NOTIFICATION_IMMEDIATELY ) {
			$mail['to'] = $manager->display_name . ' <' . $manager->user_email . '>';
			if ( $update ) {
				$mail['subject']  = sprintf(
					__( 'Translator has updated translation job for %s', 'sitepress' ),
					get_bloginfo( 'name' )
				);
				$body_placeholder = __(
					"Translator %shas updated translation of job \"%s\" for %s to %s." . $jobs_links,
					'sitepress'
				);
			} else {
				$mail['subject']  = sprintf(
					__( 'Translator has completed translation job for %s', 'sitepress' ),
					get_bloginfo( 'name' ) );
				$body_placeholder = __(
					"Translator %shas completed translation of job \"%s\" for %s to %s." . $jobs_links,
					'sitepress'
				);
			}
			$translator_name = ! empty( $translator->display_name ) ? '(' . $translator->display_name . ') ' : '';
			$mail['body']    = sprintf( $body_placeholder,
			                            $translator_name,
			                            $doc_title,
			                            $lang_from,
			                            $lang_to
			);
			$mail['type']    = 'admin';
			$this->enqueue_mail( $mail );
		}
		$this->sitepress->switch_locale();

		return $mail;
	}

	/**
	 * @param WPML_Translation_Job|int $job
	 * @param int                      $translator_id
	 *
	 * @return bool|false|array representation of the email to be sent, false on error
	 */
	public function new_job_translator_mail( $job, $translator_id ) {
		/** @var WPML_Translation_Job $job */
		list( $manager_id, $job ) = $this->get_mail_elements( $job );
		if ( !$job || $manager_id == $job->get_translator_id() ) {
			return false;
		}
		$user          = new WP_User( $translator_id );
		$edit_url      = "";
		$post_link     = "";
		$user_language = $this->sitepress->get_user_admin_language( $user->ID );
		list( $lang_from, $lang_to ) = $this->get_lang_to_from( $job, $user_language );
		$this->sitepress->switch_locale( $user_language );
		if ( strtolower( $job->get_type() ) !== 'string' ) {
			$edit_url  = apply_filters(
				'icl_job_edit_url',
				admin_url(
					'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $job->get_id()
				),
				$job->get_id()
			);
			$post_link = $this->tm_post_permalink( $job->get_original_element_id() );
			if ( $post_link != "" ) {
				$post_link = sprintf(
					__( "\nView the original document in %s here: %s\n", 'sitepress' ),
					$lang_from,
					$post_link
				);
			}
		}
		$mail['to'] = $user->display_name . ' <' . $user->user_email . '>';
		//$mail['to'] = $user->user_email;
		$mail['subject'] = sprintf( __( 'New translation job from %s', 'sitepress' ), get_bloginfo( 'name' ) );
		//exit;
		$mail['body'] = sprintf( __( "Hi %s,", 'sitepress' ), $user->display_name );
		$mail['body'] .= "\n\n";
		$mail['body'] .= sprintf(
			__( "You have been assigned to new translation job from %s to %s.\n%s\nStart editing: %s", 'sitepress' ),
			$lang_from,
			$lang_to,
			$post_link,
			$edit_url
		);
		$mail['type'] = 'translator';
		$mail         = apply_filters( 'wpml_new_job_notification', $mail, $job->get_id() );

		/**
		 * @deprecated Use 'wpml_new_job_notification' instead
		 */
		$mail         = apply_filters( 'WPML_new_job_notification', $mail, $job->get_id() );
		$this->enqueue_mail( $mail );
		$this->sitepress->switch_locale();

		return $mail;
	}

	/**
	 * @param int                      $translator_id
	 * @param WPML_Translation_Job|int $job
	 *
	 * @return bool
	 */
	public function translator_removed_mail( $translator_id, $job ) {
		/** @var WPML_Translation_Job $job */
		list( $manager_id, $job ) = $this->get_mail_elements( $job );
		if ( $manager_id == $translator_id ) {
			return false;
		}
		$translator    = new WP_User( $translator_id );
		$manager       = new WP_User( $manager_id );
		$user_language = $this->sitepress->get_user_admin_language( $manager->ID );
		$doc_title     = $job->get_title();
		$this->sitepress->switch_locale( $user_language );
		list( $lang_from, $lang_to ) = $this->get_lang_to_from( $job, $user_language );
		$mail['to']      = $translator->display_name . ' <' . $translator->user_email . '>';
		$mail['subject'] = sprintf( __( 'Removed from translation job on %s', 'sitepress' ), get_bloginfo( 'name' ) );
		$mail['body']    = sprintf(
			__( 'You have been removed from the translation job "%s" for %s to %s.', 'sitepress' ),
			$doc_title,
			$lang_from,
			$lang_to
		);
		$mail['type']    = 'translator';
		$this->enqueue_mail( $mail );
		$this->sitepress->switch_locale();

		return $mail;
	}

	/**
	 * @param int                      $translator_id
	 * @param int|WPML_Translation_Job $job_id
	 *
	 * @return array|bool
	 */
	public function translator_resign_mail( $translator_id, $job_id ) {
		/** @var WPML_Translation_Job $job */
		list( $manager_id, $job ) = $this->get_mail_elements( $job_id );
		if ( $manager_id == $translator_id ) {
			return false;
		}
		$translator    = new WP_User( $translator_id );
		$manager       = new WP_User( $manager_id );
		$tj_url        = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=jobs' );
		$doc_title     = $job->get_title();
		$user_language = $this->sitepress->get_user_admin_language( $manager->ID );
		$this->sitepress->switch_locale( $user_language );
		list( $lang_from, $lang_to ) = $this->get_lang_to_from( $job, $user_language );
		$mail = array();
		if ( $this->notification_settings['resigned'] == ICL_TM_NOTIFICATION_IMMEDIATELY ) {
			$mail['to']         = $manager->display_name . ' <' . $manager->user_email . '>';
			$mail['subject']    = sprintf( __( 'Translator has resigned from job on %s', 'sitepress' ),
			                               get_bloginfo( 'name' ) );
			$original_doc_title = $doc_title ? $doc_title : __( "Deleted", "sitepress" );
			$mail['body']       = sprintf(
				__(
					'Translator %s has resigned from the translation job "%s" for %s to %s.%sView translation jobs: %s',
					'sitepress'
				),
				$translator->display_name,
				$original_doc_title,
				$lang_from,
				$lang_to,
				"\n",
				$tj_url
			);
			$mail['type']       = 'admin';
			$this->enqueue_mail( $mail );
		}
		//restore locale
		$this->sitepress->switch_locale();

		return $mail;
	}

	private function enqueue_mail( $mail ) {
		if ( $mail !== 'empty_queue' ) {
			$this->mail_cache[ $mail['type'] ][ $mail['to'] ][ $mail['subject'] ]['body'][] = $mail['body'];
			if ( isset( $mail['attachment'] ) ) {
				$this->mail_cache[ $mail['type'] ][ $mail['to'] ][ $mail['subject'] ]['attachment'][] = $mail['attachment'];
			}
			$this->process_mail_queue = true;
		}
	}

	/**
	 * @param int|WPML_Translation_Job $job_id
	 *
	 * @return array
	 */
	private function get_mail_elements( $job_id ) {
		$job = is_object( $job_id ) ? $job_id : $this->job_factory->get_translation_job( $job_id,
		                                                                                 false,
		                                                                                 0,
		                                                                                 true );
		if ( is_object( $job ) ) {
			$data       = $job->get_basic_data();
			$manager_id = isset( $data->manager_id ) ? $data->manager_id : - 1;
		} else {
			$job        = false;
			$manager_id = false;
		}

		return array( $manager_id, $job );
	}

	/**
	 * @param WPML_Translation_Job $job
	 * @param string               $user_language
	 *
	 * @return array
	 */
	private function get_lang_to_from( $job, $user_language ) {
		$sql       = "SELECT name FROM {$this->wpdb->prefix}icl_languages_translations WHERE language_code=%s AND display_language_code=%s LIMIT 1";
		$lang_from = $this->wpdb->get_var( $this->wpdb->prepare( $sql,
		                                                         $job->get_source_language_code(),
		                                                         $user_language ) );
		$lang_to   = $this->wpdb->get_var( $this->wpdb->prepare( $sql, $job->get_language_code(), $user_language ) );

		return array( $lang_from, $lang_to );
	}

	private function tm_post_permalink( $post_id ) {
		$opost = $this->sitepress->get_wp_api()->get_post( $post_id );
		if ( ! $opost
		     || ( ( $opost->post_status == 'draft' || $opost->post_status == 'private' || $opost->post_status == 'trash' )
		          && $opost->post_author != $this->sitepress->get_wp_api()->get_current_user_id() )
		) {
			$elink = '';
		} else {
			$elink = $this->sitepress->get_wp_api()->get_permalink( $post_id );
		}

		return $elink;
	}
}