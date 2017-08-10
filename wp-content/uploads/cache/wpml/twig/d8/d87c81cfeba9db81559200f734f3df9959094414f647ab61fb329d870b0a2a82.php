<?php

/* table-slot-row.twig */
class __TwigTemplate_996549666b6cbe50dc0a3977a78609d24f207921811a786acebea085c4d3dc14 extends Twig_Template
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
        if ((($context["slot_type"] ?? null) == "statics")) {
            // line 2
            echo "\t";
            $context["is_static"] = true;
            // line 3
            echo "\t";
            $context["dialog_title"] = $this->getAttribute($this->getAttribute(($context["strings"] ?? null), ($context["slug"] ?? null), array(), "array"), "dialog_title", array());
            // line 4
            echo "\t";
            $context["include_row"] = (((("slot-subform-" . ($context["slot_type"] ?? null)) . "-") . ($context["slug"] ?? null)) . ".twig");
        } else {
            // line 6
            echo "\t";
            $context["dialog_title"] = $this->getAttribute($this->getAttribute(($context["strings"] ?? null), ($context["slot_type"] ?? null), array(), "array"), "dialog_title", array());
            // line 7
            echo "\t";
            $context["include_row"] = (("slot-subform-" . ($context["slot_type"] ?? null)) . ".twig");
        }
        // line 9
        echo "
";
        // line 10
        $context["slot_row_id"] = ((("wpml-ls-" . ($context["slot_type"] ?? null)) . "-row-") . ($context["slug"] ?? null));
        // line 11
        echo "<tr id=\"";
        echo twig_escape_filter($this->env, ($context["slot_row_id"] ?? null), "html", null, true);
        echo "\" class=\"js-wpml-ls-row\" data-item-slug=\"";
        echo twig_escape_filter($this->env, ($context["slug"] ?? null), "html", null, true);
        echo "\" data-item-type=\"";
        echo twig_escape_filter($this->env, ($context["slot_type"] ?? null), "html", null, true);
        echo "\">
    <td class=\"wpml-ls-cell-preview\">
        <div class=\"js-wpml-ls-subform wpml-ls-subform\" data-origin-id=\"";
        // line 13
        echo twig_escape_filter($this->env, ($context["slot_row_id"] ?? null), "html", null, true);
        echo "\" data-title=\"";
        echo twig_escape_filter($this->env, ($context["dialog_title"] ?? null), "html", null, true);
        echo "\" data-item-slug=\"";
        echo twig_escape_filter($this->env, ($context["slug"] ?? null), "html", null, true);
        echo "\" data-item-type=\"";
        echo twig_escape_filter($this->env, ($context["slot_type"] ?? null), "html", null, true);
        echo "\">
            ";
        // line 14
        if (($context["slot_settings"] ?? null)) {
            // line 15
            echo "                ";
            $this->loadTemplate(($context["include_row"] ?? null), "table-slot-row.twig", 15)->display(array_merge($context, array("slug" =>             // line 17
($context["slug"] ?? null), "slot_settings" =>             // line 18
($context["slot_settings"] ?? null), "settings" =>             // line 19
($context["settings"] ?? null), "slots" =>             // line 20
($context["slots"] ?? null), "strings" =>             // line 21
($context["strings"] ?? null), "preview" => $this->getAttribute($this->getAttribute(            // line 22
($context["previews"] ?? null), ($context["slot_type"] ?? null), array(), "array"), ($context["slug"] ?? null), array(), "array"), "color_schemes" =>             // line 23
($context["color_schemes"] ?? null))));
            // line 26
            echo "            ";
        }
        // line 27
        echo "        </div>
    </td>

\t";
        // line 30
        if ( !($context["is_static"] ?? null)) {
            // line 31
            echo "    <td>
        <span class=\"js-wpml-ls-row-title\">";
            // line 32
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["slots"] ?? null), ($context["slug"] ?? null), array(), "array"), "name", array()), "html", null, true);
            echo "</span>
    </td>
\t";
        }
        // line 35
        echo "
\t<td class=\"wpml-ls-cell-action\">
        <a href=\"#\" title=\"";
        // line 37
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "title_action_edit", array()), "html", null, true);
        echo "\" class=\"js-wpml-ls-row-edit wpml-ls-row-edit\"><i class=\"otgs-ico-edit\"></i></a>
    </td>

\t";
        // line 40
        if ( !($context["is_static"] ?? null)) {
            // line 41
            echo "    <td class=\"wpml-ls-cell-action\">
        <a href=\"#\" title=\"";
            // line 42
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "misc", array()), "title_action_delete", array()), "html", null, true);
            echo "\" class=\"js-wpml-ls-row-remove wpml-ls-row-remove\"><i class=\"otgs-ico-delete\"></i></a>
    </td>
\t";
        }
        // line 45
        echo "</tr>";
    }

    public function getTemplateName()
    {
        return "table-slot-row.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  115 => 45,  109 => 42,  106 => 41,  104 => 40,  98 => 37,  94 => 35,  88 => 32,  85 => 31,  83 => 30,  78 => 27,  75 => 26,  73 => 23,  72 => 22,  71 => 21,  70 => 20,  69 => 19,  68 => 18,  67 => 17,  65 => 15,  63 => 14,  53 => 13,  43 => 11,  41 => 10,  38 => 9,  34 => 7,  31 => 6,  27 => 4,  24 => 3,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "table-slot-row.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/table-slot-row.twig");
    }
}
