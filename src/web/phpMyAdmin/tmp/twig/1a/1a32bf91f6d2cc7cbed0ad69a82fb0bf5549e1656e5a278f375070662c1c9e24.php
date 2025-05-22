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

/* columns_definitions/column_definitions_form.twig */
class __TwigTemplate_a4f22179ea3e81a4f97c3f2c3abbff691d6cd2257fac2c233360642760e12bdf extends \Twig\Template
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
        echo "<form method=\"post\" action=\"";
        echo twig_escape_filter($this->env, ($context["action"] ?? null), "html", null, true);
        echo "\" class=\"";
        // line 2
        echo (((($context["action"] ?? null) == PhpMyAdmin\Url::getFromRoute("/table/create"))) ? ("create_table") : ("append_fields"));
        // line 3
        echo "_form ajax lock-page\">
    ";
        // line 4
        echo PhpMyAdmin\Url::getHiddenInputs(($context["form_params"] ?? null));
        echo "
    ";
        // line 6
        echo "    ";
        // line 7
        echo "    ";
        // line 8
        echo "    <input type=\"hidden\" name=\"primary_indexes\" value=\"";
        // line 9
        (( !twig_test_empty(($context["primary_indexes"] ?? null))) ? (print (twig_escape_filter($this->env, ($context["primary_indexes"] ?? null), "html", null, true))) : (print ("[]")));
        echo "\">
    <input type=\"hidden\" name=\"unique_indexes\" value=\"";
        // line 11
        (( !twig_test_empty(($context["unique_indexes"] ?? null))) ? (print (twig_escape_filter($this->env, ($context["unique_indexes"] ?? null), "html", null, true))) : (print ("[]")));
        echo "\">
    <input type=\"hidden\" name=\"indexes\" value=\"";
        // line 13
        (( !twig_test_empty(($context["indexes"] ?? null))) ? (print (twig_escape_filter($this->env, ($context["indexes"] ?? null), "html", null, true))) : (print ("[]")));
        echo "\">
    <input type=\"hidden\" name=\"fulltext_indexes\" value=\"";
        // line 15
        (( !twig_test_empty(($context["fulltext_indexes"] ?? null))) ? (print (twig_escape_filter($this->env, ($context["fulltext_indexes"] ?? null), "html", null, true))) : (print ("[]")));
        echo "\">
    <input type=\"hidden\" name=\"spatial_indexes\" value=\"";
        // line 17
        (( !twig_test_empty(($context["spatial_indexes"] ?? null))) ? (print (twig_escape_filter($this->env, ($context["spatial_indexes"] ?? null), "html", null, true))) : (print ("[]")));
        echo "\">

    ";
        // line 19
        if ((($context["action"] ?? null) == PhpMyAdmin\Url::getFromRoute("/table/create"))) {
            // line 20
            echo "        <div id=\"table_name_col_no_outer\">
            <table id=\"table_name_col_no\" class=\"pma-table tdblock\">
                <tr class=\"vmiddle floatleft\">
                    <td>";
            // line 23
            echo _gettext("Table name");
            echo ":
                    <input type=\"text\"
                        name=\"table\"
                        size=\"40\"
                        maxlength=\"64\"
                        value=\"";
            // line 28
            (((isset($context["table"]) || array_key_exists("table", $context))) ? (print (twig_escape_filter($this->env, ($context["table"] ?? null), "html", null, true))) : (print ("")));
            echo "\"
                        class=\"textfield\" autofocus required>
                    </td>
                    <td>
                        ";
            // line 32
            echo _gettext("Add");
            // line 33
            echo "                        <input type=\"number\"
                            id=\"added_fields\"
                            name=\"added_fields\"
                            size=\"2\"
                            value=\"1\"
                            min=\"1\"
                            onfocus=\"this.select()\">
                        ";
            // line 40
            echo _gettext("column(s)");
            // line 41
            echo "                        <input class=\"btn btn-secondary\" type=\"button\"
                            name=\"submit_num_fields\"
                            value=\"";
            // line 43
            echo _gettext("Go");
            echo "\">
                    </td>
                </tr>
            </table>
        </div>
    ";
        }
        // line 49
        echo "    ";
        if (twig_test_iterable(($context["content_cells"] ?? null))) {
            // line 50
            echo "        ";
            $this->loadTemplate("columns_definitions/table_fields_definitions.twig", "columns_definitions/column_definitions_form.twig", 50)->display(twig_to_array(["is_backup" =>             // line 51
($context["is_backup"] ?? null), "fields_meta" =>             // line 52
($context["fields_meta"] ?? null), "mimework" =>             // line 53
($context["mimework"] ?? null), "content_cells" =>             // line 54
($context["content_cells"] ?? null), "change_column" =>             // line 55
($context["change_column"] ?? null), "is_virtual_columns_supported" =>             // line 56
($context["is_virtual_columns_supported"] ?? null), "server_version" =>             // line 57
($context["server_version"] ?? null), "browse_mime" =>             // line 58
($context["browse_mime"] ?? null), "server_type" =>             // line 59
($context["server_type"] ?? null), "max_rows" =>             // line 60
($context["max_rows"] ?? null), "char_editing" =>             // line 61
($context["char_editing"] ?? null), "attribute_types" =>             // line 62
($context["attribute_types"] ?? null), "privs_available" =>             // line 63
($context["privs_available"] ?? null), "max_length" =>             // line 64
($context["max_length"] ?? null), "charsets" =>             // line 65
($context["charsets"] ?? null)]));
            // line 67
            echo "    ";
        }
        // line 68
        echo "    ";
        if ((($context["action"] ?? null) == PhpMyAdmin\Url::getFromRoute("/table/create"))) {
            // line 69
            echo "        <div class=\"responsivetable\">
        <table class=\"pma-table\">
            <tr class=\"vtop\">
                <th>";
            // line 72
            echo _gettext("Table comments:");
            echo "</th>
                <td width=\"25\">&nbsp;</td>
                <th>";
            // line 74
            echo _gettext("Collation:");
            echo "</th>
                <td width=\"25\">&nbsp;</td>
                <th>
                    ";
            // line 77
            echo _gettext("Storage Engine:");
            // line 78
            echo "                    ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("Storage_engines");
            echo "
                </th>
                <td width=\"25\">&nbsp;</td>
                <th id=\"storage-engine-connection\">
                    ";
            // line 82
            echo _gettext("Connection:");
            // line 83
            echo "                    ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("federated-create-connection");
            echo "
                </th>
            </tr>
            <tr>
                <td>
                    <input type=\"text\"
                        name=\"comment\"
                        size=\"40\"
                        maxlength=\"60\"
                        value=\"";
            // line 92
            (((isset($context["comment"]) || array_key_exists("comment", $context))) ? (print (twig_escape_filter($this->env, ($context["comment"] ?? null), "html", null, true))) : (print ("")));
            echo "\"
                        class=\"textfield\">
                </td>
                <td width=\"25\">&nbsp;</td>
                <td>
                  <select lang=\"en\" dir=\"ltr\" name=\"tbl_collation\">
                    <option value=\"\"></option>
                    ";
            // line 99
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["charsets"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["charset"]) {
                // line 100
                echo "                      <optgroup label=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "name", [], "any", false, false, false, 100), "html", null, true);
                echo "\" title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "description", [], "any", false, false, false, 100), "html", null, true);
                echo "\">
                        ";
                // line 101
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["charset"], "collations", [], "any", false, false, false, 101));
                foreach ($context['_seq'] as $context["_key"] => $context["collation"]) {
                    // line 102
                    echo "                          <option value=\"";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "name", [], "any", false, false, false, 102), "html", null, true);
                    echo "\" title=\"";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "description", [], "any", false, false, false, 102), "html", null, true);
                    echo "\"";
                    // line 103
                    echo (((twig_get_attribute($this->env, $this->source, $context["collation"], "name", [], "any", false, false, false, 103) == ($context["tbl_collation"] ?? null))) ? (" selected") : (""));
                    echo ">";
                    // line 104
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "name", [], "any", false, false, false, 104), "html", null, true);
                    // line 105
                    echo "</option>
                        ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['collation'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 107
                echo "                      </optgroup>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['charset'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 109
            echo "                  </select>
                </td>
                <td width=\"25\">&nbsp;</td>
                <td>
                  <select class=\"form-control\" name=\"tbl_storage_engine\" aria-label=\"";
            // line 113
            echo _gettext("Storage engine");
            echo "\">
                    ";
            // line 114
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["storage_engines"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["engine"]) {
                // line 115
                echo "                      <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 115), "html", null, true);
                echo "\"";
                if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["engine"], "comment", [], "any", false, false, false, 115))) {
                    echo " title=\"";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "comment", [], "any", false, false, false, 115), "html", null, true);
                    echo "\"";
                }
                // line 116
                echo ((((twig_lower_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 116)) == twig_lower_filter($this->env, ($context["tbl_storage_engine"] ?? null))) || (twig_test_empty(($context["tbl_storage_engine"] ?? null)) && twig_get_attribute($this->env, $this->source, $context["engine"], "is_default", [], "any", false, false, false, 116)))) ? (" selected") : (""));
                echo ">";
                // line 117
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 117), "html", null, true);
                // line 118
                echo "</option>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['engine'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 120
            echo "                  </select>
                </td>
                <td width=\"25\">&nbsp;</td>
                <td>
                    <input type=\"text\"
                        name=\"connection\"
                        size=\"40\"
                        value=\"";
            // line 127
            (((isset($context["connection"]) || array_key_exists("connection", $context))) ? (print (twig_escape_filter($this->env, ($context["connection"] ?? null), "html", null, true))) : (print ("")));
            echo "\"
                        placeholder=\"scheme://user_name[:password]@host_name[:port_num]/db_name/tbl_name\"
                        class=\"textfield\"
                        required=\"required\">
                </td>
            </tr>
            ";
            // line 133
            if (($context["have_partitioning"] ?? null)) {
                // line 134
                echo "                <tr class=\"vtop\">
                    <th colspan=\"5\">
                        ";
                // line 136
                echo _gettext("PARTITION definition:");
                // line 137
                echo "                        ";
                echo \PhpMyAdmin\Html\MySQLDocumentation::show("Partitioning");
                echo "
                    </th>
                </tr>
                <tr>
                    <td colspan=\"5\">
                        ";
                // line 142
                $this->loadTemplate("columns_definitions/partitions.twig", "columns_definitions/column_definitions_form.twig", 142)->display(twig_to_array(["partition_details" =>                 // line 143
($context["partition_details"] ?? null), "storage_engines" =>                 // line 144
($context["storage_engines"] ?? null)]));
                // line 146
                echo "                    </td>
                </tr>
            ";
            }
            // line 149
            echo "        </table>
        </div>
    ";
        }
        // line 152
        echo "    <fieldset class=\"tblFooters\">
        ";
        // line 153
        if (((($context["action"] ?? null) == PhpMyAdmin\Url::getFromRoute("/table/add-field")) || (($context["action"] ?? null) == PhpMyAdmin\Url::getFromRoute("/table/structure/save")))) {
            // line 154
            echo "            <input type=\"checkbox\" name=\"online_transaction\" value=\"ONLINE_TRANSACTION_ENABLED\" />";
            echo _pgettext(            "Online transaction part of the SQL DDL for InnoDB", "Online transaction");
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("innodb-online-ddl");
            echo "
        ";
        }
        // line 156
        echo "        <input class=\"btn btn-secondary preview_sql\" type=\"button\"
            value=\"";
        // line 157
        echo _gettext("Preview SQL");
        echo "\">
        <input class=\"btn btn-primary\" type=\"submit\"
            name=\"do_save_data\"
            value=\"";
        // line 160
        echo _gettext("Save");
        echo "\">
    </fieldset>
    <div id=\"properties_message\"></div>
</form>
";
    }

    public function getTemplateName()
    {
        return "columns_definitions/column_definitions_form.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  348 => 160,  342 => 157,  339 => 156,  332 => 154,  330 => 153,  327 => 152,  322 => 149,  317 => 146,  315 => 144,  314 => 143,  313 => 142,  304 => 137,  302 => 136,  298 => 134,  296 => 133,  287 => 127,  278 => 120,  271 => 118,  269 => 117,  266 => 116,  257 => 115,  253 => 114,  249 => 113,  243 => 109,  236 => 107,  229 => 105,  227 => 104,  224 => 103,  218 => 102,  214 => 101,  207 => 100,  203 => 99,  193 => 92,  180 => 83,  178 => 82,  170 => 78,  168 => 77,  162 => 74,  157 => 72,  152 => 69,  149 => 68,  146 => 67,  144 => 65,  143 => 64,  142 => 63,  141 => 62,  140 => 61,  139 => 60,  138 => 59,  137 => 58,  136 => 57,  135 => 56,  134 => 55,  133 => 54,  132 => 53,  131 => 52,  130 => 51,  128 => 50,  125 => 49,  116 => 43,  112 => 41,  110 => 40,  101 => 33,  99 => 32,  92 => 28,  84 => 23,  79 => 20,  77 => 19,  72 => 17,  68 => 15,  64 => 13,  60 => 11,  56 => 9,  54 => 8,  52 => 7,  50 => 6,  46 => 4,  43 => 3,  41 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "columns_definitions/column_definitions_form.twig", "/var/www/html/phpMyAdmin/templates/columns_definitions/column_definitions_form.twig");
    }
}
