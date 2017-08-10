<?php

Class WPML_ST_User_Fields {

	/**
	 * @var string
	 */
	private $context = 'Authors';

	/**
	 * @var mixed|WP_User|null
	 */
	private $authordata;

	public function __construct( SitePress $sitepress, &$authordata ) {
		$this->authordata = &$authordata;
		$this->sitepress  = $sitepress;
	}

	public function init_hooks() {
		if ( ! is_admin() ) {
			add_action( 'init', array( $this, 'add_get_the_author_field_filters' ) );
			add_filter( 'the_author', array( $this, 'the_author_filter' ), 10, 2 );
		}

		add_action( 'profile_update', array( $this, 'profile_update_action' ), 10 );
		add_action( 'user_register',  array( $this, 'profile_update_action' ), 10 );
	}

	public function add_get_the_author_field_filters() {
		$translatable_fields = $this->get_translatable_meta_fields();
		foreach ( $translatable_fields as $field ) {
			add_filter( "get_the_author_{$field}", array( $this, 'get_the_author_field_filter' ), 10, 2 );
		}
	}

	/**
	 * @param int $user_id
	 */
	public function profile_update_action( $user_id ) {
		$this->register_user_strings( $user_id );
	}

	/**
	 * @param int $user_id
	 */
	private function register_user_strings( $user_id ) {
		if ( $this->is_user_role_translatable( $user_id ) ) {
			$fields = $this->get_translatable_meta_fields();
			foreach( $fields as $field ){
				$name    = $this->get_string_name( $field, $user_id );
				$value   = get_the_author_meta( $field, $user_id );
				icl_register_string( $this->context, $name, $value, true );
			}
		}
	}

	/**
	 * @param string $value
	 * @param int $user_id
	 *
	 * @return string
	 */
	public function get_the_author_field_filter( $value, $user_id ) {
		$field = preg_replace( '/get_the_author_/', '', current_filter(), 1);
		return $this->translate_user_meta_field( $field, $value, $user_id );
	}

	/**
	 * This filter will only replace the "display_name" of the current author (in global $authordata)
	 *
	 * @param mixed|string|null $value
	 *
	 * @return mixed|string|null
	 */
	public function the_author_filter( $value ) {
		if ( isset( $this->authordata->ID ) ) {
			$value = $this->translate_user_meta_field( 'display_name', $value, $this->authordata->ID );
		}
		return $value;
	}

	/**
	 * @param string $field
	 * @param string $value
	 * @param mixed|int|null $user_id
	 *
	 * @return string
	 */
	private function translate_user_meta_field( $field, $value, $user_id = null ) {
		if ( !is_admin() && $this->is_user_role_translatable( $user_id ) ) {
			$name = $this->get_string_name( $field, $user_id );
			$value = icl_translate( $this->context, $name, $value, true);
		}
		return $value;
	}

	/**
	 * @return array
	 */
	private function get_translatable_meta_fields() {
		$default_fields = array(
			'first_name',
			'last_name',
			'nickname',
			'description',
			'display_name',
		);

		return apply_filters( 'wpml_translatable_user_meta_fields', $default_fields );
	}

	/**
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function is_user_role_translatable( $user_id ) {
		$ret = false;
		$translated_roles = $this->get_translated_roles();
		$user             = new WP_User( $user_id );
		if ( is_array( $user->roles ) && array_intersect( $user->roles, $translated_roles ) ) {
			$ret = true;
		}
		return $ret;
	}

	/**
	 * @return array
	 */
	private function get_translated_roles() {
		$st_settings      = $this->sitepress->get_setting( 'st' );
		return isset( $st_settings['translated-users'] ) && is_array( $st_settings['translated-users'] )
			? $st_settings['translated-users'] : array();
	}

	/**
	 * @param string $field
	 * @param int $user_id
	 *
	 * @return string
	 */
	private function get_string_name( $field, $user_id ) {
		return $field . '_' . $user_id;
	}

	/**
	 * @return array
	 */
	public function init_register_strings() {
		$processed_ids    = array();
		$translated_roles = $this->get_translated_roles();
		$blog_id = get_current_blog_id();
		foreach ( $translated_roles as $role ) {
			$args = array(
				'blog_id' => $blog_id,
				'fields'  => 'ID',
				'exclude' => $processed_ids,
				'role'    => $role,
			);
			$users = get_users( $args );
			foreach( $users as $user_id ){
				$this->register_user_strings( $user_id );
				$processed_ids[] = $user_id;
			}
		}
		return $processed_ids;
	}
}