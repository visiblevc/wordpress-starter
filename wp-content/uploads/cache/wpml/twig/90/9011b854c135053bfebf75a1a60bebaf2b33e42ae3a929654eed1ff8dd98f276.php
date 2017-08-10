<?php

/* checkboxes-includes.twig */
class __TwigTemplate_cd34e8a8d228f07838bc5a3d2cada273cb856377ff589231dd0519fb3cd52b64 extends Twig_Template
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
        $context["force"] = $this->getAttribute($this->getAttribute($this->getAttribute(($context["data"] ?? null), "templates", array()), ($context["template_slug"] ?? null), array(), "array"), "force_settings", array());
        // line 2
        $context["is_hierarchical"] = (($this->getAttribute(($context["slot_settings"] ?? null), "slot_group", array()) == "menus") && $this->getAttribute(($context["slot_settings"] ?? null), "is_hierarchical", array()));
        // line 3
        echo "
<h4>";
        // line 4
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "title_what_to_include", array()), "html", null, true);
        echo " ";
        $this->loadTemplate("tooltip.twig", "checkboxes-includes.twig", 4)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "what_to_include", array()))));
        echo "</h4>
<ul class=\"js-wpml-ls-to-include\">
    <li>
        <label><input type=\"checkbox\" class=\"js-wpml-ls-setting-display_flags js-wpml-ls-trigger-update\"
                      name=\"";
        // line 8
        if (($context["name_base"] ?? null)) {
            echo twig_escape_filter($this->env, ($context["name_base"] ?? null), "html", null, true);
            echo "[display_flags]";
        } else {
            echo "display_flags";
        }
        echo "\"
                      ";
        // line 9
        if ($this->getAttribute(($context["force"] ?? null), "display_flags", array(), "any", true, true)) {
            echo " disabled=\"disabled\"";
        }
        // line 10
        echo "                      value=\"1\"";
        if ($this->getAttribute(($context["slot_settings"] ?? null), "display_flags", array())) {
            echo " checked=\"checked\"";
        }
        echo "> ";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "label_include_flag", array()), "html", null, true);
        echo "</label>
    </li>
    <li>
        <label><input type=\"checkbox\" class=\"js-wpml-ls-setting-display_names_in_native_lang js-wpml-ls-trigger-update\"
                      name=\"";
        // line 14
        if (($context["name_base"] ?? null)) {
            echo twig_escape_filter($this->env, ($context["name_base"] ?? null), "html", null, true);
            echo "[display_names_in_native_lang]";
        } else {
            echo "display_names_in_native_lang";
        }
        echo "\"
                      ";
        // line 15
        if ($this->getAttribute(($context["force"] ?? null), "display_names_in_native_lang", array(), "any", true, true)) {
            echo " disabled=\"disabled\"";
        }
        // line 16
        echo "                      value=\"1\"";
        if ($this->getAttribute(($context["slot_settings"] ?? null), "display_names_in_native_lang", array())) {
            echo " checked=\"checked\"";
        }
        echo "> ";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "label_include_native_lang", array()), "html", null, true);
        echo "</label>
    </li>
    <li>
        <label><input type=\"checkbox\" class=\"js-wpml-ls-setting-display_names_in_current_lang js-wpml-ls-trigger-update\"
                      name=\"";
        // line 20
        if (($context["name_base"] ?? null)) {
            echo twig_escape_filter($this->env, ($context["name_base"] ?? null), "html", null, true);
            echo "[display_names_in_current_lang]";
        } else {
            echo "display_names_in_current_lang";
        }
        echo "\"
                      ";
        // line 21
        if ($this->getAttribute(($context["force"] ?? null), "display_names_in_current_lang", array(), "any", true, true)) {
            echo " disabled=\"disabled\"";
        }
        // line 22
        echo "                      value=\"1\"";
        if ((($this->getAttribute(($context["slot_settings"] ?? null), "display_names_in_current_lang", array(), "any", true, true)) ? (_twig_default_filter($this->getAttribute(($context["slot_settings"] ?? null), "display_names_in_current_lang", array()), 1)) : (1))) {
            echo " checked=\"checked\"";
        }
        echo "> ";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "label_include_display_lang", array()), "html", null, true);
        echo "</label>
    </li>
    <li>
        <label><input type=\"checkbox\" class=\"js-wpml-ls-setting-display_link_for_current_lang js-wpml-ls-trigger-update\"
                      name=\"";
        // line 26
        if (($context["name_base"] ?? null)) {
            echo twig_escape_filter($this->env, ($context["name_base"] ?? null), "html", null, true);
            echo "[display_link_for_current_lang]";
        } else {
            echo "display_link_for_current_lang";
        }
        echo "\"
                      ";
        // line 27
        if (($this->getAttribute(($context["force"] ?? null), "display_link_for_current_lang", array(), "any", true, true) || ($context["is_hierarchical"] ?? null))) {
            echo " disabled=\"disabled\"";
        }
        // line 28
        echo "                      value=\"1\"";
        if ((($this->getAttribute(($context["slot_settings"] ?? null), "display_link_for_current_lang", array(), "any", true, true)) ? (_twig_default_filter($this->getAttribute(($context["slot_settings"] ?? null), "display_link_for_current_lang", array()), 1)) : (1))) {
            echo " checked=\"checked\"";
        }
        echo "> ";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "label_include_current_lang", array()), "html", null, true);
        echo "</label>
    </li>
</ul>";
    }

    public function getTemplateName()
    {
        return "checkboxes-includes.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  123 => 28,  119 => 27,  110 => 26,  98 => 22,  94 => 21,  85 => 20,  73 => 16,  69 => 15,  60 => 14,  48 => 10,  44 => 9,  35 => 8,  26 => 4,  23 => 3,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "checkboxes-includes.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/checkboxes-includes.twig");
    }
}
