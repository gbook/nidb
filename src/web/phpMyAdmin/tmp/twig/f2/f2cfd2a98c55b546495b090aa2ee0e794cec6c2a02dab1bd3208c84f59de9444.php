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

/* server/privileges/user_overview.twig */
class __TwigTemplate_6c53c17b25b1c5e0b089fb70a503d0c7430374347606d681e9e7b440d4ef312d extends \Twig\Template
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
  <div class=\"col-12\">
    <h2>
      ";
        // line 4
        echo \PhpMyAdmin\Html\Generator::getIcon("b_usrlist");
        echo "
      ";
        // line 5
        echo _gettext("User accounts overview");
        // line 6
        echo "    </h2>
  </div>
</div>

";
        // line 10
        echo ($context["error_messages"] ?? null);
        echo "

";
        // line 12
        echo ($context["empty_user_notice"] ?? null);
        echo "

";
        // line 14
        echo ($context["initials"] ?? null);
        echo "

";
        // line 16
        if ( !twig_test_empty(($context["users_overview"] ?? null))) {
            // line 17
            echo "  ";
            echo ($context["users_overview"] ?? null);
            echo "
";
        } elseif (        // line 18
($context["is_createuser"] ?? null)) {
            // line 19
            echo "  <div class=\"row\">
    <div class=\"col-12\">
      <fieldset id=\"fieldset_add_user\">
        <legend>";
            // line 22
            echo _pgettext(            "Create new user", "New");
            echo "</legend>
        <a id=\"add_user_anchor\" href=\"";
            // line 23
            echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["adduser" => true]);
            echo "\">
          ";
            // line 24
            echo \PhpMyAdmin\Html\Generator::getIcon("b_usradd", _gettext("Add user account"));
            echo "
        </a>
      </fieldset>
    </div>
  </div>
";
        }
        // line 30
        echo "
";
        // line 31
        echo ($context["flush_notice"] ?? null);
        echo "
";
    }

    public function getTemplateName()
    {
        return "server/privileges/user_overview.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  103 => 31,  100 => 30,  91 => 24,  87 => 23,  83 => 22,  78 => 19,  76 => 18,  71 => 17,  69 => 16,  64 => 14,  59 => 12,  54 => 10,  48 => 6,  46 => 5,  42 => 4,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/privileges/user_overview.twig", "/var/www/html/phpMyAdmin/templates/server/privileges/user_overview.twig");
    }
}
