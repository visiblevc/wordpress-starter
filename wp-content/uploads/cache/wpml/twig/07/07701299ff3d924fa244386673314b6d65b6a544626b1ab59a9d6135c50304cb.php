<?php

/* slot-subform-menus.twig */
class __TwigTemplate_6522e3a09e9a381fe17cd4ea98b2596876d7f6967ba6cbe1186ef200722679d5 extends Twig_Template
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
            echo "\t";
            $context["slot_settings"] = ($context["default_menus_slot"] ?? null);
        }
        // line 4
        echo "
";
        // line 5
        $this->loadTemplate("preview.twig", "slot-subform-menus.twig", 5)->display(array_merge($context, array("preview" => ($context["preview"] ?? null))));
        // line 6
        echo "
<div class=\"wpml-ls-subform-options\">

    ";
        // line 9
        $this->loadTemplate("dropdown-menus.twig", "slot-subform-menus.twig", 9)->display(array_merge($context, array("slug" =>         // line 11
($context["slug"] ?? null), "menus" =>         // line 12
($context["slots"] ?? null))));
        // line 15
        echo "
    ";
        // line 16
        $this->loadTemplate("dropdown-templates.twig", "slot-subform-menus.twig", 16)->display(array_merge($context, array("id" => ("in-menus-" .         // line 18
($context["slug"] ?? null)), "name" => (("menus[" .         // line 19
($context["slug"] ?? null)) . "][template]"), "value" => $this->getAttribute(        // line 20
($context["slot_settings"] ?? null), "template", array()), "slot_type" => "menus")));
        // line 24
        echo "
    ";
        // line 25
        $this->loadTemplate("radio-position-menu.twig", "slot-subform-menus.twig", 25)->display(array_merge($context, array("name_base" => (("menus[" .         // line 27
($context["slug"] ?? null)) . "]"), "slot_settings" =>         // line 28
($context["slot_settings"] ?? null))));
        // line 31
        echo "
    ";
        // line 32
        $this->loadTemplate("radio-hierarchical-menu.twig", "slot-subform-menus.twig", 32)->display(array_merge($context, array("name_base" => (("menus[" .         // line 34
($context["slug"] ?? null)) . "]"), "slot_settings" =>         // line 35
($context["slot_settings"] ?? null))));
        // line 38
        echo "

    ";
        // line 40
        $this->loadTemplate("checkboxes-includes.twig", "slot-subform-menus.twig", 40)->display(array_merge($context, array("name_base" => (("menus[" .         // line 42
($context["slug"] ?? null)) . "]"), "slot_settings" =>         // line 43
($context["slot_settings"] ?? null), "template_slug" => $this->getAttribute(        // line 44
($context["slot_settings"] ?? null), "template", array()))));
        // line 47
        echo "
    ";
        // line 48
        $this->loadTemplate("panel-colors.twig", "slot-subform-menus.twig", 48)->display(array_merge($context, array("id" => ("in-menus-" .         // line 50
($context["slug"] ?? null)), "name_base" => (("menus[" .         // line 51
($context["slug"] ?? null)) . "]"), "slot_settings" =>         // line 52
($context["slot_settings"] ?? null), "slot_type" => "menus")));
        // line 56
        echo "
</div>";
    }

    public function getTemplateName()
    {
        return "slot-subform-menus.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  78 => 56,  76 => 52,  75 => 51,  74 => 50,  73 => 48,  70 => 47,  68 => 44,  67 => 43,  66 => 42,  65 => 40,  61 => 38,  59 => 35,  58 => 34,  57 => 32,  54 => 31,  52 => 28,  51 => 27,  50 => 25,  47 => 24,  45 => 20,  44 => 19,  43 => 18,  42 => 16,  39 => 15,  37 => 12,  36 => 11,  35 => 9,  30 => 6,  28 => 5,  25 => 4,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "slot-subform-menus.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/slot-subform-menus.twig");
    }
}
