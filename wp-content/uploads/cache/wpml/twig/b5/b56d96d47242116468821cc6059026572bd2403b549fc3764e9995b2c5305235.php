<?php

/* section-menus.twig */
class __TwigTemplate_3d837b45188a7178d831f12e02ad0181304ab890e499077061c80067ead6a3ad extends Twig_Template
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
        $context["slug_placeholder"] = "%id%";
        // line 2
        echo "
";
        // line 3
        $this->loadTemplate("table-slots.twig", "section-menus.twig", 3)->display(array_merge($context, array("slot_type" => "menus", "slots_settings" => $this->getAttribute(        // line 6
($context["settings"] ?? null), "menus", array()), "slots" => $this->getAttribute(        // line 7
($context["data"] ?? null), "menus", array()))));
        // line 10
        echo "
";
        // line 11
        $this->loadTemplate("button-add-new-ls.twig", "section-menus.twig", 11)->display(array_merge($context, array("existing_items" => twig_length_filter($this->env, $this->getAttribute(        // line 13
($context["data"] ?? null), "menus", array())), "settings_items" => twig_length_filter($this->env, $this->getAttribute(        // line 14
($context["settings"] ?? null), "menus", array())), "tooltip_all_assigned" => $this->getAttribute($this->getAttribute(        // line 15
($context["strings"] ?? null), "tooltips", array()), "add_menu_all_assigned", array()), "tooltip_no_item" => $this->getAttribute($this->getAttribute(        // line 16
($context["strings"] ?? null), "tooltips", array()), "add_menu_no_menu", array()), "button_target" => "#wpml-ls-new-menus-template", "button_label" => $this->getAttribute($this->getAttribute(        // line 18
($context["strings"] ?? null), "menus", array()), "add_button_label", array()))));
        // line 21
        echo "
<script type=\"text/html\" id=\"wpml-ls-new-menus-template\" class=\"js-wpml-ls-template\">
    <div class=\"js-wpml-ls-subform wpml-ls-subform\" data-title=\"";
        // line 23
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "menus", array()), "dialog_title_new", array()), "html", null, true);
        echo "\" data-item-slug=\"";
        echo twig_escape_filter($this->env, ($context["slug_placeholder"] ?? null), "html", null, true);
        echo "\" data-item-type=\"menus\">

        ";
        // line 25
        $this->loadTemplate("slot-subform-menus.twig", "section-menus.twig", 25)->display(array_merge($context, array("slug" =>         // line 27
($context["slug_placeholder"] ?? null), "slots_settings" =>         // line 28
($context["slots_settings"] ?? null), "slots" => $this->getAttribute(        // line 29
($context["data"] ?? null), "menus", array()), "preview" => $this->getAttribute($this->getAttribute(        // line 30
($context["previews"] ?? null), "menu", array()), ($context["slug"] ?? null), array(), "array"))));
        // line 33
        echo "    </div>
</script>

<script type=\"text/html\" id=\"wpml-ls-new-menus-row-template\" class=\"js-wpml-ls-template\">
    ";
        // line 37
        $this->loadTemplate("table-slot-row.twig", "section-menus.twig", 37)->display(array_merge($context, array("slug" =>         // line 39
($context["slug_placeholder"] ?? null), "slots" =>         // line 40
($context["menus"] ?? null))));
        // line 43
        echo "</script>";
    }

    public function getTemplateName()
    {
        return "section-menus.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  65 => 43,  63 => 40,  62 => 39,  61 => 37,  55 => 33,  53 => 30,  52 => 29,  51 => 28,  50 => 27,  49 => 25,  42 => 23,  38 => 21,  36 => 18,  35 => 16,  34 => 15,  33 => 14,  32 => 13,  31 => 11,  28 => 10,  26 => 7,  25 => 6,  24 => 3,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "section-menus.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/section-menus.twig");
    }
}
