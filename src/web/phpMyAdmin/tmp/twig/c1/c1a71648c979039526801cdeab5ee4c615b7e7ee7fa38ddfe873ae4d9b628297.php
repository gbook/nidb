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

/* filter.twig */
class __TwigTemplate_b9f9440ea34ede09948368ad32b55218732ef6ee64d8e7e50112e48f650ec74e extends \Twig\Template
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
        echo "<div class=\"row\">
\t<div class=\"col-12\">
\t\t<fieldset id=\"tableFilter\">
\t\t    <legend>";
        // line 4
        echo _gettext("Filters");
        echo "</legend>
\t\t    <div class=\"formelement\">
\t\t        <label for=\"filterText\">";
        // line 6
        echo _gettext("Containing the word:");
        echo "</label>
\t\t        <input name=\"filterText\" type=\"text\" id=\"filterText\"
\t\t               value=\"";
        // line 8
        echo twig_escape_filter($this->env, ($context["filter_value"] ?? null), "html", null, true);
        echo "\">
\t\t    </div>
\t\t</fieldset>
\t</div>
</div>
";
    }

    public function getTemplateName()
    {
        return "filter.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  52 => 8,  47 => 6,  42 => 4,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "filter.twig", "/var/www/html/phpMyAdmin/templates/filter.twig");
    }
}
