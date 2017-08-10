<?php

/* setup-wizard-buttons.twig */
class __TwigTemplate_4af85889b8463a619ecb363b9c38f5f3d1d808616ebe343440a6465bd6f5d16a extends Twig_Template
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
        if ( !($context["setup_complete"] ?? null)) {
            // line 2
            echo "\t<footer id=\"icl_setup_nav_3\" class=\"js-wpml-ls-section wpml-section clearfix text-right\">
\t\t<input id=\"icl_setup_back_2\" class=\"button-secondary alignleft\" name=\"save\" value=\"";
            // line 3
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "button_back", array()), "html", null, true);
            echo "\" type=\"button\" />
\t\t";
            // line 4
            echo ($context["setup_step_2_nonce_field"] ?? null);
            echo "
\t\t<input class=\"button-primary alignright\" name=\"save\" value=\"";
            // line 5
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "button_next", array()), "html", null, true);
            echo "\" type=\"submit\" />
\t\t<input type=\"hidden\" name=\"submit_setup_wizard\" value=\"0\" />
\t</footer>
";
        }
    }

    public function getTemplateName()
    {
        return "setup-wizard-buttons.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  32 => 5,  28 => 4,  24 => 3,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "setup-wizard-buttons.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/setup-wizard-buttons.twig");
    }
}
