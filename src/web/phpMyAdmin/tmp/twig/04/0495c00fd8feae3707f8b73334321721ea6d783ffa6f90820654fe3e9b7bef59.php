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

/* table/relation/common_form.twig */
class __TwigTemplate_98d4b6b37241289e2e2c59a281c5ecabfc54ca8dfd985d753a456b12b74b0272 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "table/page_with_secondary_tabs.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("table/page_with_secondary_tabs.twig", "table/relation/common_form.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo "<form method=\"post\" action=\"";
        echo PhpMyAdmin\Url::getFromRoute("/table/relation");
        echo "\">
    ";
        // line 5
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
    ";
        // line 7
        echo "    ";
        if (PhpMyAdmin\Util::isForeignKeySupported(($context["tbl_storage_engine"] ?? null))) {
            // line 8
            echo "        <fieldset>
            <legend>";
            // line 9
            echo _gettext("Foreign key constraints");
            echo "</legend>
            <div class=\"table-responsive-md jsresponsive\">
            <table class=\"relationalTable table table-light table-striped w-auto\">
                <thead class=\"thead-light\">
                <tr>
                    <th>";
            // line 14
            echo _gettext("Actions");
            echo "</th>
                    <th>";
            // line 15
            echo _gettext("Constraint properties");
            echo "</th>
                    ";
            // line 16
            if ((twig_upper_filter($this->env, ($context["tbl_storage_engine"] ?? null)) == "INNODB")) {
                // line 17
                echo "                        <th>
                            ";
                // line 18
                echo _gettext("Column");
                // line 19
                echo "                            ";
                echo \PhpMyAdmin\Html\Generator::showHint(_gettext("Creating a foreign key over a non-indexed column would automatically create an index on it. Alternatively, you can define an index below, before creating the foreign key."));
                echo "
                        </th>
                    ";
            } else {
                // line 22
                echo "                        <th>
                            ";
                // line 23
                echo _gettext("Column");
                // line 24
                echo "                            ";
                echo \PhpMyAdmin\Html\Generator::showHint(_gettext("Only columns with index will be displayed. You can define an index below."));
                echo "
                        </th>
                    ";
            }
            // line 27
            echo "                    <th colspan=\"3\">
                        ";
            // line 28
            echo _gettext("Foreign key constraint");
            // line 29
            echo "                        (";
            echo twig_escape_filter($this->env, ($context["tbl_storage_engine"] ?? null), "html", null, true);
            echo ")
                    </th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>";
            // line 36
            echo _gettext("Database");
            echo "</th>
                    <th>";
            // line 37
            echo _gettext("Table");
            echo "</th>
                    <th>";
            // line 38
            echo _gettext("Column");
            echo "</th>
                </tr></thead>
                ";
            // line 40
            $context["i"] = 0;
            // line 41
            echo "                ";
            if ( !twig_test_empty(($context["existrel_foreign"] ?? null))) {
                // line 42
                echo "                    ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(($context["existrel_foreign"] ?? null));
                foreach ($context['_seq'] as $context["key"] => $context["one_key"]) {
                    // line 43
                    echo "                        ";
                    // line 44
                    echo "                        ";
                    $context["foreign_db"] = (((twig_get_attribute($this->env, $this->source, $context["one_key"], "ref_db_name", [], "array", true, true, false, 44) &&  !(null === (($__internal_compile_0 =                     // line 45
$context["one_key"]) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["ref_db_name"] ?? null) : null)))) ? ((($__internal_compile_1 =                     // line 46
$context["one_key"]) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["ref_db_name"] ?? null) : null)) : (($context["db"] ?? null)));
                    // line 47
                    echo "                        ";
                    $context["foreign_table"] = false;
                    // line 48
                    echo "                        ";
                    if (($context["foreign_db"] ?? null)) {
                        // line 49
                        echo "                            ";
                        $context["foreign_table"] = (((twig_get_attribute($this->env, $this->source, $context["one_key"], "ref_table_name", [], "array", true, true, false, 49) &&  !(null === (($__internal_compile_2 =                         // line 50
$context["one_key"]) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["ref_table_name"] ?? null) : null)))) ? ((($__internal_compile_3 =                         // line 51
$context["one_key"]) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["ref_table_name"] ?? null) : null)) : (false));
                        // line 52
                        echo "                        ";
                    }
                    // line 53
                    echo "                        ";
                    $context["unique_columns"] = [];
                    // line 54
                    echo "                        ";
                    if ((($context["foreign_db"] ?? null) && ($context["foreign_table"] ?? null))) {
                        // line 55
                        echo "                            ";
                        $context["table_obj"] = PhpMyAdmin\Table::get(($context["foreign_table"] ?? null), ($context["foreign_db"] ?? null));
                        // line 56
                        echo "                            ";
                        $context["unique_columns"] = twig_get_attribute($this->env, $this->source, ($context["table_obj"] ?? null), "getUniqueColumns", [0 => false, 1 => false], "method", false, false, false, 56);
                        // line 57
                        echo "                        ";
                    }
                    // line 58
                    echo "                        ";
                    $this->loadTemplate("table/relation/foreign_key_row.twig", "table/relation/common_form.twig", 58)->display(twig_to_array(["i" =>                     // line 59
($context["i"] ?? null), "one_key" =>                     // line 60
$context["one_key"], "column_array" =>                     // line 61
($context["column_array"] ?? null), "options_array" =>                     // line 62
($context["options_array"] ?? null), "tbl_storage_engine" =>                     // line 63
($context["tbl_storage_engine"] ?? null), "db" =>                     // line 64
($context["db"] ?? null), "table" =>                     // line 65
($context["table"] ?? null), "url_params" =>                     // line 66
($context["url_params"] ?? null), "databases" =>                     // line 67
($context["databases"] ?? null), "foreign_db" =>                     // line 68
($context["foreign_db"] ?? null), "foreign_table" =>                     // line 69
($context["foreign_table"] ?? null), "unique_columns" =>                     // line 70
($context["unique_columns"] ?? null)]));
                    // line 72
                    echo "                        ";
                    $context["i"] = (($context["i"] ?? null) + 1);
                    // line 73
                    echo "                    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['key'], $context['one_key'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 74
                echo "                ";
            }
            // line 75
            echo "                ";
            $this->loadTemplate("table/relation/foreign_key_row.twig", "table/relation/common_form.twig", 75)->display(twig_to_array(["i" =>             // line 76
($context["i"] ?? null), "one_key" => [], "column_array" =>             // line 78
($context["column_array"] ?? null), "options_array" =>             // line 79
($context["options_array"] ?? null), "tbl_storage_engine" =>             // line 80
($context["tbl_storage_engine"] ?? null), "db" =>             // line 81
($context["db"] ?? null), "table" =>             // line 82
($context["table"] ?? null), "url_params" =>             // line 83
($context["url_params"] ?? null), "databases" =>             // line 84
($context["databases"] ?? null), "foreign_db" =>             // line 85
($context["foreign_db"] ?? null), "foreign_table" =>             // line 86
($context["foreign_table"] ?? null), "unique_columns" =>             // line 87
($context["unique_columns"] ?? null)]));
            // line 89
            echo "                ";
            $context["i"] = (($context["i"] ?? null) + 1);
            // line 90
            echo "                <tr>
                    <th colspan=\"6\">
                        <a class=\"formelement clearfloat add_foreign_key\" href=\"\">
                            ";
            // line 93
            echo _gettext("+ Add constraint");
            // line 94
            echo "                        </a>
                    </th>
                </tr>
            </table>
            </div>
        </fieldset>
    ";
        }
        // line 101
        echo "
    ";
        // line 102
        if ((($__internal_compile_4 = ($context["cfg_relation"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["relwork"] ?? null) : null)) {
            // line 103
            echo "        ";
            if (PhpMyAdmin\Util::isForeignKeySupported(($context["tbl_storage_engine"] ?? null))) {
                // line 104
                echo "            <div id=\"ir_div\"";
                if ((($context["default_sliders_state"] ?? null) != "disabled")) {
                    // line 105
                    echo (((($context["default_sliders_state"] ?? null) == "closed")) ? (" style=\"display: none; overflow:auto;\"") : (""));
                    echo " class=\"pma_auto_slider\" title=\"";
                    echo _gettext("Internal relationships");
                    echo "\"";
                }
                // line 106
                echo ">
        ";
            }
            // line 108
            echo "
        <fieldset>
            <legend>
                ";
            // line 111
            echo _gettext("Internal relationships");
            // line 112
            echo "                ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("config", "cfg_Servers_relation");
            echo "
            </legend>
            <table class=\"relationalTable table table-light table-striped table-hover table-sm w-auto\">
                <thead class=\"thead-light\">
                  <tr>
                    <th>";
            // line 117
            echo _gettext("Column");
            echo "</th>
                    <th>
                      ";
            // line 119
            echo _gettext("Internal relation");
            // line 120
            echo "                      ";
            if (PhpMyAdmin\Util::isForeignKeySupported(($context["tbl_storage_engine"] ?? null))) {
                // line 121
                echo "                        ";
                echo \PhpMyAdmin\Html\Generator::showHint(_gettext("An internal relation is not necessary when a corresponding FOREIGN KEY relation exists."));
                echo "
                      ";
            }
            // line 123
            echo "                    </th>
                  </tr>
                </thead>
                <tbody>
                    ";
            // line 127
            $context["saved_row_cnt"] = (twig_length_filter($this->env, ($context["save_row"] ?? null)) - 1);
            // line 128
            echo "                    ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(range(0, ($context["saved_row_cnt"] ?? null)));
            foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
                // line 129
                echo "                        ";
                $context["myfield"] = (($__internal_compile_5 = (($__internal_compile_6 = ($context["save_row"] ?? null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6[$context["i"]] ?? null) : null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["Field"] ?? null) : null);
                // line 130
                echo "                        ";
                // line 132
                echo "                        ";
                $context["myfield_md5"] = (($__internal_compile_7 = ($context["column_hash_array"] ?? null)) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7[($context["myfield"] ?? null)] ?? null) : null);
                // line 133
                echo "
                        ";
                // line 134
                $context["foreign_table"] = false;
                // line 135
                echo "                        ";
                $context["foreign_column"] = false;
                // line 136
                echo "
                        ";
                // line 138
                echo "                        ";
                if (twig_get_attribute($this->env, $this->source, ($context["existrel"] ?? null), ($context["myfield"] ?? null), [], "array", true, true, false, 138)) {
                    // line 139
                    echo "                            ";
                    $context["foreign_db"] = (($__internal_compile_8 = (($__internal_compile_9 = ($context["existrel"] ?? null)) && is_array($__internal_compile_9) || $__internal_compile_9 instanceof ArrayAccess ? ($__internal_compile_9[($context["myfield"] ?? null)] ?? null) : null)) && is_array($__internal_compile_8) || $__internal_compile_8 instanceof ArrayAccess ? ($__internal_compile_8["foreign_db"] ?? null) : null);
                    // line 140
                    echo "                        ";
                } else {
                    // line 141
                    echo "                            ";
                    $context["foreign_db"] = ($context["db"] ?? null);
                    // line 142
                    echo "                        ";
                }
                // line 143
                echo "
                        ";
                // line 145
                echo "                        ";
                $context["tables"] = [];
                // line 146
                echo "                        ";
                if (($context["foreign_db"] ?? null)) {
                    // line 147
                    echo "                            ";
                    if (twig_get_attribute($this->env, $this->source, ($context["existrel"] ?? null), ($context["myfield"] ?? null), [], "array", true, true, false, 147)) {
                        // line 148
                        echo "                                ";
                        $context["foreign_table"] = (($__internal_compile_10 = (($__internal_compile_11 = ($context["existrel"] ?? null)) && is_array($__internal_compile_11) || $__internal_compile_11 instanceof ArrayAccess ? ($__internal_compile_11[($context["myfield"] ?? null)] ?? null) : null)) && is_array($__internal_compile_10) || $__internal_compile_10 instanceof ArrayAccess ? ($__internal_compile_10["foreign_table"] ?? null) : null);
                        // line 149
                        echo "                            ";
                    }
                    // line 150
                    echo "                            ";
                    $context["tables"] = twig_get_attribute($this->env, $this->source, ($context["dbi"] ?? null), "getTables", [0 => ($context["foreign_db"] ?? null)], "method", false, false, false, 150);
                    // line 151
                    echo "                        ";
                }
                // line 152
                echo "
                        ";
                // line 154
                echo "                        ";
                $context["unique_columns"] = [];
                // line 155
                echo "                        ";
                if ((($context["foreign_db"] ?? null) && ($context["foreign_table"] ?? null))) {
                    // line 156
                    echo "                            ";
                    if (twig_get_attribute($this->env, $this->source, ($context["existrel"] ?? null), ($context["myfield"] ?? null), [], "array", true, true, false, 156)) {
                        // line 157
                        echo "                                ";
                        $context["foreign_column"] = (($__internal_compile_12 = (($__internal_compile_13 = ($context["existrel"] ?? null)) && is_array($__internal_compile_13) || $__internal_compile_13 instanceof ArrayAccess ? ($__internal_compile_13[($context["myfield"] ?? null)] ?? null) : null)) && is_array($__internal_compile_12) || $__internal_compile_12 instanceof ArrayAccess ? ($__internal_compile_12["foreign_field"] ?? null) : null);
                        // line 158
                        echo "                            ";
                    }
                    // line 159
                    echo "                            ";
                    $context["table_obj"] = PhpMyAdmin\Table::get(($context["foreign_table"] ?? null), ($context["foreign_db"] ?? null));
                    // line 160
                    echo "                            ";
                    $context["unique_columns"] = twig_get_attribute($this->env, $this->source, ($context["table_obj"] ?? null), "getUniqueColumns", [0 => false, 1 => false], "method", false, false, false, 160);
                    // line 161
                    echo "                        ";
                }
                // line 162
                echo "
                        <tr>
                            <td class=\"vmiddle\">
                                <strong>";
                // line 165
                echo twig_escape_filter($this->env, ($context["myfield"] ?? null), "html", null, true);
                echo "</strong>
                                <input type=\"hidden\" name=\"fields_name[";
                // line 166
                echo twig_escape_filter($this->env, ($context["myfield_md5"] ?? null), "html", null, true);
                echo "]\"
                                    value=\"";
                // line 167
                echo twig_escape_filter($this->env, ($context["myfield"] ?? null), "html", null, true);
                echo "\">
                            </td>

                            <td>
                                ";
                // line 171
                $this->loadTemplate("table/relation/relational_dropdown.twig", "table/relation/common_form.twig", 171)->display(twig_to_array(["name" => (("destination_db[" .                 // line 172
($context["myfield_md5"] ?? null)) . "]"), "title" => _gettext("Database"), "values" =>                 // line 174
($context["databases"] ?? null), "foreign" =>                 // line 175
($context["foreign_db"] ?? null)]));
                // line 177
                echo "
                                ";
                // line 178
                $this->loadTemplate("table/relation/relational_dropdown.twig", "table/relation/common_form.twig", 178)->display(twig_to_array(["name" => (("destination_table[" .                 // line 179
($context["myfield_md5"] ?? null)) . "]"), "title" => _gettext("Table"), "values" =>                 // line 181
($context["tables"] ?? null), "foreign" =>                 // line 182
($context["foreign_table"] ?? null)]));
                // line 184
                echo "
                                ";
                // line 185
                $this->loadTemplate("table/relation/relational_dropdown.twig", "table/relation/common_form.twig", 185)->display(twig_to_array(["name" => (("destination_column[" .                 // line 186
($context["myfield_md5"] ?? null)) . "]"), "title" => _gettext("Column"), "values" =>                 // line 188
($context["unique_columns"] ?? null), "foreign" =>                 // line 189
($context["foreign_column"] ?? null)]));
                // line 191
                echo "                            </td>
                        </tr>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 194
            echo "                </tbody>
            </table>
        </fieldset>
        ";
            // line 197
            if (PhpMyAdmin\Util::isForeignKeySupported(($context["tbl_storage_engine"] ?? null))) {
                // line 198
                echo "            </div>
        ";
            }
            // line 200
            echo "    ";
        }
        // line 201
        echo "
    ";
        // line 202
        if ((($__internal_compile_14 = ($context["cfg_relation"] ?? null)) && is_array($__internal_compile_14) || $__internal_compile_14 instanceof ArrayAccess ? ($__internal_compile_14["displaywork"] ?? null) : null)) {
            // line 203
            echo "        ";
            $context["disp"] = call_user_func_array($this->env->getFunction('get_display_field')->getCallable(), [($context["db"] ?? null), ($context["table"] ?? null)]);
            // line 204
            echo "        <fieldset>
            <label>";
            // line 205
            echo _gettext("Choose column to display:");
            echo "</label>
            <select name=\"display_field\">
                <option value=\"\">---</option>
                ";
            // line 208
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["save_row"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
                // line 209
                echo "                    <option value=\"";
                echo twig_escape_filter($this->env, (($__internal_compile_15 = $context["row"]) && is_array($__internal_compile_15) || $__internal_compile_15 instanceof ArrayAccess ? ($__internal_compile_15["Field"] ?? null) : null), "html", null, true);
                echo "\"";
                // line 210
                if (((isset($context["disp"]) || array_key_exists("disp", $context)) && ((($__internal_compile_16 = $context["row"]) && is_array($__internal_compile_16) || $__internal_compile_16 instanceof ArrayAccess ? ($__internal_compile_16["Field"] ?? null) : null) == ($context["disp"] ?? null)))) {
                    // line 211
                    echo "                            selected=\"selected\"";
                }
                // line 212
                echo ">
                        ";
                // line 213
                echo twig_escape_filter($this->env, (($__internal_compile_17 = $context["row"]) && is_array($__internal_compile_17) || $__internal_compile_17 instanceof ArrayAccess ? ($__internal_compile_17["Field"] ?? null) : null), "html", null, true);
                echo "
                    </option>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 216
            echo "            </select>
        </fieldset>
    ";
        }
        // line 219
        echo "
    <fieldset class=\"tblFooters\">
        <input class=\"btn btn-secondary preview_sql\" type=\"button\" value=\"";
        // line 221
        echo _gettext("Preview SQL");
        echo "\">
        <input class=\"btn btn-primary\" type=\"submit\" value=\"";
        // line 222
        echo _gettext("Save");
        echo "\">
    </fieldset>
</form>

";
    }

    public function getTemplateName()
    {
        return "table/relation/common_form.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  511 => 222,  507 => 221,  503 => 219,  498 => 216,  489 => 213,  486 => 212,  483 => 211,  481 => 210,  477 => 209,  473 => 208,  467 => 205,  464 => 204,  461 => 203,  459 => 202,  456 => 201,  453 => 200,  449 => 198,  447 => 197,  442 => 194,  434 => 191,  432 => 189,  431 => 188,  430 => 186,  429 => 185,  426 => 184,  424 => 182,  423 => 181,  422 => 179,  421 => 178,  418 => 177,  416 => 175,  415 => 174,  414 => 172,  413 => 171,  406 => 167,  402 => 166,  398 => 165,  393 => 162,  390 => 161,  387 => 160,  384 => 159,  381 => 158,  378 => 157,  375 => 156,  372 => 155,  369 => 154,  366 => 152,  363 => 151,  360 => 150,  357 => 149,  354 => 148,  351 => 147,  348 => 146,  345 => 145,  342 => 143,  339 => 142,  336 => 141,  333 => 140,  330 => 139,  327 => 138,  324 => 136,  321 => 135,  319 => 134,  316 => 133,  313 => 132,  311 => 130,  308 => 129,  303 => 128,  301 => 127,  295 => 123,  289 => 121,  286 => 120,  284 => 119,  279 => 117,  270 => 112,  268 => 111,  263 => 108,  259 => 106,  253 => 105,  250 => 104,  247 => 103,  245 => 102,  242 => 101,  233 => 94,  231 => 93,  226 => 90,  223 => 89,  221 => 87,  220 => 86,  219 => 85,  218 => 84,  217 => 83,  216 => 82,  215 => 81,  214 => 80,  213 => 79,  212 => 78,  211 => 76,  209 => 75,  206 => 74,  200 => 73,  197 => 72,  195 => 70,  194 => 69,  193 => 68,  192 => 67,  191 => 66,  190 => 65,  189 => 64,  188 => 63,  187 => 62,  186 => 61,  185 => 60,  184 => 59,  182 => 58,  179 => 57,  176 => 56,  173 => 55,  170 => 54,  167 => 53,  164 => 52,  162 => 51,  161 => 50,  159 => 49,  156 => 48,  153 => 47,  151 => 46,  150 => 45,  148 => 44,  146 => 43,  141 => 42,  138 => 41,  136 => 40,  131 => 38,  127 => 37,  123 => 36,  112 => 29,  110 => 28,  107 => 27,  100 => 24,  98 => 23,  95 => 22,  88 => 19,  86 => 18,  83 => 17,  81 => 16,  77 => 15,  73 => 14,  65 => 9,  62 => 8,  59 => 7,  55 => 5,  50 => 4,  46 => 3,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/relation/common_form.twig", "/var/www/html/phpMyAdmin/templates/table/relation/common_form.twig");
    }
}
