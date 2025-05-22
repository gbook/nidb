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

/* database/triggers/list.twig */
class __TwigTemplate_3ee85ad7dd1f0448740b27d39631b5f70e7a34e2dbfdacfbfdc3d371b515966c extends \Twig\Template
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
        echo "<form id=\"rteListForm\" class=\"ajax\" action=\"";
        echo PhpMyAdmin\Url::getFromRoute((( !twig_test_empty(($context["table"] ?? null))) ? ("/table/triggers") : ("/database/triggers")));
        echo "\">
  ";
        // line 2
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "

  <fieldset>
    <legend>
      ";
        // line 6
        echo _gettext("Triggers");
        // line 7
        echo "      ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("TRIGGERS");
        echo "
    </legend>

    <div id=\"nothing2display\"";
        // line 10
        echo (( !twig_test_empty(($context["items"] ?? null))) ? (" class=\"hide\"") : (""));
        echo ">
      ";
        // line 11
        echo _gettext("There are no triggers to display.");
        // line 12
        echo "    </div>

    <table id=\"triggersTable\" class=\"table table-light table-striped table-hover";
        // line 14
        echo ((twig_test_empty(($context["items"] ?? null))) ? (" hide") : (""));
        echo " w-auto data\">
      <thead class=\"thead-light\">
      <tr>
        <th></th>
        <th>";
        // line 18
        echo _gettext("Name");
        echo "</th>
        ";
        // line 19
        if (twig_test_empty(($context["table"] ?? null))) {
            // line 20
            echo "          <th>";
            echo _gettext("Table");
            echo "</th>
        ";
        }
        // line 22
        echo "        <th colspan=\"3\">";
        echo _gettext("Action");
        echo "</th>
        <th>";
        // line 23
        echo _gettext("Time");
        echo "</th>
        <th>";
        // line 24
        echo _gettext("Event");
        echo "</th>
      </tr>
      </thead>
      <tbody>
      <tr class=\"hide\">";
        // line 28
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(range(0, ((twig_test_empty(($context["table"] ?? null))) ? (7) : (6))));
        foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
            echo "<td></td>";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        echo "</tr>

      ";
        // line 30
        echo ($context["rows"] ?? null);
        echo "
      </tbody>
    </table>

    ";
        // line 34
        if ( !twig_test_empty(($context["items"] ?? null))) {
            // line 35
            echo "      <div class=\"withSelected\">
        <img class=\"selectallarrow\" src=\"";
            // line 36
            echo twig_escape_filter($this->env, ($context["select_all_arrow_src"] ?? null), "html", null, true);
            echo "\" width=\"38\" height=\"22\" alt=\"";
            echo _gettext("With selected:");
            echo "\">
        <input type=\"checkbox\" id=\"rteListForm_checkall\" class=\"checkall_box\" title=\"";
            // line 37
            echo _gettext("Check all");
            echo "\">
        <label for=\"rteListForm_checkall\">";
            // line 38
            echo _gettext("Check all");
            echo "</label>
        <em class=\"with-selected\">";
            // line 39
            echo _gettext("With selected:");
            echo "</em>

        <button class=\"btn btn-link mult_submit\" type=\"submit\" name=\"submit_mult\" value=\"export\" title=\"";
            // line 41
            echo _gettext("Export");
            echo "\">
          ";
            // line 42
            echo \PhpMyAdmin\Html\Generator::getIcon("b_export", _gettext("Export"));
            echo "
        </button>
        <button class=\"btn btn-link mult_submit\" type=\"submit\" name=\"submit_mult\" value=\"drop\" title=\"";
            // line 44
            echo _gettext("Drop");
            echo "\">
          ";
            // line 45
            echo \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop"));
            echo "
        </button>
      </div>
    ";
        }
        // line 49
        echo "  </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "database/triggers/list.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  166 => 49,  159 => 45,  155 => 44,  150 => 42,  146 => 41,  141 => 39,  137 => 38,  133 => 37,  127 => 36,  124 => 35,  122 => 34,  115 => 30,  103 => 28,  96 => 24,  92 => 23,  87 => 22,  81 => 20,  79 => 19,  75 => 18,  68 => 14,  64 => 12,  62 => 11,  58 => 10,  51 => 7,  49 => 6,  42 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/triggers/list.twig", "/var/www/html/phpMyAdmin/templates/database/triggers/list.twig");
    }
}
