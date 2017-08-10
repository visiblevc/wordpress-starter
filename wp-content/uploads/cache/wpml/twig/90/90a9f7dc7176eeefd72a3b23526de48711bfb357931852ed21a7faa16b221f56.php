<?php

/* layout-reset.twig */
class __TwigTemplate_2e6b89ccc77b89a9010930d220e2d4dc606e7a94b777534a2f930077dafa6aeb extends Twig_Template
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
        echo "<div class=\"wpml-section\" id=\"wpml_ls_reset\">
\t<div class=\"wpml-section-header\">
\t\t<h3>";
        // line 3
        echo twig_escape_filter($this->env, ($context["title"] ?? null), "html", null, true);
        echo "</h3>
\t</div>
\t<div class=\"wpml-section-content\">
\t\t<p>";
        // line 6
        echo ($context["description"] ?? null);
        echo "</p>

\t\t";
        // line 8
        if (($context["theme_config_file"] ?? null)) {
            // line 9
            echo "\t\t\t<p class=\"explanation-text\">";
            echo ($context["explanation_text"] ?? null);
            echo "</p>
\t\t";
        }
        // line 11
        echo "
\t\t<p class=\"buttons-wrap\">
\t\t\t<a class=\"button button-secondary\" onclick=\"if(!confirm('";
        // line 13
        echo twig_escape_filter($this->env, ($context["confirmation_message"] ?? null), "html", null, true);
        echo "')) return false;\"
\t\t\t   href=\"";
        // line 14
        echo twig_escape_filter($this->env, ($context["restore_page_url"] ?? null), "html", null, true);
        echo "\">";
        echo twig_escape_filter($this->env, ($context["restore_button_label"] ?? null), "html", null, true);
        if (($context["theme_config_file"] ?? null)) {
            echo " *";
        }
        echo "</a>
\t\t</p>
\t</div>
</div>";
    }

    public function getTemplateName()
    {
        return "layout-reset.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  50 => 14,  46 => 13,  42 => 11,  36 => 9,  34 => 8,  29 => 6,  23 => 3,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "layout-reset.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/layout-reset.twig");
    }
}
