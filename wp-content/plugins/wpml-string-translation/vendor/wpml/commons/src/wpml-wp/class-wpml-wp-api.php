<?php

class WPML_WP_API {
	public function get_file_mime_type( $filename ) {

		$mime_type = 'application/octet-stream';
		if ( file_exists( $filename ) ) {
			if ( function_exists( 'finfo_open' ) ) {
				$finfo     = finfo_open( FILEINFO_MIME_TYPE ); // return mime type ala mimetype extension
				$mime_type = finfo_file( $finfo, $filename );
				finfo_close( $finfo );
			} else {
				$mime_type = mime_content_type( $filename );
			}
		}

		return $mime_type;
	}

	/**
	 * Wrapper for \get_option
	 *
	 * @param string     $option
	 * @param bool|false $default
	 *
	 * @return mixed|void
	 */
	public function get_option( $option, $default = false ) {

		return get_option( $option, $default );
	}

	public function is_url( $value ) {
		$regex = "((https?|ftp)\:\/\/)?"; // SCHEME
		$regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
		$regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
		$regex .= "(\:[0-9]{2,5})?"; // Port
		$regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
		$regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
		$regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor

		return preg_match("/^$regex$/", $value);
	}

	public function get_transient( $transient ) {
		return get_transient( $transient );
	}

	public function set_transient( $transient, $value, $expiration = 0 ) {
		set_transient( $transient, $value, $expiration );
	}

	/**
	 * @param string      $option
	 * @param mixed       $value
	 * @param string|bool $autoload
	 *
	 * @return bool False if value was not updated and true if value was updated.
	 */
	public function update_option( $option, $value, $autoload = null ) {
		return update_option( $option, $value, $autoload );
	}

	/**
	 * @param string|int|WP_Post $ID Optional. Post ID or post object. Default empty.
	 *
	 * @return false|string
	 */
	public function get_post_status( $ID = ''  ) {
		return get_post_status($ID);
	}

	/**
	 * Wrapper for \get_term_link
	 *
	 * @param  object|int|string $term
	 * @param string             $taxonomy
	 *
	 * @return string|WP_Error
	 */
	public function get_term_link( $term, $taxonomy = '' ) {

		return get_term_link( $term, $taxonomy );
	}

	/**
	 *  Wrapper for \get_term_by
	 *
	 * @param string     $field
	 * @param string|int $value
	 * @param string     $taxonomy
	 * @param string     $output
	 * @param string     $filter
	 *
	 * @return bool|WP_Term
	 */
	public function get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
		return get_term_by( $field, $value, $taxonomy, $output, $filter );
	}

	/**
	 * Wrapper for \add_submenu_page
	 *
	 * @param              $parent_slug
	 * @param              $page_title
	 * @param              $menu_title
	 * @param              $capability
	 * @param              $menu_slug
	 * @param array|string $function
	 *
	 * @return false|string
	 */
	public function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {

		return add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	/**
	 * @param              $page_title
	 * @param              $menu_title
	 * @param              $capability
	 * @param              $menu_slug
	 * @param array|string $function
	 * @param string       $icon_url
	 * @param null         $position
	 *
	 * @return string
	 */
	public function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {

		return add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	}

	/**
	 * Wrapper for \get_post_type_archive_link
	 *
	 * @param string $post_type
	 *
	 * @return string
	 */
	public function get_post_type_archive_link( $post_type ) {

		return get_post_type_archive_link( $post_type );
	}

	/**
	 * Wrapper for \get_edit_post_link
	 *
	 * @param int    $id
	 * @param string $context
	 *
	 * @return null|string|void
	 */
	public function get_edit_post_link( $id = 0, $context = 'display' ) {

		return get_edit_post_link( $id, $context );
	}

	/**
	 * Wrapper for get_the_title
	 *
	 * @param int|WP_Post $post
	 *
	 * @return string
	 */
	public function get_the_title( $post ) {

		return get_the_title( $post );
	}

	/**
	 * Wrapper for \get_day_link
	 *
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 *
	 * @return string
	 */
	public function get_day_link( $year, $month, $day ) {

		return get_day_link( $year, $month, $day );
	}

	/**
	 * Wrapper for \get_month_link
	 *
	 * @param int $year
	 * @param int $month
	 *
	 * @return string
	 */
	public function get_month_link( $year, $month ) {

		return get_month_link( $year, $month );
	}

	/**
	 * Wrapper for \get_year_link
	 *
	 * @param int $year
	 *
	 * @return string
	 */
	public function get_year_link( $year ) {

		return get_year_link( $year );
	}

	/**
	 * Wrapper for \get_author_posts_url
	 *
	 * @param int    $author_id
	 * @param string $author_nicename
	 *
	 * @return string
	 */
	public function get_author_posts_url( $author_id, $author_nicename = '' ) {

		return get_author_posts_url( $author_id, $author_nicename );
	}

	/**
	 * Wrapper for \current_user_can
	 *
	 * @param string $capability
	 *
	 * @return bool
	 */
	public function current_user_can( $capability ) {

		return current_user_can( $capability );
	}

	/**
	 * @param int    $user_id
	 * @param string $key
	 * @param bool   $single
	 *
	 * @return mixed
	 */
	public function get_user_meta( $user_id, $key = '', $single = false ) {

		return get_user_meta( $user_id, $key, $single );
	}

	/**
	 * Wrapper for \get_post_type
	 *
	 * @param null|int|WP_Post $post
	 *
	 * @return false|string
	 */
	public function get_post_type( $post = null ) {

		return get_post_type( $post );
	}

	public function is_archive() {
		return is_archive();
	}

	public function is_front_page() {
		return is_front_page();
	}

	public function is_home() {
		return is_home();
	}

	/**
	 * @param int|string|array $page Optional. Page ID, title, slug, or array of such. Default empty.
	 *
	 * @return bool
	 */
	public function is_page($page = '' ) {
		return is_page($page);
	}

	public function is_paged() {
		return is_paged();
	}

	/**
	 * @param string $post
	 *
	 * @return int|string|array $post Optional. Post ID, title, slug, or array of such. Default empty.
	 */
	public function is_single($post = '') {
		return is_single($post);
	}

	/**
	 * @param string|array $post_types
	 *
	 * @return bool
	 */
	public function is_singular( $post_types = '' ) {
		return is_singular( $post_types );
	}

	/**
	 * @param int|WP_User $user
	 * @param string      $capability
	 *
	 * @return bool
	 */
	public function user_can( $user, $capability ) {

		return user_can( $user, $capability );
	}

	/**
	 * Wrapper for add_filter
	 */
	public function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {

		return add_filter( $tag, $function_to_add, $priority, $accepted_args );
	}

	/**
	 * Wrapper for remove_filter
	 */
	public function remove_filter( $tag, $function_to_remove, $priority = 10 ) {

		return remove_filter( $tag, $function_to_remove, $priority );
	}

	/**
	 * Wrapper for current_filter
	 */
	public function current_filter() {
		return current_filter();
	}

	public function get_tm_url( $tab = null, $hash = null ) {
		$tm_url = menu_page_url( $this->constant( 'WPML_TM_FOLDER' ) . '/menu/main.php', false );

		$query_vars = array();
		if ( $tab ) {
			$query_vars['sm'] = $tab;
		}

		$tm_url = add_query_arg( $query_vars, $tm_url );

		if ( $hash ) {
			if ( strpos( $hash, '#' ) !== 0 ) {
				$hash = '#' . $hash;
			}
			$tm_url .= $hash;
		}

		return $tm_url;
	}

	/**
	 * Wrapper for \is_admin()
	 *
	 * @return bool
	 */
	public function is_admin() {

		return is_admin();
	}

	public function is_jobs_tab() {
		return $this->is_tm_page( 'jobs' );
	}

	public function is_tm_page( $tab = null ) {
		$result = is_admin()
		          && isset( $_GET['page'] )
		          && $_GET['page'] == $this->constant( 'WPML_TM_FOLDER' ) . '/menu/main.php';

		if ( $tab ) {
			if ( $tab == 'dashboard' && ! isset( $_GET['sm'] ) ) {
				$result = $result && true;
			} else {
				$result = $result && isset( $_GET['sm'] ) && $_GET['sm'] == $tab;
			}
		}

		return $result;
	}

	public function is_translation_queue_page() {
		return is_admin() && isset( $_GET['page'] ) && $this->constant( 'WPML_TM_FOLDER' ) . '/menu/translations-queue.php' == $_GET['page'];
	}

	public function is_string_translation_page() {
		return is_admin() && isset( $_GET['page'] ) && $this->constant( 'WPML_ST_FOLDER' ) . '/menu/string-translation.php' == $_GET['page'];
	}

	public function is_support_page() {
		return $this->is_core_page( 'support.php' );
	}

	public function is_troubleshooting_page() {
		return $this->is_core_page( 'troubleshooting.php' );
	}

	public function is_core_page( $page = '' ) {
		$result = is_admin()
		          && isset( $_GET['page'] )
		          && stripos( $_GET['page'], $this->constant( 'ICL_PLUGIN_FOLDER' ) . '/menu/' . $page ) !== false;
		return $result;
	}

	public function is_back_end() {
		return is_admin() && ! $this->is_ajax() && ! $this->is_cron_job();
	}

	public function is_front_end() {
		return ! is_admin() && ! $this->is_ajax() && ! $this->is_cron_job();
	}

	public function is_ajax() {

		$result = defined( 'DOING_AJAX' ) && DOING_AJAX;

		if ( $this->function_exists( 'wpml_is_ajax' ) ) {
			/** @noinspection PhpUndefinedFunctionInspection */
			$result = $result || wpml_is_ajax();
		}

		return $result;
	}

	public function mb_strtolower( $string ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $string );
		}

		return strtolower( $string );
	}

	public function is_cron_job() {
		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	public function is_heartbeat() {
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );

		return 'heartbeat' === $action;
	}

	public function is_term_edit_page() {
		global $pagenow;

		return 'term.php' === $pagenow || ( 'edit-tags.php' === $pagenow && isset( $_GET['action'] ) && 'edit' === filter_var( $_GET['action'] ) );
	}

	public function is_customize_page() {
		global $pagenow;

		return 'customize.php' === $pagenow;
	}

	public function is_comments_post_page() {
		global $pagenow;

		return 'wp-comments-post.php' === $pagenow;
	}

	public function is_plugins_page() {
		global $pagenow;

		return 'plugins.php' === $pagenow;
	}

	public function is_themes_page() {
		global $pagenow;

		return 'themes.php' === $pagenow;
	}

	/**
	 * Wrapper for \is_feed that returns false if called before the loop
	 *
	 * @param string $feeds
	 *
	 * @return bool
	 */
	public function is_feed( $feeds = '' ) {
		global $wp_query;

		return isset( $wp_query ) && is_feed( $feeds );
	}

	/**
	 * Wrapper for \wp_update_term_count
	 *
	 * @param  int[]     $terms given by their term_taxonomy_ids
	 * @param  string    $taxonomy
	 * @param bool|false $do_deferred
	 *
	 * @return bool
	 */
	public function wp_update_term_count( $terms, $taxonomy, $do_deferred = false ) {

		return wp_update_term_count( $terms, $taxonomy, $do_deferred );
	}

	/**
	 * Wrapper for \get_taxonomy
	 *
	 * @param string $taxonomy
	 *
	 * @return bool|object
	 */
	public function get_taxonomy( $taxonomy ) {

		return get_taxonomy( $taxonomy );
	}

	/**
	 * Wrapper for \wp_set_object_terms
	 *
	 * @param int              $object_id The object to relate to.
	 * @param array|int|string $terms A single term slug, single term id, or array of either term slugs or ids.
	 *                                    Will replace all existing related terms in this taxonomy.
	 * @param string           $taxonomy The context in which to relate the term to the object.
	 * @param bool             $append Optional. If false will delete difference of terms. Default false.
	 *
	 * @return array|WP_Error Affected Term IDs.
	 */
	public function wp_set_object_terms( $object_id, $terms, $taxonomy, $append = false ) {

		return wp_set_object_terms( $object_id, $terms, $taxonomy, $append );
	}

	/**
	 * Wrapper for \get_post_types
	 *
	 * @param array  $args
	 * @param string $output
	 * @param string $operator
	 *
	 * @return array
	 */
	public function get_post_types( $args = array(), $output = 'names', $operator = 'and' ) {

		return get_post_types( $args, $output, $operator );
	}

	public function wp_send_json( $response ) {
		wp_send_json( $response );

		return $response;
	}

	public function wp_send_json_success( $data = null ) {
		wp_send_json_success( $data );

		return $data;
	}

	public function wp_send_json_error( $data = null ) {
		wp_send_json_error( $data );

		return $data;
	}

	/**
	 * Wrapper for \get_current_user_id
	 * @return int
	 */
	public function get_current_user_id() {

		return get_current_user_id();
	}

	/**
	 * Wrapper for \get_post
	 *
	 * @param null|int|WP_Post $post
	 * @param string           $output
	 * @param string           $filter
	 *
	 * @return array|null|WP_Post
	 */
	public function get_post( $post = null, $output = OBJECT, $filter = 'raw' ) {

		return get_post( $post, $output, $filter );
	}

	/**
	 * Wrapper for \get_post_meta
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Optional. The meta key to retrieve. By default, returns
	 *                        data for all keys. Default empty.
	 * @param bool   $single Optional. Whether to return a single value. Default false.
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data
	 *               field if $single is true.
	 */
	public function get_post_meta( $post_id, $key = '', $single = false ) {

		return get_post_meta( $post_id, $key, $single );
	}

	/**
	 * Wrapper for \update_post_meta
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key
	 * @param mixed  $value
	 * @param mixed  $prev_value
	 *
	 * @return int|bool
	 */
	public function update_post_meta(
		$post_id,
		$key,
		$value,
		$prev_value = ''
	) {

		return update_post_meta( $post_id, $key, $value, $prev_value );
	}

	/**
	 * Wrapper for add_post_meta
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param bool   $unique     Optional. Whether the same key should not be added.
	 *                           Default false.
	 * @return int|false Meta ID on success, false on failure.
	 */
	public function add_post_meta( $post_id, $meta_key, $meta_value, $unique = false ) {
		return add_post_meta( $post_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Wrapper for delete_post_meta
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if
	 *                           non-scalar. Default empty.
	 * @return bool True on success, false on failure.
	 */
	public function delete_post_meta( $post_id, $meta_key, $meta_value = '' ) {
		return delete_post_meta( $post_id, $meta_key, $meta_value );
	}

	/**
	 * Wrapper for \get_term_meta
	 *
	 * @param int    $term_id
	 * @param string $key
	 * @param bool   $single
	 *
	 * @return mixed
	 */
	public function get_term_meta( $term_id, $key = '', $single = false ) {

		return get_term_meta( $term_id, $key, $single );
	}

	/**
	 * Wrapper for \get_permalink
	 *
	 * @param int        $id
	 * @param bool|false $leavename
	 *
	 * @return bool|string
	 */
	public function get_permalink( $id = 0, $leavename = false ) {

		return get_permalink( $id, $leavename );
	}

	/**
	 * Wrapper for \wp_mail
	 *
	 * @param string       $to
	 * @param string       $subject
	 * @param string       $message
	 * @param string|array $headers
	 * @param array|array  $attachments
	 *
	 * @return bool
	 */
	public function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {

		return wp_mail( $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Wrapper for \get_post_custom
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_post_custom( $post_id = 0 ) {

		return get_post_custom( $post_id );
	}

	public function is_dashboard_tab() {
		return $this->is_tm_page( 'dashboard' );
	}

	public function wp_safe_redirect( $redir_target, $status = 302 ) {
		wp_safe_redirect( $redir_target, $status );
		exit;
	}

	/**
	 * Wrapper around PHP constant defined
	 *
	 * @param string $constant_name
	 *
	 * @return bool
	 */
	public function defined( $constant_name ) {
		return defined( $constant_name );
	}

	/**
	 * Wrapper around PHP constant lookup
	 *
	 * @param string $constant_name
	 *
	 * @return string|int
	 */
	public function constant( $constant_name ) {

		return $this->defined( $constant_name ) ? constant( $constant_name ) : null;
	}

	/**
	 * Wrapper for \load_textdomain
	 *
	 * @param string $domain
	 * @param string $mofile
	 *
	 * @return bool
	 */
	public function load_textdomain( $domain, $mofile ) {

		return load_textdomain( $domain, $mofile );
	}

	/**
	 * Wrapper for \get_home_url
	 *
	 * @param null|int    $blog_id
	 * @param string      $path
	 * @param null|string $scheme
	 *
	 * @return string
	 */
	public function get_home_url(
		$blog_id = null,
		$path = '',
		$scheme = null
	) {

		return get_home_url( $blog_id, $path, $scheme );
	}

	/**
	 * Wrapper for \get_site_url
	 *
	 * @param null|int    $blog_id
	 * @param string      $path
	 * @param null|string $scheme
	 *
	 * @return string
	 */
	public function get_site_url(
		$blog_id = null,
		$path = '',
		$scheme = null
	) {

		return get_site_url( $blog_id, $path, $scheme );
	}

	/**
	 * Wrapper for \is_multisite
	 *
	 * @return bool
	 */
	public function is_multisite() {

		return is_multisite();
	}

	/**
	 * Wrapper for \is_main_site
	 *
	 * @param null|int $site_id
	 *
	 * @return bool
	 */
	public function is_main_site( $site_id = null ) {
		return is_main_site( $site_id );
	}

	/**
	 * Wrapper for \ms_is_switched
	 *
	 * @return bool
	 */
	public function ms_is_switched() {

		return ms_is_switched();
	}

	/**
	 * Wrapper for \get_current_blog_id
	 *
	 * @return int
	 */
	public function get_current_blog_id() {

		return get_current_blog_id();
	}

	/**
	 * Wrapper for wp_get_post_terms
	 *
	 * @param int $post_id
	 * @param string $taxonomy
	 * @param array $args
	 *
	 * @return array|WP_Error
	 */
	public function wp_get_post_terms(
		$post_id = 0,
		$taxonomy = 'post_tag',
		$args = array()
	) {

		return wp_get_post_terms( $post_id, $taxonomy, $args );
	}

	/**
	 * Wrapper for get_taxonomies
	 *
	 * @param array  $args
	 * @param string $output
	 * @param string $operator
	 *
	 * @return array
	 */
	public function get_taxonomies(
		$args = array(),
		$output = 'names',
		$operator = 'and'
	) {

		return get_taxonomies( $args, $output, $operator );
	}

	/**
	 * Wrapper for \wp_get_theme
	 *
	 * @param string $stylesheet
	 * @param string $theme_root
	 *
	 * @return WP_Theme
	 */
	public function wp_get_theme( $stylesheet = null, $theme_root = null ) {

		return wp_get_theme( $stylesheet, $theme_root );
	}

	/**
	 * Wrapper for \wp_get_theme->get('Name')
	 *
	 * @return string
	 */
	public function get_theme_name() {

		return wp_get_theme()->get( 'Name' );
	}

	/**
	 * Wrapper for \wp_get_theme->parent_theme
	 *
	 * @return string
	 */
	public function get_theme_parent_name() {

		return wp_get_theme()->parent_theme;
	}

	/**
	 * Wrapper for \wp_get_theme->get('URI')
	 *
	 * @return string
	 */
	public function get_theme_URI() {

		return wp_get_theme()->get( 'URI' );
	}

	/**
	 * Wrapper for \wp_get_theme->get('Author')
	 *
	 * @return string
	 */
	public function get_theme_author() {

		return wp_get_theme()->get( 'Author' );
	}

	/**
	 * Wrapper for \wp_get_theme->get('AuthorURI')
	 *
	 * @return string
	 */
	public function get_theme_authorURI() {

		return wp_get_theme()->get( 'AuthorURI' );
	}

	/**
	 * Wrapper for \wp_get_theme->get('Template')
	 *
	 * @return string
	 */
	public function get_theme_template() {

		return wp_get_theme()->get( 'Template' );
	}

	/**
	 * Wrapper for \wp_get_theme->get('Version')
	 *
	 * @return string
	 */
	public function get_theme_version() {

		return wp_get_theme()->get( 'Version' );
	}

	/**
	 * Wrapper for \wp_get_theme->get('TextDomain')
	 *
	 * @return string
	 */
	public function get_theme_textdomain() {

		return wp_get_theme()->get( 'TextDomain' );
	}

	/**
	 * Wrapper for \wp_get_theme->get('DomainPath')
	 *
	 * @return string
	 */
	public function get_theme_domainpath() {

		return wp_get_theme()->get( 'DomainPath' );
	}

	/**
	 * Wrapper for \get_plugins()
	 *
	 * @return array
	 */
	public function get_plugins() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		return get_plugins();
	}

	/**
	 * Wrapper for \get_post_custom_keys
	 *
	 * @param int $post_id
	 *
	 * @return array|void
	 */
	public function get_post_custom_keys( $post_id ) {

		return get_post_custom_keys( $post_id );
	}

	/**
	 * Wrapper for \get_bloginfo
	 *
	 * @param string $show (optional)
	 * @param string $filter (optional)
	 *
	 * @return string
	 */
	public function get_bloginfo( $show = '', $filter = 'raw' ) {

		return get_bloginfo( $show, $filter );
	}

	/**
	 * Wrapper for \phpversion()
	 *
	 * * @param string $extension (optional)
	 *
	 * @return string
	 */
	public function phpversion( $extension = null ) {
		if ( defined( 'PHP_VERSION' ) ) {
			return PHP_VERSION;
		} else {
			return phpversion( $extension );
		}
	}

	/**
	 * Compares two "PHP-standardized" version number strings
	 * @see \WPML_WP_API::version_compare
	 *
	 * @param string $version1
	 * @param string $version2
	 * @param null   $operator
	 *
	 * @return mixed
	 */
	public function version_compare( $version1, $version2, $operator = null ) {
		return version_compare( $version1, $version2, $operator );
	}

	/**
	 * Compare version in their "naked" form
	 * @see \WPML_WP_API::get_naked_version
	 * @see \WPML_WP_API::version_compare
	 * @see \version_compare
	 *
	 * @param string $version1
	 * @param string $version2
	 * @param null   $operator
	 *
	 * @return mixed
	 */
	public function version_compare_naked( $version1, $version2, $operator = null ) {
		return $this->version_compare( $this->get_naked_version( $version1 ), $this->get_naked_version( $version2 ), $operator );
	}

	/**
	 * Returns only the first 3 numeric elements of a version (assuming to use MAJOR.MINOR.PATCH
	 *
	 * @param string $version
	 *
	 * @return string
	 */
	public function get_naked_version( $version ) {

		$elements = explode( '.', str_replace( '..', '.', preg_replace( '/([^0-9\.]+)/', '.$1.', str_replace( array( '-', '_', '+' ), '.', trim( $version ) ) ) ) );

		$naked_elements = array('0', '0', '0');

		$elements_count = 0;
		foreach ( $elements as $element ) {
			if ( $elements_count === 3 || ! is_numeric( $element ) ) {
				break;
			}
			$naked_elements[$elements_count] = $element;
			$elements_count ++;
		}

		return implode( $naked_elements );
	}

	public function has_filter($tag, $function_to_check = false) {
		return has_filter( $tag, $function_to_check );
	}

	public function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return  add_action( $tag, $function_to_add, $priority, $accepted_args );
	}

	public function get_current_screen() {
		return get_current_screen();
	}

	public function array_unique( $array, $sort_flags = SORT_REGULAR ) {
		if ( version_compare( $this->phpversion(), '5.2.9', '>=' ) ) {
			return array_unique( $array, $sort_flags );
		} else {
			return $this->array_unique_fallback( $array, true );
		}
	}

	/**
	 * @param $array
	 * @param $keep_key_assoc
	 *
	 * @return array
	 */
	private function array_unique_fallback( $array, $keep_key_assoc ) {
		$duplicate_keys = array();
		$tmp            = array();

		foreach ( $array as $key => $val ) {
			// convert objects to arrays, in_array() does not support objects
			if ( is_object( $val ) ) {
				$val = (array) $val;
			}

			if ( ! in_array( $val, $tmp ) ) {
				$tmp[] = $val;
			} else {
				$duplicate_keys[] = $key;
			}
		}

		foreach ( $duplicate_keys as $key ) {
			unset( $array[ $key ] );
		}

		return $keep_key_assoc ? $array : array_values( $array );
	}

	/**
	 * Wrapper for \get_query_var
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function get_query_var( $var, $default = '' ) {
		return get_query_var( $var, $default );
	}

	/**
	 * Wrapper for \get_queried_object
	 */
	public function get_queried_object() {
		return get_queried_object();
	}

	public function get_raw_post_data() {
		$raw_post_data = @file_get_contents( "php://input" );
		if ( ! $raw_post_data && array_key_exists( 'HTTP_RAW_POST_DATA', $GLOBALS ) ) {
			$raw_post_data = $GLOBALS['HTTP_RAW_POST_DATA'];
		}

		return $raw_post_data;
	}

	public function wp_verify_nonce( $nonce, $action = -1 ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * @param string $class_name The class name. The name is matched in a case-insensitive manner.
	 * @param bool   $autoload   [optional] Whether or not to call &link.autoload; by default.
	 *
	 * @return bool true if <i>class_name</i> is a defined class, false otherwise.
	 * @return bool
	 */
	public function class_exists( $class_name, $autoload = true ) {
		return class_exists( $class_name, $autoload );
	}

	/**
	 * @param string $function_name The function name, as a string.
	 *
	 * @return bool true if <i>function_name</i> exists and is a function, false otherwise.
	 * This function will return false for constructs, such as <b>include_once</b> and <b>echo</b>.
	 * @return bool
	 */
	public function function_exists( $function_name ) {
		return function_exists( $function_name );
	}

	/**
	 * @param string $name The extension name
	 *
	 * @return bool true if the extension identified by <i>name</i> is loaded, false otherwise.
	 */
	public function extension_loaded( $name ) {
		return extension_loaded( $name );
	}

	/**
	 * @param string $action
	 *
	 * @return int The number of times action hook $tag is fired.
	 */
	public function did_action( $action ) {
		return did_action( $action );
	}

	/**
	 * @return string
	 */
	public function current_action() {
		return current_action();
	}

	public function get_wp_post_types_global() {
		global $wp_post_types;
		return $wp_post_types;
	}

	/**
	 * @return wp_xmlrpc_server
	 */
	public function get_wp_xmlrpc_server() {
		global $wp_xmlrpc_server;

		return $wp_xmlrpc_server;
	}

	/**
	 * Wrapper for $wp_taxonomies global variable
	 *
	 */
	public function get_wp_taxonomies() {
		global $wp_taxonomies;

		return $wp_taxonomies;
	}

	/**
	 * Wrapper for get_category_link function
	 *
	 * @param int $category_id
	 *
	 * @return string
	 */
	public function get_category_link( $category_id ) {
		return get_category_link(  $category_id );
	}

	/**
	 * Wrapper for is_wp_error function
	 *
	 * @param mixed $thing
	 *
	 * @return bool
	 */
	public function is_wp_error( $thing ) {
		return is_wp_error( $thing );
	}

	/**
	 * @param int  $limit
	 * @param bool $provide_object
	 * @param bool $ignore_args
	 *
	 * @return array
	 */
	public function get_backtrace($limit = 0, $provide_object = false, $ignore_args = true) {
		$options = false;

		if ( version_compare( $this->phpversion(), '5.3.6' ) < 0 ) {
			// Before 5.3.6, the only values recognized are TRUE or FALSE,
			// which are the same as setting or not setting the DEBUG_BACKTRACE_PROVIDE_OBJECT option respectively.
			$options = $provide_object;
		} else {
			// As of 5.3.6, 'options' parameter is a bitmask for the following options:
			if ( $provide_object ) {
				$options |= DEBUG_BACKTRACE_PROVIDE_OBJECT;
			}
			if ( $ignore_args ) {
				$options |= DEBUG_BACKTRACE_IGNORE_ARGS;
			}
		}
		if ( version_compare( $this->phpversion(), '5.4.0' ) >= 0 ) {
			$debug_backtrace = debug_backtrace( $options, $limit ); //add one item to include the current frame
		} elseif ( version_compare( $this->phpversion(), '5.2.4' ) >= 0 ) {
			//@link https://core.trac.wordpress.org/ticket/20953
			$debug_backtrace = debug_backtrace();
		} else {
			$debug_backtrace = debug_backtrace( $options );
		}

		//Remove the current frame
		if($debug_backtrace) {
			array_shift($debug_backtrace);
		}
		return $debug_backtrace;
	}

	/**
	 * @return WP_Filesystem_Direct
	 */
	public function get_wp_filesystem_direct() {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );

		return new WP_Filesystem_Direct( null );
	}

	/**
	 * @return WPML_Notices
	 */
	public function get_admin_notices() {
		global $wpml_admin_notices;

		if ( ! $wpml_admin_notices ) {
			$wpml_admin_notices = new WPML_Notices( new WPML_Notice_Render() );
			$wpml_admin_notices->init_hooks();
		}

		return $wpml_admin_notices;
	}

	/**
	 * @param Twig_LoaderInterface $loader
	 * @param array                $environment_args
	 *
	 * @return Twig_Environment
	 */
	public function get_twig_environment( $loader, $environment_args ) {
		return new Twig_Environment( $loader, $environment_args );
	}

	/**
	 * @param array $template_paths
	 *
	 * @return Twig_Loader_Filesystem
	 */
	public function get_twig_loader_filesystem( $template_paths ) {
		return new Twig_Loader_Filesystem( $template_paths );
	}

	/**
	 * @return Twig_Loader_String
	 */
	public function get_twig_loader_string() {
		return new Twig_Loader_String();
	}

	/**
	 * @param string $message
	 * @param int    $message_type
	 * @param string $destination
	 * @param string $extra_headers
	 *
	 * @return bool
	 */
	public function error_log( $message, $message_type = null, $destination = null, $extra_headers = null ) {
		return error_log( $message, $message_type, $destination, $extra_headers );
	}
}