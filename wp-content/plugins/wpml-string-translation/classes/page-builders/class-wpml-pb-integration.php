<?php

/**
 * Class WPML_PB_Integration
 */
class WPML_PB_Integration {

	private $sitepress;
	private $factory;
	private $new_translations_recieved = false;
	private $save_post_queue = array();

	private $strategies = array();

	/**
	 * @var WPML_PB_Integration_Rescan
	 */
	private $rescan;

	/**
	 * WPML_PB_Integration constructor.
	 *
	 * @param SitePress $sitepress
	 * @param WPML_PB_Factory $factory
	 */
	public function __construct( SitePress $sitepress, WPML_PB_Factory $factory ) {
		$this->sitepress = $sitepress;
		$this->factory   = $factory;
	}

	/**
	 * @param IWPML_PB_Strategy $strategy
	 */
	public function add_strategy( IWPML_PB_Strategy $strategy ) {
		$this->strategies[] = $strategy;
	}

	/**
	 * @return WPML_PB_Integration_Rescan
	 */
	public function get_rescan() {
		if ( null === $this->rescan ) {
			$this->rescan = new WPML_PB_Integration_Rescan( $this );
		}

		return $this->rescan;
	}

	/**
	 * @param WPML_PB_Integration_Rescan $rescan
	 */
	public function set_rescan( WPML_PB_Integration_Rescan $rescan ) {
		$this->rescan = $rescan;
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public function queue_save_post_actions( $post_id, $post ) {
		$this->save_post_queue[ $post_id ] = $post;
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public function register_all_strings_for_translation( $post ) {
		if ( $this->is_post_status_ok( $post ) && $this->is_original_post( $post ) ) {
			foreach ( $this->strategies as $strategy ) {
				$strategy->register_strings( $post );
			}
		}
	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	private function is_original_post( $post ) {
		return $post->ID == $this->sitepress->get_original_element_id( $post->ID, 'post_' . $post->post_type );
	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	private function is_post_status_ok( $post ) {
		return ! in_array( $post->post_status, array( 'trash', 'auto-draft', 'inherit' ) );
	}

	/**
	 * Add all actions filters.
	 */
	public function add_hooks() {
		add_action( 'save_post', array( $this, 'queue_save_post_actions' ), PHP_INT_MAX, 2 );
		add_action( 'icl_st_add_string_translation', array( $this, 'new_translation' ), 10, 1 );
		add_action( 'shutdown', array( $this, 'do_shutdown_action' ) );

		add_filter( 'wpml_tm_translation_job_data', array( $this, 'rescan' ), 9, 2 );
	}

	public function do_shutdown_action() {
		$this->save_translations_to_post();

		foreach( $this->save_post_queue as $post_id => $post ) {
			$this->register_all_strings_for_translation( $post );
		}
	}
	public function new_translation( $translated_string_id ) {
		foreach ( $this->strategies as $strategy ) {
			$this->factory->get_string_translations( $strategy )->new_translation( $translated_string_id );
		}
		$this->new_translations_recieved = true;
	}

	public function save_translations_to_post() {
		if ( $this->new_translations_recieved ) {
			foreach ( $this->strategies as $strategy ) {
				$this->factory->get_string_translations( $strategy )->save_translations_to_post();
			}
		}
	}

	/**
	 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-958
	 * @param array $translation_package
	 * @param $post
	 *
	 * @return array
	 */
	public function rescan( array $translation_package, $post ) {
		return $this->get_rescan()->rescan( $translation_package, $post );
	}

	public function get_factory() {
		return $this->factory;
	}
}
