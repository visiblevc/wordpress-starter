<?php

/* section-sidebars.twig */
class __TwigTemplate_4330ada3296c44ffd5463f4a441deac0beb8ddab6d7114071c94d7c43119e842 extends Twig_Template
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
        $this->loadTemplate("table-slots.twig", "section-sidebars.twig", 3)->display(array_merge($context, array("slot_type" => "sidebars", "slots_settings" => $this->getAttribute(        // line 6
($context["settings"] ?? null), "sidebars", array()), "slots" => $this->getAttribute(        // line 7
($context["data"] ?? null), "sidebars", array()))));
        // line 10
        echo "
";
        // line 11
        $this->loadTemplate("button-add-new-ls.twig", "section-sidebars.twig", 11)->display(array_merge($context, array("existing_items" => twig_length_filter($this->env, $this->getAttribute(        // line 13
($context["data"] ?? null), "sidebars", array())), "settings_items" => twig_length_filter($this->env, $this->getAttribute(        // line 14
($context["settings"] ?? null), "sidebars", array())), "tooltip_all_assigned" => $this->getAttribute($this->getAttribute(        // line 15
($context["strings"] ?? null), "tooltips", array()), "add_sidebar_all_assigned", array()), "tooltip_no_item" => $this->getAttribute($this->getAttribute(        // line 16
($context["strings"] ?? null), "tooltips", array()), "add_sidebar_no_sidebar", array()), "button_target" => "#wpml-ls-new-sidebars-template", "button_label" => $this->getAttribute($this->getAttribute(        // line 18
($context["strings"] ?? null), "sidebars", array()), "add_button_label", array()))));
        // line 21
        echo "
<script type=\"text/html\" id=\"wpml-ls-new-sidebars-template\" class=\"js-wpml-ls-template\">
    <div class=\"js-wpml-ls-subform wpml-ls-subform\" data-title=\"";
        // line 23
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "sidebars", array()), "dialog_title_new", array()), "html", null, true);
        echo "\" data-item-slug=\"";
        echo twig_escape_filter($this->env, ($context["slug_placeholder"] ?? null), "html", null, true);
        echo "\" data-item-type=\"sidebars\">

        ";
        // line 25
        $this->loadTemplate("slot-subform-sidebars.twig", "section-sidebars.twig", 25)->display(array_merge($context, array("slug" =>         // line 27
($context["slug_placeholder"] ?? null), "slots_settings" =>         // line 28
($context["slots_settings"] ?? null), "slots" => $this->getAttribute(        // line 29
($context["data"] ?? null), "sidebars", array()), "preview" => $this->getAttribute($this->getAttribute(        // line 30
($context["previews"] ?? null), "sidebars", array()), ($context["slug"] ?? null), array(), "array"))));
        // line 33
        echo "
    </div>
</script>

<script type=\"text/html\" id=\"wpml-ls-new-sidebars-row-template\" class=\"js-wpml-ls-template\">
    ";
        // line 38
        $this->loadTemplate("table-slot-row.twig", "section-sidebars.twig", 38)->display(array_merge($context, array("slug" =>         // line 40
($context["slug_placeholder"] ?? null), "slots" =>         // line 41
($context["sidebars"] ?? null))));
        // line 44
        echo "</script>";
    }

    public function getTemplateName()
    {
        return "section-sidebars.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  66 => 44,  64 => 41,  63 => 40,  62 => 38,  55 => 33,  53 => 30,  52 => 29,  51 => 28,  50 => 27,  49 => 25,  42 => 23,  38 => 21,  36 => 18,  35 => 16,  34 => 15,  33 => 14,  32 => 13,  31 => 11,  28 => 10,  26 => 7,  25 => 6,  24 => 3,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "section-sidebars.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/section-sidebars.twig");
    }
}
