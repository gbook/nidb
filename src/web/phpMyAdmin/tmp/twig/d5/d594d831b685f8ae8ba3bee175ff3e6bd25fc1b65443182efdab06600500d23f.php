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

/* table/structure/display_table_stats.twig */
class __TwigTemplate_5c33b36c414bf757cad1ca77c4f877261e0b3d96ed875365eddc453eba42a594 extends \Twig\Template
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
        echo "<div id=\"tablestatistics\">
    <fieldset>
        <legend>";
        // line 3
        echo _gettext("Information");
        echo "</legend>
        ";
        // line 4
        if ((($__internal_compile_0 = ($context["showtable"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["TABLE_COMMENT"] ?? null) : null)) {
            // line 5
            echo "            <p>
                <strong>";
            // line 6
            echo _gettext("Table comments:");
            echo "</strong>
                ";
            // line 7
            echo twig_escape_filter($this->env, (($__internal_compile_1 = ($context["showtable"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["TABLE_COMMENT"] ?? null) : null), "html", null, true);
            echo "
            </p>
        ";
        }
        // line 10
        echo "        <a id=\"showusage\"></a>

        ";
        // line 12
        if (( !($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null))) {
            // line 13
            echo "            <table class=\"table table-light table-striped table-hover table-sm w-auto\">
                <caption>";
            // line 14
            echo _gettext("Space usage");
            echo "</caption>
                <tbody>
                    <tr>
                        <th class=\"name\">";
            // line 17
            echo _gettext("Data");
            echo "</th>
                        <td class=\"value\">";
            // line 18
            echo twig_escape_filter($this->env, ($context["data_size"] ?? null), "html", null, true);
            echo "</td>
                        <td class=\"unit\">";
            // line 19
            echo twig_escape_filter($this->env, ($context["data_unit"] ?? null), "html", null, true);
            echo "</td>
                    </tr>

                ";
            // line 22
            if ((isset($context["index_size"]) || array_key_exists("index_size", $context))) {
                // line 23
                echo "                    <tr>
                        <th class=\"name\">";
                // line 24
                echo _gettext("Index");
                echo "</th>
                        <td class=\"value\">";
                // line 25
                echo twig_escape_filter($this->env, ($context["index_size"] ?? null), "html", null, true);
                echo "</td>
                        <td class=\"unit\">";
                // line 26
                echo twig_escape_filter($this->env, ($context["index_unit"] ?? null), "html", null, true);
                echo "</td>
                    </tr>
                ";
            }
            // line 29
            echo "
                ";
            // line 30
            if ((isset($context["free_size"]) || array_key_exists("free_size", $context))) {
                // line 31
                echo "                    <tr>
                        <th class=\"name\">";
                // line 32
                echo _gettext("Overhead");
                echo "</th>
                        <td class=\"value\">";
                // line 33
                echo twig_escape_filter($this->env, ($context["free_size"] ?? null), "html", null, true);
                echo "</td>
                        <td class=\"unit\">";
                // line 34
                echo twig_escape_filter($this->env, ($context["free_unit"] ?? null), "html", null, true);
                echo "</td>
                    </tr>
                    <tr>
                        <th class=\"name\">";
                // line 37
                echo _gettext("Effective");
                echo "</th>
                        <td class=\"value\">";
                // line 38
                echo twig_escape_filter($this->env, ($context["effect_size"] ?? null), "html", null, true);
                echo "</td>
                        <td class=\"unit\">";
                // line 39
                echo twig_escape_filter($this->env, ($context["effect_unit"] ?? null), "html", null, true);
                echo "</td>
                    </tr>
                ";
            }
            // line 42
            echo "
                ";
            // line 43
            if (((isset($context["tot_size"]) || array_key_exists("tot_size", $context)) && (($context["mergetable"] ?? null) == false))) {
                // line 44
                echo "                    <tr>
                        <th class=\"name\">";
                // line 45
                echo _gettext("Total");
                echo "</th>
                        <td class=\"value\">";
                // line 46
                echo twig_escape_filter($this->env, ($context["tot_size"] ?? null), "html", null, true);
                echo "</td>
                        <td class=\"unit\">";
                // line 47
                echo twig_escape_filter($this->env, ($context["tot_unit"] ?? null), "html", null, true);
                echo "</td>
                    </tr>
                ";
            }
            // line 50
            echo "                </tbody>

                ";
            // line 53
            echo "                ";
            if ((((isset($context["free_size"]) || array_key_exists("free_size", $context)) && ((((            // line 54
($context["tbl_storage_engine"] ?? null) == "MYISAM") || (            // line 55
($context["tbl_storage_engine"] ?? null) == "ARIA")) || (            // line 56
($context["tbl_storage_engine"] ?? null) == "MARIA")) || (            // line 57
($context["tbl_storage_engine"] ?? null) == "BDB"))) || ((            // line 58
($context["tbl_storage_engine"] ?? null) == "INNODB") && (($context["innodb_file_per_table"] ?? null) == true)))) {
                // line 59
                echo "                <tfoot class=\"thead-light\">
                    <tr class=\"print_ignore\">
                        <th colspan=\"3\" class=\"center\">
                            <a href=\"";
                // line 62
                echo PhpMyAdmin\Url::getFromRoute("/sql");
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(["db" =>                 // line 63
($context["db"] ?? null), "table" =>                 // line 64
($context["table"] ?? null), "sql_query" => ("OPTIMIZE TABLE " . PhpMyAdmin\Util::backquote(                // line 65
($context["table"] ?? null))), "pos" => 0]);
                // line 67
                echo "\">
                                ";
                // line 68
                echo \PhpMyAdmin\Html\Generator::getIcon("b_tbloptimize", _gettext("Optimize table"));
                echo "
                            </a>
                        </th>
                    </tr>
                </tfoot>
                ";
            }
            // line 74
            echo "            </table>
        ";
        }
        // line 76
        echo "
        ";
        // line 77
        $context["avg_size"] = (((isset($context["avg_size"]) || array_key_exists("avg_size", $context))) ? (($context["avg_size"] ?? null)) : (null));
        // line 78
        echo "        ";
        $context["avg_unit"] = (((isset($context["avg_unit"]) || array_key_exists("avg_unit", $context))) ? (($context["avg_unit"] ?? null)) : (null));
        // line 79
        echo "        <table class=\"table table-light table-striped table-hover table-sm w-auto\">
            <caption>";
        // line 80
        echo _gettext("Row statistics");
        echo "</caption>
            <tbody>
                ";
        // line 82
        if (twig_get_attribute($this->env, $this->source, ($context["showtable"] ?? null), "Row_format", [], "array", true, true, false, 82)) {
            // line 83
            echo "                    <tr>
                    <th class=\"name\">";
            // line 84
            echo _gettext("Format");
            echo "</th>
                    ";
            // line 85
            if (((($__internal_compile_2 = ($context["showtable"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["Row_format"] ?? null) : null) == "Fixed")) {
                // line 86
                echo "                        <td class=\"value\">";
                echo _gettext("static");
                echo "</td>
                    ";
            } elseif (((($__internal_compile_3 =             // line 87
($context["showtable"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["Row_format"] ?? null) : null) == "Dynamic")) {
                // line 88
                echo "                        <td class=\"value\">";
                echo _gettext("dynamic");
                echo "</td>
                    ";
            } else {
                // line 90
                echo "                        <td class=\"value\">";
                echo twig_escape_filter($this->env, (($__internal_compile_4 = ($context["showtable"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["Row_format"] ?? null) : null), "html", null, true);
                echo "</td>
                    ";
            }
            // line 92
            echo "                    </tr>
                ";
        }
        // line 94
        echo "
                ";
        // line 95
        if ( !twig_test_empty((($__internal_compile_5 = ($context["showtable"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["Create_options"] ?? null) : null))) {
            // line 96
            echo "                    <tr>
                    <th class=\"name\">";
            // line 97
            echo _gettext("Options");
            echo "</th>
                    ";
            // line 98
            if (((($__internal_compile_6 = ($context["showtable"] ?? null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6["Create_options"] ?? null) : null) == "partitioned")) {
                // line 99
                echo "                        <td class=\"value\">";
                echo _gettext("partitioned");
                echo "</td>
                    ";
            } else {
                // line 101
                echo "                        <td class=\"value\">";
                echo twig_escape_filter($this->env, (($__internal_compile_7 = ($context["showtable"] ?? null)) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7["Create_options"] ?? null) : null), "html", null, true);
                echo "</td>
                    ";
            }
            // line 103
            echo "                    </tr>
                ";
        }
        // line 105
        echo "
                ";
        // line 106
        if ( !twig_test_empty(($context["table_collation"] ?? null))) {
            // line 107
            echo "                    <tr>
                    <th class=\"name\">";
            // line 108
            echo _gettext("Collation");
            echo "</th>
                    <td class=\"value\">
                        <dfn title=\"";
            // line 110
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["table_collation"] ?? null), "description", [], "any", false, false, false, 110), "html", null, true);
            echo "\">
                            ";
            // line 111
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["table_collation"] ?? null), "name", [], "any", false, false, false, 111), "html", null, true);
            echo "
                        </dfn>
                    </td>
                    </tr>
                ";
        }
        // line 116
        echo "
                ";
        // line 117
        if (( !($context["is_innodb"] ?? null) && twig_get_attribute($this->env, $this->source, ($context["showtable"] ?? null), "Rows", [], "array", true, true, false, 117))) {
            // line 118
            echo "                    <tr>
                    <th class=\"name\">";
            // line 119
            echo _gettext("Rows");
            echo "</th>
                    <td class=\"value\">";
            // line 120
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::formatNumber((($__internal_compile_8 = ($context["showtable"] ?? null)) && is_array($__internal_compile_8) || $__internal_compile_8 instanceof ArrayAccess ? ($__internal_compile_8["Rows"] ?? null) : null), 0), "html", null, true);
            echo "</td>
                    </tr>
                ";
        }
        // line 123
        echo "
                ";
        // line 124
        if ((( !($context["is_innodb"] ?? null) && twig_get_attribute($this->env, $this->source,         // line 125
($context["showtable"] ?? null), "Avg_row_length", [], "array", true, true, false, 125)) && ((($__internal_compile_9 =         // line 126
($context["showtable"] ?? null)) && is_array($__internal_compile_9) || $__internal_compile_9 instanceof ArrayAccess ? ($__internal_compile_9["Avg_row_length"] ?? null) : null) > 0))) {
            // line 127
            echo "                    <tr>
                    <th class=\"name\">";
            // line 128
            echo _gettext("Row length");
            echo "</th>
                    ";
            // line 129
            $context["avg_row_length"] = PhpMyAdmin\Util::formatByteDown((($__internal_compile_10 = ($context["showtable"] ?? null)) && is_array($__internal_compile_10) || $__internal_compile_10 instanceof ArrayAccess ? ($__internal_compile_10["Avg_row_length"] ?? null) : null), 6, 1);
            // line 130
            echo "                    <td class=\"value\">";
            echo twig_escape_filter($this->env, (($__internal_compile_11 = ($context["avg_row_length"] ?? null)) && is_array($__internal_compile_11) || $__internal_compile_11 instanceof ArrayAccess ? ($__internal_compile_11[0] ?? null) : null), "html", null, true);
            echo " ";
            echo twig_escape_filter($this->env, (($__internal_compile_12 = ($context["avg_row_length"] ?? null)) && is_array($__internal_compile_12) || $__internal_compile_12 instanceof ArrayAccess ? ($__internal_compile_12[1] ?? null) : null), "html", null, true);
            echo "</td>
                    </tr>
                ";
        }
        // line 133
        echo "
                ";
        // line 134
        if ((((( !($context["is_innodb"] ?? null) && twig_get_attribute($this->env, $this->source,         // line 135
($context["showtable"] ?? null), "Data_length", [], "array", true, true, false, 135)) && twig_get_attribute($this->env, $this->source,         // line 136
($context["showtable"] ?? null), "Rows", [], "array", true, true, false, 136)) && ((($__internal_compile_13 =         // line 137
($context["showtable"] ?? null)) && is_array($__internal_compile_13) || $__internal_compile_13 instanceof ArrayAccess ? ($__internal_compile_13["Rows"] ?? null) : null) > 0)) && (        // line 138
($context["mergetable"] ?? null) == false))) {
            // line 139
            echo "                    <tr>
                    <th class=\"name\">";
            // line 140
            echo _gettext("Row size");
            echo "</th>
                    <td class=\"value\">";
            // line 141
            echo twig_escape_filter($this->env, ($context["avg_size"] ?? null), "html", null, true);
            echo " ";
            echo twig_escape_filter($this->env, ($context["avg_unit"] ?? null), "html", null, true);
            echo "</td>
                    </tr>
                ";
        }
        // line 144
        echo "
                ";
        // line 145
        if (twig_get_attribute($this->env, $this->source, ($context["showtable"] ?? null), "Auto_increment", [], "array", true, true, false, 145)) {
            // line 146
            echo "                    <tr>
                    <th class=\"name\">";
            // line 147
            echo _gettext("Next autoindex");
            echo "</th>
                    <td class=\"value\">";
            // line 148
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::formatNumber((($__internal_compile_14 = ($context["showtable"] ?? null)) && is_array($__internal_compile_14) || $__internal_compile_14 instanceof ArrayAccess ? ($__internal_compile_14["Auto_increment"] ?? null) : null), 0), "html", null, true);
            echo "</td>
                    </tr>
                ";
        }
        // line 151
        echo "
                ";
        // line 152
        if (twig_get_attribute($this->env, $this->source, ($context["showtable"] ?? null), "Create_time", [], "array", true, true, false, 152)) {
            // line 153
            echo "                    <tr>
                    <th class=\"name\">";
            // line 154
            echo _gettext("Creation");
            echo "</th>
                    <td class=\"value\">";
            // line 155
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::localisedDate(twig_date_format_filter($this->env, (($__internal_compile_15 = ($context["showtable"] ?? null)) && is_array($__internal_compile_15) || $__internal_compile_15 instanceof ArrayAccess ? ($__internal_compile_15["Create_time"] ?? null) : null), "U")), "html", null, true);
            echo "</td>
                    </tr>
                ";
        }
        // line 158
        echo "
                ";
        // line 159
        if (twig_get_attribute($this->env, $this->source, ($context["showtable"] ?? null), "Update_time", [], "array", true, true, false, 159)) {
            // line 160
            echo "                    <tr>
                    <th class=\"name\">";
            // line 161
            echo _gettext("Last update");
            echo "</th>
                    <td class=\"value\">";
            // line 162
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::localisedDate(twig_date_format_filter($this->env, (($__internal_compile_16 = ($context["showtable"] ?? null)) && is_array($__internal_compile_16) || $__internal_compile_16 instanceof ArrayAccess ? ($__internal_compile_16["Update_time"] ?? null) : null), "U")), "html", null, true);
            echo "</td>
                    </tr>
                ";
        }
        // line 165
        echo "
                ";
        // line 166
        if (twig_get_attribute($this->env, $this->source, ($context["showtable"] ?? null), "Check_time", [], "array", true, true, false, 166)) {
            // line 167
            echo "                    <tr>
                    <th class=\"name\">";
            // line 168
            echo _gettext("Last check");
            echo "</th>
                    <td class=\"value\">";
            // line 169
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::localisedDate(twig_date_format_filter($this->env, (($__internal_compile_17 = ($context["showtable"] ?? null)) && is_array($__internal_compile_17) || $__internal_compile_17 instanceof ArrayAccess ? ($__internal_compile_17["Check_time"] ?? null) : null), "U")), "html", null, true);
            echo "</td>
                    </tr>
                ";
        }
        // line 172
        echo "            </tbody>
        </table>
    </fieldset>
</div>
";
    }

    public function getTemplateName()
    {
        return "table/structure/display_table_stats.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  450 => 172,  444 => 169,  440 => 168,  437 => 167,  435 => 166,  432 => 165,  426 => 162,  422 => 161,  419 => 160,  417 => 159,  414 => 158,  408 => 155,  404 => 154,  401 => 153,  399 => 152,  396 => 151,  390 => 148,  386 => 147,  383 => 146,  381 => 145,  378 => 144,  370 => 141,  366 => 140,  363 => 139,  361 => 138,  360 => 137,  359 => 136,  358 => 135,  357 => 134,  354 => 133,  345 => 130,  343 => 129,  339 => 128,  336 => 127,  334 => 126,  333 => 125,  332 => 124,  329 => 123,  323 => 120,  319 => 119,  316 => 118,  314 => 117,  311 => 116,  303 => 111,  299 => 110,  294 => 108,  291 => 107,  289 => 106,  286 => 105,  282 => 103,  276 => 101,  270 => 99,  268 => 98,  264 => 97,  261 => 96,  259 => 95,  256 => 94,  252 => 92,  246 => 90,  240 => 88,  238 => 87,  233 => 86,  231 => 85,  227 => 84,  224 => 83,  222 => 82,  217 => 80,  214 => 79,  211 => 78,  209 => 77,  206 => 76,  202 => 74,  193 => 68,  190 => 67,  188 => 65,  187 => 64,  186 => 63,  183 => 62,  178 => 59,  176 => 58,  175 => 57,  174 => 56,  173 => 55,  172 => 54,  170 => 53,  166 => 50,  160 => 47,  156 => 46,  152 => 45,  149 => 44,  147 => 43,  144 => 42,  138 => 39,  134 => 38,  130 => 37,  124 => 34,  120 => 33,  116 => 32,  113 => 31,  111 => 30,  108 => 29,  102 => 26,  98 => 25,  94 => 24,  91 => 23,  89 => 22,  83 => 19,  79 => 18,  75 => 17,  69 => 14,  66 => 13,  64 => 12,  60 => 10,  54 => 7,  50 => 6,  47 => 5,  45 => 4,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/structure/display_table_stats.twig", "/var/www/html/phpMyAdmin/templates/table/structure/display_table_stats.twig");
    }
}
