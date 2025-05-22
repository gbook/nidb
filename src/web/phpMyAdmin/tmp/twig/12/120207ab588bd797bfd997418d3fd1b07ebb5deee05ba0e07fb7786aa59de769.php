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

/* database/designer/main.twig */
class __TwigTemplate_9cfd32905b31d4de689ea04853c7ce6e5c5b28eb69d601207ba9777f69a365c8 extends \Twig\Template
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
        // line 2
        echo "<script type=\"text/javascript\">
var designerConfig = ";
        // line 3
        echo ($context["designer_config"] ?? null);
        echo ";
</script>

";
        // line 7
        if ( !($context["has_query"] ?? null)) {
            // line 8
            echo "    <div id=\"name-panel\">
        <span id=\"page_name\">
            ";
            // line 10
            echo twig_escape_filter($this->env, (((($context["selected_page"] ?? null) == null)) ? (_gettext("Untitled")) : (($context["selected_page"] ?? null))), "html", null, true);
            echo "
        </span>
        <span id=\"saved_state\">
            ";
            // line 13
            echo (((($context["selected_page"] ?? null) == null)) ? ("*") : (""));
            echo "
        </span>
    </div>
";
        }
        // line 17
        echo "<div class=\"designer_header side-menu\" id=\"side_menu\">
    <a class=\"M_butt\" id=\"key_Show_left_menu\" href=\"#\">
        <img title=\"";
        // line 19
        echo _gettext("Show/Hide tables list");
        echo "\"
             alt=\"v\"
             src=\"";
        // line 21
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/downarrow2_m.png"], "method", false, false, false, 21), "html", null, true);
        echo "\"
             data-down=\"";
        // line 22
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/downarrow2_m.png"], "method", false, false, false, 22), "html", null, true);
        echo "\"
             data-up=\"";
        // line 23
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/uparrow2_m.png"], "method", false, false, false, 23), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 25
        echo _gettext("Show/Hide tables list");
        // line 26
        echo "        </span>
    </a>
    <a href=\"#\" id=\"toggleFullscreen\" class=\"M_butt\">
        <img title=\"";
        // line 29
        echo _gettext("View in fullscreen");
        echo "\"
             src=\"";
        // line 30
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/viewInFullscreen.png"], "method", false, false, false, 30), "html", null, true);
        echo "\"
             data-enter=\"";
        // line 31
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/viewInFullscreen.png"], "method", false, false, false, 31), "html", null, true);
        echo "\"
             data-exit=\"";
        // line 32
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/exitFullscreen.png"], "method", false, false, false, 32), "html", null, true);
        echo "\">
        <span class=\"hide hidable\"
              data-exit=\"";
        // line 34
        echo _gettext("Exit fullscreen");
        echo "\"
              data-enter=\"";
        // line 35
        echo _gettext("View in fullscreen");
        echo "\">
            ";
        // line 36
        echo _gettext("View in fullscreen");
        // line 37
        echo "        </span>
    </a>
    <a href=\"#\" id=\"addOtherDbTables\" class=\"M_butt\">
        <img title=\"";
        // line 40
        echo _gettext("Add tables from other databases");
        echo "\"
             src=\"";
        // line 41
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/other_table.png"], "method", false, false, false, 41), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 43
        echo _gettext("Add tables from other databases");
        // line 44
        echo "        </span>
    </a>
    ";
        // line 46
        if ( !($context["has_query"] ?? null)) {
            // line 47
            echo "        <a id=\"newPage\" href=\"#\" class=\"M_butt\">
            <img title=\"";
            // line 48
            echo _gettext("New page");
            echo "\"
                 alt=\"\"
                 src=\"";
            // line 50
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/page_add.png"], "method", false, false, false, 50), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 52
            echo _gettext("New page");
            // line 53
            echo "            </span>
        </a>
        <a href=\"#\" id=\"editPage\" class=\"M_butt ajax\">
            <img title=\"";
            // line 56
            echo _gettext("Open page");
            echo "\"
                 src=\"";
            // line 57
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/page_edit.png"], "method", false, false, false, 57), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 59
            echo _gettext("Open page");
            // line 60
            echo "            </span>
        </a>
        <a href=\"#\" id=\"savePos\" class=\"M_butt\">
            <img title=\"";
            // line 63
            echo _gettext("Save page");
            echo "\"
                 src=\"";
            // line 64
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/save.png"], "method", false, false, false, 64), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 66
            echo _gettext("Save page");
            // line 67
            echo "            </span>
        </a>
        <a href=\"#\" id=\"SaveAs\" class=\"M_butt ajax\">
            <img title=\"";
            // line 70
            echo _gettext("Save page as");
            echo "\"
                 src=\"";
            // line 71
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/save_as.png"], "method", false, false, false, 71), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 73
            echo _gettext("Save page as");
            // line 74
            echo "            </span>
        </a>
        <a href=\"#\" id=\"delPages\" class=\"M_butt ajax\">
            <img title=\"";
            // line 77
            echo _gettext("Delete pages");
            echo "\"
                 src=\"";
            // line 78
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/page_delete.png"], "method", false, false, false, 78), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 80
            echo _gettext("Delete pages");
            // line 81
            echo "            </span>
        </a>
        <a href=\"#\" id=\"StartTableNew\" class=\"M_butt\">
            <img title=\"";
            // line 84
            echo _gettext("Create table");
            echo "\"
                 src=\"";
            // line 85
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/table.png"], "method", false, false, false, 85), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 87
            echo _gettext("Create table");
            // line 88
            echo "            </span>
        </a>
        <a href=\"#\" class=\"M_butt\" id=\"rel_button\">
            <img title=\"";
            // line 91
            echo _gettext("Create relationship");
            echo "\"
                 src=\"";
            // line 92
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/relation.png"], "method", false, false, false, 92), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 94
            echo _gettext("Create relationship");
            // line 95
            echo "            </span>
        </a>
        <a href=\"#\" class=\"M_butt\" id=\"display_field_button\">
            <img title=\"";
            // line 98
            echo _gettext("Choose column to display");
            echo "\"
                 src=\"";
            // line 99
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/display_field.png"], "method", false, false, false, 99), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 101
            echo _gettext("Choose column to display");
            // line 102
            echo "            </span>
        </a>
        <a href=\"#\" id=\"reloadPage\" class=\"M_butt\">
            <img title=\"";
            // line 105
            echo _gettext("Reload");
            echo "\"
                 src=\"";
            // line 106
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/reload.png"], "method", false, false, false, 106), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 108
            echo _gettext("Reload");
            // line 109
            echo "            </span>
        </a>
        <a href=\"";
            // line 111
            echo \PhpMyAdmin\Html\MySQLDocumentation::getDocumentationLink("faq", "faq6-31");
            echo "\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"M_butt\">
            <img title=\"";
            // line 112
            echo _gettext("Help");
            echo "\"
                 src=\"";
            // line 113
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/help.png"], "method", false, false, false, 113), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 115
            echo _gettext("Help");
            // line 116
            echo "            </span>
        </a>
    ";
        }
        // line 119
        echo "    <a href=\"#\" class=\"";
        echo twig_escape_filter($this->env, (($__internal_compile_0 = ($context["params_array"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["angular_direct"] ?? null) : null), "html", null, true);
        echo "\" id=\"angular_direct_button\">
        <img title=\"";
        // line 120
        echo _gettext("Angular links");
        echo " / ";
        echo _gettext("Direct links");
        echo "\"
             src=\"";
        // line 121
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/ang_direct.png"], "method", false, false, false, 121), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 123
        echo _gettext("Angular links");
        echo " / ";
        echo _gettext("Direct links");
        // line 124
        echo "        </span>
    </a>
    <a href=\"#\" class=\"";
        // line 126
        echo twig_escape_filter($this->env, (($__internal_compile_1 = ($context["params_array"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["snap_to_grid"] ?? null) : null), "html", null, true);
        echo "\" id=\"grid_button\">
        <img title=\"";
        // line 127
        echo _gettext("Snap to grid");
        echo "\" src=\"";
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/grid.png"], "method", false, false, false, 127), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 129
        echo _gettext("Snap to grid");
        // line 130
        echo "        </span>
    </a>
    <a href=\"#\" class=\"";
        // line 132
        echo twig_escape_filter($this->env, (($__internal_compile_2 = ($context["params_array"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["small_big_all"] ?? null) : null), "html", null, true);
        echo "\" id=\"key_SB_all\">
        <img title=\"";
        // line 133
        echo _gettext("Small/Big All");
        echo "\"
             alt=\"v\"
             src=\"";
        // line 135
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/downarrow1.png"], "method", false, false, false, 135), "html", null, true);
        echo "\"
             data-down=\"";
        // line 136
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/downarrow1.png"], "method", false, false, false, 136), "html", null, true);
        echo "\"
             data-right=\"";
        // line 137
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/rightarrow1.png"], "method", false, false, false, 137), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 139
        echo _gettext("Small/Big All");
        // line 140
        echo "        </span>
    </a>
    <a href=\"#\" id=\"SmallTabInvert\" class=\"M_butt\">
        <img title=\"";
        // line 143
        echo _gettext("Toggle small/big");
        echo "\"
             src=\"";
        // line 144
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/bottom.png"], "method", false, false, false, 144), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 146
        echo _gettext("Toggle small/big");
        // line 147
        echo "        </span>
    </a>
    <a href=\"#\" id=\"relLineInvert\" class=\"";
        // line 149
        echo twig_escape_filter($this->env, (($__internal_compile_3 = ($context["params_array"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["relation_lines"] ?? null) : null), "html", null, true);
        echo "\" >
        <img title=\"";
        // line 150
        echo _gettext("Toggle relationship lines");
        echo "\"
             src=\"";
        // line 151
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/toggle_lines.png"], "method", false, false, false, 151), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 153
        echo _gettext("Toggle relationship lines");
        // line 154
        echo "        </span>
    </a>
    ";
        // line 156
        if ( !($context["visual_builder"] ?? null)) {
            // line 157
            echo "        <a href=\"#\" id=\"exportPages\" class=\"M_butt\" >
            <img title=\"";
            // line 158
            echo _gettext("Export schema");
            echo "\"
                 src=\"";
            // line 159
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/export.png"], "method", false, false, false, 159), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 161
            echo _gettext("Export schema");
            // line 162
            echo "            </span>
        </a>
    ";
        } else {
            // line 165
            echo "        <a id=\"build_query_button\"
           class=\"M_butt\"
           href=\"#\"
           class=\"M_butt\">
            <img title=\"";
            // line 169
            echo _gettext("Build Query");
            echo "\"
                 src=\"";
            // line 170
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/query_builder.png"], "method", false, false, false, 170), "html", null, true);
            echo "\">
            <span class=\"hide hidable\">
                ";
            // line 172
            echo _gettext("Build Query");
            // line 173
            echo "            </span>
        </a>
    ";
        }
        // line 176
        echo "    <a href=\"#\" class=\"";
        echo twig_escape_filter($this->env, (($__internal_compile_4 = ($context["params_array"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["side_menu"] ?? null) : null), "html", null, true);
        echo "\" id=\"key_Left_Right\">
        <img title=\"";
        // line 177
        echo _gettext("Move Menu");
        echo "\" alt=\">\"
             data-right=\"";
        // line 178
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/2leftarrow_m.png"], "method", false, false, false, 178), "html", null, true);
        echo "\"
             src=\"";
        // line 179
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/2rightarrow_m.png"], "method", false, false, false, 179), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 181
        echo _gettext("Move Menu");
        // line 182
        echo "        </span>
    </a>
    <a href=\"#\" class=\"";
        // line 184
        echo twig_escape_filter($this->env, (($__internal_compile_5 = ($context["params_array"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["pin_text"] ?? null) : null), "html", null, true);
        echo "\" id=\"pin_Text\">
        <img title=\"";
        // line 185
        echo _gettext("Pin text");
        echo "\"
             alt=\">\"
             data-right=\"";
        // line 187
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/anchor.png"], "method", false, false, false, 187), "html", null, true);
        echo "\"
             src=\"";
        // line 188
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/anchor.png"], "method", false, false, false, 188), "html", null, true);
        echo "\">
        <span class=\"hide hidable\">
            ";
        // line 190
        echo _gettext("Pin text");
        // line 191
        echo "        </span>
    </a>
</div>
<div id=\"canvas_outer\">
    <form action=\"\" id=\"container-form\" method=\"post\" name=\"form1\">
        <div id=\"osn_tab\">
            <canvas class=\"designer\" id=\"canvas\" width=\"100\" height=\"100\"></canvas>
        </div>
        <div id=\"layer_menu\" class=\"hide\">
            <div class=\"text-center\">
                <a href=\"#\" class=\"M_butt\" target=\"_self\" >
                    <img title=\"";
        // line 202
        echo _gettext("Hide/Show all");
        echo "\"
                        alt=\"v\"
                        id=\"key_HS_all\"
                        src=\"";
        // line 205
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/downarrow1.png"], "method", false, false, false, 205), "html", null, true);
        echo "\"
                        data-down=\"";
        // line 206
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/downarrow1.png"], "method", false, false, false, 206), "html", null, true);
        echo "\"
                        data-right=\"";
        // line 207
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/rightarrow1.png"], "method", false, false, false, 207), "html", null, true);
        echo "\">
                </a>
                <a href=\"#\" class=\"M_butt\" target=\"_self\" >
                    <img alt=\"v\"
                        id=\"key_HS\"
                        title=\"";
        // line 212
        echo _gettext("Hide/Show tables with no relationship");
        echo "\"
                        src=\"";
        // line 213
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/downarrow2.png"], "method", false, false, false, 213), "html", null, true);
        echo "\"
                        data-down=\"";
        // line 214
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/downarrow2.png"], "method", false, false, false, 214), "html", null, true);
        echo "\"
                        data-right=\"";
        // line 215
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/rightarrow2.png"], "method", false, false, false, 215), "html", null, true);
        echo "\">
                </a>
            </div>
            <div id=\"id_scroll_tab\" class=\"scroll_tab\">
                <table class=\"pma-table\" width=\"100%\" style=\"padding-left: 3px;\"></table>
            </div>
            ";
        // line 222
        echo "            <div class=\"text-center\">
                ";
        // line 223
        echo _gettext("Number of tables:");
        echo " <span id=\"tables_counter\">0</span>
            </div>
            <div id=\"layer_menu_sizer\">
                  <img class=\"icon floatleft\"
                      id=\"layer_menu_sizer_btn\"
                      data-right=\"";
        // line 228
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/resizeright.png"], "method", false, false, false, 228), "html", null, true);
        echo "\"
                      src=\"";
        // line 229
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["theme"] ?? null), "getImgPath", [0 => "designer/resize.png"], "method", false, false, false, 229), "html", null, true);
        echo "\">
            </div>
        </div>
        ";
        // line 233
        echo "        ";
        $this->loadTemplate("database/designer/database_tables.twig", "database/designer/main.twig", 233)->display(twig_to_array(["db" =>         // line 234
($context["db"] ?? null), "text_dir" =>         // line 235
($context["text_dir"] ?? null), "get_db" =>         // line 236
($context["get_db"] ?? null), "has_query" =>         // line 237
($context["has_query"] ?? null), "tab_pos" =>         // line 238
($context["tab_pos"] ?? null), "display_page" =>         // line 239
($context["display_page"] ?? null), "tab_column" =>         // line 240
($context["tab_column"] ?? null), "tables_all_keys" =>         // line 241
($context["tables_all_keys"] ?? null), "tables_pk_or_unique_keys" =>         // line 242
($context["tables_pk_or_unique_keys"] ?? null), "columns_type" =>         // line 243
($context["columns_type"] ?? null), "tables" =>         // line 244
($context["designerTables"] ?? null), "theme" =>         // line 245
($context["theme"] ?? null)]));
        // line 247
        echo "    </form>
</div>
<div id=\"designer_hint\"></div>
";
        // line 251
        echo "<table id=\"layer_new_relation\" class=\"pma-table hide\" width=\"5%\" cellpadding=\"0\" cellspacing=\"0\">
    <tbody>
        <tr>
            <td class=\"frams1\" width=\"10px\">
            </td>
            <td class=\"frams5\" width=\"99%\" >
            </td>
            <td class=\"frams2\" width=\"10px\">
                <div class=\"bor\">
                </div>
            </td>
        </tr>
        <tr>
            <td class=\"frams8\">
            </td>
            <td class=\"input_tab\">
                <table width=\"168\" class=\"pma-table text-center\" cellpadding=\"2\" cellspacing=\"0\">
                    <thead>
                        <tr>
                            <td colspan=\"2\" class=\"text-center nowrap\">
                                <strong>
                                    ";
        // line 272
        echo _gettext("Create relationship");
        // line 273
        echo "                                </strong>
                            </td>
                        </tr>
                    </thead>
                    <tbody id=\"foreign_relation\">
                        <tr>
                            <td colspan=\"2\" class=\"text-center nowrap\">
                                <strong>
                                    FOREIGN KEY
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <td width=\"58\" class=\"nowrap\">
                                on delete
                            </td>
                            <td width=\"102\">
                                <select name=\"on_delete\" id=\"on_delete\">
                                    <option value=\"nix\" selected=\"selected\">
                                        --
                                    </option>
                                    <option value=\"CASCADE\">
                                        CASCADE
                                    </option>
                                    <option value=\"SET NULL\">
                                        SET NULL
                                    </option>
                                    <option value=\"NO ACTION\">
                                        NO ACTION
                                    </option>
                                    <option value=\"RESTRICT\">
                                        RESTRICT
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class=\"nowrap\">
                                on update
                            </td>
                            <td>
                                <select name=\"on_update\" id=\"on_update\">
                                    <option value=\"nix\" selected=\"selected\">
                                        --
                                    </option>
                                    <option value=\"CASCADE\">
                                        CASCADE
                                    </option>
                                    <option value=\"SET NULL\">
                                        SET NULL
                                    </option>
                                    <option value=\"NO ACTION\">
                                        NO ACTION
                                    </option>
                                    <option value=\"RESTRICT\">
                                        RESTRICT
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                    <tbody>
                        <tr>
                            <td colspan=\"2\" class=\"text-center nowrap\">
                                <input type=\"button\" id=\"ok_new_rel_panel\" class=\"btn btn-secondary butt\"
                                    name=\"Button\" value=\"";
        // line 338
        echo _gettext("OK");
        echo "\">
                                <input type=\"button\" id=\"cancel_new_rel_panel\"
                                    class=\"btn btn-secondary butt\" name=\"Button\" value=\"";
        // line 340
        echo _gettext("Cancel");
        echo "\">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td class=\"frams6\">
            </td>
        </tr>
        <tr>
            <td class=\"frams4\">
                <div class=\"bor\">
                </div>
            </td>
            <td class=\"frams7\">
            </td>
            <td class=\"frams3\">
            </td>
        </tr>
    </tbody>
</table>
";
        // line 362
        echo "<table id=\"layer_upd_relation\" class=\"pma-table hide\" width=\"5%\" cellpadding=\"0\" cellspacing=\"0\">
    <tbody>
        <tr>
            <td class=\"frams1\" width=\"10px\">
            </td>
            <td class=\"frams5\" width=\"99%\">
            </td>
            <td class=\"frams2\" width=\"10px\">
                <div class=\"bor\">
                </div>
            </td>
        </tr>
        <tr>
            <td class=\"frams8\">
            </td>
            <td class=\"input_tab\">
                <table width=\"100%\" class=\"pma-table text-center\" cellpadding=\"2\" cellspacing=\"0\">
                    <tr>
                        <td colspan=\"3\" class=\"text-center nowrap\">
                            <strong>
                                ";
        // line 382
        echo _gettext("Delete relationship");
        // line 383
        echo "                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=\"3\" class=\"text-center nowrap\">
                            <input id=\"del_button\" name=\"Button\" type=\"button\"
                                class=\"btn btn-secondary butt\" value=\"";
        // line 389
        echo _gettext("Delete");
        echo "\">
                            <input id=\"cancel_button\" type=\"button\" class=\"btn btn-secondary butt\"
                                name=\"Button\" value=\"";
        // line 391
        echo _gettext("Cancel");
        echo "\">
                        </td>
                    </tr>
                </table>
            </td>
            <td class=\"frams6\">
            </td>
        </tr>
        <tr>
            <td class=\"frams4\">
                <div class=\"bor\">
                </div>
            </td>
            <td class=\"frams7\">
            </td>
            <td class=\"frams3\">
            </td>
        </tr>
    </tbody>
</table>
";
        // line 411
        if (($context["has_query"] ?? null)) {
            // line 412
            echo "    ";
            // line 413
            echo "    <table id=\"designer_optionse\" class=\"pma-table hide\" width=\"5%\" cellpadding=\"0\" cellspacing=\"0\">
        <tbody>
            <tr>
                <td class=\"frams1\" width=\"10px\">
                </td>
                <td class=\"frams5\" width=\"99%\" >
                </td>
                <td class=\"frams2\" width=\"10px\">
                    <div class=\"bor\">
                    </div>
                </td>
            </tr>
            <tr>
                <td class=\"frams8\">
                </td>
                <td class=\"input_tab\">
                    <table width=\"168\" class=\"pma-table text-center\" cellpadding=\"2\" cellspacing=\"0\">
                        <thead>
                            <tr>
                                <td colspan=\"2\" rowspan=\"2\" id=\"option_col_name\" class=\"text-center nowrap\">
                                </td>
                            </tr>
                        </thead>
                        <tbody id=\"where\">
                            <tr>
                                <td class=\"text-center nowrap\">
                                    <b>
                                        WHERE
                                    </b>
                                </td>
                            </tr>
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 446
            echo _gettext("Relationship operator");
            // line 447
            echo "                                </td>
                                <td width=\"102\">
                                    <select name=\"rel_opt\" id=\"rel_opt\">
                                        <option value=\"--\" selected=\"selected\">
                                            --
                                        </option>
                                        <option value=\"=\">
                                            =
                                        </option>
                                        <option value=\"&gt;\">
                                            &gt;
                                        </option>
                                        <option value=\"&lt;\">
                                            &lt;
                                        </option>
                                        <option value=\"&gt;=\">
                                            &gt;=
                                        </option>
                                        <option value=\"&lt;=\">
                                            &lt;=
                                        </option>
                                        <option value=\"NOT\">
                                            NOT
                                        </option>
                                        <option value=\"IN\">
                                            IN
                                        </option>
                                        <option value=\"EXCEPT\">
                                            ";
            // line 475
            echo _gettext("Except");
            // line 476
            echo "                                        </option>
                                        <option value=\"NOT IN\">
                                            NOT IN
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class=\"nowrap\">
                                    ";
            // line 485
            echo _gettext("Value");
            // line 486
            echo "                                    <br>
                                    ";
            // line 487
            echo _gettext("subquery");
            // line 488
            echo "                                </td>
                                <td>
                                    <textarea id=\"Query\" cols=\"18\"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td class=\"text-center nowrap\">
                                    <b>
                                        ";
            // line 496
            echo _gettext("Rename to");
            // line 497
            echo "                                    </b>
                                </td>
                            </tr>
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 502
            echo _gettext("New name");
            // line 503
            echo "                                </td>
                                <td width=\"102\">
                                    <input type=\"text\" id=\"new_name\">
                                </td>
                            </tr>
                            <tr>
                                <td class=\"text-center nowrap\">
                                    <b>
                                        ";
            // line 511
            echo _gettext("Aggregate");
            // line 512
            echo "                                    </b>
                                </td>
                            </tr>
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 517
            echo _gettext("Operator");
            // line 518
            echo "                                </td>
                                <td width=\"102\">
                                    <select name=\"operator\" id=\"operator\">
                                        <option value=\"---\" selected=\"selected\">
                                            ---
                                        </option>
                                        <option value=\"sum\" >
                                            SUM
                                        </option>
                                        <option value=\"min\">
                                            MIN
                                        </option>
                                        <option value=\"max\">
                                            MAX
                                        </option>
                                        <option value=\"avg\">
                                            AVG
                                        </option>
                                        <option value=\"count\">
                                            COUNT
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td width=\"58\" class=\"text-center nowrap\">
                                    <b>
                                        GROUP BY
                                    </b>
                                </td>
                                <td>
                                    <input type=\"checkbox\" value=\"groupby\" id=\"groupby\">
                                </td>
                            </tr>
                            <tr>
                                <td width=\"58\" class=\"text-center nowrap\">
                                    <b>
                                        ORDER BY
                                    </b>
                                </td>
                                <td>
                                    <select name=\"orderby\" id=\"orderby\">
                                        <option value=\"---\" selected=\"selected\">
                                            ---
                                        </option>
                                        <option value=\"ASC\" >
                                            ASC
                                        </option>
                                        <option value=\"DESC\">
                                            DESC
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class=\"text-center nowrap\">
                                    <b>
                                        HAVING
                                    </b>
                                </td>
                            </tr>
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 581
            echo _gettext("Operator");
            // line 582
            echo "                                </td>
                                <td width=\"102\">
                                    <select name=\"h_operator\" id=\"h_operator\">
                                        <option value=\"---\" selected=\"selected\">
                                            ---
                                        </option>
                                        <option value=\"None\" >
                                            ";
            // line 589
            echo _gettext("None");
            // line 590
            echo "                                        </option>
                                        <option value=\"sum\" >
                                            SUM
                                        </option>
                                        <option value=\"min\">
                                            MIN
                                        </option>
                                        <option value=\"max\">
                                            MAX
                                        </option>
                                        <option value=\"avg\">
                                            AVG
                                        </option>
                                        <option value=\"count\">
                                            COUNT
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 611
            echo _gettext("Relationship operator");
            // line 612
            echo "                                </td>
                                <td width=\"102\">
                                    <select name=\"h_rel_opt\" id=\"h_rel_opt\">
                                        <option value=\"--\" selected=\"selected\">
                                            --
                                        </option>
                                        <option value=\"=\">
                                            =
                                        </option>
                                        <option value=\"&gt;\">
                                            &gt;
                                        </option>
                                        <option value=\"&lt;\">
                                            &lt;
                                        </option>
                                        <option value=\"&gt;=\">
                                            &gt;=
                                        </option>
                                        <option value=\"&lt;=\">
                                            &lt;=
                                        </option>
                                        <option value=\"NOT\">
                                            NOT
                                        </option>
                                        <option value=\"IN\">
                                            IN
                                        </option>
                                        <option value=\"EXCEPT\">
                                            ";
            // line 640
            echo _gettext("Except");
            // line 641
            echo "                                        </option>
                                        <option value=\"NOT IN\">
                                            NOT IN
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 650
            echo _gettext("Value");
            // line 651
            echo "                                    <br>
                                    ";
            // line 652
            echo _gettext("subquery");
            // line 653
            echo "                                </td>
                                <td width=\"102\">
                                    <textarea id=\"having\" cols=\"18\"></textarea>
                                </td>
                            </tr>
                        </tbody>
                        <tbody>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <input type=\"hidden\" id=\"ok_add_object_db_and_table_name_url\" />
                                    <input type=\"hidden\" id=\"ok_add_object_db_name\" />
                                    <input type=\"hidden\" id=\"ok_add_object_table_name\" />
                                    <input type=\"hidden\" id=\"ok_add_object_col_name\" />
                                    <input type=\"button\" id=\"ok_add_object\" class=\"btn btn-secondary butt\"
                                        name=\"Button\" value=\"";
            // line 667
            echo _gettext("OK");
            echo "\">
                                    <input type=\"button\" id=\"cancel_close_option\" class=\"btn btn-secondary butt\"
                                        name=\"Button\" value=\"";
            // line 669
            echo _gettext("Cancel");
            echo "\">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class=\"frams6\">
                </td>
            </tr>
            <tr>
                <td class=\"frams4\">
                    <div class=\"bor\">
                    </div>
                </td>
                <td class=\"frams7\">
                </td>
                <td class=\"frams3\">
                </td>
            </tr>
        </tbody>
    </table>
    ";
            // line 691
            echo "    <table id=\"query_rename_to\" class=\"pma-table hide\" width=\"5%\" cellpadding=\"0\" cellspacing=\"0\">
        <tbody>
            <tr>
                <td class=\"frams1\" width=\"10px\">
                </td>
                <td class=\"frams5\" width=\"99%\" >
                </td>
                <td class=\"frams2\" width=\"10px\">
                    <div class=\"bor\">
                    </div>
                </td>
            </tr>
            <tr>
                <td class=\"frams8\">
                </td>
                <td class=\"input_tab\">
                    <table width=\"168\" class=\"pma-table text-center\" cellpadding=\"2\" cellspacing=\"0\">
                        <thead>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <strong>
                                        ";
            // line 712
            echo _gettext("Rename to");
            // line 713
            echo "                                    </strong>
                                </td>
                            </tr>
                        </thead>
                        <tbody id=\"rename_to\">
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 720
            echo _gettext("New name");
            // line 721
            echo "                                </td>
                                <td width=\"102\">
                                    <input type=\"text\" id=\"e_rename\">
                                </td>
                            </tr>
                        </tbody>
                        <tbody>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <input type=\"button\" id=\"ok_edit_rename\" class=\"btn btn-secondary butt\"
                                        name=\"Button\" value=\"";
            // line 731
            echo _gettext("OK");
            echo "\">
                                    <input id=\"query_rename_to_button\" type=\"button\"
                                        class=\"btn btn-secondary butt\"
                                        name=\"Button\"
                                        value=\"";
            // line 735
            echo _gettext("Cancel");
            echo "\">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class=\"frams6\">
                </td>
            </tr>
            <tr>
                <td class=\"frams4\">
                    <div class=\"bor\">
                    </div>
                </td>
                <td class=\"frams7\">
                </td>
                <td class=\"frams3\">
                </td>
            </tr>
        </tbody>
    </table>
    ";
            // line 757
            echo "    <table id=\"query_having\" class=\"pma-table hide\" width=\"5%\" cellpadding=\"0\" cellspacing=\"0\">
        <tbody>
            <tr>
                <td class=\"frams1\" width=\"10px\">
                </td>
                <td class=\"frams5\" width=\"99%\" >
                </td>
                <td class=\"frams2\" width=\"10px\">
                    <div class=\"bor\">
                    </div>
                </td>
            </tr>
            <tr>
                <td class=\"frams8\">
                </td>
                <td class=\"input_tab\">
                    <table width=\"168\" class=\"pma-table text-center\" cellpadding=\"2\" cellspacing=\"0\">
                        <thead>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <strong>
                                        HAVING
                                    </strong>
                                </td>
                            </tr>
                        </thead>
                        <tbody id=\"rename_to\">
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 786
            echo _gettext("Operator");
            // line 787
            echo "                                </td>
                                <td width=\"102\">
                                    <select name=\"hoperator\" id=\"hoperator\">
                                        <option value=\"---\" selected=\"selected\">
                                            ---
                                        </option>
                                        <option value=\"None\" >
                                            None
                                        </option>
                                        <option value=\"sum\" >
                                            SUM
                                        </option>
                                        <option value=\"min\">
                                            MIN
                                        </option>
                                        <option value=\"max\">
                                            MAX
                                        </option>
                                        <option value=\"avg\">
                                            AVG
                                        </option>
                                        <option value=\"count\">
                                            COUNT
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <tr>
                                    <td width=\"58\" class=\"nowrap\">
                                        ";
            // line 817
            echo _gettext("Operator");
            // line 818
            echo "                                    </td>
                                    <td width=\"102\">
                                        <select name=\"hrel_opt\" id=\"hrel_opt\">
                                            <option value=\"--\" selected=\"selected\">
                                                --
                                            </option>
                                            <option value=\"=\">
                                                =
                                            </option>
                                            <option value=\"&gt;\">
                                                &gt;
                                            </option>
                                            <option value=\"&lt;\">
                                                &lt;
                                            </option>
                                            <option value=\"&gt;=\">
                                                &gt;=
                                            </option>
                                            <option value=\"&lt;=\">
                                                &lt;=
                                            </option>
                                            <option value=\"NOT\">
                                                NOT
                                            </option>
                                            <option value=\"IN\">
                                                IN
                                            </option>
                                            <option value=\"EXCEPT\">
                                                ";
            // line 846
            echo _gettext("Except");
            // line 847
            echo "                                            </option>
                                            <option value=\"NOT IN\">
                                                NOT IN
                                            </option>
                                        </select>
                                    </td>
                            </tr>
                            <tr>
                                <td class=\"nowrap\">
                                    ";
            // line 856
            echo _gettext("Value");
            // line 857
            echo "                                    <br>
                                    ";
            // line 858
            echo _gettext("subquery");
            // line 859
            echo "                                </td>
                                <td>
                                    <textarea id=\"hQuery\" cols=\"18\">
                                    </textarea>
                                </td>
                            </tr>
                        </tbody>
                        <tbody>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <input type=\"button\" id=\"ok_edit_having\" class=\"btn btn-secondary butt\"
                                        name=\"Button\" value=\"";
            // line 870
            echo _gettext("OK");
            echo "\">
                                    <input id=\"query_having_button\" type=\"button\"
                                        class=\"btn btn-secondary butt\"
                                        name=\"Button\"
                                        value=\"";
            // line 874
            echo _gettext("Cancel");
            echo "\">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class=\"frams6\">
                </td>
            </tr>
            <tr>
                <td class=\"frams4\">
                    <div class=\"bor\">
                    </div>
                </td>
                <td class=\"frams7\">
                </td>
                <td class=\"frams3\">
                </td>
            </tr>
        </tbody>
    </table>
    ";
            // line 896
            echo "    <table id=\"query_Aggregate\" class=\"pma-table hide\" width=\"5%\" cellpadding=\"0\" cellspacing=\"0\">
        <tbody>
            <tr>
                <td class=\"frams1\" width=\"10px\">
                </td>
                <td class=\"frams5\" width=\"99%\" >
                </td>
                <td class=\"frams2\" width=\"10px\">
                    <div class=\"bor\">
                    </div>
                </td>
            </tr>
            <tr>
                <td class=\"frams8\">
                </td>
                <td class=\"input_tab\">
                    <table width=\"168\" class=\"pma-table text-center\" cellpadding=\"2\" cellspacing=\"0\">
                        <thead>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <strong>
                                        ";
            // line 917
            echo _gettext("Aggregate");
            // line 918
            echo "                                    </strong>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 925
            echo _gettext("Operator");
            // line 926
            echo "                                </td>
                                <td width=\"102\">
                                    <select name=\"operator\" id=\"e_operator\">
                                        <option value=\"---\" selected=\"selected\">
                                            ---
                                        </option>
                                        <option value=\"sum\" >
                                            SUM
                                        </option>
                                        <option value=\"min\">
                                            MIN
                                        </option>
                                        <option value=\"max\">
                                            MAX
                                        </option>
                                        <option value=\"avg\">
                                            AVG
                                        </option>
                                        <option value=\"count\">
                                            COUNT
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                        <tbody>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <input type=\"button\" id=\"ok_edit_Aggr\" class=\"btn btn-secondary butt\"
                                        name=\"Button\" value=\"";
            // line 955
            echo _gettext("OK");
            echo "\">
                                    <input id=\"query_Aggregate_Button\" type=\"button\"
                                        class=\"btn btn-secondary butt\"
                                        name=\"Button\"
                                        value=\"";
            // line 959
            echo _gettext("Cancel");
            echo "\">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class=\"frams6\">
                </td>
            </tr>
            <tr>
                <td class=\"frams4\">
                    <div class=\"bor\">
                    </div>
                </td>
                <td class=\"frams7\">
                </td>
                <td class=\"frams3\">
                </td>
            </tr>
        </tbody>
    </table>
    ";
            // line 981
            echo "    <table id=\"query_where\" class=\"pma-table hide\" width=\"5%\" cellpadding=\"0\" cellspacing=\"0\">
        <tbody>
            <tr>
                <td class=\"frams1\" width=\"10px\">
                </td>
                <td class=\"frams5\" width=\"99%\" >
                </td>
                <td class=\"frams2\" width=\"10px\">
                    <div class=\"bor\">
                    </div>
                </td>
            </tr>
            <tr>
                <td class=\"frams8\">
                </td>
                <td class=\"input_tab\">
                    <table width=\"168\" class=\"pma-table text-center\" cellpadding=\"2\" cellspacing=\"0\">
                        <thead>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <strong>
                                        WHERE
                                    </strong>
                                </td>
                            </tr>
                        </thead>
                        <tbody id=\"rename_to\">
                            <tr>
                                <td width=\"58\" class=\"nowrap\">
                                    ";
            // line 1010
            echo _gettext("Operator");
            // line 1011
            echo "                                </td>
                                <td width=\"102\">
                                    <select name=\"erel_opt\" id=\"erel_opt\">
                                        <option value=\"--\" selected=\"selected\">
                                            --
                                        </option>
                                        <option value=\"=\" >
                                            =
                                        </option>
                                        <option value=\"&gt;\">
                                            &gt;
                                        </option>
                                        <option value=\"&lt;\">
                                            &lt;
                                        </option>
                                        <option value=\"&gt;=\">
                                            &gt;=
                                        </option>
                                        <option value=\"&lt;=\">
                                            &lt;=
                                        </option>
                                        <option value=\"NOT\">
                                            NOT
                                        </option>
                                        <option value=\"IN\">
                                            IN
                                        </option>
                                        <option value=\"EXCEPT\">
                                            ";
            // line 1039
            echo _gettext("Except");
            // line 1040
            echo "                                        </option>
                                        <option value=\"NOT IN\">
                                            NOT IN
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class=\"nowrap\">
                                    ";
            // line 1049
            echo _gettext("Value");
            // line 1050
            echo "                                    <br>
                                    ";
            // line 1051
            echo _gettext("subquery");
            // line 1052
            echo "                                </td>
                                <td>
                                    <textarea id=\"eQuery\" cols=\"18\"></textarea>
                                </td>
                            </tr>
                        </tbody>
                        <tbody>
                            <tr>
                                <td colspan=\"2\" class=\"text-center nowrap\">
                                    <input type=\"button\" id=\"ok_edit_where\" class=\"btn btn-secondary butt\"
                                        name=\"Button\" value=\"";
            // line 1062
            echo _gettext("OK");
            echo "\">
                                    <input id=\"query_where_button\" type=\"button\" class=\"btn btn-secondary butt\" name=\"Button\"
                                           value=\"";
            // line 1064
            echo _gettext("Cancel");
            echo "\">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class=\"frams6\">
                </td>
            </tr>
            <tr>
                <td class=\"frams4\">
                    <div class=\"bor\">
                    </div>
                </td>
                <td class=\"frams7\">
                </td>
                <td class=\"frams3\">
                </td>
            </tr>
        </tbody>
    </table>
    ";
            // line 1086
            echo "    <div class=\"panel\">
        <div class=\"clearfloat\"></div>
        <div id=\"ab\"></div>
        <div class=\"clearfloat\"></div>
    </div>
    <a class=\"trigger\" href=\"#\">";
            // line 1091
            echo _gettext("Active options");
            echo "</a>
    <div id=\"box\">
        <form method=\"post\" action=\"";
            // line 1093
            echo PhpMyAdmin\Url::getFromRoute("/database/qbe");
            echo "\" id=\"vqb_form\">
            <textarea cols=\"80\" name=\"sql_query\" id=\"textSqlquery\" rows=\"15\"></textarea>
            <input type=\"hidden\" name=\"submit_sql\" value=\"true\">
            ";
            // line 1096
            echo PhpMyAdmin\Url::getHiddenInputs(($context["get_db"] ?? null));
            echo "
        </form>
    </div>
";
        }
        // line 1100
        echo "<div id=\"PMA_disable_floating_menubar\"></div>
";
    }

    public function getTemplateName()
    {
        return "database/designer/main.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  1597 => 1100,  1590 => 1096,  1584 => 1093,  1579 => 1091,  1572 => 1086,  1548 => 1064,  1543 => 1062,  1531 => 1052,  1529 => 1051,  1526 => 1050,  1524 => 1049,  1513 => 1040,  1511 => 1039,  1481 => 1011,  1479 => 1010,  1448 => 981,  1424 => 959,  1417 => 955,  1386 => 926,  1384 => 925,  1375 => 918,  1373 => 917,  1350 => 896,  1326 => 874,  1319 => 870,  1306 => 859,  1304 => 858,  1301 => 857,  1299 => 856,  1288 => 847,  1286 => 846,  1256 => 818,  1254 => 817,  1222 => 787,  1220 => 786,  1189 => 757,  1165 => 735,  1158 => 731,  1146 => 721,  1144 => 720,  1135 => 713,  1133 => 712,  1110 => 691,  1086 => 669,  1081 => 667,  1065 => 653,  1063 => 652,  1060 => 651,  1058 => 650,  1047 => 641,  1045 => 640,  1015 => 612,  1013 => 611,  990 => 590,  988 => 589,  979 => 582,  977 => 581,  912 => 518,  910 => 517,  903 => 512,  901 => 511,  891 => 503,  889 => 502,  882 => 497,  880 => 496,  870 => 488,  868 => 487,  865 => 486,  863 => 485,  852 => 476,  850 => 475,  820 => 447,  818 => 446,  783 => 413,  781 => 412,  779 => 411,  756 => 391,  751 => 389,  743 => 383,  741 => 382,  719 => 362,  695 => 340,  690 => 338,  623 => 273,  621 => 272,  598 => 251,  593 => 247,  591 => 245,  590 => 244,  589 => 243,  588 => 242,  587 => 241,  586 => 240,  585 => 239,  584 => 238,  583 => 237,  582 => 236,  581 => 235,  580 => 234,  578 => 233,  572 => 229,  568 => 228,  560 => 223,  557 => 222,  548 => 215,  544 => 214,  540 => 213,  536 => 212,  528 => 207,  524 => 206,  520 => 205,  514 => 202,  501 => 191,  499 => 190,  494 => 188,  490 => 187,  485 => 185,  481 => 184,  477 => 182,  475 => 181,  470 => 179,  466 => 178,  462 => 177,  457 => 176,  452 => 173,  450 => 172,  445 => 170,  441 => 169,  435 => 165,  430 => 162,  428 => 161,  423 => 159,  419 => 158,  416 => 157,  414 => 156,  410 => 154,  408 => 153,  403 => 151,  399 => 150,  395 => 149,  391 => 147,  389 => 146,  384 => 144,  380 => 143,  375 => 140,  373 => 139,  368 => 137,  364 => 136,  360 => 135,  355 => 133,  351 => 132,  347 => 130,  345 => 129,  338 => 127,  334 => 126,  330 => 124,  326 => 123,  321 => 121,  315 => 120,  310 => 119,  305 => 116,  303 => 115,  298 => 113,  294 => 112,  290 => 111,  286 => 109,  284 => 108,  279 => 106,  275 => 105,  270 => 102,  268 => 101,  263 => 99,  259 => 98,  254 => 95,  252 => 94,  247 => 92,  243 => 91,  238 => 88,  236 => 87,  231 => 85,  227 => 84,  222 => 81,  220 => 80,  215 => 78,  211 => 77,  206 => 74,  204 => 73,  199 => 71,  195 => 70,  190 => 67,  188 => 66,  183 => 64,  179 => 63,  174 => 60,  172 => 59,  167 => 57,  163 => 56,  158 => 53,  156 => 52,  151 => 50,  146 => 48,  143 => 47,  141 => 46,  137 => 44,  135 => 43,  130 => 41,  126 => 40,  121 => 37,  119 => 36,  115 => 35,  111 => 34,  106 => 32,  102 => 31,  98 => 30,  94 => 29,  89 => 26,  87 => 25,  82 => 23,  78 => 22,  74 => 21,  69 => 19,  65 => 17,  58 => 13,  52 => 10,  48 => 8,  46 => 7,  40 => 3,  37 => 2,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/designer/main.twig", "/var/www/html/phpMyAdmin/templates/database/designer/main.twig");
    }
}
