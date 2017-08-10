<?php

interface IWPML_PB_Strategy {

	/**
	 * @param $post
	 *
	 */
	public function register_strings( $post );

	/**
	 * @param WPML_PB_Factory $factory
	 *
	 */
	public function set_factory( $factory );

	public function get_package_key( $page_id );
	public function get_package_kind();
	public function get_update_post( $package_data );
	public function get_content_updater();
	public function get_package_strings( $package_data );
	public function remove_string( $string_data );
}
