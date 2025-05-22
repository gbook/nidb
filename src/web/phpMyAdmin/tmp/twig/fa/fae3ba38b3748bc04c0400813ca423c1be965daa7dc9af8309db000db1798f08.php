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

/* sql/set_column.twig */
class __TwigTemplate_ffa05781086aa433afe4508080ab67abd0edba82e5417ea23c62b8b6f5191650 extends \Twig\Template
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
        $context["values_amount"] = twig_length_filter($this->env, ($context["values"] ?? null));
        // line 2
        $context["selected_values"] = twig_split_filter($this->env, ($context["current_values"] ?? null), ",");
        // line 3
        echo "<select class=\"resize-vertical\" size=\"";
        (((($context["values_amount"] ?? null) < 10)) ? (print (twig_escape_filter($this->env, ($context["values_amount"] ?? null), "html", null, true))) : (print (10)));
        echo "\" multiple>
  ";
        // line 4
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["values"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["value"]) {
            // line 5
            echo "    <option value=\"";
            echo $context["value"];
            echo "\"";
            echo ((twig_in_filter($context["value"], ($context["selected_values"] ?? null))) ? (" selected") : (""));
            echo ">";
            echo $context["value"];
            echo "</option>
  ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['value'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 7
        echo "</select>
";
    }

    public function getTemplateName()
    {
        return "sql/set_column.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  63 => 7,  50 => 5,  46 => 4,  41 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "sql/set_column.twig", "/var/www/html/phpMyAdmin/templates/sql/set_column.twig");
    }
}
