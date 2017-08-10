<?php

class WPML_LS_Assets {

    /* @var array $enqueued_templates */
    private $enqueued_templates = array();

    /* @var WPML_LS_Templates $templates */
    private $templates;

    /* @var WPML_LS_Settings $settings */
    private $settings;

    /**
     * WPML_Language_Switcher_Render_Assets constructor.
     *
     * @param WPML_LS_Templates $templates
     * @param WPML_LS_Settings $settings
     */
    public function __construct( $templates, &$settings ) {
        $this->templates = $templates;
        $this->settings  = $settings;
    }

    public function init_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts_action' ) );
    }

    public function wp_enqueue_scripts_action() {
        $active_templates_slugs = $this->settings->get_active_templates();

        /**
         * Filter the templates to be enqueued (CSS & JS)
         * To use if a language switcher is rendered
         * with a specific template later in the script
         *
         * @param array $active_templates
         */
        $active_templates_slugs = apply_filters( 'wpml_ls_enqueue_templates', $active_templates_slugs );

        $templates = $this->templates->get_templates( $active_templates_slugs );

        foreach( $templates as $slug => $template ) {
            $this->enqueue_template_resources( $slug, $template );
        }
    }

    /**
     * @param $slug
     */
    public function maybe_late_enqueue_template( $slug ) {
        if ( ! in_array( $slug, $this->enqueued_templates ) ) {
            $template = $this->templates->get_template( $slug );
            $this->enqueue_template_resources( $slug, $template );
        }
    }

    /**
     * @param string                          $slug
     * @param WPML_LS_Template $template
     */
    private function enqueue_template_resources( $slug, $template ) {
        $this->enqueued_templates[] = $slug;

        if ( $this->settings->can_load_script( $slug ) ) {
            foreach ( $template->get_scripts() as $k => $url ) {
                wp_enqueue_script( $template->get_resource_handler( $k ), $url, array(), $template->get_version() );
            }
        }

        if ( $this->settings->can_load_styles( $slug ) ) {
            foreach ( $template->get_styles() as $k => $url ) {
                wp_enqueue_style( $template->get_resource_handler( $k ), $url, array(), $template->get_version() );
            }
        }
    }
}