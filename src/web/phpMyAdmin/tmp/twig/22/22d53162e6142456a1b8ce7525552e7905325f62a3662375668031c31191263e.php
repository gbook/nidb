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

/* database/designer/database_tables.twig */
class __TwigTemplate_17aa19045fd027a2def87589f0ff67fd906d3bb56649d525b1d530f3664afb7d extends \Twig\Template
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
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["tables"] ?? null));
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["designerTable"]) {
            // line 2
            echo "    ";
            $context["i"] = twig_get_attribute($this->env, $this->source, $context["loop"], "index0", [], "any", false, false, false, 2);
            // line 3
            echo "    ";
            $context["t_n_url"] = twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getDbTableString", [], "method", false, false, false, 3), "url");
            // line 4
            echo "    ";
            $context["db"] = twig_get_attribute($this->env, $this->source, $context["designerTable"], "getDatabaseName", [], "method", false, false, false, 4);
            // line 5
            echo "    ";
            $context["db_url"] = twig_escape_filter($this->env, ($context["db"] ?? null), "url");
            // line 6
            echo "    ";
            $context["t_n"] = twig_get_attribute($this->env, $this->source, $context["designerTable"], "getDbTableString", [], "method", false, false, false, 6);
            // line 7
            echo "    ";
            $context["table_name"] = twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getTableName", [], "method", false, false, false, 7), "html");
            // line 8
            echo "    <input name=\"t_x[";
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "]\" type=\"hidden\" id=\"t_x_";
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "_\" />
    <input name=\"t_y[";
            // line 9
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "]\" type=\"hidden\" id=\"t_y_";
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "_\" />
    <input name=\"t_v[";
            // line 10
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "]\" type=\"hidden\" id=\"t_v_";
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "_\" />
    <input name=\"t_h[";
            // line 11
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "]\" type=\"hidden\" id=\"t_h_";
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "_\" />
    <table id=\"";
            // line 12
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "\"
        db_url=\"";
            // line 13
            echo twig_escape_filter($this->env, twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getDatabaseName", [], "method", false, false, false, 13), "url"), "html", null, true);
            echo "\"
        table_name_url=\"";
            // line 14
            echo twig_escape_filter($this->env, twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getTableName", [], "method", false, false, false, 14), "url"), "html", null, true);
            echo "\"
        cellpadding=\"0\"
        cellspacing=\"0\"
        class=\"pma-table designer_tab\"
        style=\"position:absolute; ";
            // line 18
            echo (((($context["text_dir"] ?? null) == "rtl")) ? ("right") : ("left"));
            echo ":";
            // line 19
            echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, ($context["tab_pos"] ?? null), ($context["t_n"] ?? null), [], "array", true, true, false, 19)) ? ((($__internal_compile_0 = (($__internal_compile_1 = ($context["tab_pos"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["X"] ?? null) : null)) : (twig_random($this->env, range(20, 700)))), "html", null, true);
            echo "px; top:";
            // line 20
            echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, ($context["tab_pos"] ?? null), ($context["t_n"] ?? null), [], "array", true, true, false, 20)) ? ((($__internal_compile_2 = (($__internal_compile_3 = ($context["tab_pos"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["Y"] ?? null) : null)) : (twig_random($this->env, range(20, 550)))), "html", null, true);
            echo "px; display:";
            // line 21
            echo (((twig_get_attribute($this->env, $this->source, ($context["tab_pos"] ?? null), ($context["t_n"] ?? null), [], "array", true, true, false, 21) || (($context["display_page"] ?? null) ==  -1))) ? ("block") : ("none"));
            echo "; z-index: 1;\"> <!--\"-->
        <thead>
            <tr class=\"header\">
                ";
            // line 24
            if (($context["has_query"] ?? null)) {
                // line 25
                echo "                    <td class=\"select_all\">
                        <input class=\"select_all_1\"
                            type=\"checkbox\"
                            style=\"margin: 0;\"
                            value=\"select_all_";
                // line 29
                echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
                echo "\"
                            id=\"select_all_";
                // line 30
                echo twig_escape_filter($this->env, ($context["i"] ?? null), "html", null, true);
                echo "\"
                            title=\"";
                // line 31
                echo _gettext("Select all");
                echo "\"
                            table_name=\"";
                // line 32
                echo twig_escape_filter($this->env, ($context["table_name"] ?? null), "html", null, true);
                echo "\"
                            db_name=\"";
                // line 33
                echo twig_escape_filter($this->env, ($context["db"] ?? null), "html", null, true);
                echo "\">
                    </td>
                ";
            }
            // line 36
            echo "                <td class=\"small_tab\"
                    title=\"";
            // line 37
            echo _gettext("Show/hide columns");
            echo "\"
                    id=\"id_hide_tbody_";
            // line 38
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "\"
                    table_name=\"";
            // line 39
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "\">";
            echo ((( !twig_get_attribute($this->env, $this->source, ($context["tab_pos"] ?? null), ($context["t_n"] ?? null), [], "array", true, true, false, 39) ||  !twig_test_empty((($__internal_compile_4 = (($__internal_compile_5 = ($context["tab_pos"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["V"] ?? null) : null)))) ? ("v") : ("&gt;"));
            echo "</td>
                <td class=\"small_tab_pref small_tab_pref_1\"
                    db=\"";
            // line 41
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getDatabaseName", [], "method", false, false, false, 41), "html", null, true);
            echo "\"
                    db_url=\"";
            // line 42
            echo twig_escape_filter($this->env, twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getDatabaseName", [], "method", false, false, false, 42), "url"), "html", null, true);
            echo "\"
                    table_name=\"";
            // line 43
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getTableName", [], "method", false, false, false, 43), "html", null, true);
            echo "\"
                    table_name_url=\"";
            // line 44
            echo twig_escape_filter($this->env, twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getTableName", [], "method", false, false, false, 44), "url"), "html", null, true);
            echo "\">
                    <img src=\"";
            // line 45
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/exec_small.png"], "method", false, false, false, 45), "html", null, true);
            echo "\"
                        title=\"";
            // line 46
            echo _gettext("See table structure");
            echo "\">
                </td>
                <td id=\"id_zag_";
            // line 48
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "\"
                    class=\"tab_zag nowrap tab_zag_noquery\"
                    table_name=\"";
            // line 50
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "\"
                    query_set=\"";
            // line 51
            echo ((($context["has_query"] ?? null)) ? (1) : (0));
            echo "\">
                    <span class=\"owner\">";
            // line 52
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getDatabaseName", [], "method", false, false, false, 52), "html", null, true);
            echo "</span>
                    ";
            // line 53
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getTableName", [], "method", false, false, false, 53), "html", null, true);
            echo "
                </td>
                ";
            // line 55
            if (($context["has_query"] ?? null)) {
                // line 56
                echo "                    <td class=\"tab_zag tab_zag_query\"
                        id=\"id_zag_";
                // line 57
                echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
                echo "_2\"
                        table_name=\"";
                // line 58
                echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
                echo "\">
                    </td>
               ";
            }
            // line 61
            echo "            </tr>
        </thead>
        <tbody id=\"id_tbody_";
            // line 63
            echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
            echo "\"";
            // line 64
            echo (((twig_get_attribute($this->env, $this->source, ($context["tab_pos"] ?? null), ($context["t_n"] ?? null), [], "array", true, true, false, 64) && twig_test_empty((($__internal_compile_6 = (($__internal_compile_7 = ($context["tab_pos"] ?? null)) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6["V"] ?? null) : null)))) ? (" style=\"display: none\"") : (""));
            echo ">
            ";
            // line 65
            $context["display_field"] = twig_get_attribute($this->env, $this->source, $context["designerTable"], "getDisplayField", [], "method", false, false, false, 65);
            // line 66
            echo "            ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(range(0, (twig_length_filter($this->env, (($__internal_compile_8 = (($__internal_compile_9 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_9) || $__internal_compile_9 instanceof ArrayAccess ? ($__internal_compile_9[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_8) || $__internal_compile_8 instanceof ArrayAccess ? ($__internal_compile_8["COLUMN_ID"] ?? null) : null)) - 1)));
            foreach ($context['_seq'] as $context["_key"] => $context["j"]) {
                // line 67
                echo "                ";
                $context["col_name"] = (($__internal_compile_10 = (($__internal_compile_11 = (($__internal_compile_12 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_12) || $__internal_compile_12 instanceof ArrayAccess ? ($__internal_compile_12[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_11) || $__internal_compile_11 instanceof ArrayAccess ? ($__internal_compile_11["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_10) || $__internal_compile_10 instanceof ArrayAccess ? ($__internal_compile_10[$context["j"]] ?? null) : null);
                // line 68
                echo "                ";
                $context["tmp_column"] = ((($context["t_n"] ?? null) . ".") . (($__internal_compile_13 = (($__internal_compile_14 = (($__internal_compile_15 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_15) || $__internal_compile_15 instanceof ArrayAccess ? ($__internal_compile_15[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_14) || $__internal_compile_14 instanceof ArrayAccess ? ($__internal_compile_14["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_13) || $__internal_compile_13 instanceof ArrayAccess ? ($__internal_compile_13[$context["j"]] ?? null) : null));
                // line 69
                echo "                ";
                $context["click_field_param"] = [0 => twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source,                 // line 70
$context["designerTable"], "getTableName", [], "method", false, false, false, 70), "url"), 1 => twig_urlencode_filter((($__internal_compile_16 = (($__internal_compile_17 = (($__internal_compile_18 =                 // line 71
($context["tab_column"] ?? null)) && is_array($__internal_compile_18) || $__internal_compile_18 instanceof ArrayAccess ? ($__internal_compile_18[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_17) || $__internal_compile_17 instanceof ArrayAccess ? ($__internal_compile_17["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_16) || $__internal_compile_16 instanceof ArrayAccess ? ($__internal_compile_16[$context["j"]] ?? null) : null))];
                // line 73
                echo "                ";
                if ( !twig_get_attribute($this->env, $this->source, $context["designerTable"], "supportsForeignkeys", [], "method", false, false, false, 73)) {
                    // line 74
                    echo "                    ";
                    $context["click_field_param"] = twig_array_merge(($context["click_field_param"] ?? null), [0 => ((twig_get_attribute($this->env, $this->source, ($context["tables_pk_or_unique_keys"] ?? null), ($context["tmp_column"] ?? null), [], "array", true, true, false, 74)) ? (1) : (0))]);
                    // line 75
                    echo "                ";
                } else {
                    // line 76
                    echo "                    ";
                    // line 78
                    echo "                    ";
                    $context["click_field_param"] = twig_array_merge(($context["click_field_param"] ?? null), [0 => ((twig_get_attribute($this->env, $this->source, ($context["tables_all_keys"] ?? null), ($context["tmp_column"] ?? null), [], "array", true, true, false, 78)) ? (1) : (0))]);
                    // line 79
                    echo "                ";
                }
                // line 80
                echo "                ";
                $context["click_field_param"] = twig_array_merge(($context["click_field_param"] ?? null), [0 => ($context["db"] ?? null)]);
                // line 81
                echo "                <tr id=\"id_tr_";
                echo twig_escape_filter($this->env, twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["designerTable"], "getTableName", [], "method", false, false, false, 81), "url"), "html", null, true);
                echo ".";
                echo twig_escape_filter($this->env, (($__internal_compile_19 = (($__internal_compile_20 = (($__internal_compile_21 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_21) || $__internal_compile_21 instanceof ArrayAccess ? ($__internal_compile_21[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_20) || $__internal_compile_20 instanceof ArrayAccess ? ($__internal_compile_20["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_19) || $__internal_compile_19 instanceof ArrayAccess ? ($__internal_compile_19[$context["j"]] ?? null) : null), "html", null, true);
                echo "\" class=\"tab_field";
                // line 82
                echo (((($context["display_field"] ?? null) == (($__internal_compile_22 = (($__internal_compile_23 = (($__internal_compile_24 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_24) || $__internal_compile_24 instanceof ArrayAccess ? ($__internal_compile_24[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_23) || $__internal_compile_23 instanceof ArrayAccess ? ($__internal_compile_23["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_22) || $__internal_compile_22 instanceof ArrayAccess ? ($__internal_compile_22[$context["j"]] ?? null) : null))) ? ("_3") : (""));
                echo "\" click_field_param=\"";
                // line 83
                echo twig_escape_filter($this->env, twig_join_filter(($context["click_field_param"] ?? null), ","), "html", null, true);
                echo "\">
                    ";
                // line 84
                if (($context["has_query"] ?? null)) {
                    // line 85
                    echo "                        <td class=\"select_all\">
                            <input class=\"select_all_store_col\"
                                value=\"";
                    // line 87
                    echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
                    echo twig_escape_filter($this->env, twig_urlencode_filter((($__internal_compile_25 = (($__internal_compile_26 = (($__internal_compile_27 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_27) || $__internal_compile_27 instanceof ArrayAccess ? ($__internal_compile_27[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_26) || $__internal_compile_26 instanceof ArrayAccess ? ($__internal_compile_26["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_25) || $__internal_compile_25 instanceof ArrayAccess ? ($__internal_compile_25[$context["j"]] ?? null) : null)), "html", null, true);
                    echo "\"
                                type=\"checkbox\"
                                id=\"select_";
                    // line 89
                    echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
                    echo "._";
                    echo twig_escape_filter($this->env, twig_urlencode_filter((($__internal_compile_28 = (($__internal_compile_29 = (($__internal_compile_30 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_30) || $__internal_compile_30 instanceof ArrayAccess ? ($__internal_compile_30[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_29) || $__internal_compile_29 instanceof ArrayAccess ? ($__internal_compile_29["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_28) || $__internal_compile_28 instanceof ArrayAccess ? ($__internal_compile_28[$context["j"]] ?? null) : null)), "html", null, true);
                    echo "\"
                                style=\"margin: 0;\"
                                title=\"";
                    // line 91
                    echo twig_escape_filter($this->env, twig_sprintf(_gettext("Select \"%s\""), ($context["col_name"] ?? null)), "html", null, true);
                    echo "\"
                                id_check_all=\"select_all_";
                    // line 92
                    echo twig_escape_filter($this->env, ($context["i"] ?? null), "html", null, true);
                    echo "\"
                                db_name=\"";
                    // line 93
                    echo twig_escape_filter($this->env, ($context["db"] ?? null), "html", null, true);
                    echo "\"
                                table_name=\"";
                    // line 94
                    echo twig_escape_filter($this->env, ($context["table_name"] ?? null), "html", null, true);
                    echo "\"
                                col_name=\"";
                    // line 95
                    echo twig_escape_filter($this->env, ($context["col_name"] ?? null), "html", null, true);
                    echo "\">
                        </td>
                    ";
                }
                // line 98
                echo "                    <td width=\"10px\" colspan=\"3\" id=\"";
                echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
                echo ".";
                // line 99
                echo twig_escape_filter($this->env, twig_urlencode_filter((($__internal_compile_31 = (($__internal_compile_32 = (($__internal_compile_33 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_33) || $__internal_compile_33 instanceof ArrayAccess ? ($__internal_compile_33[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_32) || $__internal_compile_32 instanceof ArrayAccess ? ($__internal_compile_32["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_31) || $__internal_compile_31 instanceof ArrayAccess ? ($__internal_compile_31[$context["j"]] ?? null) : null)), "html", null, true);
                echo "\">
                        <div class=\"nowrap\">
                            ";
                // line 101
                $context["type"] = (($__internal_compile_34 = ($context["columns_type"] ?? null)) && is_array($__internal_compile_34) || $__internal_compile_34 instanceof ArrayAccess ? ($__internal_compile_34[((($context["t_n"] ?? null) . ".") . (($__internal_compile_35 = (($__internal_compile_36 = (($__internal_compile_37 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_37) || $__internal_compile_37 instanceof ArrayAccess ? ($__internal_compile_37[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_36) || $__internal_compile_36 instanceof ArrayAccess ? ($__internal_compile_36["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_35) || $__internal_compile_35 instanceof ArrayAccess ? ($__internal_compile_35[$context["j"]] ?? null) : null))] ?? null) : null);
                // line 102
                echo "                            <img src=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => ($context["type"] ?? null)], "method", false, false, false, 102), "html", null, true);
                echo ".png\" alt=\"*\">
                            ";
                // line 103
                echo twig_escape_filter($this->env, (($__internal_compile_38 = (($__internal_compile_39 = (($__internal_compile_40 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_40) || $__internal_compile_40 instanceof ArrayAccess ? ($__internal_compile_40[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_39) || $__internal_compile_39 instanceof ArrayAccess ? ($__internal_compile_39["COLUMN_NAME"] ?? null) : null)) && is_array($__internal_compile_38) || $__internal_compile_38 instanceof ArrayAccess ? ($__internal_compile_38[$context["j"]] ?? null) : null), "html", null, true);
                echo " : ";
                echo twig_escape_filter($this->env, (($__internal_compile_41 = (($__internal_compile_42 = (($__internal_compile_43 = ($context["tab_column"] ?? null)) && is_array($__internal_compile_43) || $__internal_compile_43 instanceof ArrayAccess ? ($__internal_compile_43[($context["t_n"] ?? null)] ?? null) : null)) && is_array($__internal_compile_42) || $__internal_compile_42 instanceof ArrayAccess ? ($__internal_compile_42["TYPE"] ?? null) : null)) && is_array($__internal_compile_41) || $__internal_compile_41 instanceof ArrayAccess ? ($__internal_compile_41[$context["j"]] ?? null) : null), "html", null, true);
                echo "
                        </div>
                    </td>
                    ";
                // line 106
                if (($context["has_query"] ?? null)) {
                    // line 107
                    echo "                        <td class=\"small_tab_pref small_tab_pref_click_opt\"
                            ";
                    // line 109
                    echo "                            option_col_name_modal=\"<strong>";
                    echo twig_escape_filter($this->env, twig_escape_filter($this->env, twig_sprintf(_gettext("Add an option for column \"%s\"."), ($context["col_name"] ?? null)), "html"), "html");
                    echo "</strong>\"
                            db_name=\"";
                    // line 110
                    echo twig_escape_filter($this->env, ($context["db"] ?? null), "html", null, true);
                    echo "\"
                            table_name=\"";
                    // line 111
                    echo twig_escape_filter($this->env, ($context["table_name"] ?? null), "html", null, true);
                    echo "\"
                            col_name=\"";
                    // line 112
                    echo twig_escape_filter($this->env, ($context["col_name"] ?? null), "html", null, true);
                    echo "\"
                            db_table_name_url=\"";
                    // line 113
                    echo twig_escape_filter($this->env, ($context["t_n_url"] ?? null), "html", null, true);
                    echo "\">
                            <img src=\"";
                    // line 114
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/exec_small.png"], "method", false, false, false, 114), "html", null, true);
                    echo "\" title=\"";
                    echo _gettext("Options");
                    echo "\" />
                        </td>
                    ";
                }
                // line 117
                echo "                </tr>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['j'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 119
            echo "        </tbody>
    </table>
";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['designerTable'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
    }

    public function getTemplateName()
    {
        return "database/designer/database_tables.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  405 => 119,  398 => 117,  390 => 114,  386 => 113,  382 => 112,  378 => 111,  374 => 110,  369 => 109,  366 => 107,  364 => 106,  356 => 103,  351 => 102,  349 => 101,  344 => 99,  340 => 98,  334 => 95,  330 => 94,  326 => 93,  322 => 92,  318 => 91,  311 => 89,  305 => 87,  301 => 85,  299 => 84,  295 => 83,  292 => 82,  286 => 81,  283 => 80,  280 => 79,  277 => 78,  275 => 76,  272 => 75,  269 => 74,  266 => 73,  264 => 71,  263 => 70,  261 => 69,  258 => 68,  255 => 67,  250 => 66,  248 => 65,  244 => 64,  241 => 63,  237 => 61,  231 => 58,  227 => 57,  224 => 56,  222 => 55,  217 => 53,  213 => 52,  209 => 51,  205 => 50,  200 => 48,  195 => 46,  191 => 45,  187 => 44,  183 => 43,  179 => 42,  175 => 41,  168 => 39,  164 => 38,  160 => 37,  157 => 36,  151 => 33,  147 => 32,  143 => 31,  139 => 30,  135 => 29,  129 => 25,  127 => 24,  121 => 21,  118 => 20,  115 => 19,  112 => 18,  105 => 14,  101 => 13,  97 => 12,  91 => 11,  85 => 10,  79 => 9,  72 => 8,  69 => 7,  66 => 6,  63 => 5,  60 => 4,  57 => 3,  54 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/designer/database_tables.twig", "/var/www/html/phpMyAdmin/templates/database/designer/database_tables.twig");
    }
}
