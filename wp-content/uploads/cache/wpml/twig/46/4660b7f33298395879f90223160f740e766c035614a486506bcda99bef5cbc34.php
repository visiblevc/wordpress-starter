<?php

/* button-add-new-ls.twig */
class __TwigTemplate_c1614fef9122112737a4025f9d2852d2453851bcc511270fec7e4ab7a865eb25 extends Twig_Template
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
        echo "<p class=\"alignright\">

\t";
        // line 3
        $context["add_tooltip"] = ($context["tooltip_all_assigned"] ?? null);
        // line 4
        echo "
\t";
        // line 5
        if ((($context["existing_items"] ?? null) == 0)) {
            // line 6
            echo "\t\t";
            $context["add_tooltip"] = ($context["tooltip_no_item"] ?? null);
            // line 7
            echo "\t";
        }
        // line 8
        echo "
\t";
        // line 9
        if ((($context["settings_items"] ?? null) >= ($context["existing_items"] ?? null))) {
            // line 10
            echo "\t\t";
            $context["disabled"] = true;
            // line 11
            echo "\t";
        }
        // line 12
        echo "
\t<span class=\"js-wpml-ls-tooltip-wrapper";
        // line 13
        if ( !($context["disabled"] ?? null)) {
            echo " hidden";
        }
        echo "\">
        ";
        // line 14
        $this->loadTemplate("tooltip.twig", "button-add-new-ls.twig", 14)->display(array_merge($context, array("content" => ($context["add_tooltip"] ?? null))));
        // line 15
        echo "    </span>

\t<button class=\"js-wpml-ls-open-dialog button-secondary\"";
        // line 17
        if (($context["disabled"] ?? null)) {
            echo " disabled=\"disabled\"";
        }
        // line 18
        echo "\t\t\tdata-target=\"";
        echo twig_escape_filter($this->env, ($context["button_target"] ?? null), "html", null, true);
        echo "\">+ ";
        echo twig_escape_filter($this->env, ($context["button_label"] ?? null), "html", null, true);
        echo "</button>
</p>";
    }

    public function getTemplateName()
    {
        return "button-add-new-ls.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  66 => 18,  62 => 17,  58 => 15,  56 => 14,  50 => 13,  47 => 12,  44 => 11,  41 => 10,  39 => 9,  36 => 8,  33 => 7,  30 => 6,  28 => 5,  25 => 4,  23 => 3,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "button-add-new-ls.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/button-add-new-ls.twig");
    }
}
