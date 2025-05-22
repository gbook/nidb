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

/* table/relation/dropdown_generate.twig */
class __TwigTemplate_e6cadb7213ba51a7df5f68dd98697302b68c8a29e9cf6ac0c732266c5e1902a5 extends \Twig\Template
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
        (( !twig_test_empty(($context["dropdown_question"] ?? null))) ? (print (twig_escape_filter($this->env, ($context["dropdown_question"] ?? null), "html", null, true))) : (print ("")));
        // line 2
        echo "<select name=\"";
        echo twig_escape_filter($this->env, ($context["select_name"] ?? null), "html", null, true);
        echo "\">
";
        // line 3
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["choices"] ?? null));
        foreach ($context['_seq'] as $context["one_value"] => $context["one_label"]) {
            // line 4
            echo "    <option value=\"";
            echo twig_escape_filter($this->env, $context["one_value"], "html", null, true);
            echo "\"";
            // line 5
            if ((($context["selected_value"] ?? null) == $context["one_value"])) {
                echo " selected=\"selected\"";
            }
            echo ">
        ";
            // line 6
            echo twig_escape_filter($this->env, $context["one_label"], "html", null, true);
            echo "
    </option>
";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['one_value'], $context['one_label'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 9
        echo "</select>
";
    }

    public function getTemplateName()
    {
        return "table/relation/dropdown_generate.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  67 => 9,  58 => 6,  52 => 5,  48 => 4,  44 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/relation/dropdown_generate.twig", "/var/www/html/phpMyAdmin/templates/table/relation/dropdown_generate.twig");
    }
}
