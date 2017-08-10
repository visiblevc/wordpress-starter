<?php

/**
 * @author OnTheGo Systems
 */
class WPML_ACF_Xliff {
	/** @var WPDB $wpdb */
	public $wpdb;
	/** @var SitePress $sitepress */
	protected $sitepress;

	private $reg_ext_patterns            = array();
	private $cache_key_for_fields_groups = 'get_acf_groups';
	private $cache_group                 = 'wpml_acf';

	/**
	 * WPML_ACF constructor.
	 *
	 * @param wpdb      $wpdb
	 * @param SitePress $sitepress
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	public function init_hooks() {
		add_action( 'save_post', array( $this, 'save_post' ), WPML_PRIORITY_BEFORE_EVERYTHING );
		add_action( 'acf/update_field_group', array( $this, 'update_acf_field_group' ) );
	}

	public function save_post() {
		if ( $this->is_updating_a_translatable_post_with_acf_fields() ) {
			$this->reg_ext_patterns = array();
			$fields                 = get_field_objects( $_POST['post_ID'] );

			if ( $fields && is_array( $fields ) ) {
				$this->update_custom_fields_settings( $fields );
				$this->update_post_meta_settings();
			}
		}
	}

	/**
	 * @return bool
	 */
	private function is_updating_a_translatable_post_with_acf_fields() {
		return array_key_exists( 'post_type', $_POST )
		&& array_key_exists( 'post_ID', $_POST )
		&& array_key_exists( 'action', $_POST )
		&& 'editpost' === $_POST['action']
		&& array_key_exists( 'acf', $_POST )
		&& is_array( $_POST['acf'] )
		&& 'acf-field-group' !== $_POST['post_type']
		&& $this->sitepress->is_translated_post_type( $_POST['post_type'] );
	}

	/**
	 * @param array $acf_fields
	 */
	private function update_custom_fields_settings( array $acf_fields ) {
		$fields = $this->build_fields_names( $acf_fields );
		foreach ( $fields as $field ) {

			if ( array_key_exists( 'cf-names', $field ) && count( $field['cf-names'] ) ) {
				$this->collect_meta_keys_to_update( '_' . $this->get_wildcards_field_name( $field ) );
			}
			if ( $this->is_a_container( $field ) ) {
				$this->set_field_to_be_copied( $field['name'] );
				$this->set_field_to_be_copied( '_' . $field['name'] );
				$this->collect_meta_keys_to_update( $this->get_wildcards_field_name( $field ) );
			}

			if ( array_key_exists( 'sub_fields', $field ) ) {
				foreach ( $field['sub_fields'] as $sub_field ) {
					if ( $this->is_a_container( $sub_field ) && ( array_key_exists( 'cf-names', $sub_field ) && count( $sub_field['cf-names'] ) ) ) {
						$this->collect_meta_keys_to_update( $this->get_wildcards_field_name( $sub_field ) );
					}

					$this->collect_meta_keys_to_update( '_' . $this->get_wildcards_field_name( $sub_field ) );
					if ( $this->is_a_container( $sub_field ) ) {
						$this->update_custom_fields_settings( $sub_field['sub_fields'] );
					}
				}
			}
		}
	}

	private function update_post_meta_settings() {
		$conditions = count( $this->reg_ext_patterns );

		if ( $conditions ) {
			$this->reg_ext_patterns = array_unique( $this->reg_ext_patterns );

			$sql_post_meta = "SELECT DISTINCT meta_key FROM {$this->wpdb->postmeta} WHERE ";

			$sql_post_meta_where = array();
			for ( $i = 0; $i < $conditions; $i ++ ) {
				$sql_post_meta_where[] = 'meta_key REGEXP %s';
			}
			$sql_post_meta .= implode( ' OR ', $sql_post_meta_where );

			$sql = $this->wpdb->prepare( $sql_post_meta, $this->reg_ext_patterns );

			$metas = $this->wpdb->get_col( $sql );

			/** @var array $metas */
			foreach ( $metas as $meta ) {
				$this->set_field_to_be_copied( $meta );
			}

			$this->reg_ext_patterns = array();
		}
	}

	private function build_fields_names( array $fields, array $parent_names = array() ) {
		foreach ( $fields as $index => &$field ) {
			$field_names       = $parent_names;
			$field_names[]     = $field['name'];
			$field['cf-names'] = $field_names;
			if ( array_key_exists( 'sub_fields', $field ) ) {
				$field['sub_fields'] = $this->build_fields_names( $field['sub_fields'], $field_names );
			}
		}

		return $fields;
	}

	/**
	 * @param $reg_ex_pattern
	 */
	private function collect_meta_keys_to_update( $reg_ex_pattern ) {
		$this->reg_ext_patterns[] = '^' . $reg_ex_pattern . '$';
	}

	private function get_wildcards_field_name( array $field = array() ) {
		$cf_names = $field['cf-names'];
		$result   = array_shift( $cf_names );

		$result = esc_sql( $result );

		foreach ( $cf_names as $cf_name ) {
			$result .= '_' . $this->get_wildcards() . '_' . esc_sql( $cf_name );
		}

		return $result;
	}

	private function is_a_container( $field ) {
		return array_key_exists( 'sub_fields', $field ) && is_array( $field['sub_fields'] ) && count( $field['sub_fields'] );
	}

	private function set_field_to_be_copied( $field ) {
		$custom_fields_readonly_config   = $this->sitepress->get_setting( 'custom_fields_readonly_config' );
		$custom_fields_readonly_config[] = $field;
		$this->sitepress->set_setting( 'custom_fields_readonly_config', $custom_fields_readonly_config, true );

		$custom_fields_translation           = $this->sitepress->get_setting( 'custom_fields_translation' );
		$custom_fields_translation[ $field ] = WPML_COPY_CUSTOM_FIELD;
		$this->sitepress->set_setting( 'custom_fields_translation', $custom_fields_translation, true );
	}

	/**
	 * @return string
	 */
	private function get_wildcards() {
		return '[0-9]*';
	}

	public function update_acf_field_group() {
		if ( $this->is_updating_acf_group() ) {
			$cache = new WPML_WP_Cache( $this->cache_group );
			$cache->flush_group_cache();

			$this->reg_ext_patterns = array();
			$group_id               = $_POST['post_ID'];
			$groups                 = $this->get_acf_groups();

			/** @var WP_Post $group */
			foreach ( $groups as $group ) {
				if ( (int) $group_id === $group->ID ) {
					$fields = acf_get_fields( $group->ID );
					if ( is_array( $fields ) && $fields ) {
						$this->update_custom_fields_settings( $fields );
					}
				}
			}

			$this->update_post_meta_settings();
		}
	}

	/**
	 * @return bool
	 */
	private function is_updating_acf_group() {
		return array_key_exists( 'post_type', $_POST ) && array_key_exists( 'post_ID', $_POST ) && 'acf-field-group' === $_POST['post_type'];
	}

	/**
	 * @todo Improve this query since only ID is used no need for all fields.
	 *
	 * @return array
	 */
	private function get_acf_groups() {
		$found  = false;
		$cache  = new WPML_WP_Cache( $this->cache_group );
		$result = $cache->get( $this->cache_key_for_fields_groups, $found );
		if ( ! $found ) {
			$result = get_posts( array(
				'post_type'              => 'acf-field-group',
				'posts_per_page'         => -1,
				'orderby'                => 'menu_order title',
				'order'                  => 'asc',
				'suppress_filters'       => false, // allow WPML to modify the query
				'post_status'            => array( 'publish', 'acf-disabled' ),
				'update_post_meta_cache' => false,
			) );
			$cache->set( $this->cache_key_for_fields_groups, $result );
		}

		return $result;
	}
}
