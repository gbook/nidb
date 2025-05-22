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

/* table/page_with_secondary_tabs.twig */
class __TwigTemplate_6259aacce2ba5a80a92112cb79cdeedd32d0e08c685700def58ccfe3d30e71cf extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        if (((($__internal_compile_0 = ($context["cfg_relation"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["relwork"] ?? null) : null) || ($context["is_foreign_key_supported"] ?? null))) {
            // line 2
            echo "  <ul class=\"nav nav-pills m-2 d-print-none\">
    <li class=\"nav-item\">
      <a href=\"";
            // line 4
            echo PhpMyAdmin\Url::getFromRoute("/table/structure", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]);
            echo "\" id=\"table_structure_id\" class=\"nav-link";
            echo (((($context["route"] ?? null) == "/table/structure")) ? (" active") : (""));
            echo "\">
        ";
            // line 5
            echo \PhpMyAdmin\Html\Generator::getIcon("b_props", _gettext("Table structure"), true);
            echo "
      </a>
    </li>

    <li class=\"nav-item\">
      <a href=\"";
            // line 10
            echo PhpMyAdmin\Url::getFromRoute("/table/relation", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]);
            echo "\" id=\"table_relation_id\" class=\"nav-link";
            echo (((($context["route"] ?? null) == "/table/relation")) ? (" active") : (""));
            echo "\">
        ";
            // line 11
            echo \PhpMyAdmin\Html\Generator::getIcon("b_relations", _gettext("Relation view"), true);
            echo "
      </a>
    </li>
  </ul>
";
        }
        // line 16
        echo "
<div id=\"structure_content\">
  ";
        // line 18
        $this->displayBlock('content', $context, $blocks);
        // line 19
        echo "</div>
";
    }

    // line 18
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function getTemplateName()
    {
        return "table/page_with_secondary_tabs.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  83 => 18,  78 => 19,  76 => 18,  72 => 16,  64 => 11,  58 => 10,  50 => 5,  44 => 4,  40 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/page_with_secondary_tabs.twig", "/var/www/html/phpMyAdmin/templates/table/page_with_secondary_tabs.twig");
    }
}
