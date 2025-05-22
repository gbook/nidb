<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* columns_definitions/column_attribute.twig */
class __TwigTemplate_7779d20b3db9045b945c4cad4e647708c24a67876e85e0fe09efe98b7e79b8ec extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        if (((isset($context["submit_attribute"]) || array_key_exists("submit_attribute", $context)) && (($context["submit_attribute"] ?? null) != false))) {
            // line 2
            echo "    ";
            $context["attribute"] = ($context["submit_attribute"] ?? null);
            // line 3
            echo "    ";
        } elseif ((twig_get_attribute($this->env, $this->source,         // line 4
($context["column_meta"] ?? null), "Extra", [], "array", true, true, false, 4) && (twig_in_filter("on update current_timestamp", (($__internal_compile_0 =         // line 5
($context["column_meta"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["Extra"] ?? null) : null)) || twig_in_filter("on update current_timestamp()", twig_lower_filter($this->env, (($__internal_compile_1 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["Extra"] ?? null) : null)))))) {
            // line 6
            echo "    ";
            $context["attribute"] = "on update CURRENT_TIMESTAMP";
        } elseif (twig_get_attribute($this->env, $this->source,         // line 7
($context["extracted_columnspec"] ?? null), "attribute", [], "array", true, true, false, 7)) {
            // line 8
            echo "    ";
            $context["attribute"] = (($__internal_compile_2 = ($context["extracted_columnspec"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["attribute"] ?? null) : null);
        } else {
            // line 10
            echo "    ";
            $context["attribute"] = "";
        }
        // line 12
        $context["attribute"] = twig_upper_filter($this->env, ($context["attribute"] ?? null));
        // line 13
        echo "<select name=\"field_attribute[";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\"
    id=\"field_";
        // line 14
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\">
    ";
        // line 15
        $context["cnt_attribute_types"] = (twig_length_filter($this->env, ($context["attribute_types"] ?? null)) - 1);
        // line 16
        echo "    ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(range(0, ($context["cnt_attribute_types"] ?? null)));
        foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
            // line 17
            echo "        <option value=\"";
            echo twig_escape_filter($this->env, (($__internal_compile_3 = ($context["attribute_types"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3[$context["i"]] ?? null) : null), "html", null, true);
            echo "\"";
            // line 18
            echo (((($context["attribute"] ?? null) == twig_upper_filter($this->env, (($__internal_compile_4 = ($context["attribute_types"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4[$context["i"]] ?? null) : null)))) ? (" selected=\"selected\"") : (""));
            echo ">
            ";
            // line 19
            echo twig_escape_filter($this->env, (($__internal_compile_5 = ($context["attribute_types"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5[$context["i"]] ?? null) : null), "html", null, true);
            echo "
        </option>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 22
        echo "</select>
";
    }

    public function getTemplateName()
    {
        return "columns_definitions/column_attribute.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  97 => 22,  88 => 19,  84 => 18,  80 => 17,  75 => 16,  73 => 15,  67 => 14,  62 => 13,  60 => 12,  56 => 10,  52 => 8,  50 => 7,  47 => 6,  45 => 5,  44 => 4,  42 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "columns_definitions/column_attribute.twig", "/var/www/html/phpMyAdmin/templates/columns_definitions/column_attribute.twig");
    }
}
