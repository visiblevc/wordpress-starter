<?php

class WPML_PB_API_Hooks_Strategy implements IWPML_PB_Strategy {

	/** @var  WPML_PB_Factory $factory */
	private $factory;
	private $name;

	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * @param $post
	 *
	 */
	public function register_strings( $post ) {
		do_action( 'wpml_page_builder_register_strings', $post, $this->get_package_key( $post->ID ) );
	}

	public function set_factory( $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @param int $page_id
	 *
	 * @return array
	 */
	public function get_package_key( $page_id ) {
		return array(
			'kind'    => $this->get_package_kind(),
			'name'    => $page_id,
			'title'   => 'Page Builder Page ' . $page_id,
			'post_id' => $page_id,
		);
	}

	public function get_package_kind() {
		return $this->name;
	}

	public function get_update_post( $package_data) {
		return $this->factory->get_update_post( $package_data, $this );
	}

	public function get_content_updater() {
		return $this->factory->get_api_hooks_content_updater( $this );
	}

	public function get_package_strings( $package_data ) {
		$this->factory->get_string_translations( $this )->get_package_strings( $package_data );
	}
	public function remove_string( $string_data ) {
		$this->factory->get_string_translations( $this )->remove_string( $string_data );
	}
}
