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

/* database/operations/index.twig */
class __TwigTemplate_e2f4f80cdbd63c9a24de78677723103a0a2ccddcd69a7491c74f3ea2431136b4 extends \Twig\Template
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
        echo "<div class=\"container-fluid\">

  ";
        // line 3
        echo ($context["message"] ?? null);
        echo "

  ";
        // line 5
        if (($context["has_comment"] ?? null)) {
            // line 6
            echo "    <form method=\"post\" action=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/operations");
            echo "\" id=\"formDatabaseComment\">
      ";
            // line 7
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
            echo "
      <div class=\"card mb-2\">
        <div class=\"card-header\">";
            // line 9
            echo \PhpMyAdmin\Html\Generator::getIcon("b_comment", _gettext("Database comment"), true);
            echo "</div>
        <div class=\"card-body\">
          <div class=\"form-row\">
            <div class=\"col-auto\">
              <label class=\"sr-only\" for=\"databaseCommentInput\">";
            // line 13
            echo _gettext("Database comment");
            echo "</label>
              <input class=\"form-control textfield\" id=\"databaseCommentInput\" type=\"text\" name=\"comment\" value=\"";
            // line 14
            echo twig_escape_filter($this->env, ($context["db_comment"] ?? null), "html", null, true);
            echo "\">
            </div>
          </div>
        </div>
        <div class=\"card-footer text-right\">
          <input class=\"btn btn-primary\" type=\"submit\" value=\"";
            // line 19
            echo _gettext("Go");
            echo "\">
        </div>
      </div>
    </form>
  ";
        }
        // line 24
        echo "
  <form id=\"create_table_form_minimal\" method=\"post\" action=\"";
        // line 25
        echo PhpMyAdmin\Url::getFromRoute("/table/create");
        echo "\" class=\"lock-page\">
    ";
        // line 26
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
        echo "

    <div class=\"card mb-2\">
      <div class=\"card-header\">";
        // line 29
        echo \PhpMyAdmin\Html\Generator::getIcon("b_table_add", _gettext("Create table"), true);
        echo "</div>
      <div class=\"card-body\">
        <div class=\"form-row\">
          <div class=\"col-auto\">
            <label for=\"exampleInputEmail1\">";
        // line 33
        echo _gettext("Name");
        echo "</label>
            <input class=\"form-control\" type=\"text\" id=\"exampleInputEmail1\" name=\"table\" required>
          </div>
          <div class=\"col-auto\">
            <label for=\"exampleInputEmail1\">";
        // line 37
        echo _gettext("Number of columns");
        echo "</label>
            <input class=\"form-control\" type=\"number\" id=\"exampleInputEmail1\" name=\"num_fields\" min=\"1\" value=\"4\" required>
          </div>
        </div>
      </div>

      <div class=\"card-footer text-right\">
        <input class=\"btn btn-primary\" type=\"submit\" value=\"";
        // line 44
        echo _gettext("Go");
        echo "\">
      </div>
    </div>
  </form>

  ";
        // line 49
        if ((($context["db"] ?? null) != "mysql")) {
            // line 50
            echo "    <form id=\"rename_db_form\" class=\"ajax\" method=\"post\" action=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/operations");
            echo "\">
      ";
            // line 51
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
            echo "
      <input type=\"hidden\" name=\"what\" value=\"data\">
      <input type=\"hidden\" name=\"db_rename\" value=\"true\">

      ";
            // line 55
            if ( !twig_test_empty(($context["db_collation"] ?? null))) {
                // line 56
                echo "        <input type=\"hidden\" name=\"db_collation\" value=\"";
                echo twig_escape_filter($this->env, ($context["db_collation"] ?? null), "html", null, true);
                echo "\">
      ";
            }
            // line 58
            echo "
      <div class=\"card mb-2\">
        <div class=\"card-header\">";
            // line 60
            echo \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Rename database to"), true);
            echo "</div>
        <div class=\"card-body\">
          <div class=\"form-group form-row\">
            <div class=\"col-auto\">
              <label class=\"sr-only\" for=\"new_db_name\">";
            // line 64
            echo _gettext("New database name");
            echo "</label>
              <input class=\"form-control textfield\" id=\"new_db_name\" type=\"text\" name=\"newname\" maxlength=\"64\" required>
            </div>
          </div>

          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"checkbox\" name=\"adjust_privileges\" value=\"1\" id=\"checkbox_adjust_privileges\"";
            // line 71
            if (($context["has_adjust_privileges"] ?? null)) {
                echo " checked";
            } else {
                echo " title=\"";
                // line 72
                echo _gettext("You don't have sufficient privileges to perform this operation; Please refer to the documentation for more details.");
                echo "\" disabled";
            }
            echo ">
            <label class=\"form-check-label\" for=\"checkbox_adjust_privileges\">
              ";
            // line 74
            echo _gettext("Adjust privileges");
            // line 75
            echo "              ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-39");
            echo "
            </label>
          </div>
        </div>

        <div class=\"card-footer text-right\">
          <input class=\"btn btn-primary\" type=\"submit\" value=\"";
            // line 81
            echo _gettext("Go");
            echo "\">
        </div>
      </div>
    </form>
  ";
        }
        // line 86
        echo "
  ";
        // line 87
        if (($context["is_drop_database_allowed"] ?? null)) {
            // line 88
            echo "    <div class=\"card mb-2\">
      <div class=\"card-header\">";
            // line 89
            echo \PhpMyAdmin\Html\Generator::getIcon("b_deltbl", _gettext("Remove database"), true);
            echo "</div>
      <div class=\"card-body\">
        <div class=\"card-text\">
          ";
            // line 92
            echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), ["sql_query" => ("DROP DATABASE " . PhpMyAdmin\Util::backquote(            // line 95
($context["db"] ?? null))), "back" => PhpMyAdmin\Url::getFromRoute("/database/operations"), "goto" => PhpMyAdmin\Url::getFromRoute("/"), "reload" => true, "purge" => true, "message_to_show" => twig_escape_filter($this->env, twig_sprintf(_gettext("Database %s has been dropped."), PhpMyAdmin\Util::backquote(            // line 100
($context["db"] ?? null)))), "db" => null], _gettext("Drop the database (DROP)"), ["id" => "drop_db_anchor", "class" => "ajax text-danger"]);
            // line 108
            echo "
          ";
            // line 109
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("DROP_DATABASE");
            echo "
        </div>
      </div>
    </div>
  ";
        }
        // line 114
        echo "
  <form id=\"copy_db_form\" class=\"ajax\" method=\"post\" action=\"";
        // line 115
        echo PhpMyAdmin\Url::getFromRoute("/database/operations");
        echo "\">
    ";
        // line 116
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
        echo "
    <input type=\"hidden\" name=\"db_copy\" value=\"true\">

    ";
        // line 119
        if ( !twig_test_empty(($context["db_collation"] ?? null))) {
            // line 120
            echo "      <input type=\"hidden\" name=\"db_collation\" value=\"";
            echo twig_escape_filter($this->env, ($context["db_collation"] ?? null), "html", null, true);
            echo "\">
    ";
        }
        // line 122
        echo "
    <div class=\"card mb-2\">
      <div class=\"card-header\">";
        // line 124
        echo \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Copy database to"), true);
        echo "</div>
      <div class=\"card-body\">
        <div class=\"form-group form-row\">
          <div class=\"col-auto\">
            <label class=\"sr-only\" for=\"renameDbNameInput\">";
        // line 128
        echo _gettext("Database name");
        echo "</label>
            <input class=\"form-control textfield\" id=\"renameDbNameInput\" type=\"text\" maxlength=\"64\" name=\"newname\" required>
          </div>
        </div>

        <div class=\"form-group\">
          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio1\" value=\"structure\">
            <label class=\"form-check-label\" for=\"whatRadio1\">
              ";
        // line 137
        echo _gettext("Structure only");
        // line 138
        echo "            </label>
          </div>
          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio2\" value=\"data\" checked>
            <label class=\"form-check-label\" for=\"whatRadio2\">
              ";
        // line 143
        echo _gettext("Structure and data");
        // line 144
        echo "            </label>
          </div>
          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio3\" value=\"dataonly\">
            <label class=\"form-check-label\" for=\"whatRadio3\">
              ";
        // line 149
        echo _gettext("Data only");
        // line 150
        echo "            </label>
          </div>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"create_database_before_copying\" value=\"1\" id=\"checkbox_create_database_before_copying\" checked>
          <label class=\"form-check-label\" for=\"checkbox_create_database_before_copying\">";
        // line 156
        echo _gettext("CREATE DATABASE before copying");
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"drop_if_exists\" value=\"true\" id=\"checkbox_drop\">
          <label class=\"form-check-label\" for=\"checkbox_drop\">";
        // line 161
        echo twig_escape_filter($this->env, twig_sprintf(_gettext("Add %s"), "DROP TABLE / DROP VIEW"), "html", null, true);
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"sql_auto_increment\" value=\"1\" id=\"checkbox_auto_increment\" checked>
          <label class=\"form-check-label\" for=\"checkbox_auto_increment\">";
        // line 166
        echo _gettext("Add AUTO_INCREMENT value");
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"add_constraints\" value=\"1\" id=\"checkbox_constraints\" checked>
          <label class=\"form-check-label\" for=\"checkbox_constraints\">";
        // line 171
        echo _gettext("Add constraints");
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"adjust_privileges\" value=\"1\" id=\"checkbox_privileges\"";
        // line 176
        if (($context["has_adjust_privileges"] ?? null)) {
            echo " checked";
        } else {
            echo " title=\"";
            // line 177
            echo _gettext("You don't have sufficient privileges to perform this operation; Please refer to the documentation for more details.");
            echo "\" disabled";
        }
        echo ">
          <label class=\"form-check-label\" for=\"checkbox_privileges\">
            ";
        // line 179
        echo _gettext("Adjust privileges");
        // line 180
        echo "            ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-39");
        echo "
          </label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"switch_to_new\" value=\"true\" id=\"checkbox_switch\"";
        // line 185
        echo ((($context["switch_to_new"] ?? null)) ? (" checked") : (""));
        echo ">
          <label class=\"form-check-label\" for=\"checkbox_switch\">";
        // line 186
        echo _gettext("Switch to copied database");
        echo "</label>
        </div>
      </div>

      <div class=\"card-footer text-right\">
        <input class=\"btn btn-primary\" type=\"submit\" name=\"submit_copy\" value=\"";
        // line 191
        echo _gettext("Go");
        echo "\">
      </div>
    </div>
  </form>

  <form id=\"change_db_charset_form\" class=\"ajax\" method=\"post\" action=\"";
        // line 196
        echo PhpMyAdmin\Url::getFromRoute("/database/operations/collation");
        echo "\">
    ";
        // line 197
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
        echo "

    <div class=\"card mb-2\">
      <div class=\"card-header\">";
        // line 200
        echo \PhpMyAdmin\Html\Generator::getIcon("s_asci", _gettext("Collation"), true);
        echo "</div>
      <div class=\"card-body\">
        <div class=\"form-group form-row\">
          <div class=\"col-auto\">
            <label class=\"sr-only\" for=\"select_db_collation\">";
        // line 204
        echo _gettext("Collation");
        echo "</label>
            <select class=\"form-control\" lang=\"en\" dir=\"ltr\" name=\"db_collation\" id=\"select_db_collation\">
              <option value=\"\"></option>
              ";
        // line 207
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["charsets"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["charset"]) {
            // line 208
            echo "                <optgroup label=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "getName", [], "method", false, false, false, 208), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "getDescription", [], "method", false, false, false, 208), "html", null, true);
            echo "\">
                  ";
            // line 209
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((($__internal_compile_0 = ($context["collations"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[twig_get_attribute($this->env, $this->source, $context["charset"], "getName", [], "method", false, false, false, 209)] ?? null) : null));
            foreach ($context['_seq'] as $context["_key"] => $context["collation"]) {
                // line 210
                echo "                    <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 210), "html", null, true);
                echo "\" title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getDescription", [], "method", false, false, false, 210), "html", null, true);
                echo "\"";
                echo (((($context["db_collation"] ?? null) == twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 210))) ? (" selected") : (""));
                echo ">
                      ";
                // line 211
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 211), "html", null, true);
                echo "
                    </option>
                  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['collation'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 214
            echo "                </optgroup>
              ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['charset'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 216
        echo "            </select>
          </div>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"change_all_tables_collations\" id=\"checkbox_change_all_tables_collations\">
          <label class=\"form-check-label\" for=\"checkbox_change_all_tables_collations\">";
        // line 222
        echo _gettext("Change all tables collations");
        echo "</label>
        </div>
        <div class=\"form-check\" id=\"span_change_all_tables_columns_collations\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"change_all_tables_columns_collations\" id=\"checkbox_change_all_tables_columns_collations\">
          <label class=\"form-check-label\" for=\"checkbox_change_all_tables_columns_collations\">";
        // line 226
        echo _gettext("Change all tables columns collations");
        echo "</label>
        </div>
      </div>

      <div class=\"card-footer text-right\">
        <input class=\"btn btn-primary\" type=\"submit\" value=\"";
        // line 231
        echo _gettext("Go");
        echo "\">
      </div>
    </div>
  </form>

</div>
";
    }

    public function getTemplateName()
    {
        return "database/operations/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  465 => 231,  457 => 226,  450 => 222,  442 => 216,  435 => 214,  426 => 211,  417 => 210,  413 => 209,  406 => 208,  402 => 207,  396 => 204,  389 => 200,  383 => 197,  379 => 196,  371 => 191,  363 => 186,  359 => 185,  350 => 180,  348 => 179,  341 => 177,  336 => 176,  329 => 171,  321 => 166,  313 => 161,  305 => 156,  297 => 150,  295 => 149,  288 => 144,  286 => 143,  279 => 138,  277 => 137,  265 => 128,  258 => 124,  254 => 122,  248 => 120,  246 => 119,  240 => 116,  236 => 115,  233 => 114,  225 => 109,  222 => 108,  220 => 100,  219 => 95,  218 => 92,  212 => 89,  209 => 88,  207 => 87,  204 => 86,  196 => 81,  186 => 75,  184 => 74,  177 => 72,  172 => 71,  163 => 64,  156 => 60,  152 => 58,  146 => 56,  144 => 55,  137 => 51,  132 => 50,  130 => 49,  122 => 44,  112 => 37,  105 => 33,  98 => 29,  92 => 26,  88 => 25,  85 => 24,  77 => 19,  69 => 14,  65 => 13,  58 => 9,  53 => 7,  48 => 6,  46 => 5,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/operations/index.twig", "/var/www/html/phpMyAdmin/templates/database/operations/index.twig");
    }
}
