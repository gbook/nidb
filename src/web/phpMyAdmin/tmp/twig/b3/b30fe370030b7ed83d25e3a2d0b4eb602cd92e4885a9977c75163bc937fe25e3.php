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

/* table/index_form.twig */
class __TwigTemplate_e964caff5f31747b15c3a804bfc73ca799105c7ac333b3b40ef19a14c2d1f9d7 extends \Twig\Template
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
        echo "<form action=\"";
        echo PhpMyAdmin\Url::getFromRoute("/table/indexes");
        echo "\"
    method=\"post\"
    name=\"index_frm\"
    id=\"index_frm\"
    class=\"ajax\">

    ";
        // line 7
        echo PhpMyAdmin\Url::getHiddenInputs(($context["form_params"] ?? null));
        echo "

    <fieldset id=\"index_edit_fields\">
        <div class=\"index_info\">
            <div>
                <div class=\"label\">
                    <strong>
                        <label for=\"input_index_name\">
                            ";
        // line 15
        echo _gettext("Index name:");
        // line 16
        echo "                            ";
        echo \PhpMyAdmin\Html\Generator::showHint(_gettext("\"PRIMARY\" <b>must</b> be the name of and <b>only of</b> a primary key!"));
        echo "
                        </label>
                    </strong>
                </div>

                <input type=\"text\"
                    name=\"index[Key_name]\"
                    id=\"input_index_name\"
                    size=\"25\"
                    maxlength=\"64\"
                    value=\"";
        // line 26
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getName", [], "method", false, false, false, 26), "html", null, true);
        echo "\"
                    onfocus=\"this.select()\">
            </div>

            <div>
                <div class=\"label\">
                    <strong>
                        <label for=\"select_index_choice\">
                            ";
        // line 34
        echo _gettext("Index choice:");
        // line 35
        echo "                            ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("ALTER_TABLE");
        echo "
                        </label>
                    </strong>
                </div>

              <select name=\"index[Index_choice]\" id=\"select_index_choice\"";
        // line 40
        echo ((($context["create_edit_table"] ?? null)) ? (" disabled") : (""));
        echo ">
                ";
        // line 41
        if (((twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getChoice", [], "method", false, false, false, 41) == "PRIMARY") ||  !twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "hasPrimary", [], "method", false, false, false, 41))) {
            // line 42
            echo "                  <option value=\"PRIMARY\"";
            echo (((twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getChoice", [], "method", false, false, false, 42) == "PRIMARY")) ? (" selected") : (""));
            echo ">PRIMARY</option>
                ";
        }
        // line 44
        echo "                <option value=\"INDEX\"";
        echo (((twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getChoice", [], "method", false, false, false, 44) == "INDEX")) ? (" selected") : (""));
        echo ">INDEX</option>
                <option value=\"UNIQUE\"";
        // line 45
        echo (((twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getChoice", [], "method", false, false, false, 45) == "UNIQUE")) ? (" selected") : (""));
        echo ">UNIQUE</option>
                <option value=\"SPATIAL\"";
        // line 46
        echo (((twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getChoice", [], "method", false, false, false, 46) == "SPATIAL")) ? (" selected") : (""));
        echo ">SPATIAL</option>
                <option value=\"FULLTEXT\"";
        // line 47
        echo (((twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getChoice", [], "method", false, false, false, 47) == "FULLTEXT")) ? (" selected") : (""));
        echo ">FULLTEXT</option>
              </select>
            </div>

            <div id=\"indexoptions\"";
        // line 51
        if ((($context["default_sliders_state"] ?? null) != "disabled")) {
            // line 52
            echo (((($context["default_sliders_state"] ?? null) == "closed")) ? (" style=\"display: none; overflow:auto;\"") : (""));
            echo " class=\"pma_auto_slider\" title=\"";
            echo _gettext("Advanced options");
            echo "\"";
        }
        // line 53
        echo ">

            <div>
                <div class=\"label\">
                    <strong>
                        <label for=\"input_key_block_size\">
                            ";
        // line 59
        echo _gettext("Key block size:");
        // line 60
        echo "                        </label>
                    </strong>
                </div>

                <input type=\"text\"
                    name=\"index[Key_block_size]\"
                    id=\"input_key_block_size\"
                    size=\"30\"
                    value=\"";
        // line 68
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getKeyBlockSize", [], "method", false, false, false, 68), "html", null, true);
        echo "\">
            </div>

            <div>

                <div class=\"label\">
                    <strong>
                        <label for=\"select_index_type\">
                            ";
        // line 76
        echo _gettext("Index type:");
        // line 77
        echo "                            ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("ALTER_TABLE");
        echo "
                        </label>
                    </strong>
                </div>

              <select name=\"index[Index_type]\" id=\"select_index_type\">
                ";
        // line 83
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable([0 => "", 1 => "BTREE", 2 => "HASH"]);
        foreach ($context['_seq'] as $context["_key"] => $context["index_type"]) {
            // line 84
            echo "                  <option value=\"";
            echo twig_escape_filter($this->env, $context["index_type"], "html", null, true);
            echo "\"";
            echo (((twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getType", [], "method", false, false, false, 84) == $context["index_type"])) ? (" selected") : (""));
            echo ">";
            echo twig_escape_filter($this->env, $context["index_type"], "html", null, true);
            echo "</option>
                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['index_type'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 86
        echo "              </select>
            </div>

            <div>
                <div class=\"label\">
                    <strong>
                        <label for=\"input_parser\">
                            ";
        // line 93
        echo _gettext("Parser:");
        // line 94
        echo "                        </label>
                    </strong>
                </div>

                <input type=\"text\"
                    name=\"index[Parser]\"
                    id=\"input_parse\"
                    size=\"30\"
                    value=\"";
        // line 102
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getParser", [], "method", false, false, false, 102), "html", null, true);
        echo "\">
            </div>

            <div>
                <div class=\"label\">
                    <strong>
                        <label for=\"input_index_comment\">
                            ";
        // line 109
        echo _gettext("Comment:");
        // line 110
        echo "                        </label>
                    </strong>
                </div>

                <input type=\"text\"
                    name=\"index[Index_comment]\"
                    id=\"input_index_comment\"
                    size=\"30\"
                    maxlength=\"1024\"
                    value=\"";
        // line 119
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getComment", [], "method", false, false, false, 119), "html", null, true);
        echo "\">
            </div>
        </div>
        <!-- end of indexoptions div -->

        <div class=\"clearfloat\"></div>

        <table class=\"pma-table\" id=\"index_columns\">
            <thead>
                <tr>
                    <th></th>
                    <th>
                        ";
        // line 131
        echo _gettext("Column");
        // line 132
        echo "                    </th>
                    <th>
                        ";
        // line 134
        echo _gettext("Size");
        // line 135
        echo "                    </th>
                </tr>
            </thead>
            ";
        // line 138
        $context["spatial_types"] = [0 => "geometry", 1 => "point", 2 => "linestring", 3 => "polygon", 4 => "multipoint", 5 => "multilinestring", 6 => "multipolygon", 7 => "geomtrycollection"];
        // line 148
        echo "            <tbody>
                ";
        // line 149
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getColumns", [], "method", false, false, false, 149));
        foreach ($context['_seq'] as $context["_key"] => $context["column"]) {
            // line 150
            echo "                    <tr class=\"noclick\">
                        <td>
                            <span class=\"drag_icon\" title=\"";
            // line 152
            echo _gettext("Drag to reorder");
            echo "\"></span>
                        </td>
                        <td>
                            <select name=\"index[columns][names][]\">
                                <option value=\"\">
                                    -- ";
            // line 157
            echo _gettext("Ignore");
            echo " --
                                </option>
                                ";
            // line 159
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["fields"] ?? null));
            foreach ($context['_seq'] as $context["field_name"] => $context["field_type"]) {
                // line 160
                echo "                                    ";
                if ((((twig_get_attribute($this->env, $this->source, ($context["index"] ?? null), "getChoice", [], "method", false, false, false, 160) != "FULLTEXT") || preg_match("/(char|text)/i",                 // line 161
$context["field_type"])) && ((twig_get_attribute($this->env, $this->source,                 // line 162
($context["index"] ?? null), "getChoice", [], "method", false, false, false, 162) != "SPATIAL") || twig_in_filter(                // line 163
$context["field_type"], ($context["spatial_types"] ?? null))))) {
                    // line 164
                    echo "
                                        <option value=\"";
                    // line 165
                    echo twig_escape_filter($this->env, $context["field_name"], "html", null, true);
                    echo "\"";
                    // line 166
                    if (($context["field_name"] == twig_get_attribute($this->env, $this->source, $context["column"], "getName", [], "method", false, false, false, 166))) {
                        // line 167
                        echo "                                                selected=\"selected\"";
                    }
                    // line 168
                    echo ">
                                            ";
                    // line 169
                    echo twig_escape_filter($this->env, $context["field_name"], "html", null, true);
                    echo " [";
                    echo twig_escape_filter($this->env, $context["field_type"], "html", null, true);
                    echo "]
                                        </option>
                                    ";
                }
                // line 172
                echo "                                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['field_name'], $context['field_type'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 173
            echo "                            </select>
                        </td>
                        <td>
                            <input type=\"text\"
                                size=\"5\"
                                onfocus=\"this.select()\"
                                name=\"index[columns][sub_parts][]\"
                                value=\"";
            // line 181
            (((twig_get_attribute($this->env, $this->source,             // line 180
($context["index"] ?? null), "getChoice", [], "method", false, false, false, 180) != "SPATIAL")) ? (print (twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source,             // line 181
$context["column"], "getSubPart", [], "method", false, false, false, 181), "html", null, true))) : (print ("")));
            echo "\">
                        </td>
                    </tr>
                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['column'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 185
        echo "                ";
        if ((($context["add_fields"] ?? null) > 0)) {
            // line 186
            echo "                    ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(range(1, ($context["add_fields"] ?? null)));
            foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
                // line 187
                echo "                        <tr class=\"noclick\">
                            <td>
                                <span class=\"drag_icon\" title=\"";
                // line 189
                echo _gettext("Drag to reorder");
                echo "\"></span>
                            </td>
                            <td>
                                <select name=\"index[columns][names][]\">
                                    <option value=\"\">-- ";
                // line 193
                echo _gettext("Ignore");
                echo " --</option>
                                    ";
                // line 194
                $context["j"] = 0;
                // line 195
                echo "                                    ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(($context["fields"] ?? null));
                foreach ($context['_seq'] as $context["field_name"] => $context["field_type"]) {
                    // line 196
                    echo "                                        ";
                    if (($context["create_edit_table"] ?? null)) {
                        // line 197
                        echo "                                            ";
                        $context["col_index"] = (($__internal_compile_0 = $context["field_type"]) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[1] ?? null) : null);
                        // line 198
                        echo "                                            ";
                        $context["field_type"] = (($__internal_compile_1 = $context["field_type"]) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1[0] ?? null) : null);
                        // line 199
                        echo "                                        ";
                    }
                    // line 200
                    echo "                                        ";
                    $context["j"] = (($context["j"] ?? null) + 1);
                    // line 201
                    echo "                                        <option value=\"";
                    echo twig_escape_filter($this->env, (((isset($context["col_index"]) || array_key_exists("col_index", $context))) ? (                    // line 202
($context["col_index"] ?? null)) : ($context["field_name"])), "html", null, true);
                    echo "\"";
                    // line 203
                    echo (((($context["j"] ?? null) == $context["i"])) ? (" selected=\"selected\"") : (""));
                    echo ">
                                            ";
                    // line 204
                    echo twig_escape_filter($this->env, $context["field_name"], "html", null, true);
                    echo " [";
                    echo twig_escape_filter($this->env, $context["field_type"], "html", null, true);
                    echo "]
                                        </option>
                                    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['field_name'], $context['field_type'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 207
                echo "                                </select>
                            </td>
                            <td>
                                <input type=\"text\"
                                    size=\"5\"
                                    onfocus=\"this.select()\"
                                    name=\"index[columns][sub_parts][]\"
                                    value=\"\">
                            </td>
                        </tr>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 218
            echo "                ";
        }
        // line 219
        echo "            </tbody>
        </table>
        <div class=\"add_more\">

            <div class=\"slider\"></div>
            <div class=\"add_fields hide\">
                <input class=\"btn btn-secondary\" type=\"submit\"
                    id=\"add_fields\"
                    value=\"";
        // line 227
        echo twig_escape_filter($this->env, twig_sprintf(_gettext("Add %s column(s) to index"), 1), "html", null, true);
        echo "\">
            </div>
        </div>
        </div>
    </fieldset>
    <fieldset class=\"tblFooters\">
        <button class=\"btn btn-secondary\" type=\"submit\" id=\"preview_index_frm\">";
        // line 233
        echo _gettext("Preview SQL");
        echo "</button>
    </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "table/index_form.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  456 => 233,  447 => 227,  437 => 219,  434 => 218,  418 => 207,  407 => 204,  403 => 203,  400 => 202,  398 => 201,  395 => 200,  392 => 199,  389 => 198,  386 => 197,  383 => 196,  378 => 195,  376 => 194,  372 => 193,  365 => 189,  361 => 187,  356 => 186,  353 => 185,  343 => 181,  342 => 180,  341 => 181,  332 => 173,  326 => 172,  318 => 169,  315 => 168,  312 => 167,  310 => 166,  307 => 165,  304 => 164,  302 => 163,  301 => 162,  300 => 161,  298 => 160,  294 => 159,  289 => 157,  281 => 152,  277 => 150,  273 => 149,  270 => 148,  268 => 138,  263 => 135,  261 => 134,  257 => 132,  255 => 131,  240 => 119,  229 => 110,  227 => 109,  217 => 102,  207 => 94,  205 => 93,  196 => 86,  183 => 84,  179 => 83,  169 => 77,  167 => 76,  156 => 68,  146 => 60,  144 => 59,  136 => 53,  130 => 52,  128 => 51,  121 => 47,  117 => 46,  113 => 45,  108 => 44,  102 => 42,  100 => 41,  96 => 40,  87 => 35,  85 => 34,  74 => 26,  60 => 16,  58 => 15,  47 => 7,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/index_form.twig", "/var/www/html/phpMyAdmin/templates/table/index_form.twig");
    }
}
