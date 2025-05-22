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

/* table/relation/relational_dropdown.twig */
class __TwigTemplate_f15738e4cb154505708de85c314738af24d1a48538e28e0942906e2bcfa92404 extends \Twig\Template
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
        echo "<select name=\"";
        echo twig_escape_filter($this->env, ($context["name"] ?? null), "html", null, true);
        echo "\" title=\"";
        echo twig_escape_filter($this->env, ($context["title"] ?? null), "html", null, true);
        echo "\">
    <option value=\"\"></option>
    ";
        // line 3
        $context["seen_key"] = false;
        // line 4
        echo "    ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["values"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["value"]) {
            // line 5
            echo "        <option value=\"";
            echo twig_escape_filter($this->env, $context["value"], "html", null, true);
            echo "\"";
            // line 6
            if ((( !(($context["foreign"] ?? null) === false) && ($context["value"] == ($context["foreign"] ?? null))) || (( !            // line 7
($context["foreign"] ?? null) && (isset($context["db"]) || array_key_exists("db", $context))) && (($context["db"] ?? null) === $context["value"])))) {
                // line 8
                echo "                selected=\"selected\"";
                // line 9
                $context["seen_key"] = true;
            }
            // line 10
            echo ">
            ";
            // line 11
            echo twig_escape_filter($this->env, $context["value"], "html", null, true);
            echo "
        </option>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['value'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 14
        echo "    ";
        if ((( !(($context["foreign"] ?? null) === false) && (($context["foreign"] ?? null) != "")) &&  !($context["seen_key"] ?? null))) {
            // line 15
            echo "        <option value=\"";
            echo twig_escape_filter($this->env, ($context["foreign"] ?? null), "html", null, true);
            echo "\" selected=\"selected\">
            ";
            // line 16
            echo twig_escape_filter($this->env, ($context["foreign"] ?? null), "html", null, true);
            echo "
        </option>
    ";
        }
        // line 19
        echo "</select>
";
    }

    public function getTemplateName()
    {
        return "table/relation/relational_dropdown.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  90 => 19,  84 => 16,  79 => 15,  76 => 14,  67 => 11,  64 => 10,  61 => 9,  59 => 8,  57 => 7,  56 => 6,  52 => 5,  47 => 4,  45 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/relation/relational_dropdown.twig", "/var/www/html/phpMyAdmin/templates/table/relation/relational_dropdown.twig");
    }
}
