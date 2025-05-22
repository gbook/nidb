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

/* sql/no_results_returned.twig */
class __TwigTemplate_b85ecc35f36f05e89cb138075d5427b567ee72212b7634a12b418b5d81669f27 extends \Twig\Template
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
        echo ($context["message"] ?? null);
        echo "

";
        // line 3
        echo ($context["sql_query_results_table"] ?? null);
        echo "

";
        // line 5
        echo ($context["profiling_chart"] ?? null);
        echo "

";
        // line 7
        if ( !($context["is_procedure"] ?? null)) {
            // line 8
            echo "  <fieldset class=\"print_ignore\">
    <legend>";
            // line 9
            echo _gettext("Query results operations");
            echo "</legend>
    <span>
      ";
            // line 11
            echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/view/create"), ["db" =>             // line 13
($context["db"] ?? null), "table" => ($context["table"] ?? null), "printview" => "1", "sql_query" => ($context["sql_query"] ?? null)], \PhpMyAdmin\Html\Generator::getIcon("b_view_add", _gettext("Create view"), true), ["class" => "create_view ajax btn"]);
            // line 16
            echo "
    </span>
  </fieldset>
";
        }
        // line 20
        echo "
";
        // line 21
        echo ($context["bookmark"] ?? null);
        echo "
";
    }

    public function getTemplateName()
    {
        return "sql/no_results_returned.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  74 => 21,  71 => 20,  65 => 16,  63 => 13,  62 => 11,  57 => 9,  54 => 8,  52 => 7,  47 => 5,  42 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "sql/no_results_returned.twig", "/var/www/html/phpMyAdmin/templates/sql/no_results_returned.twig");
    }
}
