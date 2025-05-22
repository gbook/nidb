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

/* server/databases/index.twig */
class __TwigTemplate_7f3f487ea6ff1bc6d8f704546cbc1bc699a9e9e218e241c4e701e87e39d7dfee extends \Twig\Template
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
<div class=\"row\">
  <h2>
    ";
        // line 4
        echo \PhpMyAdmin\Html\Generator::getImage("s_db");
        echo "
    ";
        // line 5
        if (($context["has_statistics"] ?? null)) {
            // line 6
            echo "      ";
            echo _gettext("Databases statistics");
            // line 7
            echo "    ";
        } else {
            // line 8
            echo "      ";
            echo _gettext("Databases");
            // line 9
            echo "    ";
        }
        // line 10
        echo "  </h2>
</div>

";
        // line 13
        if (($context["is_create_database_shown"] ?? null)) {
            // line 14
            echo "<div class=\"row\">
  <ul>
    <li id=\"li_create_database\" class=\"no_bullets\">
      ";
            // line 17
            if (($context["has_create_database_privileges"] ?? null)) {
                // line 18
                echo "        <form method=\"post\" action=\"";
                echo PhpMyAdmin\Url::getFromRoute("/server/databases/create");
                echo "\" id=\"create_database_form\" class=\"ajax\">
          <p>
            <strong>
              <label for=\"text_create_db\">
                ";
                // line 22
                echo \PhpMyAdmin\Html\Generator::getImage("b_newdb");
                echo "
                ";
                // line 23
                echo _gettext("Create database");
                // line 24
                echo "              </label>
              ";
                // line 25
                echo \PhpMyAdmin\Html\MySQLDocumentation::show("CREATE_DATABASE");
                echo "
            </strong>
          </p>

          ";
                // line 29
                echo PhpMyAdmin\Url::getHiddenInputs("", "", 5);
                echo "
          <input type=\"hidden\" name=\"reload\" value=\"1\">
          ";
                // line 31
                if (($context["has_statistics"] ?? null)) {
                    // line 32
                    echo "            <input type=\"hidden\" name=\"statistics\" value=\"1\">
          ";
                }
                // line 34
                echo "
          <input type=\"text\" name=\"new_db\" maxlength=\"64\" class=\"textfield\" value=\"";
                // line 36
                echo twig_escape_filter($this->env, ($context["database_to_create"] ?? null), "html", null, true);
                echo "\" id=\"text_create_db\" placeholder=\"";
                // line 37
                echo _gettext("Database name");
                echo "\" required>

          ";
                // line 39
                if ( !twig_test_empty(($context["charsets"] ?? null))) {
                    // line 40
                    echo "            <select lang=\"en\" dir=\"ltr\" name=\"db_collation\">
              <option value=\"\">";
                    // line 41
                    echo _gettext("Collation");
                    echo "</option>
              <option value=\"\"></option>
              ";
                    // line 43
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(($context["charsets"] ?? null));
                    foreach ($context['_seq'] as $context["_key"] => $context["charset"]) {
                        // line 44
                        echo "                <optgroup label=\"";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "name", [], "any", false, false, false, 44), "html", null, true);
                        echo "\" title=\"";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "description", [], "any", false, false, false, 44), "html", null, true);
                        echo "\">
                  ";
                        // line 45
                        $context['_parent'] = $context;
                        $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["charset"], "collations", [], "any", false, false, false, 45));
                        foreach ($context['_seq'] as $context["_key"] => $context["collation"]) {
                            // line 46
                            echo "                    <option value=\"";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "name", [], "any", false, false, false, 46), "html", null, true);
                            echo "\" title=\"";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "description", [], "any", false, false, false, 46), "html", null, true);
                            echo "\"";
                            echo ((twig_get_attribute($this->env, $this->source, $context["collation"], "is_selected", [], "any", false, false, false, 46)) ? (" selected") : (""));
                            echo ">";
                            // line 47
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "name", [], "any", false, false, false, 47), "html", null, true);
                            // line 48
                            echo "</option>
                  ";
                        }
                        $_parent = $context['_parent'];
                        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['collation'], $context['_parent'], $context['loop']);
                        $context = array_intersect_key($context, $_parent) + $_parent;
                        // line 50
                        echo "                </optgroup>
              ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['charset'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 52
                    echo "            </select>
          ";
                }
                // line 54
                echo "
          <input id=\"buttonGo\" class=\"btn btn-primary\" type=\"submit\" value=\"";
                // line 55
                echo _gettext("Create");
                echo "\">
        </form>
      ";
            } else {
                // line 58
                echo "        <p>
          <strong>
            ";
                // line 60
                echo \PhpMyAdmin\Html\Generator::getImage("b_newdb");
                echo "
            ";
                // line 61
                echo _gettext("Create database");
                // line 62
                echo "            ";
                echo \PhpMyAdmin\Html\MySQLDocumentation::show("CREATE_DATABASE");
                echo "
          </strong>
        </p>

        <span class=\"noPrivileges\">
          ";
                // line 67
                echo \PhpMyAdmin\Html\Generator::getImage("s_error", "", ["hspace" => 2, "border" => 0, "align" => "middle"]);
                // line 71
                echo "
          ";
                // line 72
                echo _gettext("No privileges to create databases");
                // line 73
                echo "        </span>
      ";
            }
            // line 75
            echo "    </li>
  </ul>
</div>
";
        }
        // line 79
        echo "
";
        // line 80
        if ((($context["database_count"] ?? null) > 0)) {
            // line 81
            echo "  ";
            $this->loadTemplate("filter.twig", "server/databases/index.twig", 81)->display(twig_to_array(["filter_value" => ""]));
            // line 82
            echo "
  <div id=\"tableslistcontainer row\">
  <div class=\"container-fluid\">
    ";
            // line 85
            echo \PhpMyAdmin\Html\Generator::getListNavigator(            // line 86
($context["database_count"] ?? null),             // line 87
($context["pos"] ?? null),             // line 88
($context["url_params"] ?? null), PhpMyAdmin\Url::getFromRoute("/server/databases"), "frame_content",             // line 91
($context["max_db_list"] ?? null));
            // line 92
            echo "

    <form class=\"ajax\" action=\"";
            // line 94
            echo PhpMyAdmin\Url::getFromRoute("/server/databases");
            echo "\" method=\"post\" name=\"dbStatsForm\" id=\"dbStatsForm\">
      ";
            // line 95
            echo PhpMyAdmin\Url::getHiddenInputs(($context["url_params"] ?? null));
            echo "
      <div class=\"table-responsive row\">
        <table class=\"table table-striped table-hover w-auto\">
          <thead class=\"thead-light\">
            <tr>
              ";
            // line 100
            if (($context["is_drop_allowed"] ?? null)) {
                // line 101
                echo "                <th></th>
              ";
            }
            // line 103
            echo "              <th>
                <a href=\"";
            // line 104
            echo PhpMyAdmin\Url::getFromRoute("/server/databases", twig_array_merge(($context["url_params"] ?? null), ["sort_by" => "SCHEMA_NAME", "sort_order" => ((((twig_get_attribute($this->env, $this->source,             // line 106
($context["url_params"] ?? null), "sort_by", [], "any", false, false, false, 106) == "SCHEMA_NAME") && (twig_get_attribute($this->env, $this->source,             // line 107
($context["url_params"] ?? null), "sort_order", [], "any", false, false, false, 107) == "asc"))) ? ("desc") : ("asc"))]));
            // line 108
            echo "\">
                  ";
            // line 109
            echo _gettext("Database");
            // line 110
            echo "                  ";
            if ((twig_get_attribute($this->env, $this->source, ($context["url_params"] ?? null), "sort_by", [], "any", false, false, false, 110) == "SCHEMA_NAME")) {
                // line 111
                echo "                    ";
                if ((twig_get_attribute($this->env, $this->source, ($context["url_params"] ?? null), "sort_order", [], "any", false, false, false, 111) == "asc")) {
                    // line 112
                    echo "                      ";
                    echo \PhpMyAdmin\Html\Generator::getImage("s_asc", _gettext("Ascending"));
                    echo "
                    ";
                } else {
                    // line 114
                    echo "                      ";
                    echo \PhpMyAdmin\Html\Generator::getImage("s_desc", _gettext("Descending"));
                    echo "
                    ";
                }
                // line 116
                echo "                  ";
            }
            // line 117
            echo "                </a>
              </th>

              <th>
                <a href=\"";
            // line 121
            echo PhpMyAdmin\Url::getFromRoute("/server/databases", twig_array_merge(($context["url_params"] ?? null), ["sort_by" => "DEFAULT_COLLATION_NAME", "sort_order" => ((((twig_get_attribute($this->env, $this->source,             // line 123
($context["url_params"] ?? null), "sort_by", [], "any", false, false, false, 123) == "DEFAULT_COLLATION_NAME") && (twig_get_attribute($this->env, $this->source,             // line 124
($context["url_params"] ?? null), "sort_order", [], "any", false, false, false, 124) == "asc"))) ? ("desc") : ("asc"))]));
            // line 125
            echo "\">
                  ";
            // line 126
            echo _gettext("Collation");
            // line 127
            echo "                  ";
            if ((twig_get_attribute($this->env, $this->source, ($context["url_params"] ?? null), "sort_by", [], "any", false, false, false, 127) == "DEFAULT_COLLATION_NAME")) {
                // line 128
                echo "                    ";
                if ((twig_get_attribute($this->env, $this->source, ($context["url_params"] ?? null), "sort_order", [], "any", false, false, false, 128) == "asc")) {
                    // line 129
                    echo "                      ";
                    echo \PhpMyAdmin\Html\Generator::getImage("s_asc", _gettext("Ascending"));
                    echo "
                    ";
                } else {
                    // line 131
                    echo "                      ";
                    echo \PhpMyAdmin\Html\Generator::getImage("s_desc", _gettext("Descending"));
                    echo "
                    ";
                }
                // line 133
                echo "                  ";
            }
            // line 134
            echo "                </a>
              </th>

              ";
            // line 137
            if (($context["has_statistics"] ?? null)) {
                // line 138
                echo "                ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(($context["header_statistics"] ?? null));
                foreach ($context['_seq'] as $context["name"] => $context["statistic"]) {
                    // line 139
                    echo "                  <th";
                    echo (((twig_get_attribute($this->env, $this->source, $context["statistic"], "format", [], "any", false, false, false, 139) == "byte")) ? (" colspan=\"2\"") : (""));
                    echo ">
                    <a href=\"";
                    // line 140
                    echo PhpMyAdmin\Url::getFromRoute("/server/databases", twig_array_merge(($context["url_params"] ?? null), ["sort_by" =>                     // line 141
$context["name"], "sort_order" => ((((twig_get_attribute($this->env, $this->source,                     // line 142
($context["url_params"] ?? null), "sort_by", [], "any", false, false, false, 142) == $context["name"]) && (twig_get_attribute($this->env, $this->source,                     // line 143
($context["url_params"] ?? null), "sort_order", [], "any", false, false, false, 143) == "asc"))) ? ("desc") : ("asc"))]));
                    // line 144
                    echo "\">
                      ";
                    // line 145
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "title", [], "any", false, false, false, 145), "html", null, true);
                    echo "
                      ";
                    // line 146
                    if ((twig_get_attribute($this->env, $this->source, ($context["url_params"] ?? null), "sort_by", [], "any", false, false, false, 146) == $context["name"])) {
                        // line 147
                        echo "                        ";
                        if ((twig_get_attribute($this->env, $this->source, ($context["url_params"] ?? null), "sort_order", [], "any", false, false, false, 147) == "asc")) {
                            // line 148
                            echo "                          ";
                            echo \PhpMyAdmin\Html\Generator::getImage("s_asc", _gettext("Ascending"));
                            echo "
                        ";
                        } else {
                            // line 150
                            echo "                          ";
                            echo \PhpMyAdmin\Html\Generator::getImage("s_desc", _gettext("Descending"));
                            echo "
                        ";
                        }
                        // line 152
                        echo "                      ";
                    }
                    // line 153
                    echo "                    </a>
                  </th>
                ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['name'], $context['statistic'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 156
                echo "              ";
            }
            // line 157
            echo "
              ";
            // line 158
            if (($context["has_master_replication"] ?? null)) {
                // line 159
                echo "                <th>";
                echo _gettext("Master replication");
                echo "</th>
              ";
            }
            // line 161
            echo "
              ";
            // line 162
            if (($context["has_slave_replication"] ?? null)) {
                // line 163
                echo "                <th>";
                echo _gettext("Slave replication");
                echo "</th>
              ";
            }
            // line 165
            echo "
              <th>";
            // line 166
            echo _gettext("Action");
            echo "</th>
            </tr>
          </thead>

          <tbody>
            ";
            // line 171
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["databases"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["database"]) {
                // line 172
                echo "              <tr class=\"db-row";
                echo (((twig_get_attribute($this->env, $this->source, $context["database"], "is_system_schema", [], "any", false, false, false, 172) || twig_get_attribute($this->env, $this->source, $context["database"], "is_pmadb", [], "any", false, false, false, 172))) ? (" noclick") : (""));
                echo "\" data-filter-row=\"";
                echo twig_escape_filter($this->env, twig_upper_filter($this->env, twig_get_attribute($this->env, $this->source, $context["database"], "name", [], "any", false, false, false, 172)), "html", null, true);
                echo "\">
                ";
                // line 173
                if (($context["is_drop_allowed"] ?? null)) {
                    // line 174
                    echo "                  <td class=\"tool\">
                    <input type=\"checkbox\" name=\"selected_dbs[]\" class=\"checkall\" title=\"";
                    // line 176
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["database"], "name", [], "any", false, false, false, 176), "html", null, true);
                    echo "\" value=\"";
                    // line 177
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["database"], "name", [], "any", false, false, false, 177), "html", null, true);
                    echo "\"";
                    // line 178
                    echo (((twig_get_attribute($this->env, $this->source, $context["database"], "is_system_schema", [], "any", false, false, false, 178) || twig_get_attribute($this->env, $this->source, $context["database"], "is_pmadb", [], "any", false, false, false, 178))) ? (" disabled") : (""));
                    echo ">
                  </td>
                ";
                }
                // line 181
                echo "
                <td class=\"name\">
                  <a href=\"";
                // line 183
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["database"], "url", [], "any", false, false, false, 183), "html", null, true);
                echo "\" title=\"";
                // line 184
                echo twig_escape_filter($this->env, twig_sprintf(_gettext("Jump to database '%s'"), twig_get_attribute($this->env, $this->source, $context["database"], "name", [], "any", false, false, false, 184)), "html", null, true);
                echo "\">
                    ";
                // line 185
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["database"], "name", [], "any", false, false, false, 185), "html", null, true);
                echo "
                  </a>
                </td>

                <td class=\"value\">
                  <dfn title=\"";
                // line 190
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["database"], "collation", [], "any", false, false, false, 190), "description", [], "any", false, false, false, 190), "html", null, true);
                echo "\">
                    ";
                // line 191
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["database"], "collation", [], "any", false, false, false, 191), "name", [], "any", false, false, false, 191), "html", null, true);
                echo "
                  </dfn>
                </td>

                ";
                // line 195
                if (($context["has_statistics"] ?? null)) {
                    // line 196
                    echo "                  ";
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["database"], "statistics", [], "any", false, false, false, 196));
                    foreach ($context['_seq'] as $context["_key"] => $context["statistic"]) {
                        // line 197
                        echo "                    ";
                        if ((twig_get_attribute($this->env, $this->source, $context["statistic"], "format", [], "any", false, false, false, 197) === "byte")) {
                            // line 198
                            echo "                      ";
                            $context["value"] = PhpMyAdmin\Util::formatByteDown(twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 198), 3, 1);
                            // line 199
                            echo "                      <td class=\"value\">
                        <data value=\"";
                            // line 200
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 200), "html", null, true);
                            echo "\" title=\"";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 200), "html", null, true);
                            echo "\">
                          ";
                            // line 201
                            echo twig_escape_filter($this->env, (($__internal_compile_0 = ($context["value"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[0] ?? null) : null), "html", null, true);
                            echo "
                        </data>
                      </td>
                      <td class=\"unit\">";
                            // line 204
                            echo twig_escape_filter($this->env, (($__internal_compile_1 = ($context["value"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1[1] ?? null) : null), "html", null, true);
                            echo "</td>
                    ";
                        } else {
                            // line 206
                            echo "                      <td class=\"value\">
                        <data value=\"";
                            // line 207
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 207), "html", null, true);
                            echo "\" title=\"";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 207), "html", null, true);
                            echo "\">
                          ";
                            // line 208
                            echo twig_escape_filter($this->env, PhpMyAdmin\Util::formatNumber(twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 208), 0), "html", null, true);
                            echo "
                        </data>
                      </td>
                    ";
                        }
                        // line 212
                        echo "                  ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['statistic'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 213
                    echo "                ";
                }
                // line 214
                echo "
                ";
                // line 215
                if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["database"], "replication", [], "any", false, false, false, 215), "master", [], "any", false, false, false, 215), "status", [], "any", false, false, false, 215)) {
                    // line 216
                    echo "                  ";
                    if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["database"], "replication", [], "any", false, false, false, 216), "master", [], "any", false, false, false, 216), "is_replicated", [], "any", false, false, false, 216)) {
                        // line 217
                        echo "                    <td class=\"tool text-center\">
                      ";
                        // line 218
                        echo \PhpMyAdmin\Html\Generator::getIcon("s_success", _gettext("Replicated"));
                        echo "
                    </td>
                  ";
                    } else {
                        // line 221
                        echo "                    <td class=\"tool text-center\">
                      ";
                        // line 222
                        echo \PhpMyAdmin\Html\Generator::getIcon("s_cancel", _gettext("Not replicated"));
                        echo "
                    </td>
                  ";
                    }
                    // line 225
                    echo "                ";
                }
                // line 226
                echo "
                ";
                // line 227
                if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["database"], "replication", [], "any", false, false, false, 227), "slave", [], "any", false, false, false, 227), "status", [], "any", false, false, false, 227)) {
                    // line 228
                    echo "                  ";
                    if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, $context["database"], "replication", [], "any", false, false, false, 228), "slave", [], "any", false, false, false, 228), "is_replicated", [], "any", false, false, false, 228)) {
                        // line 229
                        echo "                    <td class=\"tool text-center\">
                      ";
                        // line 230
                        echo \PhpMyAdmin\Html\Generator::getIcon("s_success", _gettext("Replicated"));
                        echo "
                    </td>
                  ";
                    } else {
                        // line 233
                        echo "                    <td class=\"tool text-center\">
                      ";
                        // line 234
                        echo \PhpMyAdmin\Html\Generator::getIcon("s_cancel", _gettext("Not replicated"));
                        echo "
                    </td>
                  ";
                    }
                    // line 237
                    echo "                ";
                }
                // line 238
                echo "
                <td class=\"tool\">
                  <a class=\"server_databases\" data=\"";
                // line 241
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["database"], "name", [], "any", false, false, false, 241), "html", null, true);
                echo "\" href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["db" => twig_get_attribute($this->env, $this->source,                 // line 242
$context["database"], "name", [], "any", false, false, false, 242), "checkprivsdb" => twig_get_attribute($this->env, $this->source,                 // line 243
$context["database"], "name", [], "any", false, false, false, 243)]);
                // line 244
                echo "\" title=\"";
                // line 245
                echo twig_escape_filter($this->env, twig_sprintf(_gettext("Check privileges for database \"%s\"."), twig_get_attribute($this->env, $this->source, $context["database"], "name", [], "any", false, false, false, 245)), "html", null, true);
                echo "\">
                    ";
                // line 246
                echo \PhpMyAdmin\Html\Generator::getIcon("s_rights", _gettext("Check privileges"));
                echo "
                  </a>
                </td>
              </tr>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['database'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 251
            echo "          </tbody>

          <tfoot class=\"thead-light\">
            <tr>
              <th colspan=\"";
            // line 255
            echo ((($context["is_drop_allowed"] ?? null)) ? ("3") : ("2"));
            echo "\">
                ";
            // line 256
            echo _gettext("Total:");
            // line 257
            echo "                <span id=\"filter-rows-count\">";
            // line 258
            echo twig_escape_filter($this->env, ($context["database_count"] ?? null), "html", null, true);
            // line 259
            echo "</span>
              </th>

              ";
            // line 262
            if (($context["has_statistics"] ?? null)) {
                // line 263
                echo "                ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(($context["total_statistics"] ?? null));
                foreach ($context['_seq'] as $context["_key"] => $context["statistic"]) {
                    // line 264
                    echo "                  ";
                    if ((twig_get_attribute($this->env, $this->source, $context["statistic"], "format", [], "any", false, false, false, 264) === "byte")) {
                        // line 265
                        echo "                    ";
                        $context["value"] = PhpMyAdmin\Util::formatByteDown(twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 265), 3, 1);
                        // line 266
                        echo "                    <th class=\"value\">
                      <data value=\"";
                        // line 267
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 267), "html", null, true);
                        echo "\" title=\"";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 267), "html", null, true);
                        echo "\">
                        ";
                        // line 268
                        echo twig_escape_filter($this->env, (($__internal_compile_2 = ($context["value"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2[0] ?? null) : null), "html", null, true);
                        echo "
                      </data>
                    </th>
                    <th class=\"unit\">";
                        // line 271
                        echo twig_escape_filter($this->env, (($__internal_compile_3 = ($context["value"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3[1] ?? null) : null), "html", null, true);
                        echo "</th>
                  ";
                    } else {
                        // line 273
                        echo "                    <th class=\"value\">
                      <data value=\"";
                        // line 274
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 274), "html", null, true);
                        echo "\" title=\"";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 274), "html", null, true);
                        echo "\">
                        ";
                        // line 275
                        echo twig_escape_filter($this->env, PhpMyAdmin\Util::formatNumber(twig_get_attribute($this->env, $this->source, $context["statistic"], "raw", [], "any", false, false, false, 275), 0), "html", null, true);
                        echo "
                      </data>
                    </th>
                  ";
                    }
                    // line 279
                    echo "                ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['statistic'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 280
                echo "              ";
            }
            // line 281
            echo "
              ";
            // line 282
            if (($context["has_master_replication"] ?? null)) {
                // line 283
                echo "                <th></th>
              ";
            }
            // line 285
            echo "
              ";
            // line 286
            if (($context["has_slave_replication"] ?? null)) {
                // line 287
                echo "                <th></th>
              ";
            }
            // line 289
            echo "
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

      ";
            // line 297
            echo "      ";
            if (($context["is_drop_allowed"] ?? null)) {
                // line 298
                echo "        ";
                $this->loadTemplate("select_all.twig", "server/databases/index.twig", 298)->display(twig_to_array(["theme_image_path" =>                 // line 299
($context["theme_image_path"] ?? null), "text_dir" =>                 // line 300
($context["text_dir"] ?? null), "form_name" => "dbStatsForm"]));
                // line 303
                echo "
          <button class=\"btn btn-link mult_submit ajax\" type=\"submit\" name=\"\" value=\"Drop\" title=\"";
                // line 304
                echo _gettext("Drop");
                echo "\">
              ";
                // line 305
                echo \PhpMyAdmin\Html\Generator::getIcon("b_deltbl", _gettext("Drop"));
                echo "
          </button>
      ";
            }
            // line 308
            echo "
      ";
            // line 310
            echo "      ";
            if ( !($context["has_statistics"] ?? null)) {
                // line 311
                echo "        <div class=\"row\">
          ";
                // line 312
                echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [_gettext("Note: Enabling the database statistics here might cause heavy traffic between the web server and the MySQL server.")]);
                echo "
        </div>

        <div class=\"row\">
          <ul>
            <li class=\"li_switch_dbstats\">
              <a href=\"";
                // line 318
                echo PhpMyAdmin\Url::getFromRoute("/server/databases");
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(["statistics" => "1"], "", false);
                echo "\" title=\"";
                echo _gettext("Enable statistics");
                echo "\">
                <strong>";
                // line 319
                echo _gettext("Enable statistics");
                echo "</strong>
              </a>
            </li>
          </ul>
        </div>
      ";
            }
            // line 325
            echo "    </form>
  </div>
  </div>
</div>
";
        } else {
            // line 330
            echo "  <p>";
            echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [_gettext("No databases")]);
            echo "</p>
";
        }
    }

    public function getTemplateName()
    {
        return "server/databases/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  791 => 330,  784 => 325,  775 => 319,  767 => 318,  758 => 312,  755 => 311,  752 => 310,  749 => 308,  743 => 305,  739 => 304,  736 => 303,  734 => 300,  733 => 299,  731 => 298,  728 => 297,  719 => 289,  715 => 287,  713 => 286,  710 => 285,  706 => 283,  704 => 282,  701 => 281,  698 => 280,  692 => 279,  685 => 275,  679 => 274,  676 => 273,  671 => 271,  665 => 268,  659 => 267,  656 => 266,  653 => 265,  650 => 264,  645 => 263,  643 => 262,  638 => 259,  636 => 258,  634 => 257,  632 => 256,  628 => 255,  622 => 251,  611 => 246,  607 => 245,  605 => 244,  603 => 243,  602 => 242,  599 => 241,  595 => 238,  592 => 237,  586 => 234,  583 => 233,  577 => 230,  574 => 229,  571 => 228,  569 => 227,  566 => 226,  563 => 225,  557 => 222,  554 => 221,  548 => 218,  545 => 217,  542 => 216,  540 => 215,  537 => 214,  534 => 213,  528 => 212,  521 => 208,  515 => 207,  512 => 206,  507 => 204,  501 => 201,  495 => 200,  492 => 199,  489 => 198,  486 => 197,  481 => 196,  479 => 195,  472 => 191,  468 => 190,  460 => 185,  456 => 184,  453 => 183,  449 => 181,  443 => 178,  440 => 177,  437 => 176,  434 => 174,  432 => 173,  425 => 172,  421 => 171,  413 => 166,  410 => 165,  404 => 163,  402 => 162,  399 => 161,  393 => 159,  391 => 158,  388 => 157,  385 => 156,  377 => 153,  374 => 152,  368 => 150,  362 => 148,  359 => 147,  357 => 146,  353 => 145,  350 => 144,  348 => 143,  347 => 142,  346 => 141,  345 => 140,  340 => 139,  335 => 138,  333 => 137,  328 => 134,  325 => 133,  319 => 131,  313 => 129,  310 => 128,  307 => 127,  305 => 126,  302 => 125,  300 => 124,  299 => 123,  298 => 121,  292 => 117,  289 => 116,  283 => 114,  277 => 112,  274 => 111,  271 => 110,  269 => 109,  266 => 108,  264 => 107,  263 => 106,  262 => 104,  259 => 103,  255 => 101,  253 => 100,  245 => 95,  241 => 94,  237 => 92,  235 => 91,  234 => 88,  233 => 87,  232 => 86,  231 => 85,  226 => 82,  223 => 81,  221 => 80,  218 => 79,  212 => 75,  208 => 73,  206 => 72,  203 => 71,  201 => 67,  192 => 62,  190 => 61,  186 => 60,  182 => 58,  176 => 55,  173 => 54,  169 => 52,  162 => 50,  155 => 48,  153 => 47,  145 => 46,  141 => 45,  134 => 44,  130 => 43,  125 => 41,  122 => 40,  120 => 39,  115 => 37,  112 => 36,  109 => 34,  105 => 32,  103 => 31,  98 => 29,  91 => 25,  88 => 24,  86 => 23,  82 => 22,  74 => 18,  72 => 17,  67 => 14,  65 => 13,  60 => 10,  57 => 9,  54 => 8,  51 => 7,  48 => 6,  46 => 5,  42 => 4,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/databases/index.twig", "/var/www/html/phpMyAdmin/templates/server/databases/index.twig");
    }
}
