<?php

class WPML_TM_Widget_Filter extends WPML_SP_User {

	/** @var  WPML_ST_String_Factory $string_factory */
	private $string_factory;

	/**
	 * WPML_TM_Widget_Filter constructor.
	 *
	 * @param  SitePress              $sitepress
	 * @param  WPML_ST_String_Factory $string_factory
	 */
	public function __construct( &$sitepress, &$string_factory ) {
		parent::__construct( $sitepress );
		$this->string_factory = &$string_factory;
	}

	/**
	 * @param string              $notice
	 * @param array               $custom_posts
	 * @param WPML_Admin_Notifier $admin_notifier
	 *
	 * @return string
	 */
	public function filter_cpt_dashboard_notice( $notice, $custom_posts, $admin_notifier ) {
		$slug_settings = $this->sitepress->get_setting( 'posts_slug_translation' );
		foreach ( $custom_posts as $k => $custom_post ) {
			$_has_slug = isset( $custom_post->rewrite['slug'] ) && $custom_post->rewrite['slug'];
			if ( $_has_slug ) {
				if ( $slug_settings['on']
				     && ! empty( $slug_settings['types'][ $k ] )
				     && ICL_TM_COMPLETE !== $this->string_factory->find_by_name( 'Url slug: ' . $k )->get_status()
				) {
					$message = sprintf( __( "%s slugs are set to be translated, but they are missing their translation", 'sitepress' ), $custom_post->labels->name );
					$notice .= $admin_notifier->display_instant_message( $message, 'error', 'below-h2', true );
				}
			}
		}

		return $notice;
	}
}