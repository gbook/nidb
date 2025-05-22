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

/* server/privileges/add_user.twig */
class __TwigTemplate_2ce9dfb8face1967d7498c39e7260a3380b1e06edf251d2c85ebfea8a57daf43 extends \Twig\Template
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
        echo "<h2>
  ";
        // line 2
        echo \PhpMyAdmin\Html\Generator::getIcon("b_usradd");
        echo "
  ";
        // line 3
        echo _gettext("Add user account");
        // line 4
        echo "</h2>

<form name=\"usersForm\" id=\"addUsersForm\" action=\"";
        // line 6
        echo PhpMyAdmin\Url::getFromRoute("/server/privileges");
        echo "\" method=\"post\" autocomplete=\"off\">
  ";
        // line 7
        echo PhpMyAdmin\Url::getHiddenInputs();
        echo "

  ";
        // line 9
        echo ($context["login_information_fields_new"] ?? null);
        echo "

  <fieldset id=\"fieldset_add_user_database\">
    <legend>";
        // line 12
        echo _gettext("Database for user account");
        echo "</legend>

    <input type=\"checkbox\" name=\"createdb-1\" id=\"createdb-1\">
    <label for=\"createdb-1\">";
        // line 15
        echo _gettext("Create database with same name and grant all privileges.");
        echo "</label>
    <br>

    <input type=\"checkbox\" name=\"createdb-2\" id=\"createdb-2\">
    <label for=\"createdb-2\">";
        // line 19
        echo _gettext("Grant all privileges on wildcard name (username\\_%).");
        echo "</label>
    <br>

    ";
        // line 22
        if ( !twig_test_empty(($context["database"] ?? null))) {
            // line 23
            echo "      <input type=\"checkbox\" name=\"createdb-3\" id=\"createdb-3\" checked>
      <label for=\"createdb-3\">";
            // line 24
            echo twig_escape_filter($this->env, twig_sprintf(_gettext("Grant all privileges on database %s."), ($context["database"] ?? null)), "html", null, true);
            echo "</label>
      <input type=\"hidden\" name=\"dbname\" value=\"";
            // line 25
            echo twig_escape_filter($this->env, ($context["database"] ?? null), "html", null, true);
            echo "\">
      <br>
    ";
        }
        // line 28
        echo "  </fieldset>

  ";
        // line 30
        if (($context["is_grant_user"] ?? null)) {
            // line 31
            echo "    ";
            echo ($context["privileges_table"] ?? null);
            echo "
  ";
        }
        // line 33
        echo "
  <fieldset id=\"fieldset_add_user_footer\" class=\"tblFooters\">
    <input type=\"hidden\" name=\"adduser_submit\" value=\"1\">
    <input class=\"btn btn-primary\" type=\"submit\" id=\"adduser_submit\" value=\"";
        // line 36
        echo _gettext("Go");
        echo "\">
  </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "server/privileges/add_user.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  116 => 36,  111 => 33,  105 => 31,  103 => 30,  99 => 28,  93 => 25,  89 => 24,  86 => 23,  84 => 22,  78 => 19,  71 => 15,  65 => 12,  59 => 9,  54 => 7,  50 => 6,  46 => 4,  44 => 3,  40 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/privileges/add_user.twig", "/var/www/html/phpMyAdmin/templates/server/privileges/add_user.twig");
    }
}
