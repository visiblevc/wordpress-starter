<?php

/* template.twig */
class __TwigTemplate_78c4ad180bae7a4ae2c9da685995c2bd5efc2b0beea7e34c94fbd9312cb0ff2a extends Twig_Template
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
        $context["css_classes_flag"] = trim(("wpml-ls-flag " . $this->getAttribute(($context["backward_compatibility"] ?? null), "css_classes_flag", array())));
        // line 2
        $context["css_classes_native"] = trim(("wpml-ls-native " . $this->getAttribute(($context["backward_compatibility"] ?? null), "css_classes_native", array())));
        // line 3
        $context["css_classes_display"] = trim(("wpml-ls-display " . $this->getAttribute(($context["backward_compatibility"] ?? null), "css_classes_display", array())));
        // line 4
        $context["css_classes_bracket"] = trim(("wpml-ls-bracket " . $this->getAttribute(($context["backward_compatibility"] ?? null), "css_classes_bracket", array())));
        // line 5
        echo "
";
        // line 6
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["languages"] ?? null));
        foreach ($context['_seq'] as $context["code"] => $context["language"]) {
            // line 7
            echo "    ";
            ob_start();
            // line 8
            echo "    <span class=\"";
            echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "css_classes", array()), "html", null, true);
            echo " wpml-ls-item-legacy-post-translations\">
        <a href=\"";
            // line 9
            echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "url", array()), "html", null, true);
            echo "\"";
            if ($this->getAttribute($this->getAttribute($context["language"], "backward_compatibility", array()), "css_classes_a", array())) {
                echo " class=\"";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($context["language"], "backward_compatibility", array()), "css_classes_a", array()), "html", null, true);
                echo "\"";
            }
            echo ">";
            // line 10
            if ($this->getAttribute($context["language"], "flag_url", array())) {
                // line 11
                echo "<img class=\"";
                echo twig_escape_filter($this->env, ($context["css_classes_flag"] ?? null), "html", null, true);
                echo "\" src=\"";
                echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "flag_url", array()), "html", null, true);
                echo "\" alt=\"";
                echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "code", array()), "html", null, true);
                echo "\" title=\"";
                echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "flag_title", array()), "html", null, true);
                echo "\">";
            }
            // line 14
            if (($this->getAttribute($context["language"], "is_current", array()) && ($this->getAttribute($context["language"], "native_name", array()) || $this->getAttribute($context["language"], "display_name", array())))) {
                // line 16
                $context["current_language_name"] = (($this->getAttribute($context["language"], "native_name", array(), "any", true, true)) ? (_twig_default_filter($this->getAttribute($context["language"], "native_name", array()), $this->getAttribute($context["language"], "display_name", array()))) : ($this->getAttribute($context["language"], "display_name", array())));
                // line 17
                echo "<span class=\"";
                echo twig_escape_filter($this->env, ($context["css_classes_native"] ?? null), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, ($context["current_language_name"] ?? null), "html", null, true);
                echo "</span>";
            } else {
                // line 21
                if ($this->getAttribute($context["language"], "native_name", array())) {
                    // line 22
                    echo "<span class=\"";
                    echo twig_escape_filter($this->env, ($context["css_classes_native"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "native_name", array()), "html", null, true);
                    echo "</span>";
                }
                // line 25
                if ($this->getAttribute($context["language"], "display_name", array())) {
                    // line 26
                    echo "<span class=\"";
                    echo twig_escape_filter($this->env, ($context["css_classes_display"] ?? null), "html", null, true);
                    echo "\">";
                    // line 27
                    if ($this->getAttribute($context["language"], "native_name", array())) {
                        echo "<span class=\"";
                        echo twig_escape_filter($this->env, ($context["css_classes_bracket"] ?? null), "html", null, true);
                        echo "\"> (</span>";
                    }
                    // line 28
                    echo twig_escape_filter($this->env, $this->getAttribute($context["language"], "display_name", array()), "html", null, true);
                    // line 29
                    if ($this->getAttribute($context["language"], "native_name", array())) {
                        echo "<span class=\"";
                        echo twig_escape_filter($this->env, ($context["css_classes_bracket"] ?? null), "html", null, true);
                        echo "\">)</span>";
                    }
                    // line 30
                    echo "</span>";
                }
            }
            // line 34
            echo "</a>
    </span>
    ";
            echo trim(preg_replace('/>\s+</', '><', ob_get_clean()));
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['code'], $context['language'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    public function getTemplateName()
    {
        return "template.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  108 => 34,  104 => 30,  98 => 29,  96 => 28,  90 => 27,  86 => 26,  84 => 25,  77 => 22,  75 => 21,  68 => 17,  66 => 16,  64 => 14,  53 => 11,  51 => 10,  42 => 9,  37 => 8,  34 => 7,  30 => 6,  27 => 5,  25 => 4,  23 => 3,  21 => 2,  19 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Twig_Source("", "template.twig", "/app/wp-content/plugins/sitepress-multilingual-cms/templates/language-switchers/legacy-post-translations/template.twig");
    }
}
