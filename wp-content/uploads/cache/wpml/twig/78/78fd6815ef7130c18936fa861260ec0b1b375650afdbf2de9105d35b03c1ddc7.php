<?php

/* layout-main.twig */
class __TwigTemplate_1592bf167860d10e2c7fd754500235e2ee7cafe768bb12c5fb22432935205f83 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<form id=\"wpml-ls-settings-form\" name=\"wpml_ls_settings_form\">

\t<input type=\"hidden\" name=\"wpml-ls-refresh-on-browser-back-button\" id=\"wpml-ls-refresh-on-browser-back-button\" value=\"no\">

    ";
        // line 5
        if ($this->getAttribute(($context["notifications"] ?? null), "css_not_loaded", array())) {
            // line 6
            echo "        <div class=\"wpml-ls-message notice notice-info\">
            <p>";
            // line 7
            echo twig_escape_filter($this->env, $this->getAttribute(($context["notifications"] ?? null), "css_not_loaded", array()), "html", null, true);
            echo "</p>
        </div>
    ";
        }
        // line 10
        echo "
    <div id=\"wpml-language-switcher-options\" class=\"js-wpml-ls-section wpml-section\">
        <div class=\"wpml-section-header\">
            <h3>";
        // line 13
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "section_title", array()), "html", null, true);
        echo "</h3>
\t\t\t<p>";
        // line 14
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "section_description", array()), "html", null, true);
        echo "</p>
        </div>

        <div class=\"js-setting-group wpml-ls-settings-group wpml-section-content\">
            ";
        // line 18
        $this->loadTemplate("section-options.twig", "layout-main.twig", 18)->display($context);
        // line 19
        echo "        </div>
    </div>

    <div id=\"wpml-language-switcher-menus\" class=\"js-wpml-ls-section wpml-section\">
        <div class=\"wpml-section-header\">
            <h3>
                ";
        // line 25
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "menus", array()), "section_title", array()), "html", null, true);
        echo "
            </h3>
            ";
        // line 27
        $this->loadTemplate("save-notification.twig", "layout-main.twig", 27)->display($context);
        // line 28
        echo "        </div>

        <div class=\"js-setting-group wpml-ls-settings-group wpml-section-content\">
            ";
        // line 31
        $this->loadTemplate("section-menus.twig", "layout-main.twig", 31)->display($context);
        // line 32
        echo "        </div>
    </div>

    <div id=\"wpml-language-switcher-sidebars\" class=\"js-wpml-ls-section wpml-section\">
        <div class=\"wpml-section-header\">
            <h3>
                ";
        // line 38
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "sidebars", array()), "section_title", array()), "html", null, true);
        echo "
            </h3>
            ";
        // line 40
        $this->loadTemplate("save-notification.twig", "layout-main.twig", 40)->display($context);
        // line 41
        echo "        </div>

        <div class=\"js-setting-group wpml-ls-settings-group wpml-section-content\">
            ";
        // line 44
        $this->loadTemplate("section-sidebars.twig", "layout-main.twig", 44)->display($context);
        // line 45
        echo "        </div>
    </div>

    <div id=\"wpml-language-switcher-footer\" class=\"js-wpml-ls-section wpml-section\">
        <div class=\"wpml-section-header\">
            <h3>
                ";
        // line 51
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "footer", array()), "section_title", array()), "html", null, true);
        echo "
                ";
        // line 52
        $this->loadTemplate("tooltip.twig", "layout-main.twig", 52)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "show_in_footer", array()))));
        // line 53
        echo "            </h3>
        </div>

        <div class=\"js-setting-group wpml-ls-settings-group wpml-section-content\">
            ";
        // line 57
        $this->loadTemplate("section-footer.twig", "layout-main.twig", 57)->display($context);
        // line 58
        echo "        </div>

    </div>

    <div id=\"wpml-language-switcher-post-translations\" class=\"js-wpml-ls-section wpml-section\">
        <div class=\"wpml-section-header\">
            <h3>
                ";
        // line 65
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "post_translations", array()), "section_title", array()), "html", null, true);
        echo "
                ";
        // line 66
        $this->loadTemplate("tooltip.twig", "layout-main.twig", 66)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "section_post_translations", array()))));
        // line 67
        echo "            </h3>
        </div>

        <div class=\"js-setting-group wpml-ls-settings-group wpml-section-content\">
            ";
        // line 71
        $this->loadTemplate("section-post-translations.twig", "layout-main.twig", 71)->display($context);
        // line 72
        echo "        </div>
    </div>

    <div id=\"wpml-language-switcher-shortcode-action\" class=\"js-wpml-ls-section wpml-section\"
        ";
        // line 76
        if ( !($context["setup_complete"] ?? null)) {
            echo " style=\"display:none;\"";
        }
        echo ">
        <div class=\"wpml-section-header\">
            <h3>
                ";
        // line 79
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "shortcode_actions", array()), "section_title", array()), "html", null, true);
        echo "
                ";
        // line 81
        echo "            </h3>
            ";
        // line 82
        $this->loadTemplate("save-notification.twig", "layout-main.twig", 82)->display($context);
        // line 83
        echo "        </div>

        <div class=\"js-setting-group wpml-ls-settings-group wpml-section-content\">
            ";
        // line 86
        $this->loadTemplate("section-shortcode-action.twig", "layout-main.twig", 86)->display($context);
        // line 87
        echo "        </div>
    </div>

    ";
        // line 90
        $this->loadTemplate("setup-wizard-buttons.twig", "layout-main.twig", 90)->display($context);
        // line 91
        echo "
    ";
        // line 92
        $this->loadTemplate("dialog-box.twig", "layout-main.twig", 92)->display($context);
        // line 93
        echo "
</form>";
    }

    public function getTemplateName()
    {
        return "layout-main.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  185 => 93,  183 => 92,  180 => 91,  178 => 90,  173 => 87,  171 => 86,  166 => 83,  164 => 82,  161 => 81,  157 => 79,  149 => 76,  143 => 72,  141 => 71,  135 => 67,  133 => 66,  129 => 65,  120 => 58,  118 => 57,  112 => 53,  110 => 52,  106 => 51,  98 => 45,  96 => 44,  91 => 41,  89 => 40,  84 => 38,  76 => 32,  74 => 31,  69 => 28,  67 => 27,  62 => 25,  54 => 19,  52 => 18,  45 => 14,  41 => 13,  36 => 10,  30 => 7,  27 => 6,  25 => 5,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "layout-main.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/layout-main.twig");
    }
}
