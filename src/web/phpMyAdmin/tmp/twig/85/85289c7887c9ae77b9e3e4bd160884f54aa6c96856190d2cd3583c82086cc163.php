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

/* database/routines/index.twig */
class __TwigTemplate_a9c1e98d4f37b880c401782d67099117484fe71864b202ce88e2207236a2d1a2 extends \Twig\Template
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
    <fieldset id=\"tableFilter\">
      <legend>";
        // line 4
        echo _gettext("Filters");
        echo "</legend>
      <div class=\"formelement\">
        <label for=\"filterText\">";
        // line 6
        echo _gettext("Containing the word:");
        echo "</label>
        <input name=\"filterText\" type=\"text\" id=\"filterText\" value=\"\">
      </div>
    </fieldset>
  </div>
</div>

<form id=\"rteListForm\" class=\"ajax\" action=\"";
        // line 13
        echo PhpMyAdmin\Url::getFromRoute("/database/routines");
        echo "\">
  ";
        // line 14
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "

  <fieldset>
    <legend>
      ";
        // line 18
        echo _gettext("Routines");
        // line 19
        echo "      ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("STORED_ROUTINES");
        echo "
    </legend>

    <div id=\"nothing2display\"";
        // line 22
        echo (( !twig_test_empty(($context["items"] ?? null))) ? (" class=\"hide\"") : (""));
        echo ">
      ";
        // line 23
        echo _gettext("There are no routines to display.");
        // line 24
        echo "    </div>

    <table id=\"routinesTable\" class=\"table table-light table-striped table-hover";
        // line 26
        echo ((twig_test_empty(($context["items"] ?? null))) ? (" hide") : (""));
        echo " data w-auto\">
      <thead class=\"thead-light\">
      <tr>
        <th></th>
        <th>";
        // line 30
        echo _gettext("Name");
        echo "</th>
        <th colspan=\"4\">";
        // line 31
        echo _gettext("Action");
        echo "</th>
        <th>";
        // line 32
        echo _gettext("Type");
        echo "</th>
        <th>";
        // line 33
        echo _gettext("Returns");
        echo "</th>
      </tr>
      </thead>
      <tbody>
      <tr class=\"hide\">";
        // line 37
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(range(0, 7));
        foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
            echo "<td></td>";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        echo "</tr>

      ";
        // line 39
        echo ($context["rows"] ?? null);
        echo "
      </tbody>
    </table>

    ";
        // line 43
        if ( !twig_test_empty(($context["items"] ?? null))) {
            // line 44
            echo "      <div class=\"withSelected\">
        <img class=\"selectallarrow\" src=\"";
            // line 45
            echo twig_escape_filter($this->env, ($context["select_all_arrow_src"] ?? null), "html", null, true);
            echo "\" width=\"38\" height=\"22\" alt=\"";
            echo _gettext("With selected:");
            echo "\">
        <input type=\"checkbox\" id=\"rteListForm_checkall\" class=\"checkall_box\" title=\"";
            // line 46
            echo _gettext("Check all");
            echo "\">
        <label for=\"rteListForm_checkall\">";
            // line 47
            echo _gettext("Check all");
            echo "</label>
        <em class=\"with-selected\">";
            // line 48
            echo _gettext("With selected:");
            echo "</em>

        <button class=\"btn btn-link mult_submit\" type=\"submit\" name=\"submit_mult\" value=\"export\" title=\"";
            // line 50
            echo _gettext("Export");
            echo "\">
          ";
            // line 51
            echo \PhpMyAdmin\Html\Generator::getIcon("b_export", _gettext("Export"));
            echo "
        </button>
        <button class=\"btn btn-link mult_submit\" type=\"submit\" name=\"submit_mult\" value=\"drop\" title=\"";
            // line 53
            echo _gettext("Drop");
            echo "\">
          ";
            // line 54
            echo \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop"));
            echo "
        </button>
      </div>
    ";
        }
        // line 58
        echo "  </fieldset>
</form>

<fieldset class=\"left\">
  <legend>";
        // line 62
        echo _pgettext(        "Create new routine", "New");
        echo "</legend>
  <div class='wrap'>
    ";
        // line 64
        if (($context["has_privilege"] ?? null)) {
            // line 65
            echo "      <a class=\"ajax add_anchor\" href=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/routines", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "add_item" => true]);
            echo "\">
        ";
            // line 66
            echo \PhpMyAdmin\Html\Generator::getIcon("b_routine_add");
            echo "
        ";
            // line 67
            echo _gettext("Add routine");
            // line 68
            echo "      </a>
    ";
        } else {
            // line 70
            echo "      ";
            echo \PhpMyAdmin\Html\Generator::getIcon("bd_routine_add");
            echo "
      ";
            // line 71
            echo _gettext("Add routine");
            // line 72
            echo "    ";
        }
        // line 73
        echo "    ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("CREATE_PROCEDURE");
        echo "
  </div>
</fieldset>
";
    }

    public function getTemplateName()
    {
        return "database/routines/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  214 => 73,  211 => 72,  209 => 71,  204 => 70,  200 => 68,  198 => 67,  194 => 66,  189 => 65,  187 => 64,  182 => 62,  176 => 58,  169 => 54,  165 => 53,  160 => 51,  156 => 50,  151 => 48,  147 => 47,  143 => 46,  137 => 45,  134 => 44,  132 => 43,  125 => 39,  113 => 37,  106 => 33,  102 => 32,  98 => 31,  94 => 30,  87 => 26,  83 => 24,  81 => 23,  77 => 22,  70 => 19,  68 => 18,  61 => 14,  57 => 13,  47 => 6,  42 => 4,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/routines/index.twig", "/var/www/html/phpMyAdmin/templates/database/routines/index.twig");
    }
}
