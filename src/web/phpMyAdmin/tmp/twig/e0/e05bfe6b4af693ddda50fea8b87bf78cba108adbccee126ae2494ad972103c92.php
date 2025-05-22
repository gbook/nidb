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

/* table/structure/display_structure.twig */
class __TwigTemplate_3e83df1dac642521a53894372a41f10e4fd5a8298112f9a6090796c57819691e extends \Twig\Template
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
        $this->parent = $this->loadTemplate("table/page_with_secondary_tabs.twig", "table/structure/display_structure.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 2
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 3
        echo "<h1 class=\"d-none d-print-block\">";
        echo twig_escape_filter($this->env, ($context["table"] ?? null), "html", null, true);
        echo "</h1>
<form method=\"post\" action=\"";
        // line 4
        echo PhpMyAdmin\Url::getFromRoute("/table/structure");
        echo "\" name=\"fieldsForm\" id=\"fieldsForm\"
    class=\"ajax";
        // line 5
        echo ((($context["hide_structure_actions"] ?? null)) ? (" HideStructureActions") : (""));
        echo "\">
    ";
        // line 6
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
    <input type=\"hidden\" name=\"table_type\" value=";
        // line 8
        if (($context["db_is_system_schema"] ?? null)) {
            // line 9
            echo "\"information_schema\"";
        } elseif (        // line 10
($context["tbl_is_view"] ?? null)) {
            // line 11
            echo "\"view\"";
        } else {
            // line 13
            echo "\"table\"";
        }
        // line 14
        echo ">
    <div class=\"table-responsive-md\">
    <table id=\"tablestructure\" class=\"table table-light table-striped table-hover w-auto\">
        ";
        // line 18
        echo "        <thead class=\"thead-light\">
            <tr>
                <th class=\"print_ignore\"></th>
                <th>#</th>
                <th>";
        // line 22
        echo _gettext("Name");
        echo "</th>
                <th>";
        // line 23
        echo _gettext("Type");
        echo "</th>
                <th>";
        // line 24
        echo _gettext("Collation");
        echo "</th>
                <th>";
        // line 25
        echo _gettext("Attributes");
        echo "</th>
                <th>";
        // line 26
        echo _gettext("Null");
        echo "</th>
                <th>";
        // line 27
        echo _gettext("Default");
        echo "</th>
                ";
        // line 28
        if (($context["show_column_comments"] ?? null)) {
            // line 29
            echo "<th>";
            echo _gettext("Comments");
            echo "</th>";
        }
        // line 31
        echo "                <th>";
        echo _gettext("Extra");
        echo "</th>
                ";
        // line 33
        echo "                ";
        if (( !($context["db_is_system_schema"] ?? null) &&  !($context["tbl_is_view"] ?? null))) {
            // line 34
            echo "                    <th colspan=\"";
            echo ((PhpMyAdmin\Util::showIcons("ActionLinksMode")) ? ("8") : ("9"));
            // line 35
            echo "\" class=\"action print_ignore\">";
            echo _gettext("Action");
            echo "</th>
                ";
        }
        // line 37
        echo "            </tr>
        </thead>
        <tbody>
        ";
        // line 41
        echo "        ";
        $context["rownum"] = 0;
        // line 42
        echo "        ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["fields"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 43
            echo "            ";
            $context["rownum"] = (($context["rownum"] ?? null) + 1);
            // line 44
            echo "
            ";
            // line 45
            $context["extracted_columnspec"] = (($__internal_compile_0 = ($context["extracted_columnspecs"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[($context["rownum"] ?? null)] ?? null) : null);
            // line 46
            echo "            ";
            $context["field_name"] = twig_escape_filter($this->env, (($__internal_compile_1 = $context["row"]) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["Field"] ?? null) : null));
            // line 47
            echo "            ";
            // line 48
            echo "            ";
            $context["comments"] = (($__internal_compile_2 = ($context["row_comments"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2[($context["rownum"] ?? null)] ?? null) : null);
            // line 49
            echo "            ";
            // line 50
            echo "
        <tr>
            <td class=\"text-center print_ignore\">
                <input type=\"checkbox\" class=\"checkall\" name=\"selected_fld[]\" value=\"";
            // line 53
            echo twig_escape_filter($this->env, (($__internal_compile_3 = $context["row"]) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["Field"] ?? null) : null), "html", null, true);
            echo "\" id=\"checkbox_row_";
            echo twig_escape_filter($this->env, ($context["rownum"] ?? null), "html", null, true);
            echo "\">
            </td>
            <td class=\"text-right\">";
            // line 55
            echo twig_escape_filter($this->env, ($context["rownum"] ?? null), "html", null, true);
            echo "</td>
            <th class=\"nowrap\">
                <label for=\"checkbox_row_";
            // line 57
            echo twig_escape_filter($this->env, ($context["rownum"] ?? null), "html", null, true);
            echo "\">
                    ";
            // line 58
            if (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["displayed_fields"] ?? null), ($context["rownum"] ?? null), [], "array", false, true, false, 58), "comment", [], "any", true, true, false, 58)) {
                // line 59
                echo "                        <span class=\"commented_column\" title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (($__internal_compile_4 = ($context["displayed_fields"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4[($context["rownum"] ?? null)] ?? null) : null), "comment", [], "any", false, false, false, 59), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (($__internal_compile_5 = ($context["displayed_fields"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5[($context["rownum"] ?? null)] ?? null) : null), "text", [], "any", false, false, false, 59), "html", null, true);
                echo "</span>
                    ";
            } else {
                // line 61
                echo "                        ";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (($__internal_compile_6 = ($context["displayed_fields"] ?? null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6[($context["rownum"] ?? null)] ?? null) : null), "text", [], "any", false, false, false, 61), "html", null, true);
                echo "
                    ";
            }
            // line 63
            echo "                    ";
            echo twig_get_attribute($this->env, $this->source, (($__internal_compile_7 = ($context["displayed_fields"] ?? null)) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7[($context["rownum"] ?? null)] ?? null) : null), "icon", [], "any", false, false, false, 63);
            echo "
                </label>
            </th>
            <td";
            // line 66
            echo (((("set" != (($__internal_compile_8 = ($context["extracted_columnspec"] ?? null)) && is_array($__internal_compile_8) || $__internal_compile_8 instanceof ArrayAccess ? ($__internal_compile_8["type"] ?? null) : null)) && ("enum" != (($__internal_compile_9 = ($context["extracted_columnspec"] ?? null)) && is_array($__internal_compile_9) || $__internal_compile_9 instanceof ArrayAccess ? ($__internal_compile_9["type"] ?? null) : null)))) ? (" class=\"nowrap\"") : (""));
            echo ">
                <bdo dir=\"ltr\" lang=\"en\">
                    ";
            // line 68
            echo (($__internal_compile_10 = ($context["extracted_columnspec"] ?? null)) && is_array($__internal_compile_10) || $__internal_compile_10 instanceof ArrayAccess ? ($__internal_compile_10["displayed_type"] ?? null) : null);
            echo "
                    ";
            // line 69
            if ((((($context["relation_commwork"] ?? null) && ($context["relation_mimework"] ?? null)) && ($context["browse_mime"] ?? null)) && twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source,             // line 70
($context["mime_map"] ?? null), (($__internal_compile_11 = $context["row"]) && is_array($__internal_compile_11) || $__internal_compile_11 instanceof ArrayAccess ? ($__internal_compile_11["Field"] ?? null) : null), [], "array", false, true, false, 70), "mimetype", [], "array", true, true, false, 70))) {
                // line 71
                echo "                        <br>";
                echo _gettext("Media type:");
                echo " ";
                echo twig_escape_filter($this->env, twig_lower_filter($this->env, twig_replace_filter((($__internal_compile_12 = (($__internal_compile_13 = ($context["mime_map"] ?? null)) && is_array($__internal_compile_13) || $__internal_compile_13 instanceof ArrayAccess ? ($__internal_compile_13[(($__internal_compile_14 = $context["row"]) && is_array($__internal_compile_14) || $__internal_compile_14 instanceof ArrayAccess ? ($__internal_compile_14["Field"] ?? null) : null)] ?? null) : null)) && is_array($__internal_compile_12) || $__internal_compile_12 instanceof ArrayAccess ? ($__internal_compile_12["mimetype"] ?? null) : null), ["_" => "/"])), "html", null, true);
                echo "
                    ";
            }
            // line 73
            echo "                </bdo>
            </td>
            <td>
            ";
            // line 76
            if ( !twig_test_empty((($__internal_compile_15 = $context["row"]) && is_array($__internal_compile_15) || $__internal_compile_15 instanceof ArrayAccess ? ($__internal_compile_15["Collation"] ?? null) : null))) {
                // line 77
                echo "                <dfn title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (($__internal_compile_16 = ($context["collations"] ?? null)) && is_array($__internal_compile_16) || $__internal_compile_16 instanceof ArrayAccess ? ($__internal_compile_16[(($__internal_compile_17 = $context["row"]) && is_array($__internal_compile_17) || $__internal_compile_17 instanceof ArrayAccess ? ($__internal_compile_17["Collation"] ?? null) : null)] ?? null) : null), "description", [], "any", false, false, false, 77), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (($__internal_compile_18 = ($context["collations"] ?? null)) && is_array($__internal_compile_18) || $__internal_compile_18 instanceof ArrayAccess ? ($__internal_compile_18[(($__internal_compile_19 = $context["row"]) && is_array($__internal_compile_19) || $__internal_compile_19 instanceof ArrayAccess ? ($__internal_compile_19["Collation"] ?? null) : null)] ?? null) : null), "name", [], "any", false, false, false, 77), "html", null, true);
                echo "</dfn>
            ";
            }
            // line 79
            echo "            </td>
            <td class=\"column_attribute nowrap\">";
            // line 80
            echo twig_escape_filter($this->env, (($__internal_compile_20 = ($context["attributes"] ?? null)) && is_array($__internal_compile_20) || $__internal_compile_20 instanceof ArrayAccess ? ($__internal_compile_20[($context["rownum"] ?? null)] ?? null) : null), "html", null, true);
            echo "</td>
            <td>";
            // line 81
            echo twig_escape_filter($this->env, ((((($__internal_compile_21 = $context["row"]) && is_array($__internal_compile_21) || $__internal_compile_21 instanceof ArrayAccess ? ($__internal_compile_21["Null"] ?? null) : null) == "YES")) ? (_gettext("Yes")) : (_gettext("No"))), "html", null, true);
            echo "</td>
            <td class=\"nowrap\">
                ";
            // line 83
            if ( !(null === (($__internal_compile_22 = $context["row"]) && is_array($__internal_compile_22) || $__internal_compile_22 instanceof ArrayAccess ? ($__internal_compile_22["Default"] ?? null) : null))) {
                // line 84
                echo "                    ";
                if (((($__internal_compile_23 = ($context["extracted_columnspec"] ?? null)) && is_array($__internal_compile_23) || $__internal_compile_23 instanceof ArrayAccess ? ($__internal_compile_23["type"] ?? null) : null) == "bit")) {
                    // line 85
                    echo "                        ";
                    echo twig_escape_filter($this->env, PhpMyAdmin\Util::convertBitDefaultValue((($__internal_compile_24 = $context["row"]) && is_array($__internal_compile_24) || $__internal_compile_24 instanceof ArrayAccess ? ($__internal_compile_24["Default"] ?? null) : null)), "html", null, true);
                    echo "
                    ";
                } else {
                    // line 87
                    echo "                        ";
                    echo twig_escape_filter($this->env, (($__internal_compile_25 = $context["row"]) && is_array($__internal_compile_25) || $__internal_compile_25 instanceof ArrayAccess ? ($__internal_compile_25["Default"] ?? null) : null), "html", null, true);
                    echo "
                    ";
                }
                // line 89
                echo "                ";
            } elseif (((($__internal_compile_26 = $context["row"]) && is_array($__internal_compile_26) || $__internal_compile_26 instanceof ArrayAccess ? ($__internal_compile_26["Null"] ?? null) : null) == "YES")) {
                // line 90
                echo "                    <em>NULL</em>
                ";
            } else {
                // line 92
                echo "                    <em>";
                echo _pgettext(                "None for default", "None");
                echo "</em>
                ";
            }
            // line 94
            echo "            </td>
            ";
            // line 95
            if (($context["show_column_comments"] ?? null)) {
                // line 96
                echo "                <td>
                    ";
                // line 97
                echo twig_escape_filter($this->env, ($context["comments"] ?? null), "html", null, true);
                echo "
                </td>
            ";
            }
            // line 100
            echo "            <td class=\"nowrap\">";
            echo twig_escape_filter($this->env, twig_upper_filter($this->env, (($__internal_compile_27 = $context["row"]) && is_array($__internal_compile_27) || $__internal_compile_27 instanceof ArrayAccess ? ($__internal_compile_27["Extra"] ?? null) : null)), "html", null, true);
            echo "</td>
            ";
            // line 101
            if (( !($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null))) {
                // line 102
                echo "                <td class=\"edit text-center print_ignore\">
                    <a class=\"change_column_anchor ajax\" href=\"";
                // line 103
                echo PhpMyAdmin\Url::getFromRoute("/table/structure/change", ["db" =>                 // line 104
($context["db"] ?? null), "table" =>                 // line 105
($context["table"] ?? null), "field" => (($__internal_compile_28 =                 // line 106
$context["row"]) && is_array($__internal_compile_28) || $__internal_compile_28 instanceof ArrayAccess ? ($__internal_compile_28["Field"] ?? null) : null), "change_column" => 1]);
                // line 108
                echo "\">
                      ";
                // line 109
                echo \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Change"));
                echo "
                    </a>
                </td>
                <td class=\"drop text-center print_ignore\">
                    <a class=\"drop_column_anchor ajax\" href=\"";
                // line 113
                echo PhpMyAdmin\Url::getFromRoute("/sql");
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(["db" =>                 // line 114
($context["db"] ?? null), "table" =>                 // line 115
($context["table"] ?? null), "sql_query" => (((("ALTER TABLE " . PhpMyAdmin\Util::backquote(                // line 116
($context["table"] ?? null))) . " DROP ") . PhpMyAdmin\Util::backquote((($__internal_compile_29 = $context["row"]) && is_array($__internal_compile_29) || $__internal_compile_29 instanceof ArrayAccess ? ($__internal_compile_29["Field"] ?? null) : null))) . ";"), "dropped_column" => (($__internal_compile_30 =                 // line 117
$context["row"]) && is_array($__internal_compile_30) || $__internal_compile_30 instanceof ArrayAccess ? ($__internal_compile_30["Field"] ?? null) : null), "purge" => true, "message_to_show" => twig_sprintf(_gettext("Column %s has been dropped."), twig_escape_filter($this->env, (($__internal_compile_31 =                 // line 119
$context["row"]) && is_array($__internal_compile_31) || $__internal_compile_31 instanceof ArrayAccess ? ($__internal_compile_31["Field"] ?? null) : null)))], "", false);
                // line 120
                echo "\">
                      ";
                // line 121
                echo \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop"));
                echo "
                    </a>
                </td>
            ";
            }
            // line 125
            echo "
            ";
            // line 126
            if (( !($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null))) {
                // line 127
                echo "                ";
                $context["type"] = (( !twig_test_empty((($__internal_compile_32 = ($context["extracted_columnspec"] ?? null)) && is_array($__internal_compile_32) || $__internal_compile_32 instanceof ArrayAccess ? ($__internal_compile_32["print_type"] ?? null) : null))) ? ((($__internal_compile_33 = ($context["extracted_columnspec"] ?? null)) && is_array($__internal_compile_33) || $__internal_compile_33 instanceof ArrayAccess ? ($__internal_compile_33["print_type"] ?? null) : null)) : (""));
                // line 128
                echo "                <td class=\"print_ignore\">
                    <ul class=\"table-structure-actions resizable-menu\">
                        ";
                // line 130
                if (($context["hide_structure_actions"] ?? null)) {
                    // line 131
                    echo "                            <li class=\"submenu shown\">
                                <a href=\"#\" class=\"tab nowrap\">";
                    // line 132
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_more", _gettext("More"));
                    echo "</a>
                                <ul>
                        ";
                }
                // line 135
                echo "
                        <li class=\"primary nowrap\">
                          ";
                // line 137
                if (((((($context["type"] ?? null) == "text") || (($context["type"] ?? null) == "blob")) || (($context["tbl_storage_engine"] ?? null) == "ARCHIVE")) || (($context["primary"] ?? null) && twig_get_attribute($this->env, $this->source, ($context["primary"] ?? null), "hasColumn", [0 => ($context["field_name"] ?? null)], "method", false, false, false, 137)))) {
                    // line 138
                    echo "                            ";
                    echo \PhpMyAdmin\Html\Generator::getIcon("bd_primary", _gettext("Primary"));
                    echo "
                          ";
                } else {
                    // line 140
                    echo "                            <a rel=\"samepage\" class=\"ajax add_key print_ignore add_primary_key_anchor\" href=\"";
                    echo PhpMyAdmin\Url::getFromRoute("/table/structure/add-key");
                    echo "\" data-post=\"";
                    echo PhpMyAdmin\Url::getCommon(["db" =>                     // line 141
($context["db"] ?? null), "table" =>                     // line 142
($context["table"] ?? null), "sql_query" => ((((("ALTER TABLE " . PhpMyAdmin\Util::backquote(                    // line 143
($context["table"] ?? null))) . ((($context["primary"] ?? null)) ? (" DROP PRIMARY KEY,") : (""))) . " ADD PRIMARY KEY(") . PhpMyAdmin\Util::backquote((($__internal_compile_34 = $context["row"]) && is_array($__internal_compile_34) || $__internal_compile_34 instanceof ArrayAccess ? ($__internal_compile_34["Field"] ?? null) : null))) . ");"), "message_to_show" => twig_sprintf(_gettext("A primary key has been added on %s."), twig_escape_filter($this->env, (($__internal_compile_35 =                     // line 144
$context["row"]) && is_array($__internal_compile_35) || $__internal_compile_35 instanceof ArrayAccess ? ($__internal_compile_35["Field"] ?? null) : null)))], "", false);
                    // line 145
                    echo "\">
                              ";
                    // line 146
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_primary", _gettext("Primary"));
                    echo "
                            </a>
                          ";
                }
                // line 149
                echo "                        </li>

                        <li class=\"add_unique unique nowrap\">
                          ";
                // line 152
                if (((((($context["type"] ?? null) == "text") || (($context["type"] ?? null) == "blob")) || (($context["tbl_storage_engine"] ?? null) == "ARCHIVE")) || twig_in_filter(($context["field_name"] ?? null), ($context["columns_with_unique_index"] ?? null)))) {
                    // line 153
                    echo "                            ";
                    echo \PhpMyAdmin\Html\Generator::getIcon("bd_unique", _gettext("Unique"));
                    echo "
                          ";
                } else {
                    // line 155
                    echo "                            <a rel=\"samepage\" class=\"ajax add_key print_ignore add_unique_anchor\" href=\"";
                    echo PhpMyAdmin\Url::getFromRoute("/table/structure/add-key");
                    echo "\" data-post=\"";
                    echo PhpMyAdmin\Url::getCommon(["db" =>                     // line 156
($context["db"] ?? null), "table" =>                     // line 157
($context["table"] ?? null), "sql_query" => (((("ALTER TABLE " . PhpMyAdmin\Util::backquote(                    // line 158
($context["table"] ?? null))) . " ADD UNIQUE(") . PhpMyAdmin\Util::backquote((($__internal_compile_36 = $context["row"]) && is_array($__internal_compile_36) || $__internal_compile_36 instanceof ArrayAccess ? ($__internal_compile_36["Field"] ?? null) : null))) . ");"), "message_to_show" => twig_sprintf(_gettext("An index has been added on %s."), twig_escape_filter($this->env, (($__internal_compile_37 =                     // line 159
$context["row"]) && is_array($__internal_compile_37) || $__internal_compile_37 instanceof ArrayAccess ? ($__internal_compile_37["Field"] ?? null) : null)))], "", false);
                    // line 160
                    echo "\">
                              ";
                    // line 161
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_unique", _gettext("Unique"));
                    echo "
                            </a>
                          ";
                }
                // line 164
                echo "                        </li>

                        <li class=\"add_index nowrap\">
                          ";
                // line 167
                if ((((($context["type"] ?? null) == "text") || (($context["type"] ?? null) == "blob")) || (($context["tbl_storage_engine"] ?? null) == "ARCHIVE"))) {
                    // line 168
                    echo "                            ";
                    echo \PhpMyAdmin\Html\Generator::getIcon("bd_index", _gettext("Index"));
                    echo "
                          ";
                } else {
                    // line 170
                    echo "                            <a rel=\"samepage\" class=\"ajax add_key print_ignore add_index_anchor\" href=\"";
                    echo PhpMyAdmin\Url::getFromRoute("/table/structure/add-key");
                    echo "\" data-post=\"";
                    echo PhpMyAdmin\Url::getCommon(["db" =>                     // line 171
($context["db"] ?? null), "table" =>                     // line 172
($context["table"] ?? null), "sql_query" => (((("ALTER TABLE " . PhpMyAdmin\Util::backquote(                    // line 173
($context["table"] ?? null))) . " ADD INDEX(") . PhpMyAdmin\Util::backquote((($__internal_compile_38 = $context["row"]) && is_array($__internal_compile_38) || $__internal_compile_38 instanceof ArrayAccess ? ($__internal_compile_38["Field"] ?? null) : null))) . ");"), "message_to_show" => twig_sprintf(_gettext("An index has been added on %s."), twig_escape_filter($this->env, (($__internal_compile_39 =                     // line 174
$context["row"]) && is_array($__internal_compile_39) || $__internal_compile_39 instanceof ArrayAccess ? ($__internal_compile_39["Field"] ?? null) : null)))], "", false);
                    // line 175
                    echo "\">
                              ";
                    // line 176
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_index", _gettext("Index"));
                    echo "
                            </a>
                          ";
                }
                // line 179
                echo "                        </li>

                        ";
                // line 181
                $context["spatial_types"] = [0 => "geometry", 1 => "point", 2 => "linestring", 3 => "polygon", 4 => "multipoint", 5 => "multilinestring", 6 => "multipolygon", 7 => "geomtrycollection"];
                // line 191
                echo "                        <li class=\"spatial nowrap\">
                          ";
                // line 192
                if (((((($context["type"] ?? null) == "text") || (($context["type"] ?? null) == "blob")) || (($context["tbl_storage_engine"] ?? null) == "ARCHIVE")) || (!twig_in_filter(($context["type"] ?? null), ($context["spatial_types"] ?? null)) && ((($context["tbl_storage_engine"] ?? null) == "MYISAM") || (($context["mysql_int_version"] ?? null) >= 50705))))) {
                    // line 193
                    echo "                            ";
                    echo \PhpMyAdmin\Html\Generator::getIcon("bd_spatial", _gettext("Spatial"));
                    echo "
                          ";
                } else {
                    // line 195
                    echo "                            <a rel=\"samepage\" class=\"ajax add_key print_ignore add_spatial_anchor\" href=\"";
                    echo PhpMyAdmin\Url::getFromRoute("/table/structure/add-key");
                    echo "\" data-post=\"";
                    echo PhpMyAdmin\Url::getCommon(["db" =>                     // line 196
($context["db"] ?? null), "table" =>                     // line 197
($context["table"] ?? null), "sql_query" => (((("ALTER TABLE " . PhpMyAdmin\Util::backquote(                    // line 198
($context["table"] ?? null))) . " ADD SPATIAL(") . PhpMyAdmin\Util::backquote((($__internal_compile_40 = $context["row"]) && is_array($__internal_compile_40) || $__internal_compile_40 instanceof ArrayAccess ? ($__internal_compile_40["Field"] ?? null) : null))) . ");"), "message_to_show" => twig_sprintf(_gettext("An index has been added on %s."), twig_escape_filter($this->env, (($__internal_compile_41 =                     // line 199
$context["row"]) && is_array($__internal_compile_41) || $__internal_compile_41 instanceof ArrayAccess ? ($__internal_compile_41["Field"] ?? null) : null)))], "", false);
                    // line 200
                    echo "\">
                              ";
                    // line 201
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_spatial", _gettext("Spatial"));
                    echo "
                            </a>
                          ";
                }
                // line 204
                echo "                        </li>

                        ";
                // line 207
                echo "                        <li class=\"fulltext nowrap\">
                        ";
                // line 208
                if ((( !twig_test_empty(($context["tbl_storage_engine"] ?? null)) && ((((                // line 209
($context["tbl_storage_engine"] ?? null) == "MYISAM") || (                // line 210
($context["tbl_storage_engine"] ?? null) == "ARIA")) || (                // line 211
($context["tbl_storage_engine"] ?? null) == "MARIA")) || ((                // line 212
($context["tbl_storage_engine"] ?? null) == "INNODB") && (($context["mysql_int_version"] ?? null) >= 50604)))) && (twig_in_filter("text",                 // line 213
($context["type"] ?? null)) || twig_in_filter("char", ($context["type"] ?? null))))) {
                    // line 214
                    echo "                            <a rel=\"samepage\" class=\"ajax add_key add_fulltext_anchor\" href=\"";
                    echo PhpMyAdmin\Url::getFromRoute("/table/structure/add-key");
                    echo "\" data-post=\"";
                    echo PhpMyAdmin\Url::getCommon(["db" =>                     // line 215
($context["db"] ?? null), "table" =>                     // line 216
($context["table"] ?? null), "sql_query" => (((("ALTER TABLE " . PhpMyAdmin\Util::backquote(                    // line 217
($context["table"] ?? null))) . " ADD FULLTEXT(") . PhpMyAdmin\Util::backquote((($__internal_compile_42 = $context["row"]) && is_array($__internal_compile_42) || $__internal_compile_42 instanceof ArrayAccess ? ($__internal_compile_42["Field"] ?? null) : null))) . ");"), "message_to_show" => twig_sprintf(_gettext("An index has been added on %s."), twig_escape_filter($this->env, (($__internal_compile_43 =                     // line 218
$context["row"]) && is_array($__internal_compile_43) || $__internal_compile_43 instanceof ArrayAccess ? ($__internal_compile_43["Field"] ?? null) : null)))], "", false);
                    // line 219
                    echo "\">
                              ";
                    // line 220
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_ftext", _gettext("Fulltext"));
                    echo "
                            </a>
                        ";
                } else {
                    // line 223
                    echo "                          ";
                    echo \PhpMyAdmin\Html\Generator::getIcon("bd_ftext", _gettext("Fulltext"));
                    echo "
                        ";
                }
                // line 225
                echo "                        </li>

                        ";
                // line 228
                echo "                        <li class=\"browse nowrap\">
                            <a href=\"";
                // line 229
                echo PhpMyAdmin\Url::getFromRoute("/sql");
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(["db" =>                 // line 230
($context["db"] ?? null), "table" =>                 // line 231
($context["table"] ?? null), "sql_query" => ((((((((("SELECT COUNT(*) AS " . PhpMyAdmin\Util::backquote(_gettext("Rows"))) . ", ") . PhpMyAdmin\Util::backquote((($__internal_compile_44 =                 // line 233
$context["row"]) && is_array($__internal_compile_44) || $__internal_compile_44 instanceof ArrayAccess ? ($__internal_compile_44["Field"] ?? null) : null))) . " FROM ") . PhpMyAdmin\Util::backquote(                // line 234
($context["table"] ?? null))) . " GROUP BY ") . PhpMyAdmin\Util::backquote((($__internal_compile_45 =                 // line 235
$context["row"]) && is_array($__internal_compile_45) || $__internal_compile_45 instanceof ArrayAccess ? ($__internal_compile_45["Field"] ?? null) : null))) . " ORDER BY ") . PhpMyAdmin\Util::backquote((($__internal_compile_46 =                 // line 236
$context["row"]) && is_array($__internal_compile_46) || $__internal_compile_46 instanceof ArrayAccess ? ($__internal_compile_46["Field"] ?? null) : null))), "is_browse_distinct" => true], "", false);
                // line 238
                echo "\">
                              ";
                // line 239
                echo \PhpMyAdmin\Html\Generator::getIcon("b_browse", _gettext("Distinct values"));
                echo "
                            </a>
                        </li>
                        ";
                // line 242
                if (($context["central_columns_work"] ?? null)) {
                    // line 243
                    echo "                            <li class=\"browse nowrap\">
                            ";
                    // line 244
                    if (twig_in_filter((($__internal_compile_47 = $context["row"]) && is_array($__internal_compile_47) || $__internal_compile_47 instanceof ArrayAccess ? ($__internal_compile_47["Field"] ?? null) : null), ($context["central_list"] ?? null))) {
                        // line 245
                        echo "                                <a href=\"#\" class=\"central_columns remove_button\">
                                    ";
                        // line 246
                        echo \PhpMyAdmin\Html\Generator::getIcon("centralColumns_delete", _gettext("Remove from central columns"));
                        echo "
                                </a>
                            ";
                    } else {
                        // line 249
                        echo "                                <a href=\"#\" class=\"central_columns add_button\">
                                    ";
                        // line 250
                        echo \PhpMyAdmin\Html\Generator::getIcon("centralColumns_add", _gettext("Add to central columns"));
                        echo "
                                </a>
                            ";
                    }
                    // line 253
                    echo "                            </li>
                        ";
                }
                // line 255
                echo "                        ";
                if (($context["hide_structure_actions"] ?? null)) {
                    // line 256
                    echo "                                </ul>
                            </li>
                        ";
                }
                // line 259
                echo "                    </ul>
                </td>
            ";
            }
            // line 262
            echo "        </tr>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 264
        echo "        </tbody>
    </table>
    </div>
    <div class=\"print_ignore\">
        ";
        // line 268
        $this->loadTemplate("select_all.twig", "table/structure/display_structure.twig", 268)->display(twig_to_array(["theme_image_path" =>         // line 269
($context["theme_image_path"] ?? null), "text_dir" =>         // line 270
($context["text_dir"] ?? null), "form_name" => "fieldsForm"]));
        // line 273
        echo "
        <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
        // line 274
        echo PhpMyAdmin\Url::getFromRoute("/table/structure/browse");
        echo "\">
          ";
        // line 275
        echo \PhpMyAdmin\Html\Generator::getIcon("b_browse", _gettext("Browse"));
        echo "
        </button>

        ";
        // line 278
        if (( !($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null))) {
            // line 279
            echo "          <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
            echo PhpMyAdmin\Url::getFromRoute("/table/structure/change");
            echo "\">
            ";
            // line 280
            echo \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Change"));
            echo "
          </button>
          <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
            // line 282
            echo PhpMyAdmin\Url::getFromRoute("/table/structure/drop-confirm");
            echo "\">
            ";
            // line 283
            echo \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop"));
            echo "
          </button>

          ";
            // line 286
            if ((($context["tbl_storage_engine"] ?? null) != "ARCHIVE")) {
                // line 287
                echo "            <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
                echo PhpMyAdmin\Url::getFromRoute("/table/structure/primary");
                echo "\">
              ";
                // line 288
                echo \PhpMyAdmin\Html\Generator::getIcon("b_primary", _gettext("Primary"));
                echo "
            </button>
            <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
                // line 290
                echo PhpMyAdmin\Url::getFromRoute("/table/structure/unique");
                echo "\">
              ";
                // line 291
                echo \PhpMyAdmin\Html\Generator::getIcon("b_unique", _gettext("Unique"));
                echo "
            </button>
            <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
                // line 293
                echo PhpMyAdmin\Url::getFromRoute("/table/structure/index");
                echo "\">
              ";
                // line 294
                echo \PhpMyAdmin\Html\Generator::getIcon("b_index", _gettext("Index"));
                echo "
            </button>
            <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
                // line 296
                echo PhpMyAdmin\Url::getFromRoute("/table/structure/spatial");
                echo "\">
              ";
                // line 297
                echo \PhpMyAdmin\Html\Generator::getIcon("b_spatial", _gettext("Spatial"));
                echo "
            </button>
            <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
                // line 299
                echo PhpMyAdmin\Url::getFromRoute("/table/structure/fulltext");
                echo "\">
              ";
                // line 300
                echo \PhpMyAdmin\Html\Generator::getIcon("b_ftext", _gettext("Fulltext"));
                echo "
            </button>

            ";
                // line 303
                if (($context["central_columns_work"] ?? null)) {
                    // line 304
                    echo "              <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
                    echo PhpMyAdmin\Url::getFromRoute("/table/structure/central-columns-add");
                    echo "\">
                ";
                    // line 305
                    echo \PhpMyAdmin\Html\Generator::getIcon("centralColumns_add", _gettext("Add to central columns"));
                    echo "
              </button>
              <button class=\"btn btn-link mult_submit\" type=\"submit\" formaction=\"";
                    // line 307
                    echo PhpMyAdmin\Url::getFromRoute("/table/structure/central-columns-remove");
                    echo "\">
                ";
                    // line 308
                    echo \PhpMyAdmin\Html\Generator::getIcon("centralColumns_delete", _gettext("Remove from central columns"));
                    echo "
              </button>
            ";
                }
                // line 311
                echo "          ";
            }
            // line 312
            echo "        ";
        }
        // line 313
        echo "    </div>
</form>
<hr class=\"print_ignore\">
<div id=\"move_columns_dialog\" class=\"hide\" title=\"";
        // line 316
        echo _gettext("Move columns");
        echo "\">
    <p>";
        // line 317
        echo _gettext("Move the columns by dragging them up and down.");
        echo "</p>
    <form action=\"";
        // line 318
        echo PhpMyAdmin\Url::getFromRoute("/table/structure/move-columns");
        echo "\" name=\"move_column_form\" id=\"move_column_form\">
        <div>
            ";
        // line 320
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
            <ul></ul>
        </div>
    </form>
</div>
";
        // line 326
        echo "<div id=\"structure-action-links\">
    ";
        // line 327
        if ((($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null))) {
            // line 328
            echo "        ";
            echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/view/create"), ["db" =>             // line 330
($context["db"] ?? null), "table" => ($context["table"] ?? null)], \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Edit view"), true));
            // line 332
            echo "
    ";
        }
        // line 334
        echo "    <a href=\"#\" id=\"printView\">";
        echo \PhpMyAdmin\Html\Generator::getIcon("b_print", _gettext("Print"), true);
        echo "</a>
    ";
        // line 335
        if (( !($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null))) {
            // line 336
            echo "        ";
            // line 337
            echo "        ";
            if (((($context["mysql_int_version"] ?? null) < 80000) || ($context["is_mariadb"] ?? null))) {
                // line 338
                echo "          <a class=\"mr-0\" href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/sql");
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(["db" =>                 // line 339
($context["db"] ?? null), "table" =>                 // line 340
($context["table"] ?? null), "sql_query" => (("SELECT * FROM " . PhpMyAdmin\Util::backquote(                // line 341
($context["table"] ?? null))) . " PROCEDURE ANALYSE()"), "session_max_rows" => "all"], "", false);
                // line 343
                echo "\">
            ";
                // line 344
                echo \PhpMyAdmin\Html\Generator::getIcon("b_tblanalyse", _gettext("Propose table structure"), true);
                // line 348
                echo "
          </a>
          ";
                // line 350
                echo \PhpMyAdmin\Html\MySQLDocumentation::show("procedure_analyse");
                echo "
        ";
            }
            // line 352
            echo "        ";
            if (($context["is_active"] ?? null)) {
                // line 353
                echo "            <a href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/table/tracking", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]);
                echo "\">
                ";
                // line 354
                echo \PhpMyAdmin\Html\Generator::getIcon("eye", _gettext("Track table"), true);
                echo "
            </a>
        ";
            }
            // line 357
            echo "        <a href=\"#\" id=\"move_columns_anchor\">
            ";
            // line 358
            echo \PhpMyAdmin\Html\Generator::getIcon("b_move", _gettext("Move columns"), true);
            echo "
        </a>
        <a href=\"";
            // line 360
            echo PhpMyAdmin\Url::getFromRoute("/normalization", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]);
            echo "\">
            ";
            // line 361
            echo \PhpMyAdmin\Html\Generator::getIcon("normalize", _gettext("Normalize"), true);
            echo "
        </a>
    ";
        }
        // line 364
        echo "    ";
        if ((($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null))) {
            // line 365
            echo "        ";
            if (($context["is_active"] ?? null)) {
                // line 366
                echo "            <a href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/table/tracking", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]);
                echo "\">
                ";
                // line 367
                echo \PhpMyAdmin\Html\Generator::getIcon("eye", _gettext("Track view"), true);
                echo "
            </a>
        ";
            }
            // line 370
            echo "    ";
        }
        // line 371
        echo "</div>
";
        // line 372
        if (( !($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null))) {
            // line 373
            echo "    <form method=\"post\" action=\"";
            echo PhpMyAdmin\Url::getFromRoute("/table/add-field");
            echo "\" id=\"addColumns\" name=\"addColumns\">
        ";
            // line 374
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
            echo "
        ";
            // line 375
            if (PhpMyAdmin\Util::showIcons("ActionLinksMode")) {
                // line 376
                echo "            ";
                echo \PhpMyAdmin\Html\Generator::getImage("b_insrow", _gettext("Add column"));
                echo "&nbsp;
        ";
            }
            // line 378
            echo "        ";
            $context["num_fields"] = ('' === $tmp = "<input type=\"number\" name=\"num_fields\" value=\"1\" onfocus=\"this.select()\" min=\"1\" required>") ? '' : new Markup($tmp, $this->env->getCharset());
            // line 381
            echo "        ";
            echo twig_sprintf(_gettext("Add %s column(s)"), ($context["num_fields"] ?? null));
            echo "
        <input type=\"hidden\" name=\"field_where\" value=\"after\">&nbsp;
        ";
            // line 384
            echo "        <select name=\"after_field\">
            <option value=\"first\" data-pos=\"first\">
                ";
            // line 386
            echo _gettext("at beginning of table");
            // line 387
            echo "            </option>
            ";
            // line 388
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["columns_list"] ?? null));
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
            foreach ($context['_seq'] as $context["_key"] => $context["one_column_name"]) {
                // line 389
                echo "                <option value=\"";
                echo twig_escape_filter($this->env, $context["one_column_name"], "html", null, true);
                echo "\"";
                // line 390
                echo (((twig_get_attribute($this->env, $this->source, $context["loop"], "revindex0", [], "any", false, false, false, 390) == 0)) ? (" selected=\"selected\"") : (""));
                echo ">
                    ";
                // line 391
                echo twig_escape_filter($this->env, twig_sprintf(_gettext("after %s"), $context["one_column_name"]), "html", null, true);
                echo "
                </option>
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
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['one_column_name'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 394
            echo "        </select>
        <input class=\"btn btn-primary\" type=\"submit\" value=\"";
            // line 395
            echo _gettext("Go");
            echo "\">
    </form>
";
        }
        // line 398
        echo "
";
        // line 399
        if ((( !($context["tbl_is_view"] ?? null) &&  !($context["db_is_system_schema"] ?? null)) && (($context["tbl_storage_engine"] ?? null) != "ARCHIVE"))) {
            // line 400
            echo "  <div id=\"index_div\" class=\"w-100 ajax\">
    <fieldset class=\"index_info\">
      <legend id=\"index_header\">
        ";
            // line 403
            echo _gettext("Indexes");
            // line 404
            echo "        ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("optimizing-database-structure");
            echo "
      </legend>

      ";
            // line 407
            if ( !twig_test_empty(($context["indexes"] ?? null))) {
                // line 408
                echo "        ";
                echo ($context["indexes_duplicates"] ?? null);
                echo "

        <div class=\"table-responsive jsresponsive\">
          <table class=\"table table-light table-striped table-hover table-sm w-auto\" id=\"table_index\">
            <thead class=\"thead-light\">
              <tr>
                <th colspan=\"3\" class=\"print_ignore\">";
                // line 414
                echo _gettext("Action");
                echo "</th>
                <th>";
                // line 415
                echo _gettext("Keyname");
                echo "</th>
                <th>";
                // line 416
                echo _gettext("Type");
                echo "</th>
                <th>";
                // line 417
                echo _gettext("Unique");
                echo "</th>
                <th>";
                // line 418
                echo _gettext("Packed");
                echo "</th>
                <th>";
                // line 419
                echo _gettext("Column");
                echo "</th>
                <th>";
                // line 420
                echo _gettext("Cardinality");
                echo "</th>
                <th>";
                // line 421
                echo _gettext("Collation");
                echo "</th>
                <th>";
                // line 422
                echo _gettext("Null");
                echo "</th>
                <th>";
                // line 423
                echo _gettext("Comment");
                echo "</th>
              </tr>
            </thead>

            ";
                // line 427
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(($context["indexes"] ?? null));
                foreach ($context['_seq'] as $context["_key"] => $context["index"]) {
                    // line 428
                    echo "              <tbody class=\"row_span\">
                ";
                    // line 429
                    $context["columns_count"] = twig_get_attribute($this->env, $this->source, $context["index"], "getColumnCount", [], "method", false, false, false, 429);
                    // line 430
                    echo "                <tr class=\"noclick\">
                <td rowspan=\"";
                    // line 431
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\" class=\"edit_index print_ignore ajax\">
                  <a class=\"ajax\" href=\"";
                    // line 432
                    echo PhpMyAdmin\Url::getFromRoute("/table/indexes");
                    echo "\" data-post=\"";
                    echo PhpMyAdmin\Url::getCommon(["db" =>                     // line 433
($context["db"] ?? null), "table" =>                     // line 434
($context["table"] ?? null), "index" => twig_get_attribute($this->env, $this->source,                     // line 435
$context["index"], "getName", [], "method", false, false, false, 435)], "", false);
                    // line 436
                    echo "\">
                    ";
                    // line 437
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Edit"));
                    echo "
                  </a>
                </td>
                <td rowspan=\"";
                    // line 440
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\" class=\"rename_index print_ignore ajax\" >
                  <a class=\"ajax\" href=\"";
                    // line 441
                    echo PhpMyAdmin\Url::getFromRoute("/table/indexes/rename");
                    echo "\" data-post=\"";
                    echo PhpMyAdmin\Url::getCommon(["db" =>                     // line 442
($context["db"] ?? null), "table" =>                     // line 443
($context["table"] ?? null), "index" => twig_get_attribute($this->env, $this->source,                     // line 444
$context["index"], "getName", [], "method", false, false, false, 444)], "", false);
                    // line 445
                    echo "\">
                    ";
                    // line 446
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_rename", _gettext("Rename"));
                    echo "
                  </a>
                </td>
                <td rowspan=\"";
                    // line 449
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\" class=\"print_ignore\">
                  ";
                    // line 450
                    if ((twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 450) == "PRIMARY")) {
                        // line 451
                        echo "                    ";
                        $context["index_params"] = ["sql_query" => (("ALTER TABLE " . PhpMyAdmin\Util::backquote(                        // line 452
($context["table"] ?? null))) . " DROP PRIMARY KEY;"), "message_to_show" => _gettext("The primary key has been dropped.")];
                        // line 455
                        echo "                  ";
                    } else {
                        // line 456
                        echo "                    ";
                        $context["index_params"] = ["sql_query" => (((("ALTER TABLE " . PhpMyAdmin\Util::backquote(                        // line 457
($context["table"] ?? null))) . " DROP INDEX ") . PhpMyAdmin\Util::backquote(twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 457))) . ";"), "message_to_show" => twig_sprintf(_gettext("Index %s has been dropped."), twig_get_attribute($this->env, $this->source,                         // line 458
$context["index"], "getName", [], "method", false, false, false, 458))];
                        // line 460
                        echo "                  ";
                    }
                    // line 461
                    echo "
                  <input type=\"hidden\" class=\"drop_primary_key_index_msg\" value=\"";
                    // line 462
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["index_params"] ?? null), "sql_query", [], "any", false, false, false, 462), "html", null, true);
                    echo "\">
                  ";
                    // line 463
                    echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), twig_array_merge(                    // line 465
($context["index_params"] ?? null), ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null)]), \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop")), ["class" => "drop_primary_key_index_anchor ajax"]);
                    // line 468
                    echo "
                </td>
                <th rowspan=\"";
                    // line 470
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["index"], "getName", [], "method", false, false, false, 470), "html", null, true);
                    echo "</th>
                <td rowspan=\"";
                    // line 471
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["index"], "getType", [], "method", true, true, false, 471)) ? (_twig_default_filter(twig_get_attribute($this->env, $this->source, $context["index"], "getType", [], "method", false, false, false, 471), twig_get_attribute($this->env, $this->source, $context["index"], "getChoice", [], "method", false, false, false, 471))) : (twig_get_attribute($this->env, $this->source, $context["index"], "getChoice", [], "method", false, false, false, 471))), "html", null, true);
                    echo "</td>
                <td rowspan=\"";
                    // line 472
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["index"], "isUnique", [], "method", false, false, false, 472)) ? (_gettext("Yes")) : (_gettext("No"))), "html", null, true);
                    echo "</td>
                <td rowspan=\"";
                    // line 473
                    echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                    echo "\">";
                    echo twig_get_attribute($this->env, $this->source, $context["index"], "isPacked", [], "method", false, false, false, 473);
                    echo "</td>

                ";
                    // line 475
                    $context['_parent'] = $context;
                    $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["index"], "getColumns", [], "method", false, false, false, 475));
                    foreach ($context['_seq'] as $context["_key"] => $context["column"]) {
                        // line 476
                        echo "                  ";
                        if ((twig_get_attribute($this->env, $this->source, $context["column"], "getSeqInIndex", [], "method", false, false, false, 476) > 1)) {
                            // line 477
                            echo "                    <tr class=\"noclick\">
                  ";
                        }
                        // line 479
                        echo "                  <td>
                    ";
                        // line 480
                        if (twig_get_attribute($this->env, $this->source, $context["column"], "hasExpression", [], "method", false, false, false, 480)) {
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getExpression", [], "method", false, false, false, 480), "html", null, true);
                        } else {
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getName", [], "method", false, false, false, 480), "html", null, true);
                        }
                        // line 481
                        echo "                    ";
                        if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["column"], "getSubPart", [], "method", false, false, false, 481))) {
                            // line 482
                            echo "                      (";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getSubPart", [], "method", false, false, false, 482), "html", null, true);
                            echo ")
                    ";
                        }
                        // line 484
                        echo "                  </td>
                  <td>";
                        // line 485
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getCardinality", [], "method", false, false, false, 485), "html", null, true);
                        echo "</td>
                  <td>";
                        // line 486
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getCollation", [], "method", false, false, false, 486), "html", null, true);
                        echo "</td>
                  <td>";
                        // line 487
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "getNull", [0 => true], "method", false, false, false, 487), "html", null, true);
                        echo "</td>

                  ";
                        // line 489
                        if ((twig_get_attribute($this->env, $this->source, $context["column"], "getSeqInIndex", [], "method", false, false, false, 489) == 1)) {
                            // line 490
                            echo "                    <td rowspan=\"";
                            echo twig_escape_filter($this->env, ($context["columns_count"] ?? null), "html", null, true);
                            echo "\">";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["index"], "getComments", [], "method", false, false, false, 490), "html", null, true);
                            echo "</td>
                  ";
                        }
                        // line 492
                        echo "                  </tr>
                ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_iterated'], $context['_key'], $context['column'], $context['_parent'], $context['loop']);
                    $context = array_intersect_key($context, $_parent) + $_parent;
                    // line 494
                    echo "              </tbody>
            ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['index'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 496
                echo "          </table>
        </div>
      ";
            } else {
                // line 499
                echo "        <div class=\"no_indexes_defined\">";
                echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [_gettext("No index defined!")]);
                echo "</div>
      ";
            }
            // line 501
            echo "    </fieldset>

    <fieldset class=\"tblFooters print_ignore text-left\">
      <form action=\"";
            // line 504
            echo PhpMyAdmin\Url::getFromRoute("/table/indexes");
            echo "\" method=\"post\">
        ";
            // line 505
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
            echo "
        <input type=\"hidden\" name=\"create_index\" value=\"1\">

        ";
            // line 508
            ob_start(function () { return ''; });
            // line 509
            echo "          ";
            echo _gettext("Create an index on %s columns");
            // line 510
            echo "        ";
            $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 508
            echo twig_sprintf($___internal_parse_0_, "<input class=\"mx-2\" type=\"number\" name=\"added_fields\" value=\"1\" min=\"1\" required>");
            // line 511
            echo "
        <input class=\"btn btn-primary add_index ajax\" type=\"submit\" value=\"";
            // line 512
            echo _gettext("Go");
            echo "\">
      </form>
    </fieldset>
  </div>
";
        }
        // line 517
        echo "
";
        // line 519
        if (($context["have_partitioning"] ?? null)) {
            // line 520
            echo "    ";
            // line 521
            echo "    ";
            if (( !twig_test_empty(($context["partition_names"] ?? null)) &&  !(null === (($__internal_compile_48 = ($context["partition_names"] ?? null)) && is_array($__internal_compile_48) || $__internal_compile_48 instanceof ArrayAccess ? ($__internal_compile_48[0] ?? null) : null)))) {
                // line 522
                echo "        ";
                $context["first_partition"] = (($__internal_compile_49 = ($context["partitions"] ?? null)) && is_array($__internal_compile_49) || $__internal_compile_49 instanceof ArrayAccess ? ($__internal_compile_49[0] ?? null) : null);
                // line 523
                echo "        ";
                $context["range_or_list"] = ((((twig_get_attribute($this->env, $this->source, ($context["first_partition"] ?? null), "getMethod", [], "method", false, false, false, 523) == "RANGE") || (twig_get_attribute($this->env, $this->source,                 // line 524
($context["first_partition"] ?? null), "getMethod", [], "method", false, false, false, 524) == "RANGE COLUMNS")) || (twig_get_attribute($this->env, $this->source,                 // line 525
($context["first_partition"] ?? null), "getMethod", [], "method", false, false, false, 525) == "LIST")) || (twig_get_attribute($this->env, $this->source,                 // line 526
($context["first_partition"] ?? null), "getMethod", [], "method", false, false, false, 526) == "LIST COLUMNS"));
                // line 527
                echo "        ";
                $context["sub_partitions"] = twig_get_attribute($this->env, $this->source, ($context["first_partition"] ?? null), "getSubPartitions", [], "method", false, false, false, 527);
                // line 528
                echo "        ";
                $context["has_sub_partitions"] = twig_get_attribute($this->env, $this->source, ($context["first_partition"] ?? null), "hasSubPartitions", [], "method", false, false, false, 528);
                // line 529
                echo "        ";
                if (($context["has_sub_partitions"] ?? null)) {
                    // line 530
                    echo "            ";
                    $context["first_sub_partition"] = (($__internal_compile_50 = ($context["sub_partitions"] ?? null)) && is_array($__internal_compile_50) || $__internal_compile_50 instanceof ArrayAccess ? ($__internal_compile_50[0] ?? null) : null);
                    // line 531
                    echo "        ";
                }
                // line 532
                echo "
        <div id=\"partitions-2\"";
                // line 533
                if ((($context["default_sliders_state"] ?? null) != "disabled")) {
                    // line 534
                    echo (((($context["default_sliders_state"] ?? null) == "closed")) ? (" style=\"display: none; overflow:auto;\"") : (""));
                    echo " class=\"pma_auto_slider\" title=\"";
                    echo _gettext("Partitions");
                    echo "\"";
                }
                // line 535
                echo ">

        ";
                // line 537
                $this->loadTemplate("table/structure/display_partitions.twig", "table/structure/display_structure.twig", 537)->display(twig_to_array(["db" =>                 // line 538
($context["db"] ?? null), "table" =>                 // line 539
($context["table"] ?? null), "partitions" =>                 // line 540
($context["partitions"] ?? null), "partition_method" => twig_get_attribute($this->env, $this->source,                 // line 541
($context["first_partition"] ?? null), "getMethod", [], "method", false, false, false, 541), "partition_expression" => twig_get_attribute($this->env, $this->source,                 // line 542
($context["first_partition"] ?? null), "getExpression", [], "method", false, false, false, 542), "has_description" =>  !twig_test_empty(twig_get_attribute($this->env, $this->source,                 // line 543
($context["first_partition"] ?? null), "getDescription", [], "method", false, false, false, 543)), "has_sub_partitions" =>                 // line 544
($context["has_sub_partitions"] ?? null), "sub_partition_method" => ((                // line 545
($context["has_sub_partitions"] ?? null)) ? (twig_get_attribute($this->env, $this->source, ($context["first_sub_partition"] ?? null), "getMethod", [], "method", false, false, false, 545)) : ("")), "sub_partition_expression" => ((                // line 546
($context["has_sub_partitions"] ?? null)) ? (twig_get_attribute($this->env, $this->source, ($context["first_sub_partition"] ?? null), "getExpression", [], "method", false, false, false, 546)) : ("")), "range_or_list" =>                 // line 547
($context["range_or_list"] ?? null)]));
                // line 549
                echo "    ";
            } else {
                // line 550
                echo "        ";
                $this->loadTemplate("table/structure/display_partitions.twig", "table/structure/display_structure.twig", 550)->display(twig_to_array(["db" =>                 // line 551
($context["db"] ?? null), "table" =>                 // line 552
($context["table"] ?? null)]));
                // line 554
                echo "    ";
            }
            // line 555
            echo "    </div>
";
        }
        // line 557
        echo "
";
        // line 559
        if (($context["show_stats"] ?? null)) {
            // line 560
            echo "    ";
            echo ($context["table_stats"] ?? null);
            echo "
";
        }
        // line 562
        echo "<div class=\"clearfloat\"></div>
";
    }

    public function getTemplateName()
    {
        return "table/structure/display_structure.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  1338 => 562,  1332 => 560,  1330 => 559,  1327 => 557,  1323 => 555,  1320 => 554,  1318 => 552,  1317 => 551,  1315 => 550,  1312 => 549,  1310 => 547,  1309 => 546,  1308 => 545,  1307 => 544,  1306 => 543,  1305 => 542,  1304 => 541,  1303 => 540,  1302 => 539,  1301 => 538,  1300 => 537,  1296 => 535,  1290 => 534,  1288 => 533,  1285 => 532,  1282 => 531,  1279 => 530,  1276 => 529,  1273 => 528,  1270 => 527,  1268 => 526,  1267 => 525,  1266 => 524,  1264 => 523,  1261 => 522,  1258 => 521,  1256 => 520,  1254 => 519,  1251 => 517,  1243 => 512,  1240 => 511,  1238 => 508,  1235 => 510,  1232 => 509,  1230 => 508,  1224 => 505,  1220 => 504,  1215 => 501,  1209 => 499,  1204 => 496,  1197 => 494,  1190 => 492,  1182 => 490,  1180 => 489,  1175 => 487,  1171 => 486,  1167 => 485,  1164 => 484,  1158 => 482,  1155 => 481,  1149 => 480,  1146 => 479,  1142 => 477,  1139 => 476,  1135 => 475,  1128 => 473,  1122 => 472,  1116 => 471,  1110 => 470,  1106 => 468,  1104 => 465,  1103 => 463,  1099 => 462,  1096 => 461,  1093 => 460,  1091 => 458,  1090 => 457,  1088 => 456,  1085 => 455,  1083 => 452,  1081 => 451,  1079 => 450,  1075 => 449,  1069 => 446,  1066 => 445,  1064 => 444,  1063 => 443,  1062 => 442,  1059 => 441,  1055 => 440,  1049 => 437,  1046 => 436,  1044 => 435,  1043 => 434,  1042 => 433,  1039 => 432,  1035 => 431,  1032 => 430,  1030 => 429,  1027 => 428,  1023 => 427,  1016 => 423,  1012 => 422,  1008 => 421,  1004 => 420,  1000 => 419,  996 => 418,  992 => 417,  988 => 416,  984 => 415,  980 => 414,  970 => 408,  968 => 407,  961 => 404,  959 => 403,  954 => 400,  952 => 399,  949 => 398,  943 => 395,  940 => 394,  923 => 391,  919 => 390,  915 => 389,  898 => 388,  895 => 387,  893 => 386,  889 => 384,  883 => 381,  880 => 378,  874 => 376,  872 => 375,  868 => 374,  863 => 373,  861 => 372,  858 => 371,  855 => 370,  849 => 367,  844 => 366,  841 => 365,  838 => 364,  832 => 361,  828 => 360,  823 => 358,  820 => 357,  814 => 354,  809 => 353,  806 => 352,  801 => 350,  797 => 348,  795 => 344,  792 => 343,  790 => 341,  789 => 340,  788 => 339,  784 => 338,  781 => 337,  779 => 336,  777 => 335,  772 => 334,  768 => 332,  766 => 330,  764 => 328,  762 => 327,  759 => 326,  751 => 320,  746 => 318,  742 => 317,  738 => 316,  733 => 313,  730 => 312,  727 => 311,  721 => 308,  717 => 307,  712 => 305,  707 => 304,  705 => 303,  699 => 300,  695 => 299,  690 => 297,  686 => 296,  681 => 294,  677 => 293,  672 => 291,  668 => 290,  663 => 288,  658 => 287,  656 => 286,  650 => 283,  646 => 282,  641 => 280,  636 => 279,  634 => 278,  628 => 275,  624 => 274,  621 => 273,  619 => 270,  618 => 269,  617 => 268,  611 => 264,  604 => 262,  599 => 259,  594 => 256,  591 => 255,  587 => 253,  581 => 250,  578 => 249,  572 => 246,  569 => 245,  567 => 244,  564 => 243,  562 => 242,  556 => 239,  553 => 238,  551 => 236,  550 => 235,  549 => 234,  548 => 233,  547 => 231,  546 => 230,  543 => 229,  540 => 228,  536 => 225,  530 => 223,  524 => 220,  521 => 219,  519 => 218,  518 => 217,  517 => 216,  516 => 215,  512 => 214,  510 => 213,  509 => 212,  508 => 211,  507 => 210,  506 => 209,  505 => 208,  502 => 207,  498 => 204,  492 => 201,  489 => 200,  487 => 199,  486 => 198,  485 => 197,  484 => 196,  480 => 195,  474 => 193,  472 => 192,  469 => 191,  467 => 181,  463 => 179,  457 => 176,  454 => 175,  452 => 174,  451 => 173,  450 => 172,  449 => 171,  445 => 170,  439 => 168,  437 => 167,  432 => 164,  426 => 161,  423 => 160,  421 => 159,  420 => 158,  419 => 157,  418 => 156,  414 => 155,  408 => 153,  406 => 152,  401 => 149,  395 => 146,  392 => 145,  390 => 144,  389 => 143,  388 => 142,  387 => 141,  383 => 140,  377 => 138,  375 => 137,  371 => 135,  365 => 132,  362 => 131,  360 => 130,  356 => 128,  353 => 127,  351 => 126,  348 => 125,  341 => 121,  338 => 120,  336 => 119,  335 => 117,  334 => 116,  333 => 115,  332 => 114,  329 => 113,  322 => 109,  319 => 108,  317 => 106,  316 => 105,  315 => 104,  314 => 103,  311 => 102,  309 => 101,  304 => 100,  298 => 97,  295 => 96,  293 => 95,  290 => 94,  284 => 92,  280 => 90,  277 => 89,  271 => 87,  265 => 85,  262 => 84,  260 => 83,  255 => 81,  251 => 80,  248 => 79,  240 => 77,  238 => 76,  233 => 73,  225 => 71,  223 => 70,  222 => 69,  218 => 68,  213 => 66,  206 => 63,  200 => 61,  192 => 59,  190 => 58,  186 => 57,  181 => 55,  174 => 53,  169 => 50,  167 => 49,  164 => 48,  162 => 47,  159 => 46,  157 => 45,  154 => 44,  151 => 43,  146 => 42,  143 => 41,  138 => 37,  132 => 35,  129 => 34,  126 => 33,  121 => 31,  116 => 29,  114 => 28,  110 => 27,  106 => 26,  102 => 25,  98 => 24,  94 => 23,  90 => 22,  84 => 18,  79 => 14,  76 => 13,  73 => 11,  71 => 10,  69 => 9,  67 => 8,  63 => 6,  59 => 5,  55 => 4,  50 => 3,  46 => 2,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/structure/display_structure.twig", "/var/www/html/phpMyAdmin/templates/table/structure/display_structure.twig");
    }
}
