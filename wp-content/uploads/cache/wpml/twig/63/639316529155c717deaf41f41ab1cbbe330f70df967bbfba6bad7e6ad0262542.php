<?php

/* radio-hierarchical-menu.twig */
class __TwigTemplate_d089e3cef219eed1a323eca35f77f4b9fd71089ef23e40f8728fd065e034d138 extends Twig_Template
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
        if ( !$this->getAttribute(($context["slot_settings"] ?? null), "is_hierarchical", array(), "any", true, true)) {
            // line 2
            echo "    ";
            $context["is_hierarchical"] = 1;
        } else {
            // line 4
            echo "    ";
            $context["is_hierarchical"] = $this->getAttribute(($context["slot_settings"] ?? null), "is_hierarchical", array());
        }
        // line 6
        echo "
<h4><label>";
        // line 7
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "menus", array()), "is_hierarchical_label", array()), "html", null, true);
        echo "</label>  ";
        $this->loadTemplate("tooltip.twig", "radio-hierarchical-menu.twig", 7)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "menu_style_type", array()))));
        echo "</h4>
<ul>
    <li>
        <label>
            <input type=\"radio\" class=\"js-wpml-ls-trigger-update js-wpml-ls-menu-is-hierarchical\"
                   name=\"";
        // line 12
        if (($context["name_base"] ?? null)) {
            echo twig_escape_filter($this->env, ($context["name_base"] ?? null), "html", null, true);
            echo "[is_hierarchical]";
        } else {
            echo "is_hierarchical";
        }
        echo "\"
                   value=\"1\"";
        // line 13
        if ((($context["is_hierarchical"] ?? null) == 1)) {
            echo " checked=\"checked\"";
        }
        echo "><b>";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "menus", array()), "hierarchical", array()), "html", null, true);
        echo "</b> - ";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "menus", array()), "hierarchical_desc", array()), "html", null, true);
        echo "
        </label>
    </li>
    <li>
        <label>
            <input type=\"radio\" class=\"js-wpml-ls-trigger-update js-wpml-ls-menu-is-hierarchical\"
                   name=\"";
        // line 19
        if (($context["name_base"] ?? null)) {
            echo twig_escape_filter($this->env, ($context["name_base"] ?? null), "html", null, true);
            echo "[is_hierarchical]";
        } else {
            echo "is_hierarchical";
        }
        echo "\"
                   value=\"0\"";
        // line 20
        if ((($context["is_hierarchical"] ?? null) == 0)) {
            echo " checked=\"checked\"";
        }
        echo "><b>";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "menus", array()), "flat", array()), "html", null, true);
        echo "</b> - ";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "menus", array()), "flat_desc", array()), "html", null, true);
        echo "
        </label>
    </li>
</ul>";
    }

    public function getTemplateName()
    {
        return "radio-hierarchical-menu.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  75 => 20,  66 => 19,  51 => 13,  42 => 12,  32 => 7,  29 => 6,  25 => 4,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "radio-hierarchical-menu.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/radio-hierarchical-menu.twig");
    }
}
