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

/* table/operations/index.twig */
class __TwigTemplate_39ea6b2c06009017b7a0cb39c9191e3e2a8cfce28544d302061d44d79cbb40c0 extends \Twig\Template
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
        if ( !($context["hide_order_table"] ?? null)) {
            // line 4
            echo "  <form method=\"post\" id=\"alterTableOrderby\" action=\"";
            echo PhpMyAdmin\Url::getFromRoute("/table/operations");
            echo "\">
    ";
            // line 5
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
            echo "
    <input type=\"hidden\" name=\"submitorderby\" value=\"1\">

    <div class=\"card mb-2\">
      <div class=\"card-header\">";
            // line 9
            echo _gettext("Alter table order by");
            echo "</div>
      <div class=\"card-body\">
        <div class=\"form-row\">
          <div class=\"col-auto\">
            <label class=\"sr-only\" for=\"tableOrderFieldSelect\">";
            // line 13
            echo _gettext("Column");
            echo "</label>
            <select id=\"tableOrderFieldSelect\" class=\"form-control\" name=\"order_field\" aria-describedby=\"tableOrderFieldSelectHelp\">
              ";
            // line 15
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["columns"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["column"]) {
                // line 16
                echo "                <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "Field", [], "any", false, false, false, 16), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "Field", [], "any", false, false, false, 16), "html", null, true);
                echo "</option>
              ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['column'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 18
            echo "            </select>
            <small id=\"tableOrderFieldSelectHelp\" class=\"form-text text-muted\">
              ";
            // line 20
            echo _pgettext(            "Alter table order by a single field.", "(singly)");
            // line 21
            echo "            </small>
          </div>

          <div class=\"col-auto\">
            <div class=\"form-check\">
              <input class=\"form-check-input\" id=\"tableOrderAscRadio\" name=\"order_order\" type=\"radio\" value=\"asc\" checked>
              <label class=\"form-check-label\" for=\"tableOrderAscRadio\">";
            // line 27
            echo _gettext("Ascending");
            echo "</label>
            </div>
            <div class=\"form-check\">
              <input class=\"form-check-input\" id=\"tableOrderDescRadio\" name=\"order_order\" type=\"radio\" value=\"desc\">
              <label class=\"form-check-label\" for=\"tableOrderDescRadio\">";
            // line 31
            echo _gettext("Descending");
            echo "</label>
            </div>
          </div>
        </div>
      </div>

      <div class=\"card-footer text-right\">
        <input class=\"btn btn-primary\" type=\"submit\" value=\"";
            // line 38
            echo _gettext("Go");
            echo "\">
      </div>
    </div>
  </form>
";
        }
        // line 43
        echo "
<form method=\"post\" action=\"";
        // line 44
        echo PhpMyAdmin\Url::getFromRoute("/table/operations");
        echo "\" id=\"moveTableForm\" class=\"ajax\" onsubmit=\"return Functions.emptyCheckTheField(this, 'new_name')\">
  ";
        // line 45
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
  <input type=\"hidden\" name=\"reload\" value=\"1\">
  <input type=\"hidden\" name=\"what\" value=\"data\">

  <div class=\"card mb-2\">
    <div class=\"card-header\">";
        // line 50
        echo _gettext("Move table to (database.table)");
        echo "</div>
    <div class=\"card-body\">
      <div class=\"form-group form-row\">
        <div class=\"col-auto\">
          <div class=\"input-group\">
            ";
        // line 55
        if ( !twig_test_empty(($context["database_list"] ?? null))) {
            // line 56
            echo "              <select id=\"moveTableDatabaseInput\" class=\"form-control\" name=\"target_db\" aria-label=\"";
            echo _gettext("Database");
            echo "\">
                ";
            // line 57
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["database_list"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["each_db"]) {
                // line 58
                echo "                  <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["each_db"], "name", [], "any", false, false, false, 58), "html", null, true);
                echo "\"";
                echo ((twig_get_attribute($this->env, $this->source, $context["each_db"], "is_selected", [], "any", false, false, false, 58)) ? (" selected") : (""));
                echo ">";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["each_db"], "name", [], "any", false, false, false, 58), "html", null, true);
                echo "</option>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['each_db'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 60
            echo "              </select>
            ";
        } else {
            // line 62
            echo "              <input id=\"moveTableDatabaseInput\" class=\"form-control\" type=\"text\" maxlength=\"100\" name=\"target_db\" value=\"";
            echo twig_escape_filter($this->env, ($context["db"] ?? null), "html", null, true);
            echo "\" aria-label=\"";
            echo _gettext("Database");
            echo "\">
            ";
        }
        // line 64
        echo "            <div class=\"input-group-prepend input-group-append\">
              <span class=\"input-group-text\">.</span>
            </div>
            <input class=\"form-control\" type=\"text\" required=\"required\" name=\"new_name\" maxlength=\"64\" value=\"";
        // line 67
        echo twig_escape_filter($this->env, ($context["table"] ?? null), "html", null, true);
        echo "\" aria-label=\"";
        echo _gettext("Table");
        echo "\">
          </div>
        </div>
      </div>

      <div class=\"form-check\">
        <input class=\"form-check-input\" type=\"checkbox\" name=\"sql_auto_increment\" value=\"1\" id=\"checkbox_auto_increment_mv\">
        <label class=\"form-check-label\" for=\"checkbox_auto_increment_mv\">";
        // line 74
        echo _gettext("Add AUTO_INCREMENT value");
        echo "</label>
      </div>
      <div class=\"form-check\">
        <input class=\"form-check-input\" type=\"checkbox\" name=\"adjust_privileges\" value=\"1\" id=\"checkbox_privileges_tables_move\"";
        // line 78
        if (($context["has_privileges"] ?? null)) {
            echo " checked";
        } else {
            echo " title=\"";
            // line 79
            echo _gettext("You don't have sufficient privileges to perform this operation; Please refer to the documentation for more details.");
            echo "\" disabled";
        }
        echo ">
        <label class=\"form-check-label\" for=\"checkbox_privileges_tables_move\">
          ";
        // line 81
        echo _gettext("Adjust privileges");
        // line 82
        echo "          ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-39");
        echo "
        </label>
      </div>
    </div>

    <div class=\"card-footer text-right\">
      <input class=\"btn btn-primary\" type=\"submit\" name=\"submit_move\" value=\"";
        // line 88
        echo _gettext("Go");
        echo "\">
    </div>
  </div>
</form>

<form method=\"post\" action=\"";
        // line 93
        echo PhpMyAdmin\Url::getFromRoute("/table/operations");
        echo "\" id=\"tableOptionsForm\" class=\"ajax\">
  ";
        // line 94
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
  <input type=\"hidden\" name=\"reload\" value=\"1\">
  <input type=\"hidden\" name=\"submitoptions\" value=\"1\">
  <input type=\"hidden\" name=\"prev_comment\" value=\"";
        // line 97
        echo twig_escape_filter($this->env, ($context["table_comment"] ?? null), "html", null, true);
        echo "\">
  ";
        // line 98
        if (($context["has_auto_increment"] ?? null)) {
            // line 99
            echo "    <input type=\"hidden\" name=\"hidden_auto_increment\" value=\"";
            echo twig_escape_filter($this->env, ($context["auto_increment"] ?? null), "html", null, true);
            echo "\">
  ";
        }
        // line 101
        echo "
  <div class=\"card mb-2\">
    <div class=\"card-header\">";
        // line 103
        echo _gettext("Table options");
        echo "</div>
    <div class=\"card-body\">
      <div class=\"form-group form-inline\">
        <div class=\"form-group\">
          <label for=\"renameTableInput\">";
        // line 107
        echo _gettext("Rename table to");
        echo "</label>
          <input class=\"form-control mx-2\" id=\"renameTableInput\" type=\"text\" name=\"new_name\" maxlength=\"64\" value=\"";
        // line 108
        echo twig_escape_filter($this->env, ($context["table"] ?? null), "html", null, true);
        echo "\" required>
        </div>
        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"adjust_privileges\" value=\"1\" id=\"checkbox_privileges_table_options\"";
        // line 112
        if (($context["has_privileges"] ?? null)) {
            echo " checked";
        } else {
            echo " title=\"";
            // line 113
            echo _gettext("You don't have sufficient privileges to perform this operation; Please refer to the documentation for more details.");
            echo "\" disabled";
        }
        echo ">
          <label class=\"form-check-label\" for=\"checkbox_privileges_table_options\">
            ";
        // line 115
        echo _gettext("Adjust privileges");
        // line 116
        echo "            ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-39");
        echo "
          </label>
        </div>
      </div>

      <div class=\"form-group form-inline\">
        <label for=\"tableCommentsInput\">";
        // line 122
        echo _gettext("Table comments");
        echo "</label>
        <input class=\"form-control ml-2\" id=\"tableCommentsInput\" type=\"text\" name=\"comment\" maxlength=\"2048\" value=\"";
        // line 123
        echo twig_escape_filter($this->env, ($context["table_comment"] ?? null), "html", null, true);
        echo "\">
      </div>

      <div class=\"form-group form-inline\">
        <label class=\"text-nowrap\" for=\"newTableStorageEngineSelect\">
          ";
        // line 128
        echo _gettext("Storage engine");
        // line 129
        echo "          ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("Storage_engines");
        echo "
        </label>
        <select class=\"form-control ml-2\" name=\"new_tbl_storage_engine\" id=\"newTableStorageEngineSelect\">
          ";
        // line 132
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["storage_engines"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["engine"]) {
            // line 133
            echo "            <option value=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 133), "html", null, true);
            echo "\"";
            if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["engine"], "comment", [], "any", false, false, false, 133))) {
                echo " title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "comment", [], "any", false, false, false, 133), "html", null, true);
                echo "\"";
            }
            // line 134
            echo ((((twig_lower_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 134)) == twig_lower_filter($this->env, ($context["storage_engine"] ?? null))) || (twig_test_empty(($context["storage_engine"] ?? null)) && twig_get_attribute($this->env, $this->source, $context["engine"], "is_default", [], "any", false, false, false, 134)))) ? (" selected") : (""));
            echo ">";
            // line 135
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["engine"], "name", [], "any", false, false, false, 135), "html", null, true);
            // line 136
            echo "</option>
          ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['engine'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 138
        echo "        </select>
      </div>

      <div class=\"form-group form-inline\">
        <label for=\"collationSelect\">";
        // line 142
        echo _gettext("Collation");
        echo "</label>
        <select class=\"form-control mx-2\" id=\"collationSelect\" lang=\"en\" dir=\"ltr\" name=\"tbl_collation\">
          <option value=\"\"></option>
          ";
        // line 145
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["charsets"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["charset"]) {
            // line 146
            echo "            <optgroup label=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "getName", [], "method", false, false, false, 146), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "getDescription", [], "method", false, false, false, 146), "html", null, true);
            echo "\">
              ";
            // line 147
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((($__internal_compile_0 = ($context["collations"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[twig_get_attribute($this->env, $this->source, $context["charset"], "getName", [], "method", false, false, false, 147)] ?? null) : null));
            foreach ($context['_seq'] as $context["_key"] => $context["collation"]) {
                // line 148
                echo "                <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 148), "html", null, true);
                echo "\" title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getDescription", [], "method", false, false, false, 148), "html", null, true);
                echo "\"";
                echo (((($context["tbl_collation"] ?? null) == twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 148))) ? (" selected") : (""));
                echo ">
                  ";
                // line 149
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 149), "html", null, true);
                echo "
                </option>
              ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['collation'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 152
            echo "            </optgroup>
          ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['charset'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 154
        echo "        </select>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"change_all_collations\" value=\"1\" id=\"checkbox_change_all_collations\">
          <label class=\"form-check-label\" for=\"checkbox_change_all_collations\">";
        // line 158
        echo _gettext("Change all column collations");
        echo "</label>
        </div>
      </div>

      ";
        // line 162
        if (($context["has_pack_keys"] ?? null)) {
            // line 163
            echo "        <div class=\"form-group form-inline\">
          <label for=\"new_pack_keys\">PACK_KEYS</label>
          <select class=\"form-control ml-2\" name=\"new_pack_keys\" id=\"new_pack_keys\">
            <option value=\"DEFAULT\"";
            // line 166
            echo (((($context["pack_keys"] ?? null) == "DEFAULT")) ? (" selected") : (""));
            echo ">DEFAULT</option>
            <option value=\"0\"";
            // line 167
            echo (((($context["pack_keys"] ?? null) == "0")) ? (" selected") : (""));
            echo ">0</option>
            <option value=\"1\"";
            // line 168
            echo (((($context["pack_keys"] ?? null) == "1")) ? (" selected") : (""));
            echo ">1</option>
          </select>
        </div>
      ";
        }
        // line 172
        echo "
      ";
        // line 173
        if (($context["has_checksum_and_delay_key_write"] ?? null)) {
            // line 174
            echo "        <div class=\"form-group form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"new_checksum\" id=\"new_checksum\" value=\"1\"";
            // line 175
            echo (((($context["checksum"] ?? null) == "1")) ? (" checked") : (""));
            echo ">
          <label class=\"form-check-label\" for=\"new_checksum\">CHECKSUM</label>
        </div>

        <div class=\"form-group form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"new_delay_key_write\" id=\"new_delay_key_write\" value=\"1\"";
            // line 180
            echo (((($context["delay_key_write"] ?? null) == "1")) ? (" checked") : (""));
            echo ">
          <label class=\"form-check-label\" for=\"new_delay_key_write\">DELAY_KEY_WRITE</label>
        </div>
      ";
        }
        // line 184
        echo "
      ";
        // line 185
        if (($context["has_transactional_and_page_checksum"] ?? null)) {
            // line 186
            echo "        <div class=\"form-group form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"new_transactional\" id=\"new_transactional\" value=\"1\"";
            // line 187
            echo (((($context["transactional"] ?? null) == "1")) ? (" checked") : (""));
            echo ">
          <label class=\"form-check-label\" for=\"new_transactional\">TRANSACTIONAL</label>
        </div>

        <div class=\"form-group form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"new_page_checksum\" id=\"new_page_checksum\" value=\"1\"";
            // line 192
            echo (((($context["page_checksum"] ?? null) == "1")) ? (" checked") : (""));
            echo ">
          <label class=\"form-check-label\" for=\"new_page_checksum\">PAGE_CHECKSUM</label>
        </div>
      ";
        }
        // line 196
        echo "
      ";
        // line 197
        if (($context["has_auto_increment"] ?? null)) {
            // line 198
            echo "        <div class=\"form-group form-inline\">
          <label for=\"auto_increment_opt\">AUTO_INCREMENT</label>
          <input class=\"form-control ml-2\" id=\"auto_increment_opt\" type=\"number\" name=\"new_auto_increment\" value=\"";
            // line 200
            echo twig_escape_filter($this->env, ($context["auto_increment"] ?? null), "html", null, true);
            echo "\">
        </div>
      ";
        }
        // line 203
        echo "
      ";
        // line 204
        if ( !twig_test_empty(($context["row_formats"] ?? null))) {
            // line 205
            echo "        <div class=\"form-group form-inline\">
          <label for=\"new_row_format\">ROW_FORMAT</label>
          <select class=\"form-control ml-2\" id=\"new_row_format\" name=\"new_row_format\">
            ";
            // line 208
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["row_formats"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["row_format"]) {
                // line 209
                echo "              <option value=\"";
                echo twig_escape_filter($this->env, $context["row_format"], "html", null, true);
                echo "\"";
                echo ((($context["row_format"] == twig_upper_filter($this->env, ($context["row_format_current"] ?? null)))) ? (" selected") : (""));
                echo ">";
                echo twig_escape_filter($this->env, $context["row_format"], "html", null, true);
                echo "</option>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row_format'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 211
            echo "          </select>
        </div>
      ";
        }
        // line 214
        echo "    </div>

    <div class=\"card-footer text-right\">
      <input class=\"btn btn-primary\" type=\"submit\" value=\"";
        // line 217
        echo _gettext("Go");
        echo "\">
    </div>
  </div>
</form>

<form method=\"post\" action=\"";
        // line 222
        echo PhpMyAdmin\Url::getFromRoute("/table/operations");
        echo "\" name=\"copyTable\" id=\"copyTable\" class=\"ajax\" onsubmit=\"return Functions.emptyCheckTheField(this, 'new_name')\">
  ";
        // line 223
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
  <input type=\"hidden\" name=\"reload\" value=\"1\">

  <div class=\"card mb-2\">
    <div class=\"card-header\">";
        // line 227
        echo _gettext("Copy table to (database.table)");
        echo "</div>
    <div class=\"card-body\">
      <div class=\"form-group form-row\">
        <div class=\"col-auto\">
          <div class=\"input-group\">
            ";
        // line 232
        if ( !twig_test_empty(($context["database_list"] ?? null))) {
            // line 233
            echo "              <select class=\"form-control\" name=\"target_db\" aria-label=\"";
            echo _gettext("Database");
            echo "\">
                ";
            // line 234
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["database_list"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["each_db"]) {
                // line 235
                echo "                  <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["each_db"], "name", [], "any", false, false, false, 235), "html", null, true);
                echo "\"";
                echo ((twig_get_attribute($this->env, $this->source, $context["each_db"], "is_selected", [], "any", false, false, false, 235)) ? (" selected") : (""));
                echo ">";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["each_db"], "name", [], "any", false, false, false, 235), "html", null, true);
                echo "</option>
                ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['each_db'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 237
            echo "              </select>
            ";
        } else {
            // line 239
            echo "              <input class=\"form-control\" type=\"text\" maxlength=\"100\" name=\"target_db\" value=\"";
            echo twig_escape_filter($this->env, ($context["db"] ?? null), "html", null, true);
            echo "\" aria-label=\"";
            echo _gettext("Database");
            echo "\">
            ";
        }
        // line 241
        echo "            <div class=\"input-group-prepend input-group-append\">
              <span class=\"input-group-text\">.</span>
            </div>
            <input class=\"form-control\" type=\"text\" name=\"new_name\" maxlength=\"64\" value=\"";
        // line 244
        echo twig_escape_filter($this->env, ($context["table"] ?? null), "html", null, true);
        echo "\" aria-label=\"";
        echo _gettext("Table");
        echo "\" required>
          </div>
        </div>
      </div>

      <div class=\"form-group\">
        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio1\" value=\"structure\">
          <label class=\"form-check-label\" for=\"whatRadio1\">
            ";
        // line 253
        echo _gettext("Structure only");
        // line 254
        echo "          </label>
        </div>
        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio2\" value=\"data\" checked>
          <label class=\"form-check-label\" for=\"whatRadio2\">
            ";
        // line 259
        echo _gettext("Structure and data");
        // line 260
        echo "          </label>
        </div>
        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio3\" value=\"dataonly\">
          <label class=\"form-check-label\" for=\"whatRadio3\">
            ";
        // line 265
        echo _gettext("Data only");
        // line 266
        echo "          </label>
        </div>
      </div>

      <div class=\"form-group\">
        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"drop_if_exists\" value=\"true\" id=\"checkbox_drop\">
          <label class=\"form-check-label\" for=\"checkbox_drop\">";
        // line 273
        echo twig_escape_filter($this->env, twig_sprintf(_gettext("Add %s"), "DROP TABLE"), "html", null, true);
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"sql_auto_increment\" value=\"1\" id=\"checkbox_auto_increment_cp\">
          <label class=\"form-check-label\" for=\"checkbox_auto_increment_cp\">";
        // line 278
        echo _gettext("Add AUTO_INCREMENT value");
        echo "</label>
        </div>

        ";
        // line 281
        if (($context["has_foreign_keys"] ?? null)) {
            // line 282
            echo "          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"checkbox\" name=\"add_constraints\" value=\"1\" id=\"checkbox_constraints\" checked>
            <label class=\"form-check-label\" for=\"checkbox_constraints\">";
            // line 284
            echo _gettext("Add constraints");
            echo "</label>
          </div>
        ";
        }
        // line 287
        echo "
        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"adjust_privileges\" value=\"1\" id=\"checkbox_adjust_privileges\"";
        // line 290
        if (($context["has_privileges"] ?? null)) {
            echo " checked";
        } else {
            echo " title=\"";
            // line 291
            echo _gettext("You don't have sufficient privileges to perform this operation; Please refer to the documentation for more details.");
            echo "\" disabled";
        }
        echo ">
          <label class=\"form-check-label\" for=\"checkbox_adjust_privileges\">
            ";
        // line 293
        echo _gettext("Adjust privileges");
        // line 294
        echo "            ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-39");
        echo "
          </label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"switch_to_new\" value=\"true\" id=\"checkbox_switch\"";
        // line 299
        echo ((($context["switch_to_new"] ?? null)) ? (" checked") : (""));
        echo ">
          <label class=\"form-check-label\" for=\"checkbox_switch\">";
        // line 300
        echo _gettext("Switch to copied table");
        echo "</label>
        </div>
      </div>
    </div>

    <div class=\"card-footer text-right\">
      <input class=\"btn btn-primary\" type=\"submit\" name=\"submit_copy\" value=\"";
        // line 306
        echo _gettext("Go");
        echo "\">
    </div>
  </div>
</form>

<div class=\"card mb-2\">
  <div class=\"card-header\">";
        // line 312
        echo _gettext("Table maintenance");
        echo "</div>
  <ul class=\"list-group list-group-flush\" id=\"tbl_maintenance\">
    ";
        // line 314
        if (twig_in_filter(($context["storage_engine"] ?? null), [0 => "MYISAM", 1 => "ARIA", 2 => "INNODB", 3 => "BERKELEYDB", 4 => "TOKUDB"])) {
            // line 315
            echo "      <li class=\"list-group-item\">
        <a href=\"";
            // line 316
            echo PhpMyAdmin\Url::getFromRoute("/table/maintenance/analyze");
            echo "\" data-post=\"";
            echo PhpMyAdmin\Url::getCommon(["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "selected_tbl" => [0 => ($context["table"] ?? null)]], "", false);
            echo "\">
          ";
            // line 317
            echo _gettext("Analyze table");
            // line 318
            echo "        </a>
        ";
            // line 319
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("ANALYZE_TABLE");
            echo "
      </li>
    ";
        }
        // line 322
        echo "
    ";
        // line 323
        if (twig_in_filter(($context["storage_engine"] ?? null), [0 => "MYISAM", 1 => "ARIA", 2 => "INNODB", 3 => "TOKUDB"])) {
            // line 324
            echo "      <li class=\"list-group-item\">
        <a href=\"";
            // line 325
            echo PhpMyAdmin\Url::getFromRoute("/table/maintenance/check");
            echo "\" data-post=\"";
            echo PhpMyAdmin\Url::getCommon(["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "selected_tbl" => [0 => ($context["table"] ?? null)]], "", false);
            echo "\">
          ";
            // line 326
            echo _gettext("Check table");
            // line 327
            echo "        </a>
        ";
            // line 328
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("CHECK_TABLE");
            echo "
      </li>
    ";
        }
        // line 331
        echo "
    <li class=\"list-group-item\">
      <a href=\"";
        // line 333
        echo PhpMyAdmin\Url::getFromRoute("/table/maintenance/checksum");
        echo "\" data-post=\"";
        echo PhpMyAdmin\Url::getCommon(["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "selected_tbl" => [0 => ($context["table"] ?? null)]], "", false);
        echo "\">
        ";
        // line 334
        echo _gettext("Checksum table");
        // line 335
        echo "      </a>
      ";
        // line 336
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("CHECKSUM_TABLE");
        echo "
    </li>

    ";
        // line 339
        if ((($context["storage_engine"] ?? null) == "INNODB")) {
            // line 340
            echo "      <li class=\"list-group-item\">
        <a class=\"maintain_action ajax\" href=\"";
            // line 341
            echo PhpMyAdmin\Url::getFromRoute("/sql");
            echo "\" data-post=\"";
            echo PhpMyAdmin\Url::getCommon(twig_array_merge(($context["url_params"] ?? null), ["sql_query" => (("ALTER TABLE " . PhpMyAdmin\Util::backquote(($context["table"] ?? null))) . " ENGINE = InnoDB;")]), "", false);
            echo "\">
          ";
            // line 342
            echo _gettext("Defragment table");
            // line 343
            echo "        </a>
        ";
            // line 344
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("InnoDB_File_Defragmenting");
            echo "
      </li>
    ";
        }
        // line 347
        echo "
    <li class=\"list-group-item\">
      <a class=\"maintain_action ajax\" href=\"";
        // line 349
        echo PhpMyAdmin\Url::getFromRoute("/sql");
        echo "\" data-post=\"";
        echo PhpMyAdmin\Url::getCommon(twig_array_merge(($context["url_params"] ?? null), ["sql_query" => ("FLUSH TABLE " . PhpMyAdmin\Util::backquote(        // line 350
($context["table"] ?? null))), "message_to_show" => twig_sprintf(_gettext("Table %s has been flushed."), twig_escape_filter($this->env,         // line 351
($context["table"] ?? null))), "reload" => true]), "", false);
        // line 353
        echo "\">
        ";
        // line 354
        echo _gettext("Flush the table (FLUSH)");
        // line 355
        echo "      </a>
      ";
        // line 356
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("FLUSH");
        echo "
    </li>

    ";
        // line 359
        if (twig_in_filter(($context["storage_engine"] ?? null), [0 => "MYISAM", 1 => "ARIA", 2 => "INNODB", 3 => "BERKELEYDB", 4 => "TOKUDB"])) {
            // line 360
            echo "      <li class=\"list-group-item\">
        <a href=\"";
            // line 361
            echo PhpMyAdmin\Url::getFromRoute("/table/maintenance/optimize");
            echo "\" data-post=\"";
            echo PhpMyAdmin\Url::getCommon(["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "selected_tbl" => [0 => ($context["table"] ?? null)]], "", false);
            echo "\">
          ";
            // line 362
            echo _gettext("Optimize table");
            // line 363
            echo "        </a>
        ";
            // line 364
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("OPTIMIZE_TABLE");
            echo "
      </li>
    ";
        }
        // line 367
        echo "
    ";
        // line 368
        if (twig_in_filter(($context["storage_engine"] ?? null), [0 => "MYISAM", 1 => "ARIA"])) {
            // line 369
            echo "      <li class=\"list-group-item\">
        <a href=\"";
            // line 370
            echo PhpMyAdmin\Url::getFromRoute("/table/maintenance/repair");
            echo "\" data-post=\"";
            echo PhpMyAdmin\Url::getCommon(["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "selected_tbl" => [0 => ($context["table"] ?? null)]], "", false);
            echo "\">
          ";
            // line 371
            echo _gettext("Repair table");
            // line 372
            echo "        </a>
        ";
            // line 373
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("REPAIR_TABLE");
            echo "
      </li>
    ";
        }
        // line 376
        echo "  </ul>
</div>

";
        // line 379
        if ( !($context["is_system_schema"] ?? null)) {
            // line 380
            echo "  <div class=\"card mb-2\">
    <div class=\"card-header\">";
            // line 381
            echo _gettext("Delete data or table");
            echo "</div>
    <ul class=\"list-group list-group-flush\">
      ";
            // line 383
            if ( !($context["is_view"] ?? null)) {
                // line 384
                echo "        <li class=\"list-group-item\">
          ";
                // line 385
                echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), twig_array_merge(                // line 387
($context["url_params"] ?? null), ["sql_query" => ((("TRUNCATE TABLE " . PhpMyAdmin\Util::backquote(                // line 388
($context["db"] ?? null))) . ".") . PhpMyAdmin\Util::backquote(($context["table"] ?? null))), "goto" => PhpMyAdmin\Url::getFromRoute("/table/structure"), "reload" => true, "message_to_show" => twig_escape_filter($this->env, twig_sprintf(_gettext("Table %s has been emptied."),                 // line 391
($context["table"] ?? null)))]), _gettext("Empty the table (TRUNCATE)"), ["id" => "truncate_tbl_anchor", "class" => "text-danger ajax"]);
                // line 398
                echo "
          ";
                // line 399
                echo \PhpMyAdmin\Html\MySQLDocumentation::show("TRUNCATE_TABLE");
                echo "
        </li>
      ";
            }
            // line 402
            echo "      <li class=\"list-group-item\">
        ";
            // line 403
            echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), twig_array_merge(            // line 405
($context["url_params"] ?? null), ["sql_query" => ((("DROP TABLE " . PhpMyAdmin\Util::backquote(            // line 406
($context["db"] ?? null))) . ".") . PhpMyAdmin\Util::backquote(($context["table"] ?? null))), "goto" => PhpMyAdmin\Url::getFromRoute("/database/operations"), "reload" => true, "purge" => true, "message_to_show" => ((            // line 410
($context["is_view"] ?? null)) ? (twig_escape_filter($this->env, twig_sprintf(_gettext("View %s has been dropped."), ($context["table"] ?? null)))) : (twig_escape_filter($this->env, twig_sprintf(_gettext("Table %s has been dropped."), ($context["table"] ?? null))))), "table" =>             // line 411
($context["table"] ?? null)]), _gettext("Delete the table (DROP)"), ["id" => "drop_tbl_anchor", "class" => "text-danger ajax"]);
            // line 418
            echo "
        ";
            // line 419
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("DROP_TABLE");
            echo "
      </li>
    </ul>
  </div>
";
        }
        // line 424
        echo "
";
        // line 425
        if ( !twig_test_empty(($context["partitions"] ?? null))) {
            // line 426
            echo "  <form id=\"partitionsForm\" class=\"ajax\" method=\"post\" action=\"";
            echo PhpMyAdmin\Url::getFromRoute("/table/operations");
            echo "\">
    ";
            // line 427
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
            echo "
    <input type=\"hidden\" name=\"submit_partition\" value=\"1\">

    <div class=\"card mb-2\">
      <div class=\"card-header\">
        ";
            // line 432
            echo _gettext("Partition maintenance");
            // line 433
            echo "        ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("partitioning_maintenance");
            echo "
      </div>

      <div class=\"card-body\">
        <div class=\"form-group\">
          <label for=\"partition_name\">";
            // line 438
            echo _gettext("Partition");
            echo "</label>
          <select class=\"form-control resize-vertical\" id=\"partition_name\" name=\"partition_name[]\" multiple required>
            ";
            // line 440
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["partitions"] ?? null));
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
            foreach ($context['_seq'] as $context["_key"] => $context["partition"]) {
                // line 441
                echo "              <option value=\"";
                echo twig_escape_filter($this->env, $context["partition"], "html", null, true);
                echo "\"";
                echo ((twig_get_attribute($this->env, $this->source, $context["loop"], "first", [], "any", false, false, false, 441)) ? (" selected") : (""));
                echo ">";
                echo twig_escape_filter($this->env, $context["partition"], "html", null, true);
                echo "</option>
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
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['partition'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 443
            echo "          </select>
        </div>

        <div class=\"form-group form-check-inline\">
          ";
            // line 447
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["partitions_choices"] ?? null));
            foreach ($context['_seq'] as $context["value"] => $context["description"]) {
                // line 448
                echo "            <div class=\"form-check\">
              <input class=\"form-check-input\" type=\"radio\" name=\"partition_operation\" id=\"partitionOperationRadio";
                // line 449
                echo twig_escape_filter($this->env, twig_capitalize_string_filter($this->env, $context["value"]), "html", null, true);
                echo "\" value=\"";
                echo twig_escape_filter($this->env, $context["value"], "html", null, true);
                echo "\"";
                echo ((($context["value"] == "ANALYZE")) ? (" checked") : (""));
                echo ">
              <label class=\"form-check-label\" for=\"partitionOperationRadio";
                // line 450
                echo twig_escape_filter($this->env, twig_capitalize_string_filter($this->env, $context["value"]), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, $context["description"], "html", null, true);
                echo "</label>
            </div>
          ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['value'], $context['description'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 453
            echo "        </div>

        <div class=\"form-text\">
          <a href=\"";
            // line 456
            echo PhpMyAdmin\Url::getFromRoute("/sql", twig_array_merge(($context["url_params"] ?? null), ["sql_query" => (("ALTER TABLE " . PhpMyAdmin\Util::backquote(            // line 457
($context["table"] ?? null))) . " REMOVE PARTITIONING;")]));
            // line 458
            echo "\">";
            echo _gettext("Remove partitioning");
            echo "</a>
        </div>
      </div>

      <div class=\"card-footer text-right\">
        <input class=\"btn btn-primary\" type=\"submit\" value=\"";
            // line 463
            echo _gettext("Go");
            echo "\">
      </div>
    </div>
  </form>
";
        }
        // line 468
        echo "
";
        // line 469
        if ( !twig_test_empty(($context["foreigners"] ?? null))) {
            // line 470
            echo "  <div class=\"card mb-2\">
    <div class=\"card-header\">";
            // line 471
            echo _gettext("Check referential integrity");
            echo "</div>
    <ul class=\"list-group list-group-flush\">
      ";
            // line 473
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["foreigners"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["foreign"]) {
                // line 474
                echo "        <li class=\"list-group-item\">
          <a class=\"text-nowrap\" href=\"";
                // line 475
                echo PhpMyAdmin\Url::getFromRoute("/sql", twig_get_attribute($this->env, $this->source, $context["foreign"], "params", [], "any", false, false, false, 475));
                echo "\">
            ";
                // line 476
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["foreign"], "master", [], "any", false, false, false, 476), "html", null, true);
                echo " -> ";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["foreign"], "db", [], "any", false, false, false, 476), "html", null, true);
                echo ".";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["foreign"], "table", [], "any", false, false, false, 476), "html", null, true);
                echo ".";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["foreign"], "field", [], "any", false, false, false, 476), "html", null, true);
                echo "
          </a>
        </li>
      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['foreign'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 480
            echo "    </ul>
  </div>
";
        }
        // line 483
        echo "
</div>
";
    }

    public function getTemplateName()
    {
        return "table/operations/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  1113 => 483,  1108 => 480,  1092 => 476,  1088 => 475,  1085 => 474,  1081 => 473,  1076 => 471,  1073 => 470,  1071 => 469,  1068 => 468,  1060 => 463,  1051 => 458,  1049 => 457,  1048 => 456,  1043 => 453,  1032 => 450,  1024 => 449,  1021 => 448,  1017 => 447,  1011 => 443,  990 => 441,  973 => 440,  968 => 438,  959 => 433,  957 => 432,  949 => 427,  944 => 426,  942 => 425,  939 => 424,  931 => 419,  928 => 418,  926 => 411,  925 => 410,  924 => 406,  923 => 405,  922 => 403,  919 => 402,  913 => 399,  910 => 398,  908 => 391,  907 => 388,  906 => 387,  905 => 385,  902 => 384,  900 => 383,  895 => 381,  892 => 380,  890 => 379,  885 => 376,  879 => 373,  876 => 372,  874 => 371,  868 => 370,  865 => 369,  863 => 368,  860 => 367,  854 => 364,  851 => 363,  849 => 362,  843 => 361,  840 => 360,  838 => 359,  832 => 356,  829 => 355,  827 => 354,  824 => 353,  822 => 351,  821 => 350,  818 => 349,  814 => 347,  808 => 344,  805 => 343,  803 => 342,  797 => 341,  794 => 340,  792 => 339,  786 => 336,  783 => 335,  781 => 334,  775 => 333,  771 => 331,  765 => 328,  762 => 327,  760 => 326,  754 => 325,  751 => 324,  749 => 323,  746 => 322,  740 => 319,  737 => 318,  735 => 317,  729 => 316,  726 => 315,  724 => 314,  719 => 312,  710 => 306,  701 => 300,  697 => 299,  688 => 294,  686 => 293,  679 => 291,  674 => 290,  670 => 287,  664 => 284,  660 => 282,  658 => 281,  652 => 278,  644 => 273,  635 => 266,  633 => 265,  626 => 260,  624 => 259,  617 => 254,  615 => 253,  601 => 244,  596 => 241,  588 => 239,  584 => 237,  571 => 235,  567 => 234,  562 => 233,  560 => 232,  552 => 227,  545 => 223,  541 => 222,  533 => 217,  528 => 214,  523 => 211,  510 => 209,  506 => 208,  501 => 205,  499 => 204,  496 => 203,  490 => 200,  486 => 198,  484 => 197,  481 => 196,  474 => 192,  466 => 187,  463 => 186,  461 => 185,  458 => 184,  451 => 180,  443 => 175,  440 => 174,  438 => 173,  435 => 172,  428 => 168,  424 => 167,  420 => 166,  415 => 163,  413 => 162,  406 => 158,  400 => 154,  393 => 152,  384 => 149,  375 => 148,  371 => 147,  364 => 146,  360 => 145,  354 => 142,  348 => 138,  341 => 136,  339 => 135,  336 => 134,  327 => 133,  323 => 132,  316 => 129,  314 => 128,  306 => 123,  302 => 122,  292 => 116,  290 => 115,  283 => 113,  278 => 112,  272 => 108,  268 => 107,  261 => 103,  257 => 101,  251 => 99,  249 => 98,  245 => 97,  239 => 94,  235 => 93,  227 => 88,  217 => 82,  215 => 81,  208 => 79,  203 => 78,  197 => 74,  185 => 67,  180 => 64,  172 => 62,  168 => 60,  155 => 58,  151 => 57,  146 => 56,  144 => 55,  136 => 50,  128 => 45,  124 => 44,  121 => 43,  113 => 38,  103 => 31,  96 => 27,  88 => 21,  86 => 20,  82 => 18,  71 => 16,  67 => 15,  62 => 13,  55 => 9,  48 => 5,  43 => 4,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/operations/index.twig", "/var/www/html/phpMyAdmin/templates/table/operations/index.twig");
    }
}
