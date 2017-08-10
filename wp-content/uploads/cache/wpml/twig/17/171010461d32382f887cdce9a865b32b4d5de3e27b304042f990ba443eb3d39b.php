<?php

/* section-options.twig */
class __TwigTemplate_a1198c76ecddfd6f2b8eae67a458b089af072a2d35ef446e098efc47549f52ee extends Twig_Template
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
        echo "<div class=\"js-wpml-ls-option wpml-ls-language_order\">
\t<h4><label>";
        // line 2
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "label_language_order", array()), "html", null, true);
        echo "</label> ";
        $this->loadTemplate("tooltip.twig", "section-options.twig", 2)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "languages_order", array()))));
        // line 3
        echo "\t\t";
        $this->loadTemplate("save-notification.twig", "section-options.twig", 3)->display($context);
        // line 4
        echo "\t</h4>
\t<p class=\"explanation-text\">";
        // line 5
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "tip_drag_languages", array()), "html", null, true);
        echo "</p>
\t<ul id=\"wpml-ls-languages-order\" class=\"wpml-ls-languages-order\">
\t\t";
        // line 7
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["ordered_languages"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["language"]) {
            // line 8
            echo "\t\t<li class=\"js-wpml-languages-order-item\" data-language-code=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "code", array()), "html", null, true);
            echo "\">
\t\t\t<img src=\"";
            // line 9
            echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "flag_url", array()), "html", null, true);
            echo "\"> ";
            echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "display_name", array()), "html", null, true);
            echo "<input type=\"hidden\" name=\"languages_order[]\" value=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "code", array()), "html", null, true);
            echo "\">
\t\t</li>
\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['language'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 12
        echo "\t</ul>
</div>

<div class=\"js-wpml-ls-option wpml-ls-languages_with_no_translations\">
\t<h4><label>";
        // line 16
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "label_languages_with_no_translations", array()), "html", null, true);
        echo " ";
        $this->loadTemplate("tooltip.twig", "section-options.twig", 16)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "languages_without_translation", array()))));
        // line 17
        echo "\t\t</label>
\t\t";
        // line 18
        $this->loadTemplate("save-notification.twig", "section-options.twig", 18)->display($context);
        // line 19
        echo "\t</h4>
\t<ul>
\t\t<li>
\t\t\t<label for=\"link_empty_off\">
\t\t\t\t<input type=\"radio\" name=\"link_empty\" id=\"link_empty_off\"
\t\t\t\t\t   class=\"js-wpml-ls-trigger-save\"
\t\t\t\t\t   value=\"0\"";
        // line 25
        if ( !$this->getAttribute(($context["settings"] ?? null), "link_empty", array())) {
            echo " checked=\"checked\"";
        }
        echo ">";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "option_skip_link", array()), "html", null, true);
        echo "
\t\t\t</label>
\t\t</li>
\t\t<li>
\t\t\t<label for=\"link_empty_on\">
\t\t\t\t<input type=\"radio\" name=\"link_empty\" id=\"link_empty_on\"
\t\t\t\t\t   class=\"js-wpml-ls-trigger-save\"
\t\t\t\t\t   value=\"1\"";
        // line 32
        if ($this->getAttribute(($context["settings"] ?? null), "link_empty", array())) {
            echo " checked=\"checked\"";
        }
        echo ">";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "option_link_home", array()), "html", null, true);
        echo "
\t\t\t</label>
\t\t</li>
\t</ul>
</div>

<div class=\"js-wpml-ls-option wpml-ls-preserve_url_args\">
\t<p class=\"wpml-ls-form-line\">
\t\t";
        // line 40
        if ( !$this->getAttribute(($context["settings"] ?? null), "copy_parameters", array())) {
            echo "<a href=\"#\" class=\"js-wpml-ls-toggle-once\">";
        }
        // line 41
        echo "\t\t\t<label for=\"copy_parameters\">
\t\t\t\t";
        // line 42
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "label_preserve_url_args", array()), "html", null, true);
        if ( !$this->getAttribute(($context["settings"] ?? null), "copy_parameters", array())) {
            echo "<span class=\"otgs-ico-caret-down js-arrow-toggle\"></span>";
        }
        // line 43
        echo "</label>";
        if ( !$this->getAttribute(($context["settings"] ?? null), "copy_parameters", array())) {
            echo "</a>";
        }
        // line 44
        echo "
\t\t";
        // line 45
        $this->loadTemplate("tooltip.twig", "section-options.twig", 45)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "preserve_url_arguments", array()))));
        // line 46
        echo "
\t\t";
        // line 47
        $this->loadTemplate("save-notification.twig", "section-options.twig", 47)->display($context);
        // line 48
        echo "
\t\t<input type=\"text\" size=\"100\" id=\"copy_parameters\" name=\"copy_parameters\"
\t\t\t   value=\"";
        // line 50
        echo twig_escape_filter($this->env, $this->getAttribute(($context["settings"] ?? null), "copy_parameters", array()), "html", null, true);
        echo "\"
\t\t\t   class=\"js-wpml-ls-trigger-save js-wpml-ls-trigger-need-save";
        // line 51
        if ( !$this->getAttribute(($context["settings"] ?? null), "copy_parameters", array())) {
            echo " js-wpml-ls-toggle-target hidden";
        }
        echo "\">
\t</p>
</div>

<div class=\"js-wpml-ls-option wpml-ls-additional_css\">
\t<p class=\"wpml-ls-form-line\">
\t\t";
        // line 57
        if ( !$this->getAttribute(($context["settings"] ?? null), "additional_css", array())) {
            echo "<a href=\"#\" class=\"js-wpml-ls-toggle-once\">";
        }
        // line 58
        echo "\t\t\t<label for=\"additional_css\">
\t\t\t\t";
        // line 59
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "label_additional_css", array()), "html", null, true);
        if ( !$this->getAttribute(($context["settings"] ?? null), "additional_css", array())) {
            echo "<span class=\"otgs-ico-caret-down js-arrow-toggle\"></span>";
        }
        // line 60
        echo "</label>";
        if ( !$this->getAttribute(($context["settings"] ?? null), "additional_css", array())) {
            echo "</a>";
        }
        // line 61
        echo "

\t\t";
        // line 63
        $this->loadTemplate("tooltip.twig", "section-options.twig", 63)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "additional_css", array()))));
        // line 64
        echo "
\t\t";
        // line 65
        $this->loadTemplate("save-notification.twig", "section-options.twig", 65)->display($context);
        // line 66
        echo "
\t\t<textarea id=\"additional_css\" name=\"additional_css\" rows=\"4\"
\t\t\t\t  class=\"large-text js-wpml-ls-additional-css js-wpml-ls-trigger-save js-wpml-ls-trigger-need-save";
        // line 68
        if ( !$this->getAttribute(($context["settings"] ?? null), "additional_css", array())) {
            echo " js-wpml-ls-toggle-target hidden";
        }
        echo "\">";
        // line 69
        echo twig_escape_filter($this->env, $this->getAttribute(($context["settings"] ?? null), "additional_css", array()), "html", null, true);
        // line 70
        echo "</textarea>
\t</p>
</div>

<div class=\"js-wpml-ls-option wpml-ls-backwards_compatibility\">
\t<div class=\"wpml-ls-form-line\">
\t\t";
        // line 76
        if (( !$this->getAttribute(($context["settings"] ?? null), "migrated", array()) == 1)) {
            // line 77
            echo "\t\t\t";
            $context["hide_backwards_compatibility"] = true;
            // line 78
            echo "\t\t";
        }
        // line 79
        echo "
\t\t";
        // line 80
        if (($context["hide_backwards_compatibility"] ?? null)) {
            echo "<a href=\"#\" class=\"js-wpml-ls-toggle-once\">";
        }
        // line 81
        echo "\t\t\t<label>
\t\t\t\t";
        // line 82
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "label_migrated_toggle", array()), "html", null, true);
        if (($context["hide_backwards_compatibility"] ?? null)) {
            echo "<span class=\"otgs-ico-caret-down js-arrow-toggle\"></span>";
        }
        // line 83
        echo "</label>";
        if (($context["hide_backwards_compatibility"] ?? null)) {
            echo "</a>";
        }
        // line 84
        echo "
\t\t";
        // line 85
        $this->loadTemplate("tooltip.twig", "section-options.twig", 85)->display(array_merge($context, array("content" => $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "tooltips", array()), "backwards_compatibility", array()))));
        // line 86
        echo "
\t\t";
        // line 87
        $this->loadTemplate("save-notification.twig", "section-options.twig", 87)->display($context);
        // line 88
        echo "
\t\t<p";
        // line 89
        if (($context["hide_backwards_compatibility"] ?? null)) {
            echo " class=\"js-wpml-ls-toggle-target hidden\"";
        }
        echo ">
\t\t\t<input type=\"checkbox\" id=\"wpml-ls-backwards-compatibility\" name=\"migrated\"
\t\t\t\t   value=\"0\"";
        // line 91
        if (($this->getAttribute(($context["settings"] ?? null), "migrated", array()) == 0)) {
            echo " checked=\"checked\"";
        }
        // line 92
        echo "\t\t\t\t   class=\"js-wpml-ls-migrated js-wpml-ls-trigger-save js-wpml-ls-trigger-need-save\">

\t\t\t<label for=\"wpml-ls-backwards-compatibility\">
\t\t\t\t";
        // line 95
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["strings"] ?? null), "options", array()), "label_skip_backwards_compatibility", array()), "html", null, true);
        echo "
\t\t\t</label>
\t\t</p>

\t</div>
</div>";
    }

    public function getTemplateName()
    {
        return "section-options.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  261 => 95,  256 => 92,  252 => 91,  245 => 89,  242 => 88,  240 => 87,  237 => 86,  235 => 85,  232 => 84,  227 => 83,  222 => 82,  219 => 81,  215 => 80,  212 => 79,  209 => 78,  206 => 77,  204 => 76,  196 => 70,  194 => 69,  189 => 68,  185 => 66,  183 => 65,  180 => 64,  178 => 63,  174 => 61,  169 => 60,  164 => 59,  161 => 58,  157 => 57,  146 => 51,  142 => 50,  138 => 48,  136 => 47,  133 => 46,  131 => 45,  128 => 44,  123 => 43,  118 => 42,  115 => 41,  111 => 40,  96 => 32,  82 => 25,  74 => 19,  72 => 18,  69 => 17,  65 => 16,  59 => 12,  46 => 9,  41 => 8,  37 => 7,  32 => 5,  29 => 4,  26 => 3,  22 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "section-options.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switcher-admin-ui/section-options.twig");
    }
}
