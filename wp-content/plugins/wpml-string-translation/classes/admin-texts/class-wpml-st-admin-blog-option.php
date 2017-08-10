<?php

class WPML_ST_Admin_Blog_Option extends WPML_SP_User {

	/** @var WPML_ST_Admin_Option_Translation $admin_option */
	private $admin_option;

	/**
	 * WPML_ST_Admin_Blog_Option constructor.
	 *
	 * @param SitePress               $sitepress
	 * @param WPML_String_Translation $st_instance
	 * @param string                  $option_name
	 */
	public function __construct(
		&$sitepress,
		&$st_instance,
		$option_name
	) {
		if ( ! in_array( $option_name, array( 'Tagline', 'Blog Title' ),
			true )
		) {
			throw  new InvalidArgumentException( $option_name . ' Is not a valid blog option that is handled by this class, allowed values are "Tagline" and "Blog Title"' );
		}
		parent::__construct( $sitepress );
		$this->admin_option = $st_instance->get_admin_option( $option_name );
	}

	/**
	 * @param string $old_value
	 * @param string $new_value
	 *
	 * @return mixed
	 */
	public function pre_update_filter(
		$old_value,
		$new_value
	) {
		$wp_api = $this->sitepress->get_wp_api();
		if ( $wp_api->is_multisite() && $wp_api->ms_is_switched() && ! $this->sitepress->get_setting( 'setup_complete' ) ) {
			throw new RuntimeException( 'You cannot update blog option translations while switched to a blog on which the WPML setup is not complete! You are currently using blog ID:' . $this->sitepress->get_wp_api()->get_current_blog_id() );
		}
		WPML_Config::load_config_run();

		return $this->admin_option->update_option( '',
			$new_value, ICL_TM_COMPLETE ) ? $old_value : $new_value;
	}
}