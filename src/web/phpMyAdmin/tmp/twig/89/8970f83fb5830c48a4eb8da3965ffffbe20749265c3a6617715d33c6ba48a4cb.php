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

/* server/status/base.twig */
class __TwigTemplate_c0409ccd9b45b89a535d1030ff17f3c06703ef6b4a53f97c0ee55d18202a0c78 extends \Twig\Template
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
        echo "<div class=\"container-fluid\">
  <div class=\"row\">
    <ul class=\"nav nav-pills m-2\">
      <li class=\"nav-item\">
        <a href=\"";
        // line 5
        echo PhpMyAdmin\Url::getFromRoute("/server/status");
        echo "\" class=\"nav-link";
        echo (((($context["active"] ?? null) == "status")) ? (" active") : (""));
        echo "\">
          ";
        // line 6
        echo _gettext("Server");
        // line 7
        echo "        </a>
      </li>
      <li class=\"nav-item\">
        <a href=\"";
        // line 10
        echo PhpMyAdmin\Url::getFromRoute("/server/status/processes");
        echo "\" class=\"nav-link";
        echo (((($context["active"] ?? null) == "processes")) ? (" active") : (""));
        echo "\">
          ";
        // line 11
        echo _gettext("Processes");
        // line 12
        echo "        </a>
      </li>
      <li class=\"nav-item\">
        <a href=\"";
        // line 15
        echo PhpMyAdmin\Url::getFromRoute("/server/status/queries");
        echo "\" class=\"nav-link";
        echo (((($context["active"] ?? null) == "queries")) ? (" active") : (""));
        echo "\">
          ";
        // line 16
        echo _gettext("Query statistics");
        // line 17
        echo "        </a>
      </li>
      <li class=\"nav-item\">
        <a href=\"";
        // line 20
        echo PhpMyAdmin\Url::getFromRoute("/server/status/variables");
        echo "\" class=\"nav-link";
        echo (((($context["active"] ?? null) == "variables")) ? (" active") : (""));
        echo "\">
          ";
        // line 21
        echo _gettext("All status variables");
        // line 22
        echo "        </a>
      </li>
      <li class=\"nav-item\">
        <a href=\"";
        // line 25
        echo PhpMyAdmin\Url::getFromRoute("/server/status/monitor");
        echo "\" class=\"nav-link";
        echo (((($context["active"] ?? null) == "monitor")) ? (" active") : (""));
        echo "\">
          ";
        // line 26
        echo _gettext("Monitor");
        // line 27
        echo "        </a>
      </li>
      <li class=\"nav-item\">
        <a href=\"";
        // line 30
        echo PhpMyAdmin\Url::getFromRoute("/server/status/advisor");
        echo "\" class=\"nav-link";
        echo (((($context["active"] ?? null) == "advisor")) ? (" active") : (""));
        echo "\">
          ";
        // line 31
        echo _gettext("Advisor");
        // line 32
        echo "        </a>
      </li>
    </ul>
  </div>

  ";
        // line 37
        $this->displayBlock('content', $context, $blocks);
        // line 38
        echo "</div>
";
    }

    // line 37
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function getTemplateName()
    {
        return "server/status/base.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  131 => 37,  126 => 38,  124 => 37,  117 => 32,  115 => 31,  109 => 30,  104 => 27,  102 => 26,  96 => 25,  91 => 22,  89 => 21,  83 => 20,  78 => 17,  76 => 16,  70 => 15,  65 => 12,  63 => 11,  57 => 10,  52 => 7,  50 => 6,  44 => 5,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/status/base.twig", "/var/www/html/phpMyAdmin/templates/server/status/base.twig");
    }
}
