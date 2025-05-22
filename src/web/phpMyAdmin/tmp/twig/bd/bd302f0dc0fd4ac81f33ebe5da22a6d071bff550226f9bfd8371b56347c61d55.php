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

/* indexes.twig */
class __TwigTemplate_3263976f6e8ab7019bad9494e6f682b14d94e9932ac5c1fb340034e187bd1a9e extends \Twig\Template
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
        echo "<fieldset class=\"index_info\">
  <legend id=\"index_header\">
    ";
        // line 3
        echo _gettext("Indexes");
        // line 4
        echo "    ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("optimizing-database-structure");
        echo "
  </legend>

  ";
        // line 7
        if ( !twig_test_empty(($context["indexes"] ?? null))) {
            // line 8
            echo "    ";
            echo ($context["indexes_duplicates"] ?? null);
            echo "

    <div class=\"table-responsive jsresponsive\">
      <table class=\"table table-light table-striped table-hover table-sm w-auto\" id=\"table_index\">
        <thead class=\"thead-light\">
        <tr>
            <th colspan=\"3\" class=\"print_ignore\">";
            // line 14
            echo _gettext("Action");
            echo "</th>
            <th>";
            // line 15
            echo _gettext("Keyname");
            echo "</th>
            <th>";
            // line 16
            echo _gettext("Type");
            echo "</th>
            <th>";
            // line 17
            echo _gettext("Unique");
            echo "</th>
            <th>";
            // line 18
            echo _gettext("Packed");
            echo "</th>
            <th>";
            // line 19
            echo _gettext("Column");
            echo "</th>
            <th>";
            // line 20
            echo _gettext("Cardinality");
            echo "</th>
            <th>";
            // line 21
            echo _gettext("Collation");
            echo "</th>
            <th>";
            // line 22
            echo _gettext("Null");
            echo "</th>
            <th>";
            // line 23
            echo _gettext("Comment");
            echo "</th>
          </tr>
        </thead>

        ";
            // line 27
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["indexes"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["index"]) {
                // line 28
                echo "          <tbody class=\"row_span\">
            ";
                // line 29
                $context["columns_count"] = twig_get_attribute($this->env, $this->source, $context["index"], "getColumnCount", [], "method", false, false, false, 29);
                // line 30
                echo "            <tr class=\"noclick\">
              <td rowspan=\"";
                // line 31
                echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                echo "\" class=\"edit_index print_ignore ajax\">
                <a class=\"ajax\" href=\"";
                // line 32
                echo PhpMyAdmin\Url::getFromRoute("/table/indexes");
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(twig_array_merge(($context["url_params"] ?? null), ["index" => twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 32)]), "", false);
                echo "\">
                  ";
                // line 33
                echo \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Edit"));
                echo "
                </a>
              </td>
              <td rowspan=\"";
                // line 36
                echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                echo "\" class=\"rename_index print_ignore ajax\" >
                <a class=\"ajax\" href=\"";
                // line 37
                echo PhpMyAdmin\Url::getFromRoute("/table/indexes/rename");
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(twig_array_merge(($context["url_params"] ?? null), ["index" => twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 37)]), "", false);
                echo "\">
                  ";
                // line 38
                echo \PhpMyAdmin\Html\Generator::getIcon("b_rename", _gettext("Rename"));
                echo "
                </a>
              </td>
              <td rowspan=\"";
                // line 41
                echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                echo "\" class=\"print_ignore\">
                ";
                // line 42
                if ((twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 42) == "PRIMARY")) {
                    // line 43
                    echo "                  ";
                    $context["index_params"] = ["sql_query" => (("ALTER TABLE " . PhpMyAdmin\Util::backquote(                    // line 44
($context["table"] ?? null))) . " DROP PRIMARY KEY;"), "message_to_show" => _gettext("The primary key has been dropped.")];
                    // line 47
                    echo "                ";
                } else {
                    // line 48
                    echo "                  ";
                    $context["index_params"] = ["sql_query" => (((("ALTER TABLE " . PhpMyAdmin\Util::backquote(                    // line 49
($context["table"] ?? null))) . " DROP INDEX ") . PhpMyAdmin\Util::backquote(twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 49))) . ";"), "message_to_show" => twig_sprintf(_gettext("Index %s has been dropped."), twig_get_attribute($this->env, $this->source,                     // line 50
$context["index"], "getName", [], "method", false, false, false, 50))];
                    // line 52
                    echo "                ";
                }
                // line 53
                echo "
                <input type=\"hidden\" class=\"drop_primary_key_index_msg\" value=\"";
                // line 54
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["index_params"] ?? null), "sql_query", [], "any", false, false, false, 54), "html", null, true);
                echo "\">
                ";
                // line 55
                echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), twig_array_merge(                // line 57
($context["url_params"] ?? null), ($context["index_params"] ?? null)), \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop")), ["class" => "drop_primary_key_index_anchor ajax"]);
                // line 60
                echo "
              </td>
              <th rowspan=\"";
                // line 62
                echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 62), "html", null, true);
                echo "</th>
              <td rowspan=\"";
                // line 63
                echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["index"], "getType", [], "method", true, true, false, 63)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, $context["index"], "getType", [], "method", false, false, false, 63), twig_get_attribute($this->env, $this->source, $context["index"], "getChoice", [], "method", false, false, false, 63))) : (twig_get_attribute($this->env, $this->source, $context["index"], "getChoice", [], "method", false, false, false, 63))), "html", null, true);
                echo "</td>
              <td rowspan=\"";
                // line 64
                echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["index"], "isUnique", [], "method", false, false, false, 64)) ? (_gettext("Yes")) : (_gettext("No"))), "html", null, true);
                echo "</td>
              <td rowspan=\"";
                // line 65
                echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                echo "\">";
                echo twig_get_attribute($this->env, $this->source, $context["index"], "isPacked", [], "method", false, false, false, 65);
                echo "</td>

              ";
                // line 67
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["index"], "getColumns", [], "method", false, false, false, 67));
                foreach ($context['_seq'] as $context["_key"] => $context["column"]) {
                    // line 68
                    echo "                ";
                    if ((twig_get_attribute($this->env, $this->source, $context["column"], "getSeqInIndex", [], "method", false, false, false, 68) > 1)) {
                        // line 69
                        echo "                  <tr class=\"noclick\">
                ";
                    }
                    // line 71
                    echo "                <td>
                  ";
                    // line 72
                    if (twig_get_attribute($this->env, $this->source, $context["column"], "hasExpression", [], "method", false, false, false, 72)) {
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getExpression", [], "method", false, false, false, 72), "html", null, true);
                    } else {
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getName", [], "method", false, false, false, 72), "html", null, true);
                    }
                    // line 73
                    echo "                  ";
                    if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["column"], "getSubPart", [], "method", false, false, false, 73))) {
                        // line 74
                        echo "                    (";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getSubPart", [], "method", false, false, false, 74), "html", null, true);
                        echo ")
                  ";
                    }
                    // line 76
                    echo "                </td>
                <td>";
                    // line 77
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getCardinality", [], "method", false, false, false, 77), "html", null, true);
                    echo "</td>
                <td>";
                    // line 78
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getCollation", [], "method", false, false, false, 78), "html", null, true);
                    echo "</td>
                <td>";
                    // line 79
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getNull", [0 => true], "method", false, false, false, 79), "html", null, true);
                    echo "</td>

                ";
                    // line 81
                    if ((twig_get_attribute($this->env, $this->source, $context["column"], "getSeqInIndex", [], "method", false, false, false, 81) == 1)) {
                        // line 82
                        echo "                  <td rowspan=\"";
                        echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                        echo "\">";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["index"], "getComments", [], "method", false, false, false, 82), "html", null, true);
                        echo "</td>
                ";
                    }
                    // line 84
                    echo "            </tr>
              ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['column'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 86
                echo "          </tbody>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['index'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 88
            echo "      </table>
    </div>
  ";
        } else {
            // line 91
            echo "    <div class=\"no_indexes_defined\">";
            echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [_gettext("No index defined!")]);
            echo "</div>
  ";
        }
        // line 93
        echo "</fieldset>
";
    }

    public function getTemplateName()
    {
        return "indexes.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  289 => 93,  283 => 91,  278 => 88,  271 => 86,  264 => 84,  256 => 82,  254 => 81,  249 => 79,  245 => 78,  241 => 77,  238 => 76,  232 => 74,  229 => 73,  223 => 72,  220 => 71,  216 => 69,  213 => 68,  209 => 67,  202 => 65,  196 => 64,  190 => 63,  184 => 62,  180 => 60,  178 => 57,  177 => 55,  173 => 54,  170 => 53,  167 => 52,  165 => 50,  164 => 49,  162 => 48,  159 => 47,  157 => 44,  155 => 43,  153 => 42,  149 => 41,  143 => 38,  137 => 37,  133 => 36,  127 => 33,  121 => 32,  117 => 31,  114 => 30,  112 => 29,  109 => 28,  105 => 27,  98 => 23,  94 => 22,  90 => 21,  86 => 20,  82 => 19,  78 => 18,  74 => 17,  70 => 16,  66 => 15,  62 => 14,  52 => 8,  50 => 7,  43 => 4,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "indexes.twig", "/var/www/html/phpMyAdmin/templates/indexes.twig");
    }
}
