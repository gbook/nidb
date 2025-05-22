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

/* server/privileges/privileges_table.twig */
class __TwigTemplate_b4e136da65d1f07bb8de2a07c8a60fca0e35b2cfaaf7e6111d4fa5ab376281e6 extends \Twig\Template
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
        if ( !twig_test_empty(($context["columns"] ?? null))) {
            // line 2
            echo "
  <input type=\"hidden\" name=\"grant_count\" value=\"";
            // line 3
            echo twig_escape_filter($this->env, twig_length_filter($this->env, ($context["row"] ?? null)), "html", null, true);
            echo "\">
  <input type=\"hidden\" name=\"column_count\" value=\"";
            // line 4
            echo twig_escape_filter($this->env, twig_length_filter($this->env, ($context["columns"] ?? null)), "html", null, true);
            echo "\">
  <fieldset id=\"fieldset_user_priv\">
    <legend data-submenu-label=\"";
            // line 6
            echo _gettext("Table");
            echo "\">
      ";
            // line 7
            echo _gettext("Table-specific privileges");
            // line 8
            echo "    </legend>
    <p>
      <small><em>";
            // line 10
            echo _gettext("Note: MySQL privilege names are expressed in English.");
            echo "</em></small>
    </p>

    <div class=\"item\" id=\"div_item_select\">
      <label for=\"select_select_priv\">
        <code><dfn title=\"";
            // line 15
            echo _gettext("Allows reading data.");
            echo "\">SELECT</dfn></code>
      </label>

      <select class=\"resize-vertical\" id=\"select_select_priv\" name=\"Select_priv[]\" size=\"8\" multiple>
        ";
            // line 19
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["columns"] ?? null));
            foreach ($context['_seq'] as $context["curr_col"] => $context["curr_col_privs"]) {
                // line 20
                echo "          <option value=\"";
                echo twig_escape_filter($this->env, $context["curr_col"], "html", null, true);
                echo "\"";
                echo (((((($__internal_compile_0 = ($context["row"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["Select_priv"] ?? null) : null) == "Y") || (($__internal_compile_1 = $context["curr_col_privs"]) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["Select"] ?? null) : null))) ? (" selected") : (""));
                echo ">
            ";
                // line 21
                echo twig_escape_filter($this->env, $context["curr_col"], "html", null, true);
                echo "
          </option>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['curr_col'], $context['curr_col_privs'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 24
            echo "      </select>

      <em>";
            // line 26
            echo _gettext("Or");
            echo "</em>
      <label for=\"checkbox_Select_priv_none\">
        <input type=\"checkbox\" name=\"Select_priv_none\" id=\"checkbox_Select_priv_none\" title=\"";
            // line 29
            echo _pgettext(            "None privileges", "None");
            echo "\">
        ";
            // line 30
            echo _pgettext(            "None privileges", "None");
            // line 31
            echo "      </label>
    </div>

    <div class=\"item\" id=\"div_item_insert\">
      <label for=\"select_insert_priv\">
        <code><dfn title=\"";
            // line 36
            echo _gettext("Allows inserting and replacing data.");
            echo "\">INSERT</dfn></code>
      </label>

      <select class=\"resize-vertical\" id=\"select_insert_priv\" name=\"Insert_priv[]\" size=\"8\" multiple>
        ";
            // line 40
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["columns"] ?? null));
            foreach ($context['_seq'] as $context["curr_col"] => $context["curr_col_privs"]) {
                // line 41
                echo "          <option value=\"";
                echo twig_escape_filter($this->env, $context["curr_col"], "html", null, true);
                echo "\"";
                echo (((((($__internal_compile_2 = ($context["row"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["Insert_priv"] ?? null) : null) == "Y") || (($__internal_compile_3 = $context["curr_col_privs"]) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["Insert"] ?? null) : null))) ? (" selected") : (""));
                echo ">
            ";
                // line 42
                echo twig_escape_filter($this->env, $context["curr_col"], "html", null, true);
                echo "
          </option>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['curr_col'], $context['curr_col_privs'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 45
            echo "      </select>

      <em>";
            // line 47
            echo _gettext("Or");
            echo "</em>
      <label for=\"checkbox_Insert_priv_none\">
        <input type=\"checkbox\" name=\"Insert_priv_none\" id=\"checkbox_Insert_priv_none\" title=\"";
            // line 50
            echo _pgettext(            "None privileges", "None");
            echo "\">
        ";
            // line 51
            echo _pgettext(            "None privileges", "None");
            // line 52
            echo "      </label>
    </div>

    <div class=\"item\" id=\"div_item_update\">
      <label for=\"select_update_priv\">
        <code><dfn title=\"";
            // line 57
            echo _gettext("Allows changing data.");
            echo "\">UPDATE</dfn></code>
      </label>

      <select class=\"resize-vertical\" id=\"select_update_priv\" name=\"Update_priv[]\" size=\"8\" multiple>
        ";
            // line 61
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["columns"] ?? null));
            foreach ($context['_seq'] as $context["curr_col"] => $context["curr_col_privs"]) {
                // line 62
                echo "          <option value=\"";
                echo twig_escape_filter($this->env, $context["curr_col"], "html", null, true);
                echo "\"";
                echo (((((($__internal_compile_4 = ($context["row"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["Update_priv"] ?? null) : null) == "Y") || (($__internal_compile_5 = $context["curr_col_privs"]) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["Update"] ?? null) : null))) ? (" selected") : (""));
                echo ">
            ";
                // line 63
                echo twig_escape_filter($this->env, $context["curr_col"], "html", null, true);
                echo "
          </option>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['curr_col'], $context['curr_col_privs'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 66
            echo "      </select>

      <em>";
            // line 68
            echo _gettext("Or");
            echo "</em>
      <label for=\"checkbox_Update_priv_none\">
        <input type=\"checkbox\" name=\"Update_priv_none\" id=\"checkbox_Update_priv_none\" title=\"";
            // line 71
            echo _pgettext(            "None privileges", "None");
            echo "\">
        ";
            // line 72
            echo _pgettext(            "None privileges", "None");
            // line 73
            echo "      </label>
    </div>

    <div class=\"item\" id=\"div_item_references\">
      <label for=\"select_references_priv\">
        <code><dfn title=\"";
            // line 78
            echo _gettext("Has no effect in this MySQL version.");
            echo "\">REFERENCES</dfn></code>
      </label>

      <select class=\"resize-vertical\" id=\"select_references_priv\" name=\"References_priv[]\" size=\"8\" multiple>
        ";
            // line 82
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["columns"] ?? null));
            foreach ($context['_seq'] as $context["curr_col"] => $context["curr_col_privs"]) {
                // line 83
                echo "          <option value=\"";
                echo twig_escape_filter($this->env, $context["curr_col"], "html", null, true);
                echo "\"";
                echo (((((($__internal_compile_6 = ($context["row"] ?? null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6["References_priv"] ?? null) : null) == "Y") || (($__internal_compile_7 = $context["curr_col_privs"]) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7["References"] ?? null) : null))) ? (" selected") : (""));
                echo ">
            ";
                // line 84
                echo twig_escape_filter($this->env, $context["curr_col"], "html", null, true);
                echo "
          </option>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['curr_col'], $context['curr_col_privs'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 87
            echo "      </select>

      <em>";
            // line 89
            echo _gettext("Or");
            echo "</em>
      <label for=\"checkbox_References_priv_none\">
        <input type=\"checkbox\" name=\"References_priv_none\" id=\"checkbox_References_priv_none\" title=\"";
            // line 92
            echo _pgettext(            "None privileges", "None");
            echo "\">
        ";
            // line 93
            echo _pgettext(            "None privileges", "None");
            // line 94
            echo "      </label>
    </div>

    <div class=\"item\">
      <div class=\"item\">
        <input type=\"checkbox\" name=\"Delete_priv\" id=\"checkbox_Delete_priv\" value=\"Y\" title=\"";
            // line 100
            echo _gettext("Allows deleting data.");
            echo "\"";
            echo ((((($__internal_compile_8 = ($context["row"] ?? null)) && is_array($__internal_compile_8) || $__internal_compile_8 instanceof ArrayAccess ? ($__internal_compile_8["Delete_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Delete_priv\">
          <code>
            <dfn title=\"";
            // line 103
            echo _gettext("Allows deleting data.");
            echo "\">
              DELETE
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        <input type=\"checkbox\" name=\"Create_priv\" id=\"checkbox_Create_priv\" value=\"Y\" title=\"";
            // line 112
            echo _gettext("Allows creating new tables.");
            echo "\"";
            echo ((((($__internal_compile_9 = ($context["row"] ?? null)) && is_array($__internal_compile_9) || $__internal_compile_9 instanceof ArrayAccess ? ($__internal_compile_9["Create_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Create_priv\">
          <code>
            <dfn title=\"";
            // line 115
            echo _gettext("Allows creating new tables.");
            echo "\">
              CREATE
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        <input type=\"checkbox\" name=\"Drop_priv\" id=\"checkbox_Drop_priv\" value=\"Y\" title=\"";
            // line 124
            echo _gettext("Allows dropping tables.");
            echo "\"";
            echo ((((($__internal_compile_10 = ($context["row"] ?? null)) && is_array($__internal_compile_10) || $__internal_compile_10 instanceof ArrayAccess ? ($__internal_compile_10["Drop_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Drop_priv\">
          <code>
            <dfn title=\"";
            // line 127
            echo _gettext("Allows dropping tables.");
            echo "\">
              DROP
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        <input type=\"checkbox\" name=\"Grant_priv\" id=\"checkbox_Grant_priv\" value=\"Y\" title=\"";
            // line 136
            echo _gettext("Allows user to give to other users or remove from other users the privileges that user possess yourself.");
            echo "\"";
            // line 137
            echo ((((($__internal_compile_11 = ($context["row"] ?? null)) && is_array($__internal_compile_11) || $__internal_compile_11 instanceof ArrayAccess ? ($__internal_compile_11["Grant_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Grant_priv\">
          <code>
            <dfn title=\"";
            // line 140
            echo _gettext("Allows user to give to other users or remove from other users the privileges that user possess yourself.");
            echo "\">
              GRANT
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        <input type=\"checkbox\" name=\"Index_priv\" id=\"checkbox_Index_priv\" value=\"Y\" title=\"";
            // line 149
            echo _gettext("Allows creating and dropping indexes.");
            echo "\"";
            echo ((((($__internal_compile_12 = ($context["row"] ?? null)) && is_array($__internal_compile_12) || $__internal_compile_12 instanceof ArrayAccess ? ($__internal_compile_12["Index_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Index_priv\">
          <code>
            <dfn title=\"";
            // line 152
            echo _gettext("Allows creating and dropping indexes.");
            echo "\">
              INDEX
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        <input type=\"checkbox\" name=\"Alter_priv\" id=\"checkbox_Alter_priv\" value=\"Y\" title=\"";
            // line 161
            echo _gettext("Allows altering the structure of existing tables.");
            echo "\"";
            echo ((((($__internal_compile_13 = ($context["row"] ?? null)) && is_array($__internal_compile_13) || $__internal_compile_13 instanceof ArrayAccess ? ($__internal_compile_13["Alter_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Alter_priv\">
          <code>
            <dfn title=\"";
            // line 164
            echo _gettext("Allows altering the structure of existing tables.");
            echo "\">
              ALTER
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        <input type=\"checkbox\" name=\"Create_view_priv\" id=\"checkbox_Create_view_priv\" value=\"Y\" title=\"";
            // line 173
            echo _gettext("Allows creating new views.");
            echo "\"";
            echo ((((($__internal_compile_14 = ($context["row"] ?? null)) && is_array($__internal_compile_14) || $__internal_compile_14 instanceof ArrayAccess ? ($__internal_compile_14["Create View_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Create_view_priv\">
          <code>
            <dfn title=\"";
            // line 176
            echo _gettext("Allows creating new views.");
            echo "\">
              CREATE VIEW
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        <input type=\"checkbox\" name=\"Show_view_priv\" id=\"checkbox_Show_view_priv\" value=\"Y\" title=\"";
            // line 185
            echo _gettext("Allows performing SHOW CREATE VIEW queries.");
            echo "\"";
            echo ((((($__internal_compile_15 = ($context["row"] ?? null)) && is_array($__internal_compile_15) || $__internal_compile_15 instanceof ArrayAccess ? ($__internal_compile_15["Show view_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Show_view_priv\">
          <code>
            <dfn title=\"";
            // line 188
            echo _gettext("Allows performing SHOW CREATE VIEW queries.");
            echo "\">
              SHOW VIEW
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        <input type=\"checkbox\" name=\"Trigger_priv\" id=\"checkbox_Trigger_priv\" value=\"Y\" title=\"";
            // line 197
            echo _gettext("Allows creating and dropping triggers.");
            echo "\"";
            echo ((((($__internal_compile_16 = ($context["row"] ?? null)) && is_array($__internal_compile_16) || $__internal_compile_16 instanceof ArrayAccess ? ($__internal_compile_16["Trigger_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
        <label for=\"checkbox_Trigger_priv\">
          <code>
            <dfn title=\"";
            // line 200
            echo _gettext("Allows creating and dropping triggers.");
            echo "\">
              TRIGGER
            </dfn>
          </code>
        </label>
      </div>

      ";
            // line 207
            if ((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "Delete versioning rows_priv", [], "array", true, true, false, 207) || twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "Delete_history_priv", [], "array", true, true, false, 207))) {
                // line 208
                echo "        <div class=\"item\">
          <input type=\"checkbox\" name=\"Delete_history_priv\" id=\"checkbox_Delete_history_priv\" value=\"Y\" title=\"";
                // line 210
                echo _gettext("Allows deleting historical rows.");
                echo "\"";
                // line 211
                echo (((((($__internal_compile_17 = ($context["row"] ?? null)) && is_array($__internal_compile_17) || $__internal_compile_17 instanceof ArrayAccess ? ($__internal_compile_17["Delete versioning rows_priv"] ?? null) : null) == "Y") || ((($__internal_compile_18 = ($context["row"] ?? null)) && is_array($__internal_compile_18) || $__internal_compile_18 instanceof ArrayAccess ? ($__internal_compile_18["Delete_history_priv"] ?? null) : null) == "Y"))) ? (" checked") : (""));
                echo ">
          <label for=\"checkbox_Delete_history_priv\">
            <code>
              <dfn title=\"";
                // line 214
                echo _gettext("Allows deleting historical rows.");
                echo "\">
                DELETE HISTORY
              </dfn>
            </code>
          </label>
        </div>
      ";
            }
            // line 221
            echo "    </div>
    <div class=\"clearfloat\"></div>
  </fieldset>

";
        } else {
            // line 226
            echo "
";
            // line 227
            $context["grant_count"] = 0;
            // line 228
            echo "<fieldset id=\"fieldset_user_global_rights\">
  <legend data-submenu-label=\"";
            // line 230
            if (($context["is_global"] ?? null)) {
                // line 231
                echo _gettext("Global");
            } elseif (            // line 232
($context["is_database"] ?? null)) {
                // line 233
                echo _gettext("Database");
            } else {
                // line 235
                echo _gettext("Table");
            }
            // line 236
            echo "\">
    ";
            // line 237
            if (($context["is_global"] ?? null)) {
                // line 238
                echo "      ";
                echo _gettext("Global privileges");
                // line 239
                echo "    ";
            } elseif (($context["is_database"] ?? null)) {
                // line 240
                echo "      ";
                echo _gettext("Database-specific privileges");
                // line 241
                echo "    ";
            } else {
                // line 242
                echo "      ";
                echo _gettext("Table-specific privileges");
                // line 243
                echo "    ";
            }
            // line 244
            echo "    <input type=\"checkbox\" id=\"addUsersForm_checkall\" class=\"checkall_box\" title=\"";
            echo _gettext("Check all");
            echo "\">
    <label for=\"addUsersForm_checkall\">";
            // line 245
            echo _gettext("Check all");
            echo "</label>
  </legend>
  <p>
    <small><em>";
            // line 248
            echo _gettext("Note: MySQL privilege names are expressed in English.");
            echo "</em></small>
  </p>

  <fieldset>
    <legend>
      <input type=\"checkbox\" class=\"sub_checkall_box\" id=\"checkall_Data_priv\" title=\"";
            // line 253
            echo _gettext("Check all");
            echo "\">
      <label for=\"checkall_Data_priv\">";
            // line 254
            echo _gettext("Data");
            echo "</label>
    </legend>

    <div class=\"item\">
      ";
            // line 258
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 259
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Select_priv\" id=\"checkbox_Select_priv\" value=\"Y\" title=\"";
            // line 260
            echo _gettext("Allows reading data.");
            echo "\"";
            echo ((((($__internal_compile_19 = ($context["row"] ?? null)) && is_array($__internal_compile_19) || $__internal_compile_19 instanceof ArrayAccess ? ($__internal_compile_19["Select_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Select_priv\">
        <code>
          <dfn title=\"";
            // line 263
            echo _gettext("Allows reading data.");
            echo "\">
            SELECT
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 271
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 272
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Insert_priv\" id=\"checkbox_Insert_priv\" value=\"Y\" title=\"";
            // line 273
            echo _gettext("Allows inserting and replacing data.");
            echo "\"";
            echo ((((($__internal_compile_20 = ($context["row"] ?? null)) && is_array($__internal_compile_20) || $__internal_compile_20 instanceof ArrayAccess ? ($__internal_compile_20["Insert_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Insert_priv\">
        <code>
          <dfn title=\"";
            // line 276
            echo _gettext("Allows inserting and replacing data.");
            echo "\">
            INSERT
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 284
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 285
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Update_priv\" id=\"checkbox_Update_priv\" value=\"Y\" title=\"";
            // line 286
            echo _gettext("Allows changing data.");
            echo "\"";
            echo ((((($__internal_compile_21 = ($context["row"] ?? null)) && is_array($__internal_compile_21) || $__internal_compile_21 instanceof ArrayAccess ? ($__internal_compile_21["Update_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Update_priv\">
        <code>
          <dfn title=\"";
            // line 289
            echo _gettext("Allows changing data.");
            echo "\">
            UPDATE
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 297
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 298
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Delete_priv\" id=\"checkbox_Delete_priv\" value=\"Y\" title=\"";
            // line 299
            echo _gettext("Allows deleting data.");
            echo "\"";
            echo ((((($__internal_compile_22 = ($context["row"] ?? null)) && is_array($__internal_compile_22) || $__internal_compile_22 instanceof ArrayAccess ? ($__internal_compile_22["Delete_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Delete_priv\">
        <code>
          <dfn title=\"";
            // line 302
            echo _gettext("Allows deleting data.");
            echo "\">
            DELETE
          </dfn>
        </code>
      </label>
    </div>

    ";
            // line 309
            if (($context["is_global"] ?? null)) {
                // line 310
                echo "      <div class=\"item\">
        ";
                // line 311
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 312
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"File_priv\" id=\"checkbox_File_priv\" value=\"Y\" title=\"";
                // line 313
                echo _gettext("Allows importing data from and exporting data into files.");
                echo "\"";
                echo ((((($__internal_compile_23 = ($context["row"] ?? null)) && is_array($__internal_compile_23) || $__internal_compile_23 instanceof ArrayAccess ? ($__internal_compile_23["File_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_File_priv\">
          <code>
            <dfn title=\"";
                // line 316
                echo _gettext("Allows importing data from and exporting data into files.");
                echo "\">
              FILE
            </dfn>
          </code>
        </label>
      </div>
    ";
            }
            // line 323
            echo "  </fieldset>

  <fieldset>
    <legend>
      <input type=\"checkbox\" class=\"sub_checkall_box\" id=\"checkall_Structure_priv\" title=\"";
            // line 327
            echo _gettext("Check all");
            echo "\">
      <label for=\"checkall_Structure_priv\">";
            // line 328
            echo _gettext("Structure");
            echo "</label>
    </legend>

    <div class=\"item\">
      ";
            // line 332
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 333
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Create_priv\" id=\"checkbox_Create_priv\" value=\"Y\" title=\"";
            // line 334
            if (($context["is_database"] ?? null)) {
                // line 335
                echo _gettext("Allows creating new databases and tables.");
            } else {
                // line 337
                echo _gettext("Allows creating new tables.");
            }
            // line 338
            echo "\"";
            echo ((((($__internal_compile_24 = ($context["row"] ?? null)) && is_array($__internal_compile_24) || $__internal_compile_24 instanceof ArrayAccess ? ($__internal_compile_24["Create_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Create_priv\">
        <code>
          <dfn title=\"";
            // line 342
            if (($context["is_database"] ?? null)) {
                // line 343
                echo _gettext("Allows creating new databases and tables.");
            } else {
                // line 345
                echo _gettext("Allows creating new tables.");
            }
            // line 346
            echo "\">
            CREATE
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 354
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 355
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Alter_priv\" id=\"checkbox_Alter_priv\" value=\"Y\" title=\"";
            // line 356
            echo _gettext("Allows altering the structure of existing tables.");
            echo "\"";
            echo ((((($__internal_compile_25 = ($context["row"] ?? null)) && is_array($__internal_compile_25) || $__internal_compile_25 instanceof ArrayAccess ? ($__internal_compile_25["Alter_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Alter_priv\">
        <code>
          <dfn title=\"";
            // line 359
            echo _gettext("Allows altering the structure of existing tables.");
            echo "\">
            ALTER
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 367
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 368
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Index_priv\" id=\"checkbox_Index_priv\" value=\"Y\" title=\"";
            // line 369
            echo _gettext("Allows creating and dropping indexes.");
            echo "\"";
            echo ((((($__internal_compile_26 = ($context["row"] ?? null)) && is_array($__internal_compile_26) || $__internal_compile_26 instanceof ArrayAccess ? ($__internal_compile_26["Index_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Index_priv\">
        <code>
          <dfn title=\"";
            // line 372
            echo _gettext("Allows creating and dropping indexes.");
            echo "\">
            INDEX
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 380
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 381
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Drop_priv\" id=\"checkbox_Drop_priv\" value=\"Y\" title=\"";
            // line 382
            if (($context["is_database"] ?? null)) {
                // line 383
                echo _gettext("Allows dropping databases and tables.");
            } else {
                // line 385
                echo _gettext("Allows dropping tables.");
            }
            // line 386
            echo "\"";
            echo ((((($__internal_compile_27 = ($context["row"] ?? null)) && is_array($__internal_compile_27) || $__internal_compile_27 instanceof ArrayAccess ? ($__internal_compile_27["Drop_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Drop_priv\">
        <code>
          <dfn title=\"";
            // line 390
            if (($context["is_database"] ?? null)) {
                // line 391
                echo _gettext("Allows dropping databases and tables.");
            } else {
                // line 393
                echo _gettext("Allows dropping tables.");
            }
            // line 394
            echo "\">
            DROP
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 402
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 403
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Create_tmp_table_priv\" id=\"checkbox_Create_tmp_table_priv\" value=\"Y\" title=\"";
            // line 404
            echo _gettext("Allows creating temporary tables.");
            echo "\"";
            echo ((((($__internal_compile_28 = ($context["row"] ?? null)) && is_array($__internal_compile_28) || $__internal_compile_28 instanceof ArrayAccess ? ($__internal_compile_28["Create_tmp_table_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Create_tmp_table_priv\">
        <code>
          <dfn title=\"";
            // line 407
            echo _gettext("Allows creating temporary tables.");
            echo "\">
            CREATE TEMPORARY TABLES
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 415
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 416
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Show_view_priv\" id=\"checkbox_Show_view_priv\" value=\"Y\" title=\"";
            // line 417
            echo _gettext("Allows performing SHOW CREATE VIEW queries.");
            echo "\"";
            echo ((((($__internal_compile_29 = ($context["row"] ?? null)) && is_array($__internal_compile_29) || $__internal_compile_29 instanceof ArrayAccess ? ($__internal_compile_29["Show_view_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Show_view_priv\">
        <code>
          <dfn title=\"";
            // line 420
            echo _gettext("Allows performing SHOW CREATE VIEW queries.");
            echo "\">
            SHOW VIEW
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 428
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 429
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Create_routine_priv\" id=\"checkbox_Create_routine_priv\" value=\"Y\" title=\"";
            // line 430
            echo _gettext("Allows creating stored routines.");
            echo "\"";
            echo ((((($__internal_compile_30 = ($context["row"] ?? null)) && is_array($__internal_compile_30) || $__internal_compile_30 instanceof ArrayAccess ? ($__internal_compile_30["Create_routine_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Create_routine_priv\">
        <code>
          <dfn title=\"";
            // line 433
            echo _gettext("Allows creating stored routines.");
            echo "\">
            CREATE ROUTINE
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 441
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 442
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Alter_routine_priv\" id=\"checkbox_Alter_routine_priv\" value=\"Y\" title=\"";
            // line 443
            echo _gettext("Allows altering and dropping stored routines.");
            echo "\"";
            echo ((((($__internal_compile_31 = ($context["row"] ?? null)) && is_array($__internal_compile_31) || $__internal_compile_31 instanceof ArrayAccess ? ($__internal_compile_31["Alter_routine_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Alter_routine_priv\">
        <code>
          <dfn title=\"";
            // line 446
            echo _gettext("Allows altering and dropping stored routines.");
            echo "\">
            ALTER ROUTINE
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 454
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 455
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Execute_priv\" id=\"checkbox_Execute_priv\" value=\"Y\" title=\"";
            // line 456
            echo _gettext("Allows executing stored routines.");
            echo "\"";
            echo ((((($__internal_compile_32 = ($context["row"] ?? null)) && is_array($__internal_compile_32) || $__internal_compile_32 instanceof ArrayAccess ? ($__internal_compile_32["Execute_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Execute_priv\">
        <code>
          <dfn title=\"";
            // line 459
            echo _gettext("Allows executing stored routines.");
            echo "\">
            EXECUTE
          </dfn>
        </code>
      </label>
    </div>

    ";
            // line 466
            if (twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "Create_view_priv", [], "array", true, true, false, 466)) {
                // line 467
                echo "      <div class=\"item\">
        ";
                // line 468
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 469
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Create_view_priv\" id=\"checkbox_Create_view_priv\" value=\"Y\" title=\"";
                // line 470
                echo _gettext("Allows creating new views.");
                echo "\"";
                echo ((((($__internal_compile_33 = ($context["row"] ?? null)) && is_array($__internal_compile_33) || $__internal_compile_33 instanceof ArrayAccess ? ($__internal_compile_33["Create_view_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Create_view_priv\">
          <code>
            <dfn title=\"";
                // line 473
                echo _gettext("Allows creating new views.");
                echo "\">
              CREATE VIEW
            </dfn>
          </code>
        </label>
      </div>
    ";
            }
            // line 480
            echo "
    ";
            // line 481
            if (twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "Create View_priv", [], "array", true, true, false, 481)) {
                // line 482
                echo "      <div class=\"item\">
        ";
                // line 483
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 484
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Create View_priv\" id=\"checkbox_Create View_priv\" value=\"Y\" title=\"";
                // line 485
                echo _gettext("Allows creating new views.");
                echo "\"";
                echo ((((($__internal_compile_34 = ($context["row"] ?? null)) && is_array($__internal_compile_34) || $__internal_compile_34 instanceof ArrayAccess ? ($__internal_compile_34["Create View_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Create View_priv\">
          <code>
            <dfn title=\"";
                // line 488
                echo _gettext("Allows creating new views.");
                echo "\">
              CREATE VIEW
            </dfn>
          </code>
        </label>
      </div>
    ";
            }
            // line 495
            echo "
    ";
            // line 496
            if (twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "Event_priv", [], "array", true, true, false, 496)) {
                // line 497
                echo "      <div class=\"item\">
        ";
                // line 498
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 499
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Event_priv\" id=\"checkbox_Event_priv\" value=\"Y\" title=\"";
                // line 500
                echo _gettext("Allows to set up events for the event scheduler.");
                echo "\"";
                echo ((((($__internal_compile_35 = ($context["row"] ?? null)) && is_array($__internal_compile_35) || $__internal_compile_35 instanceof ArrayAccess ? ($__internal_compile_35["Event_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Event_priv\">
          <code>
            <dfn title=\"";
                // line 503
                echo _gettext("Allows to set up events for the event scheduler.");
                echo "\">
              EVENT
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        ";
                // line 511
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 512
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Trigger_priv\" id=\"checkbox_Trigger_priv\" value=\"Y\" title=\"";
                // line 513
                echo _gettext("Allows creating and dropping triggers.");
                echo "\"";
                echo ((((($__internal_compile_36 = ($context["row"] ?? null)) && is_array($__internal_compile_36) || $__internal_compile_36 instanceof ArrayAccess ? ($__internal_compile_36["Trigger_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Trigger_priv\">
          <code>
            <dfn title=\"";
                // line 516
                echo _gettext("Allows creating and dropping triggers.");
                echo "\">
              TRIGGER
            </dfn>
          </code>
        </label>
      </div>
    ";
            }
            // line 523
            echo "  </fieldset>

  <fieldset>
    <legend>
      <input type=\"checkbox\" class=\"sub_checkall_box\" id=\"checkall_Administration_priv\" title=\"";
            // line 527
            echo _gettext("Check all");
            echo "\">
      <label for=\"checkall_Administration_priv\">";
            // line 528
            echo _gettext("Administration");
            echo "</label>
    </legend>

    ";
            // line 531
            if (($context["is_global"] ?? null)) {
                // line 532
                echo "      <div class=\"item\">
        ";
                // line 533
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 534
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Grant_priv\" id=\"checkbox_Grant_priv\" value=\"Y\" title=\"";
                // line 535
                echo _gettext("Allows adding users and privileges without reloading the privilege tables.");
                echo "\"";
                echo ((((($__internal_compile_37 = ($context["row"] ?? null)) && is_array($__internal_compile_37) || $__internal_compile_37 instanceof ArrayAccess ? ($__internal_compile_37["Grant_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Grant_priv\">
          <code>
            <dfn title=\"";
                // line 538
                echo _gettext("Allows adding users and privileges without reloading the privilege tables.");
                echo "\">
              GRANT
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        ";
                // line 546
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 547
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Super_priv\" id=\"checkbox_Super_priv\" value=\"Y\" title=\"";
                // line 548
                echo _gettext("Allows connecting, even if maximum number of connections is reached; required for most administrative operations like setting global variables or killing threads of other users.");
                echo "\"";
                // line 549
                echo ((((($__internal_compile_38 = ($context["row"] ?? null)) && is_array($__internal_compile_38) || $__internal_compile_38 instanceof ArrayAccess ? ($__internal_compile_38["Super_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Super_priv\">
          <code>
            <dfn title=\"";
                // line 552
                echo _gettext("Allows connecting, even if maximum number of connections is reached; required for most administrative operations like setting global variables or killing threads of other users.");
                echo "\">
              SUPER
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        ";
                // line 560
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 561
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Process_priv\" id=\"checkbox_Process_priv\" value=\"Y\" title=\"";
                // line 562
                echo _gettext("Allows viewing processes of all users.");
                echo "\"";
                echo ((((($__internal_compile_39 = ($context["row"] ?? null)) && is_array($__internal_compile_39) || $__internal_compile_39 instanceof ArrayAccess ? ($__internal_compile_39["Process_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Process_priv\">
          <code>
            <dfn title=\"";
                // line 565
                echo _gettext("Allows viewing processes of all users.");
                echo "\">
              PROCESS
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        ";
                // line 573
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 574
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Reload_priv\" id=\"checkbox_Reload_priv\" value=\"Y\" title=\"";
                // line 575
                echo _gettext("Allows reloading server settings and flushing the server's caches.");
                echo "\"";
                echo ((((($__internal_compile_40 = ($context["row"] ?? null)) && is_array($__internal_compile_40) || $__internal_compile_40 instanceof ArrayAccess ? ($__internal_compile_40["Reload_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Reload_priv\">
          <code>
            <dfn title=\"";
                // line 578
                echo _gettext("Allows reloading server settings and flushing the server's caches.");
                echo "\">
              RELOAD
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        ";
                // line 586
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 587
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Shutdown_priv\" id=\"checkbox_Shutdown_priv\" value=\"Y\" title=\"";
                // line 588
                echo _gettext("Allows shutting down the server.");
                echo "\"";
                echo ((((($__internal_compile_41 = ($context["row"] ?? null)) && is_array($__internal_compile_41) || $__internal_compile_41 instanceof ArrayAccess ? ($__internal_compile_41["Shutdown_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Shutdown_priv\">
          <code>
            <dfn title=\"";
                // line 591
                echo _gettext("Allows shutting down the server.");
                echo "\">
              SHUTDOWN
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        ";
                // line 599
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 600
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Show_db_priv\" id=\"checkbox_Show_db_priv\" value=\"Y\" title=\"";
                // line 601
                echo _gettext("Gives access to the complete list of databases.");
                echo "\"";
                echo ((((($__internal_compile_42 = ($context["row"] ?? null)) && is_array($__internal_compile_42) || $__internal_compile_42 instanceof ArrayAccess ? ($__internal_compile_42["Show_db_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Show_db_priv\">
          <code>
            <dfn title=\"";
                // line 604
                echo _gettext("Gives access to the complete list of databases.");
                echo "\">
              SHOW DATABASES
            </dfn>
          </code>
        </label>
      </div>
    ";
            } else {
                // line 611
                echo "      <div class=\"item\">
        ";
                // line 612
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 613
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Grant_priv\" id=\"checkbox_Grant_priv\" value=\"Y\" title=\"";
                // line 614
                echo _gettext("Allows user to give to other users or remove from other users the privileges that user possess yourself.");
                echo "\"";
                // line 615
                echo ((((($__internal_compile_43 = ($context["row"] ?? null)) && is_array($__internal_compile_43) || $__internal_compile_43 instanceof ArrayAccess ? ($__internal_compile_43["Grant_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Grant_priv\">
          <code>
            <dfn title=\"";
                // line 618
                echo _gettext("Allows user to give to other users or remove from other users the privileges that user possess yourself.");
                echo "\">
              GRANT
            </dfn>
          </code>
        </label>
      </div>
    ";
            }
            // line 625
            echo "
    <div class=\"item\">
      ";
            // line 627
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 628
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"Lock_tables_priv\" id=\"checkbox_Lock_tables_priv\" value=\"Y\" title=\"";
            // line 629
            echo _gettext("Allows locking tables for the current thread.");
            echo "\"";
            echo ((((($__internal_compile_44 = ($context["row"] ?? null)) && is_array($__internal_compile_44) || $__internal_compile_44 instanceof ArrayAccess ? ($__internal_compile_44["Lock_tables_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_Lock_tables_priv\">
        <code>
          <dfn title=\"";
            // line 632
            echo _gettext("Allows locking tables for the current thread.");
            echo "\">
            LOCK TABLES
          </dfn>
        </code>
      </label>
    </div>

    <div class=\"item\">
      ";
            // line 640
            $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
            // line 641
            echo "      <input type=\"checkbox\" class=\"checkall\" name=\"References_priv\" id=\"checkbox_References_priv\" value=\"Y\" title=\"";
            // line 642
            echo _gettext("Has no effect in this MySQL version.");
            echo "\"";
            echo ((((($__internal_compile_45 = ($context["row"] ?? null)) && is_array($__internal_compile_45) || $__internal_compile_45 instanceof ArrayAccess ? ($__internal_compile_45["References_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
            echo ">
      <label for=\"checkbox_References_priv\">
        <code>
          ";
            // line 646
            echo "          <dfn title=\"";
            echo twig_escape_filter($this->env, ((($context["supports_references_privilege"] ?? null)) ? (_gettext("Allows creating foreign key relations.")) : (((($context["is_mariadb"] ?? null)) ? (_gettext("Not used on MariaDB.")) : (_gettext("Not used for this MySQL version."))))), "html", null, true);
            echo "\">
            REFERENCES
          </dfn>
        </code>
      </label>
    </div>

    ";
            // line 653
            if (($context["is_global"] ?? null)) {
                // line 654
                echo "      <div class=\"item\">
        ";
                // line 655
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 656
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Repl_client_priv\" id=\"checkbox_Repl_client_priv\" value=\"Y\" title=\"";
                // line 657
                echo _gettext("Allows the user to ask where the slaves / masters are.");
                echo "\"";
                echo ((((($__internal_compile_46 = ($context["row"] ?? null)) && is_array($__internal_compile_46) || $__internal_compile_46 instanceof ArrayAccess ? ($__internal_compile_46["Repl_client_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Repl_client_priv\">
          <code>
            <dfn title=\"";
                // line 660
                echo _gettext("Allows the user to ask where the slaves / masters are.");
                echo "\">
              REPLICATION CLIENT
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        ";
                // line 668
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 669
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Repl_slave_priv\" id=\"checkbox_Repl_slave_priv\" value=\"Y\" title=\"";
                // line 670
                echo _gettext("Needed for the replication slaves.");
                echo "\"";
                echo ((((($__internal_compile_47 = ($context["row"] ?? null)) && is_array($__internal_compile_47) || $__internal_compile_47 instanceof ArrayAccess ? ($__internal_compile_47["Repl_slave_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Repl_slave_priv\">
          <code>
            <dfn title=\"";
                // line 673
                echo _gettext("Needed for the replication slaves.");
                echo "\">
              REPLICATION SLAVE
            </dfn>
          </code>
        </label>
      </div>

      <div class=\"item\">
        ";
                // line 681
                $context["grant_count"] = (($context["grant_count"] ?? null) + 1);
                // line 682
                echo "        <input type=\"checkbox\" class=\"checkall\" name=\"Create_user_priv\" id=\"checkbox_Create_user_priv\" value=\"Y\" title=\"";
                // line 683
                echo _gettext("Allows creating, dropping and renaming user accounts.");
                echo "\"";
                echo ((((($__internal_compile_48 = ($context["row"] ?? null)) && is_array($__internal_compile_48) || $__internal_compile_48 instanceof ArrayAccess ? ($__internal_compile_48["Create_user_priv"] ?? null) : null) == "Y")) ? (" checked") : (""));
                echo ">
        <label for=\"checkbox_Create_user_priv\">
          <code>
            <dfn title=\"";
                // line 686
                echo _gettext("Allows creating, dropping and renaming user accounts.");
                echo "\">
              CREATE USER
            </dfn>
          </code>
        </label>
      </div>
    ";
            }
            // line 693
            echo "  </fieldset>

  ";
            // line 695
            if (($context["is_global"] ?? null)) {
                // line 696
                echo "    <fieldset>
      <legend>";
                // line 697
                echo _gettext("Resource limits");
                echo "</legend>
      <p>
        <small><em>";
                // line 699
                echo _gettext("Note: Setting these options to 0 (zero) removes the limit.");
                echo "</em></small>
      </p>

      <div class=\"item\">
        <label for=\"text_max_questions\">
          <code>
            <dfn title=\"";
                // line 705
                echo _gettext("Limits the number of queries the user may send to the server per hour.");
                echo "\">
              MAX QUERIES PER HOUR
            </dfn>
          </code>
        </label>
        <input type=\"number\" name=\"max_questions\" id=\"text_max_questions\" value=\"";
                // line 711
                (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_questions", [], "any", true, true, false, 711) &&  !(null === twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_questions", [], "any", false, false, false, 711)))) ? (print (twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_questions", [], "any", false, false, false, 711), "html", null, true))) : (print ("0")));
                echo "\" title=\"";
                // line 712
                echo _gettext("Limits the number of queries the user may send to the server per hour.");
                echo "\">
      </div>

      <div class=\"item\">
        <label for=\"text_max_updates\">
          <code>
            <dfn title=\"";
                // line 718
                echo _gettext("Limits the number of commands that change any table or database the user may execute per hour.");
                echo "\">
              MAX UPDATES PER HOUR
            </dfn>
          </code>
        </label>
        <input type=\"number\" name=\"max_updates\" id=\"text_max_updates\" value=\"";
                // line 724
                (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_updates", [], "any", true, true, false, 724) &&  !(null === twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_updates", [], "any", false, false, false, 724)))) ? (print (twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_updates", [], "any", false, false, false, 724), "html", null, true))) : (print ("0")));
                echo "\" title=\"";
                // line 725
                echo _gettext("Limits the number of commands that change any table or database the user may execute per hour.");
                echo "\">
      </div>

      <div class=\"item\">
        <label for=\"text_max_connections\">
          <code>
            <dfn title=\"";
                // line 731
                echo _gettext("Limits the number of new connections the user may open per hour.");
                echo "\">
              MAX CONNECTIONS PER HOUR
            </dfn>
          </code>
        </label>
        <input type=\"number\" name=\"max_connections\" id=\"text_max_connections\" value=\"";
                // line 737
                (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_connections", [], "any", true, true, false, 737) &&  !(null === twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_connections", [], "any", false, false, false, 737)))) ? (print (twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_connections", [], "any", false, false, false, 737), "html", null, true))) : (print ("0")));
                echo "\" title=\"";
                // line 738
                echo _gettext("Limits the number of new connections the user may open per hour.");
                echo "\">
      </div>

      <div class=\"item\">
        <label for=\"text_max_user_connections\">
          <code>
            <dfn title=\"";
                // line 744
                echo _gettext("Limits the number of simultaneous connections the user may have.");
                echo "\">
              MAX USER_CONNECTIONS
            </dfn>
          </code>
        </label>
        <input type=\"number\" name=\"max_user_connections\" id=\"text_max_user_connections\" value=\"";
                // line 750
                (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_user_connections", [], "any", true, true, false, 750) &&  !(null === twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_user_connections", [], "any", false, false, false, 750)))) ? (print (twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "max_user_connections", [], "any", false, false, false, 750), "html", null, true))) : (print ("0")));
                echo "\" title=\"";
                // line 751
                echo _gettext("Limits the number of simultaneous connections the user may have.");
                echo "\">
      </div>
    </fieldset>

    <fieldset>
      <legend>SSL</legend>
      <div id=\"require_ssl_div\">
        <div class=\"item\">
          <input type=\"radio\" name=\"ssl_type\" id=\"ssl_type_NONE\" title=\"";
                // line 760
                echo _gettext("Does not require SSL-encrypted connections.");
                echo "\" value=\"NONE\"";
                // line 761
                echo ((((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_type", [], "any", false, false, false, 761) == "NONE") || (twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_type", [], "any", false, false, false, 761) == ""))) ? (" checked") : (""));
                echo ">
          <label for=\"ssl_type_NONE\">
            <code>REQUIRE NONE</code>
          </label>
        </div>

        <div class=\"item\">
          <input type=\"radio\" name=\"ssl_type\" id=\"ssl_type_ANY\" title=\"";
                // line 769
                echo _gettext("Requires SSL-encrypted connections.");
                echo "\" value=\"ANY\"";
                // line 770
                echo (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_type", [], "any", false, false, false, 770) == "ANY")) ? (" checked") : (""));
                echo ">
          <label for=\"ssl_type_ANY\">
            <code>REQUIRE SSL</code>
          </label>
        </div>

        <div class=\"item\">
          <input type=\"radio\" name=\"ssl_type\" id=\"ssl_type_X509\" title=\"";
                // line 778
                echo _gettext("Requires a valid X509 certificate.");
                echo "\" value=\"X509\"";
                // line 779
                echo (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_type", [], "any", false, false, false, 779) == "X509")) ? (" checked") : (""));
                echo ">
          <label for=\"ssl_type_X509\">
            <code>REQUIRE X509</code>
          </label>
        </div>

        <div class=\"item\">
          <input type=\"radio\" name=\"ssl_type\" id=\"ssl_type_SPECIFIED\" value=\"SPECIFIED\"";
                // line 787
                echo (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_type", [], "any", false, false, false, 787) == "SPECIFIED")) ? (" checked") : (""));
                echo ">
          <label for=\"ssl_type_SPECIFIED\">
            <code>SPECIFIED</code>
          </label>
        </div>

        <div id=\"specified_div\" style=\"padding-left:20px;\">
          <div class=\"item\">
            <label for=\"text_ssl_cipher\">
              <code>REQUIRE CIPHER</code>
            </label>
            <input type=\"text\" name=\"ssl_cipher\" id=\"text_ssl_cipher\" value=\"";
                // line 798
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_cipher", [], "any", false, false, false, 798), "html", null, true);
                echo "\" size=\"80\" title=\"";
                // line 799
                echo _gettext("Requires that a specific cipher method be used for a connection.");
                echo "\"";
                // line 800
                echo (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_type", [], "any", false, false, false, 800) != "SPECIFIED")) ? (" disabled") : (""));
                echo ">
          </div>

          <div class=\"item\">
            <label for=\"text_x509_issuer\">
              <code>REQUIRE ISSUER</code>
            </label>
            <input type=\"text\" name=\"x509_issuer\" id=\"text_x509_issuer\" value=\"";
                // line 807
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "x509_issuer", [], "any", false, false, false, 807), "html", null, true);
                echo "\" size=\"80\" title=\"";
                // line 808
                echo _gettext("Requires that a valid X509 certificate issued by this CA be presented.");
                echo "\"";
                // line 809
                echo (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_type", [], "any", false, false, false, 809) != "SPECIFIED")) ? (" disabled") : (""));
                echo ">
          </div>

          <div class=\"item\">
            <label for=\"text_x509_subject\">
              <code>REQUIRE SUBJECT</code>
            </label>
            <input type=\"text\" name=\"x509_subject\" id=\"text_x509_subject\" value=\"";
                // line 816
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "x509_subject", [], "any", false, false, false, 816), "html", null, true);
                echo "\" size=\"80\" title=\"";
                // line 817
                echo _gettext("Requires that a valid X509 certificate with this subject be presented.");
                echo "\"";
                // line 818
                echo (((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "ssl_type", [], "any", false, false, false, 818) != "SPECIFIED")) ? (" disabled") : (""));
                echo ">
          </div>
        </div>
      </div>
    </fieldset>
  ";
            }
            // line 824
            echo "
  <div class=\"clearfloat\"></div>
</fieldset>
<input type=\"hidden\" name=\"grant_count\" value=\"";
            // line 827
            echo twig_escape_filter($this->env, (($context["grant_count"] ?? null) - ((twig_get_attribute($this->env, $this->source, ($context["row"] ?? null), "Grant_priv", [], "array", true, true, false, 827)) ? (1) : (0))), "html", null, true);
            echo "\">

";
        }
        // line 830
        echo "
";
        // line 831
        if (($context["has_submit"] ?? null)) {
            // line 832
            echo "  <fieldset id=\"fieldset_user_privtable_footer\" class=\"tblFooters\">
    <input type=\"hidden\" name=\"update_privs\" value=\"1\">
    <input class=\"btn btn-primary\" type=\"submit\" value=\"";
            // line 834
            echo _gettext("Go");
            echo "\">
  </fieldset>
";
        }
    }

    public function getTemplateName()
    {
        return "server/privileges/privileges_table.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  1552 => 834,  1548 => 832,  1546 => 831,  1543 => 830,  1537 => 827,  1532 => 824,  1523 => 818,  1520 => 817,  1517 => 816,  1507 => 809,  1504 => 808,  1501 => 807,  1491 => 800,  1488 => 799,  1485 => 798,  1471 => 787,  1461 => 779,  1458 => 778,  1448 => 770,  1445 => 769,  1435 => 761,  1432 => 760,  1421 => 751,  1418 => 750,  1410 => 744,  1401 => 738,  1398 => 737,  1390 => 731,  1381 => 725,  1378 => 724,  1370 => 718,  1361 => 712,  1358 => 711,  1350 => 705,  1341 => 699,  1336 => 697,  1333 => 696,  1331 => 695,  1327 => 693,  1317 => 686,  1309 => 683,  1307 => 682,  1305 => 681,  1294 => 673,  1286 => 670,  1284 => 669,  1282 => 668,  1271 => 660,  1263 => 657,  1261 => 656,  1259 => 655,  1256 => 654,  1254 => 653,  1243 => 646,  1235 => 642,  1233 => 641,  1231 => 640,  1220 => 632,  1212 => 629,  1210 => 628,  1208 => 627,  1204 => 625,  1194 => 618,  1188 => 615,  1185 => 614,  1183 => 613,  1181 => 612,  1178 => 611,  1168 => 604,  1160 => 601,  1158 => 600,  1156 => 599,  1145 => 591,  1137 => 588,  1135 => 587,  1133 => 586,  1122 => 578,  1114 => 575,  1112 => 574,  1110 => 573,  1099 => 565,  1091 => 562,  1089 => 561,  1087 => 560,  1076 => 552,  1070 => 549,  1067 => 548,  1065 => 547,  1063 => 546,  1052 => 538,  1044 => 535,  1042 => 534,  1040 => 533,  1037 => 532,  1035 => 531,  1029 => 528,  1025 => 527,  1019 => 523,  1009 => 516,  1001 => 513,  999 => 512,  997 => 511,  986 => 503,  978 => 500,  976 => 499,  974 => 498,  971 => 497,  969 => 496,  966 => 495,  956 => 488,  948 => 485,  946 => 484,  944 => 483,  941 => 482,  939 => 481,  936 => 480,  926 => 473,  918 => 470,  916 => 469,  914 => 468,  911 => 467,  909 => 466,  899 => 459,  891 => 456,  889 => 455,  887 => 454,  876 => 446,  868 => 443,  866 => 442,  864 => 441,  853 => 433,  845 => 430,  843 => 429,  841 => 428,  830 => 420,  822 => 417,  820 => 416,  818 => 415,  807 => 407,  799 => 404,  797 => 403,  795 => 402,  785 => 394,  782 => 393,  779 => 391,  777 => 390,  770 => 386,  767 => 385,  764 => 383,  762 => 382,  760 => 381,  758 => 380,  747 => 372,  739 => 369,  737 => 368,  735 => 367,  724 => 359,  716 => 356,  714 => 355,  712 => 354,  702 => 346,  699 => 345,  696 => 343,  694 => 342,  687 => 338,  684 => 337,  681 => 335,  679 => 334,  677 => 333,  675 => 332,  668 => 328,  664 => 327,  658 => 323,  648 => 316,  640 => 313,  638 => 312,  636 => 311,  633 => 310,  631 => 309,  621 => 302,  613 => 299,  611 => 298,  609 => 297,  598 => 289,  590 => 286,  588 => 285,  586 => 284,  575 => 276,  567 => 273,  565 => 272,  563 => 271,  552 => 263,  544 => 260,  542 => 259,  540 => 258,  533 => 254,  529 => 253,  521 => 248,  515 => 245,  510 => 244,  507 => 243,  504 => 242,  501 => 241,  498 => 240,  495 => 239,  492 => 238,  490 => 237,  487 => 236,  484 => 235,  481 => 233,  479 => 232,  477 => 231,  475 => 230,  472 => 228,  470 => 227,  467 => 226,  460 => 221,  450 => 214,  444 => 211,  441 => 210,  438 => 208,  436 => 207,  426 => 200,  418 => 197,  407 => 188,  399 => 185,  388 => 176,  380 => 173,  369 => 164,  361 => 161,  350 => 152,  342 => 149,  331 => 140,  325 => 137,  322 => 136,  311 => 127,  303 => 124,  292 => 115,  284 => 112,  273 => 103,  265 => 100,  258 => 94,  256 => 93,  252 => 92,  247 => 89,  243 => 87,  234 => 84,  227 => 83,  223 => 82,  216 => 78,  209 => 73,  207 => 72,  203 => 71,  198 => 68,  194 => 66,  185 => 63,  178 => 62,  174 => 61,  167 => 57,  160 => 52,  158 => 51,  154 => 50,  149 => 47,  145 => 45,  136 => 42,  129 => 41,  125 => 40,  118 => 36,  111 => 31,  109 => 30,  105 => 29,  100 => 26,  96 => 24,  87 => 21,  80 => 20,  76 => 19,  69 => 15,  61 => 10,  57 => 8,  55 => 7,  51 => 6,  46 => 4,  42 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/privileges/privileges_table.twig", "/var/www/html/phpMyAdmin/templates/server/privileges/privileges_table.twig");
    }
}
