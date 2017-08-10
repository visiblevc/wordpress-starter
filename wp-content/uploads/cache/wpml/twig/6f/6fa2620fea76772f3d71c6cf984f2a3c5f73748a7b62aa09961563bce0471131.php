<?php

/* slot-subform-sidebars.twig */
class __TwigTemplate_a193da613c618026bbf15a3f0e125d4826eedec91581f553bb17aa5c5dcab2e3 extends Twig_Template
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
        if ( !array_key_exists("slot_settings", $context)) {
            // line 2
            echo "    ";
            $context["slot_settings"] = ($context["default_sidebars_slot"] ?? null);
        }
        // line 4
        echo "
";
        // line 5
        $this->loadTemplate("preview.twig", "slot-subform-sidebars.twig", 5)->display(array_merge($context, array("preview" => ($context["preview"] ?? null))));
        // line 6
        echo "
<div class=\"wpml-ls-subform-options\">

    ";
        // line 9
        $this->loadTemplate("dropdown-sidebars.twig", "slot-subform-sidebars.twig", 9)->display(array_merge($context, array("slug" =>         // line 11
($context["slug"] ?? null), "settings" =>         // line 12
($context["settings"] ?? null), "sidebars" =>         // line 13
($context["slots"] ?? null), "strings" =>         // line 14
($context["strings"] ?? null))));
        // line 17
        echo "
    ";
        // line 18
        $this->loadTemplate("dropdown-templates.twig", "slot-subform-sidebars.twig", 18)->display(array_merge($context, array("id" => ("in-sidebars-" .         // line 20
($context["slug"] ?? null)), "name" => (("sidebars[" .         // line 21
($context["slug"] ?? null)) . "][template]"), "value" => $this->getAttribute(        // line 22
($context["slot_settings"] ?? null), "template", array()), "slot_type" => "sidebars")));
        // line 26
        echo "
    ";
        // line 27
        $this->loadTemplate("checkboxes-includes.twig", "slot-subform-sidebars.twig", 27)->display(array_merge($context, array("name_base" => (("sidebars[" .         // line 29
($context["slug"] ?? null)) . "]"), "slot_settings" =>         // line 30
($context["slot_settings"] ?? null), "strings" =>         // line 31
($context["strings"] ?? null), "template_slug" => $this->getAttribute(        // line 32
($context["slot_settings"] ?? null), "template", array()))));
        // line 35
        echo "
    <h4><label for=\"widget-title-in-";
        // line 36
        echo twig_escape_filter($this->env, ($context["slug"] ?? null), "html", null, true);
        echo "\">
            ";
        // line 37
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "sidebars", array()), "label_widget_title", array()), "html", null, true);
        echo "  ";
        $this->loadTemplate("tooltip.twig", "slot-subform-sidebars.twig", 37)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "widget_title", array()))));
        echo "</label></h4>

    <input type=\"text\" id=\"widget-title-in-";
        // line 39
        echo twig_escape_filter($this->env, ($context["slug"] ?? null), "html", null, true);
        echo "\"
           name=\"sidebars[";
        // line 40
        echo twig_escape_filter($this->env, ($context["slug"] ?? null), "html", null, true);
        echo "][widget_title]\" value=\"";
        echo twig_escape_filter($this->env, $this->getAttribute(($context["slot_settings"] ?? null), "widget_title", array()), "html", null, true);
        echo "\" size=\"40\">


    ";
        // line 43
        $this->loadTemplate("panel-colors.twig", "slot-subform-sidebars.twig", 43)->display(array_merge($context, array("strings" =>         // line 45
($context["strings"] ?? null), "id" => ("in-sidebars-" .         // line 46
($context["slug"] ?? null)), "name_base" => (("sidebars[" .         // line 47
($context["slug"] ?? null)) . "]"), "slot_settings" =>         // line 48
($context["slot_settings"] ?? null), "color_schemes" =>         // line 49
($context["color_schemes"] ?? null))));
        // line 52
        echo "
</div>";
    }

    public function getTemplateName()
    {
        return "slot-subform-sidebars.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  91 => 52,  89 => 49,  88 => 48,  87 => 47,  86 => 46,  85 => 45,  84 => 43,  76 => 40,  72 => 39,  65 => 37,  61 => 36,  58 => 35,  56 => 32,  55 => 31,  54 => 30,  53 => 29,  52 => 27,  49 => 26,  47 => 22,  46 => 21,  45 => 20,  44 => 18,  41 => 17,  39 => 14,  38 => 13,  37 => 12,  36 => 11,  35 => 9,  30 => 6,  28 => 5,  25 => 4,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "slot-subform-sidebars.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/slot-subform-sidebars.twig");
    }
}
