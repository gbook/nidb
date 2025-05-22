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

/* columns_definitions/partitions.twig */
class __TwigTemplate_9f83d28f946adf69c2f36408a0d96c915210eefd1eb0c324e9ee639d919309ce extends \Twig\Template
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
        $context["partition_options"] = [0 => "", 1 => "HASH", 2 => "LINEAR HASH", 3 => "KEY", 4 => "LINEAR KEY", 5 => "RANGE", 6 => "RANGE COLUMNS", 7 => "LIST", 8 => "LIST COLUMNS"];
        // line 12
        $context["sub_partition_options"] = [0 => "", 1 => "HASH", 2 => "LINEAR HASH", 3 => "KEY", 4 => "LINEAR KEY"];
        // line 13
        $context["value_type_options"] = [0 => "", 1 => "LESS THAN", 2 => "LESS THAN MAXVALUE", 3 => "IN"];
        // line 14
        echo "
<table class=\"pma-table\" id=\"partition_table\">
    <tr class=\"vmiddle\">
        <td><label for=\"partition_by\">";
        // line 17
        echo _gettext("Partition by:");
        echo "</label></td>
        <td>
            <select name=\"partition_by\" id=\"partition_by\">
                ";
        // line 20
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["partition_options"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["option"]) {
            // line 21
            echo "                    <option value=\"";
            echo twig_escape_filter($this->env, $context["option"], "html", null, true);
            echo "\"";
            // line 22
            if (((($__internal_compile_0 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["partition_by"] ?? null) : null) == $context["option"])) {
                // line 23
                echo "                            selected=\"selected\"";
            }
            // line 24
            echo ">
                        ";
            // line 25
            echo twig_escape_filter($this->env, $context["option"], "html", null, true);
            echo "
                    </option>
                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['option'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 28
        echo "            </select>
        </td>
        <td>
            (<input name=\"partition_expr\" type=\"text\"
                placeholder=\"";
        // line 32
        echo _gettext("Expression or column list");
        echo "\"
                value=\"";
        // line 33
        echo twig_escape_filter($this->env, (($__internal_compile_1 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["partition_expr"] ?? null) : null), "html", null, true);
        echo "\">)
        </td>
    </tr>
    <tr class=\"vmiddle\">
        <td><label for=\"partition_count\">";
        // line 37
        echo _gettext("Partitions:");
        echo "</label></td>
        <td colspan=\"2\">
            <input name=\"partition_count\" type=\"number\" min=\"2\"
                value=\"";
        // line 40
        echo twig_escape_filter($this->env, (($__internal_compile_2 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["partition_count"] ?? null) : null), "html", null, true);
        echo "\">
        </td>
    </tr>
    ";
        // line 43
        if ((($__internal_compile_3 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["can_have_subpartitions"] ?? null) : null)) {
            // line 44
            echo "        <tr class=\"vmiddle\">
            <td><label for=\"subpartition_by\">";
            // line 45
            echo _gettext("Subpartition by:");
            echo "</label></td>
            <td>
                <select name=\"subpartition_by\" id=\"subpartition_by\">
                    ";
            // line 48
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["sub_partition_options"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["option"]) {
                // line 49
                echo "                    <option value=\"";
                echo twig_escape_filter($this->env, $context["option"], "html", null, true);
                echo "\"";
                // line 50
                if (((($__internal_compile_4 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["subpartition_by"] ?? null) : null) == $context["option"])) {
                    // line 51
                    echo "                            selected=\"selected\"";
                }
                // line 52
                echo ">
                        ";
                // line 53
                echo twig_escape_filter($this->env, $context["option"], "html", null, true);
                echo "
                    </option>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['option'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 56
            echo "                </select>
            </td>
            <td>
                (<input name=\"subpartition_expr\" type=\"text\"
                    placeholder=\"";
            // line 60
            echo _gettext("Expression or column list");
            echo "\"
                    value=\"";
            // line 61
            echo twig_escape_filter($this->env, (($__internal_compile_5 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["subpartition_expr"] ?? null) : null), "html", null, true);
            echo "\">)
            </td>
        </tr>
        <tr class=\"vmiddle\">
            <td><label for=\"subpartition_count\">";
            // line 65
            echo _gettext("Subpartitions:");
            echo "</label></td>
            <td colspan=\"2\">
                <input name=\"subpartition_count\" type=\"number\" min=\"2\"
                       value=\"";
            // line 68
            echo twig_escape_filter($this->env, (($__internal_compile_6 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6["subpartition_count"] ?? null) : null), "html", null, true);
            echo "\">
            </td>
        </tr>
    ";
        }
        // line 72
        echo "</table>
";
        // line 73
        if (((($__internal_compile_7 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7["partition_count"] ?? null) : null) > 1)) {
            // line 74
            echo "    <table class=\"pma-table\" id=\"partition_definition_table\">
        <thead><tr>
            <th>";
            // line 76
            echo _gettext("Partition");
            echo "</th>
            ";
            // line 77
            if ((($__internal_compile_8 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_8) || $__internal_compile_8 instanceof ArrayAccess ? ($__internal_compile_8["value_enabled"] ?? null) : null)) {
                // line 78
                echo "                <th>";
                echo _gettext("Values");
                echo "</th>
            ";
            }
            // line 80
            echo "            ";
            if (((($__internal_compile_9 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_9) || $__internal_compile_9 instanceof ArrayAccess ? ($__internal_compile_9["can_have_subpartitions"] ?? null) : null) && ((($__internal_compile_10 =             // line 81
($context["partition_details"] ?? null)) && is_array($__internal_compile_10) || $__internal_compile_10 instanceof ArrayAccess ? ($__internal_compile_10["subpartition_count"] ?? null) : null) > 1))) {
                // line 82
                echo "                <th>";
                echo _gettext("Subpartition");
                echo "</th>
            ";
            }
            // line 84
            echo "            <th>";
            echo _gettext("Engine");
            echo "</th>
            <th>";
            // line 85
            echo _gettext("Comment");
            echo "</th>
            <th>";
            // line 86
            echo _gettext("Data directory");
            echo "</th>
            <th>";
            // line 87
            echo _gettext("Index directory");
            echo "</th>
            <th>";
            // line 88
            echo _gettext("Max rows");
            echo "</th>
            <th>";
            // line 89
            echo _gettext("Min rows");
            echo "</th>
            <th>";
            // line 90
            echo _gettext("Table space");
            echo "</th>
            <th>";
            // line 91
            echo _gettext("Node group");
            echo "</th>
        </tr></thead>
        ";
            // line 93
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((($__internal_compile_11 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_11) || $__internal_compile_11 instanceof ArrayAccess ? ($__internal_compile_11["partitions"] ?? null) : null));
            foreach ($context['_seq'] as $context["_key"] => $context["partition"]) {
                // line 94
                echo "            ";
                $context["rowspan"] = (( !twig_test_empty((($__internal_compile_12 = $context["partition"]) && is_array($__internal_compile_12) || $__internal_compile_12 instanceof ArrayAccess ? ($__internal_compile_12["subpartition_count"] ?? null) : null))) ? (((($__internal_compile_13 =                 // line 95
$context["partition"]) && is_array($__internal_compile_13) || $__internal_compile_13 instanceof ArrayAccess ? ($__internal_compile_13["subpartition_count"] ?? null) : null) + 1)) : (2));
                // line 96
                echo "            <tr>
                <td rowspan=\"";
                // line 97
                echo twig_escape_filter($this->env, ($context["rowspan"] ?? null), "html", null, true);
                echo "\">
                    <input type=\"text\" name=\"";
                // line 98
                echo twig_escape_filter($this->env, (($__internal_compile_14 = $context["partition"]) && is_array($__internal_compile_14) || $__internal_compile_14 instanceof ArrayAccess ? ($__internal_compile_14["prefix"] ?? null) : null), "html", null, true);
                echo "[name]\"
                        value=\"";
                // line 99
                echo twig_escape_filter($this->env, (($__internal_compile_15 = $context["partition"]) && is_array($__internal_compile_15) || $__internal_compile_15 instanceof ArrayAccess ? ($__internal_compile_15["name"] ?? null) : null), "html", null, true);
                echo "\">
                </td>
                ";
                // line 101
                if ((($__internal_compile_16 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_16) || $__internal_compile_16 instanceof ArrayAccess ? ($__internal_compile_16["value_enabled"] ?? null) : null)) {
                    // line 102
                    echo "                    <td rowspan=\"";
                    echo twig_escape_filter($this->env, ($context["rowspan"] ?? null), "html", null, true);
                    echo "\" class=\"vmiddle\">
                        <select class=\"partition_value\"
                            name=\"";
                    // line 104
                    echo twig_escape_filter($this->env, (($__internal_compile_17 = $context["partition"]) && is_array($__internal_compile_17) || $__internal_compile_17 instanceof ArrayAccess ? ($__internal_compile_17["prefix"] ?? null) : null), "html", null, true);
                    echo "[value_type]\">
                            ";
                    // line 105
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(($context["value_type_options"] ?? null));
                    foreach ($context['_seq'] as $context["_key"] => $context["option"]) {
                        // line 106
                        echo "                                <option value=\"";
                        echo twig_escape_filter($this->env, $context["option"], "html", null, true);
                        echo "\"";
                        // line 107
                        if (((($__internal_compile_18 = $context["partition"]) && is_array($__internal_compile_18) || $__internal_compile_18 instanceof ArrayAccess ? ($__internal_compile_18["value_type"] ?? null) : null) == $context["option"])) {
                            // line 108
                            echo "                                        selected=\"selected\"";
                        }
                        // line 109
                        echo ">
                                    ";
                        // line 110
                        echo twig_escape_filter($this->env, $context["option"], "html", null, true);
                        echo "
                                </option>
                            ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['option'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 113
                    echo "                        </select>
                        <input type=\"text\" class=\"partition_value\"
                            name=\"";
                    // line 115
                    echo twig_escape_filter($this->env, (($__internal_compile_19 = $context["partition"]) && is_array($__internal_compile_19) || $__internal_compile_19 instanceof ArrayAccess ? ($__internal_compile_19["prefix"] ?? null) : null), "html", null, true);
                    echo "[value]\"
                            value=\"";
                    // line 116
                    echo twig_escape_filter($this->env, (($__internal_compile_20 = $context["partition"]) && is_array($__internal_compile_20) || $__internal_compile_20 instanceof ArrayAccess ? ($__internal_compile_20["value"] ?? null) : null), "html", null, true);
                    echo "\">
                    </td>
                ";
                }
                // line 119
                echo "            </tr>

            ";
                // line 121
                if (twig_get_attribute($this->env, $this->source, $context["partition"], "subpartitions", [], "array", true, true, false, 121)) {
                    // line 122
                    echo "                ";
                    $context["subpartitions"] = (($__internal_compile_21 = $context["partition"]) && is_array($__internal_compile_21) || $__internal_compile_21 instanceof ArrayAccess ? ($__internal_compile_21["subpartitions"] ?? null) : null);
                    // line 123
                    echo "            ";
                } else {
                    // line 124
                    echo "                ";
                    $context["subpartitions"] = [0 => $context["partition"]];
                    // line 125
                    echo "            ";
                }
                // line 126
                echo "
            ";
                // line 127
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(($context["subpartitions"] ?? null));
                foreach ($context['_seq'] as $context["_key"] => $context["subpartition"]) {
                    // line 128
                    echo "                <tr>
                    ";
                    // line 129
                    if (((($__internal_compile_22 = ($context["partition_details"] ?? null)) && is_array($__internal_compile_22) || $__internal_compile_22 instanceof ArrayAccess ? ($__internal_compile_22["can_have_subpartitions"] ?? null) : null) && ((($__internal_compile_23 =                     // line 130
($context["partition_details"] ?? null)) && is_array($__internal_compile_23) || $__internal_compile_23 instanceof ArrayAccess ? ($__internal_compile_23["subpartition_count"] ?? null) : null) > 1))) {
                        // line 131
                        echo "                        <td>
                            <input type=\"text\" name=\"";
                        // line 132
                        echo twig_escape_filter($this->env, (($__internal_compile_24 = $context["subpartition"]) && is_array($__internal_compile_24) || $__internal_compile_24 instanceof ArrayAccess ? ($__internal_compile_24["prefix"] ?? null) : null), "html", null, true);
                        echo "[name]\"
                                value=\"";
                        // line 133
                        echo twig_escape_filter($this->env, (($__internal_compile_25 = $context["subpartition"]) && is_array($__internal_compile_25) || $__internal_compile_25 instanceof ArrayAccess ? ($__internal_compile_25["name"] ?? null) : null), "html", null, true);
                        echo "\">
                        </td>
                    ";
                    }
                    // line 136
                    echo "                    <td>
                      <select name=\"";
                    // line 137
                    echo twig_escape_filter($this->env, (($__internal_compile_26 = $context["subpartition"]) && is_array($__internal_compile_26) || $__internal_compile_26 instanceof ArrayAccess ? ($__internal_compile_26["prefix"] ?? null) : null), "html", null, true);
                    echo "[engine]\" aria-label=\"";
                    echo _gettext("Storage engine");
                    echo "\">
                        <option value=\"\"></option>
                        ";
                    // line 139
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(($context["storage_engines"] ?? null));
                    foreach ($context['_seq'] as $context["_key"] => $context["engine"]) {
                        // line 140
                        echo "                          <option value=\"";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 140), "html", null, true);
                        echo "\"";
                        if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["engine"], "comment", [], "any", false, false, false, 140))) {
                            echo " title=\"";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "comment", [], "any", false, false, false, 140), "html", null, true);
                            echo "\"";
                        }
                        // line 141
                        echo (((twig_lower_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 141)) == twig_lower_filter($this->env, (($__internal_compile_27 = $context["subpartition"]) && is_array($__internal_compile_27) || $__internal_compile_27 instanceof ArrayAccess ? ($__internal_compile_27["engine"] ?? null) : null)))) ? (" selected") : (""));
                        echo ">";
                        // line 142
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 142), "html", null, true);
                        // line 143
                        echo "</option>
                        ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['engine'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 145
                    echo "                      </select>
                    </td>
                    <td>
                        <textarea name=\"";
                    // line 148
                    echo twig_escape_filter($this->env, (($__internal_compile_28 = $context["subpartition"]) && is_array($__internal_compile_28) || $__internal_compile_28 instanceof ArrayAccess ? ($__internal_compile_28["prefix"] ?? null) : null), "html", null, true);
                    echo "[comment]\">";
                    // line 149
                    echo twig_escape_filter($this->env, (($__internal_compile_29 = $context["subpartition"]) && is_array($__internal_compile_29) || $__internal_compile_29 instanceof ArrayAccess ? ($__internal_compile_29["comment"] ?? null) : null), "html", null, true);
                    // line 150
                    echo "</textarea>
                    </td>
                    <td>
                        <input type=\"text\" name=\"";
                    // line 153
                    echo twig_escape_filter($this->env, (($__internal_compile_30 = $context["subpartition"]) && is_array($__internal_compile_30) || $__internal_compile_30 instanceof ArrayAccess ? ($__internal_compile_30["prefix"] ?? null) : null), "html", null, true);
                    echo "[data_directory]\"
                            value=\"";
                    // line 154
                    echo twig_escape_filter($this->env, (($__internal_compile_31 = $context["subpartition"]) && is_array($__internal_compile_31) || $__internal_compile_31 instanceof ArrayAccess ? ($__internal_compile_31["data_directory"] ?? null) : null), "html", null, true);
                    echo "\">
                    </td>
                    <td>
                        <input type=\"text\" name=\"";
                    // line 157
                    echo twig_escape_filter($this->env, (($__internal_compile_32 = $context["subpartition"]) && is_array($__internal_compile_32) || $__internal_compile_32 instanceof ArrayAccess ? ($__internal_compile_32["prefix"] ?? null) : null), "html", null, true);
                    echo "[index_directory]\"
                            value=\"";
                    // line 158
                    echo twig_escape_filter($this->env, (($__internal_compile_33 = $context["subpartition"]) && is_array($__internal_compile_33) || $__internal_compile_33 instanceof ArrayAccess ? ($__internal_compile_33["index_directory"] ?? null) : null), "html", null, true);
                    echo "\">
                    </td>
                    <td>
                        <input type=\"number\" name=\"";
                    // line 161
                    echo twig_escape_filter($this->env, (($__internal_compile_34 = $context["subpartition"]) && is_array($__internal_compile_34) || $__internal_compile_34 instanceof ArrayAccess ? ($__internal_compile_34["prefix"] ?? null) : null), "html", null, true);
                    echo "[max_rows]\"
                            value=\"";
                    // line 162
                    echo twig_escape_filter($this->env, (($__internal_compile_35 = $context["subpartition"]) && is_array($__internal_compile_35) || $__internal_compile_35 instanceof ArrayAccess ? ($__internal_compile_35["max_rows"] ?? null) : null), "html", null, true);
                    echo "\">
                    </td>
                    <td>
                        <input type=\"number\" min=\"0\" name=\"";
                    // line 165
                    echo twig_escape_filter($this->env, (($__internal_compile_36 = $context["subpartition"]) && is_array($__internal_compile_36) || $__internal_compile_36 instanceof ArrayAccess ? ($__internal_compile_36["prefix"] ?? null) : null), "html", null, true);
                    echo "[min_rows]\"
                            value=\"";
                    // line 166
                    echo twig_escape_filter($this->env, (($__internal_compile_37 = $context["subpartition"]) && is_array($__internal_compile_37) || $__internal_compile_37 instanceof ArrayAccess ? ($__internal_compile_37["min_rows"] ?? null) : null), "html", null, true);
                    echo "\">
                    </td>
                    <td>
                        <input type=\"text\" min=\"0\" name=\"";
                    // line 169
                    echo twig_escape_filter($this->env, (($__internal_compile_38 = $context["subpartition"]) && is_array($__internal_compile_38) || $__internal_compile_38 instanceof ArrayAccess ? ($__internal_compile_38["prefix"] ?? null) : null), "html", null, true);
                    echo "[tablespace]\"
                            value=\"";
                    // line 170
                    echo twig_escape_filter($this->env, (($__internal_compile_39 = $context["subpartition"]) && is_array($__internal_compile_39) || $__internal_compile_39 instanceof ArrayAccess ? ($__internal_compile_39["tablespace"] ?? null) : null), "html", null, true);
                    echo "\">
                    </td>
                    <td>
                        <input type=\"text\" name=\"";
                    // line 173
                    echo twig_escape_filter($this->env, (($__internal_compile_40 = $context["subpartition"]) && is_array($__internal_compile_40) || $__internal_compile_40 instanceof ArrayAccess ? ($__internal_compile_40["prefix"] ?? null) : null), "html", null, true);
                    echo "[node_group]\"
                            value=\"";
                    // line 174
                    echo twig_escape_filter($this->env, (($__internal_compile_41 = $context["subpartition"]) && is_array($__internal_compile_41) || $__internal_compile_41 instanceof ArrayAccess ? ($__internal_compile_41["node_group"] ?? null) : null), "html", null, true);
                    echo "\">
                    </td>
                </tr>
            ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['subpartition'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 178
                echo "        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['partition'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 179
            echo "    </table>
";
        }
    }

    public function getTemplateName()
    {
        return "columns_definitions/partitions.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  478 => 179,  472 => 178,  462 => 174,  458 => 173,  452 => 170,  448 => 169,  442 => 166,  438 => 165,  432 => 162,  428 => 161,  422 => 158,  418 => 157,  412 => 154,  408 => 153,  403 => 150,  401 => 149,  398 => 148,  393 => 145,  386 => 143,  384 => 142,  381 => 141,  372 => 140,  368 => 139,  361 => 137,  358 => 136,  352 => 133,  348 => 132,  345 => 131,  343 => 130,  342 => 129,  339 => 128,  335 => 127,  332 => 126,  329 => 125,  326 => 124,  323 => 123,  320 => 122,  318 => 121,  314 => 119,  308 => 116,  304 => 115,  300 => 113,  291 => 110,  288 => 109,  285 => 108,  283 => 107,  279 => 106,  275 => 105,  271 => 104,  265 => 102,  263 => 101,  258 => 99,  254 => 98,  250 => 97,  247 => 96,  245 => 95,  243 => 94,  239 => 93,  234 => 91,  230 => 90,  226 => 89,  222 => 88,  218 => 87,  214 => 86,  210 => 85,  205 => 84,  199 => 82,  197 => 81,  195 => 80,  189 => 78,  187 => 77,  183 => 76,  179 => 74,  177 => 73,  174 => 72,  167 => 68,  161 => 65,  154 => 61,  150 => 60,  144 => 56,  135 => 53,  132 => 52,  129 => 51,  127 => 50,  123 => 49,  119 => 48,  113 => 45,  110 => 44,  108 => 43,  102 => 40,  96 => 37,  89 => 33,  85 => 32,  79 => 28,  70 => 25,  67 => 24,  64 => 23,  62 => 22,  58 => 21,  54 => 20,  48 => 17,  43 => 14,  41 => 13,  39 => 12,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "columns_definitions/partitions.twig", "/var/www/html/phpMyAdmin/templates/columns_definitions/partitions.twig");
    }
}
