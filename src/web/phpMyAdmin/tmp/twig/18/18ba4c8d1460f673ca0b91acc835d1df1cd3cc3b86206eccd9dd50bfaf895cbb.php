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

/* sql/query.twig */
class __TwigTemplate_af47fbdc704db4de7fc7e280c866277c244f9a3f67f1391717c5b6b55eb65a7f extends \Twig\Template
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
        echo PhpMyAdmin\Url::getFromRoute("/import");
        echo "\" class=\"ajax lock-page\" id=\"sqlqueryform\" name=\"sqlform\"";
        // line 2
        echo ((($context["is_upload"] ?? null)) ? (" enctype=\"multipart/form-data\"") : (""));
        echo ">
  ";
        // line 3
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
        echo "
  <input type=\"hidden\" name=\"is_js_confirmed\" value=\"0\">
  <input type=\"hidden\" name=\"pos\" value=\"0\">
  <input type=\"hidden\" name=\"goto\" value=\"";
        // line 6
        echo twig_escape_filter($this->env, ($context["goto"] ?? null), "html", null, true);
        echo "\">
  <input type=\"hidden\" name=\"message_to_show\" value=\"";
        // line 7
        echo _gettext("Your SQL query has been executed successfully.");
        echo "\">
  <input type=\"hidden\" name=\"prev_sql_query\" value=\"";
        // line 8
        echo twig_escape_filter($this->env, ($context["query"] ?? null), "html", null, true);
        echo "\">

  ";
        // line 10
        if (((($context["display_tab"] ?? null) == "full") || (($context["display_tab"] ?? null) == "sql"))) {
            // line 11
            echo "    <a id=\"querybox\"></a>

    <div class=\"card mb-3\">
      <div class=\"card-header\">";
            // line 14
            echo ($context["legend"] ?? null);
            echo "</div>
      <div class=\"card-body\">
        <div id=\"queryfieldscontainer\">
          <div class=\"row\">
            <div class=\"col\">
              <div class=\"form-group\">
                <textarea class=\"form-control\" tabindex=\"100\" name=\"sql_query\" id=\"sqlquery\" cols=\"";
            // line 20
            echo twig_escape_filter($this->env, ($context["textarea_cols"] ?? null), "html", null, true);
            echo "\" rows=\"";
            echo twig_escape_filter($this->env, ($context["textarea_rows"] ?? null), "html", null, true);
            echo "\"";
            // line 21
            echo ((($context["textarea_auto_select"] ?? null)) ? (" onclick=\"Functions.selectContent(this, sqlBoxLocked, true);\"") : (""));
            echo " aria-label=\"";
            echo _gettext("SQL query");
            echo "\">";
            // line 22
            echo twig_escape_filter($this->env, ($context["query"] ?? null), "html", null, true);
            // line 23
            echo "</textarea>
              </div>
              <div id=\"querymessage\"></div>

              <div class=\"btn-toolbar\" role=\"toolbar\">
                ";
            // line 28
            if ( !twig_test_empty(($context["columns_list"] ?? null))) {
                // line 29
                echo "                  <div class=\"btn-group mr-2\" role=\"group\">
                    <input type=\"button\" value=\"SELECT *\" id=\"selectall\" class=\"btn btn-secondary button sqlbutton\">
                    <input type=\"button\" value=\"SELECT\" id=\"select\" class=\"btn btn-secondary button sqlbutton\">
                    <input type=\"button\" value=\"INSERT\" id=\"insert\" class=\"btn btn-secondary button sqlbutton\">
                    <input type=\"button\" value=\"UPDATE\" id=\"update\" class=\"btn btn-secondary button sqlbutton\">
                    <input type=\"button\" value=\"DELETE\" id=\"delete\" class=\"btn btn-secondary button sqlbutton\">
                  </div>
                ";
            }
            // line 37
            echo "
                <div class=\"btn-group mr-2\" role=\"group\">
                  <input type=\"button\" value=\"";
            // line 39
            echo _gettext("Clear");
            echo "\" id=\"clear\" class=\"btn btn-secondary button sqlbutton\">
                  ";
            // line 40
            if (($context["codemirror_enable"] ?? null)) {
                // line 41
                echo "                    <input type=\"button\" value=\"";
                echo _gettext("Format");
                echo "\" id=\"format\" class=\"btn btn-secondary button sqlbutton\">
                  ";
            }
            // line 43
            echo "                </div>

                <input type=\"button\" value=\"";
            // line 45
            echo _gettext("Get auto-saved query");
            echo "\" id=\"saved\" class=\"btn btn-secondary button sqlbutton\">
              </div>

              <div class=\"form-group mt-3\">
                <div class=\"form-check\">
                  <input class=\"form-check-input\" type=\"checkbox\" name=\"parameterized\" id=\"parameterized\">
                  <label class=\"form-check-label\" for=\"parameterized\">
                    ";
            // line 52
            // l10n: Bind parameters in the SQL query using :parameterName format
            echo _gettext("Bind parameters");
            // line 53
            echo "                    ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-40");
            echo "
                  </label>
                </div>
              </div>
              <div class=\"form-group\" id=\"parametersDiv\"></div>
            </div>

            ";
            // line 60
            if ( !twig_test_empty(($context["columns_list"] ?? null))) {
                // line 61
                echo "              <div class=\"col-xl-2 col-lg-3\">
                <div class=\"form-group\">
                  <label class=\"sr-only\" for=\"fieldsSelect\">";
                // line 63
                echo _gettext("Columns");
                echo "</label>
                  <select class=\"form-control resize-vertical\" id=\"fieldsSelect\" name=\"dummy\" size=\"";
                // line 64
                echo twig_escape_filter($this->env, ($context["textarea_rows"] ?? null), "html", null, true);
                echo "\" ondblclick=\"Functions.insertValueQuery()\" multiple>
                    ";
                // line 65
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(($context["columns_list"] ?? null));
                foreach ($context['_seq'] as $context["_key"] => $context["field"]) {
                    // line 66
                    echo "                      <option value=\"";
                    echo twig_escape_filter($this->env, PhpMyAdmin\Util::backquote((($__internal_compile_0 = $context["field"]) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["Field"] ?? null) : null)), "html", null, true);
                    echo "\"";
                    // line 67
                    (((( !(null === (($__internal_compile_1 = $context["field"]) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["Field"] ?? null) : null)) &&  !(null === (($__internal_compile_2 = $context["field"]) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["Comment"] ?? null) : null))) && (twig_length_filter($this->env, (($__internal_compile_3 = $context["field"]) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["Field"] ?? null) : null)) > 0))) ? (print (twig_escape_filter($this->env, ((" title=\"" . (($__internal_compile_4 = $context["field"]) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["Comment"] ?? null) : null)) . "\""), "html", null, true))) : (print ("")));
                    echo ">
                        ";
                    // line 68
                    echo twig_escape_filter($this->env, (($__internal_compile_5 = $context["field"]) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["Field"] ?? null) : null), "html", null, true);
                    echo "
                      </option>
                    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['field'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 71
                echo "                  </select>
                </div>

                <input type=\"button\" class=\"btn btn-secondary button\" id=\"insertBtn\" name=\"insert\" value=\"";
                // line 75
                if (PhpMyAdmin\Util::showIcons("ActionLinksMode")) {
                    echo "<<";
                    echo "\" title=\"";
                }
                // line 76
                echo _gettext("Insert");
                echo "\">
              </div>
            ";
            }
            // line 79
            echo "          </div>
        </div>

        ";
            // line 82
            if (($context["has_bookmark"] ?? null)) {
                // line 83
                echo "          <div class=\"form-inline\">
            <div class=\"form-group\">
              <label for=\"bkm_label\">";
                // line 85
                echo _gettext("Bookmark this SQL query:");
                echo "</label>
              <input class=\"form-control\" type=\"text\" name=\"bkm_label\" id=\"bkm_label\" tabindex=\"110\" value=\"\">
            </div>

            <div class=\"form-check form-check-inline\">
              <input class=\"form-check-input\" type=\"checkbox\" name=\"bkm_all_users\" tabindex=\"111\" id=\"id_bkm_all_users\" value=\"true\">
              <label class=\"form-check-label\" for=\"id_bkm_all_users\">";
                // line 91
                echo _gettext("Let every user access this bookmark");
                echo "</label>
            </div>

            <div class=\"form-check form-check-inline\">
              <input class=\"form-check-input\" type=\"checkbox\" name=\"bkm_replace\" tabindex=\"112\" id=\"id_bkm_replace\" value=\"true\">
              <label class=\"form-check-label\" for=\"id_bkm_replace\">";
                // line 96
                echo _gettext("Replace existing bookmark of same name");
                echo "</label>
            </div>
          </div>
        ";
            }
            // line 100
            echo "      </div>
      <div class=\"card-footer\">
        <div class=\"row\">
          <div class=\"form-inline col\">
            <div class=\"input-group mr-2\">
              <div class=\"input-group-prepend\">
                <span class=\"input-group-text\">";
            // line 106
            echo _gettext("Delimiter");
            echo "</span>
              </div>
              <label class=\"sr-only\" for=\"id_sql_delimiter\">";
            // line 108
            echo _gettext("Delimiter");
            echo "</label>
              <input class=\"form-control\" type=\"text\" name=\"sql_delimiter\" tabindex=\"131\" size=\"3\" value=\"";
            // line 109
            echo twig_escape_filter($this->env, ($context["delimiter"] ?? null), "html", null, true);
            echo "\" id=\"id_sql_delimiter\">
            </div>

            <div class=\"form-check form-check-inline\">
              <input class=\"form-check-input\" type=\"checkbox\" name=\"show_query\" value=\"1\" id=\"checkbox_show_query\" tabindex=\"132\">
              <label class=\"form-check-label\" for=\"checkbox_show_query\">";
            // line 114
            echo _gettext("Show this query here again");
            echo "</label>
            </div>

            <div class=\"form-check form-check-inline\">
              <input class=\"form-check-input\" type=\"checkbox\" name=\"retain_query_box\" value=\"1\" id=\"retain_query_box\" tabindex=\"133\"";
            // line 119
            echo ((($context["retain_query_box"] ?? null)) ? (" checked") : (""));
            echo ">
              <label class=\"form-check-label\" for=\"retain_query_box\">";
            // line 120
            echo _gettext("Retain query box");
            echo "</label>
            </div>

            <div class=\"form-check form-check-inline\">
              <input class=\"form-check-input\" type=\"checkbox\" name=\"rollback_query\" value=\"1\" id=\"rollback_query\" tabindex=\"134\">
              <label class=\"form-check-label\" for=\"rollback_query\">";
            // line 125
            echo _gettext("Rollback when finished");
            echo "</label>
            </div>

            <div class=\"form-check\">
              <input type=\"hidden\" name=\"fk_checks\" value=\"0\">
              <input class=\"form-check-input\" type=\"checkbox\" name=\"fk_checks\" id=\"fk_checks\" value=\"1\"";
            // line 130
            echo ((($context["is_foreign_key_check"] ?? null)) ? (" checked") : (""));
            echo ">
              <label class=\"form-check-label\" for=\"fk_checks\">";
            // line 131
            echo _gettext("Enable foreign key checks");
            echo "</label>
            </div>
          </div>

          <div class=\"form-inline col-auto\">
            <input class=\"btn btn-primary ml-1\" type=\"submit\" id=\"button_submit_query\" name=\"SQL\" tabindex=\"200\" value=\"";
            // line 136
            echo _gettext("Go");
            echo "\">
          </div>
        </div>
      </div>
    </div>
  ";
        }
        // line 142
        echo "
  ";
        // line 143
        if (((($context["display_tab"] ?? null) == "full") &&  !twig_test_empty(($context["bookmarks"] ?? null)))) {
            // line 144
            echo "    <div class=\"card mb-3\">
      <div class=\"card-header\">";
            // line 145
            echo _gettext("Bookmarked SQL query");
            echo "</div>
      <div class=\"card-body\">
        <div class=\"form-inline\">
          <div class=\"form-group\">
            <label for=\"id_bookmark\">";
            // line 149
            echo _gettext("Bookmark:");
            echo "</label>
            <select class=\"form-control\" name=\"id_bookmark\" id=\"id_bookmark\">
              <option value=\"\">&nbsp;</option>
              ";
            // line 152
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["bookmarks"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["bookmark"]) {
                // line 153
                echo "                <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["bookmark"], "id", [], "any", false, false, false, 153), "html", null, true);
                echo "\" data-varcount=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["bookmark"], "variable_count", [], "any", false, false, false, 153), "html", null, true);
                echo "\">
                  ";
                // line 154
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["bookmark"], "label", [], "any", false, false, false, 154), "html", null, true);
                echo "
                  ";
                // line 155
                if (twig_get_attribute($this->env, $this->source, $context["bookmark"], "is_shared", [], "any", false, false, false, 155)) {
                    // line 156
                    echo "                    (";
                    echo _gettext("shared");
                    echo ")
                  ";
                }
                // line 158
                echo "                </option>
              ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['bookmark'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 160
            echo "            </select>
          </div>

          <div class=\"form-check form-check-inline\">
            <input class=\"form-check-input\" type=\"radio\" name=\"action_bookmark\" value=\"0\" id=\"radio_bookmark_exe\" checked>
            <label class=\"form-check-label\" for=\"radio_bookmark_exe\">";
            // line 165
            echo _gettext("Submit");
            echo "</label>
          </div>
          <div class=\"form-check form-check-inline\">
            <input class=\"form-check-input\" type=\"radio\" name=\"action_bookmark\" value=\"1\" id=\"radio_bookmark_view\">
            <label class=\"form-check-label\" for=\"radio_bookmark_view\">";
            // line 169
            echo _gettext("View only");
            echo "</label>
          </div>
          <div class=\"form-check form-check-inline\">
            <input class=\"form-check-input\" type=\"radio\" name=\"action_bookmark\" value=\"2\" id=\"radio_bookmark_del\">
            <label class=\"form-check-label\" for=\"radio_bookmark_del\">";
            // line 173
            echo _gettext("Delete");
            echo "</label>
          </div>
        </div>

        <div class=\"hide\">
          ";
            // line 178
            echo _gettext("Variables");
            // line 179
            echo "          ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faqbookmark");
            echo "
          <div class=\"form-inline\" id=\"bookmarkVariables\"></div>
        </div>
      </div>

      <div class=\"card-footer text-right\">
        <input class=\"btn btn-secondary\" type=\"submit\" name=\"SQL\" id=\"button_submit_bookmark\" value=\"";
            // line 185
            echo _gettext("Go");
            echo "\">
      </div>
    </div>
  ";
        }
        // line 189
        echo "
  ";
        // line 190
        if (($context["can_convert_kanji"] ?? null)) {
            // line 191
            echo "    <div class=\"card mb-3\">
      <div class=\"card-body\">
        ";
            // line 193
            $this->loadTemplate("encoding/kanji_encoding_form.twig", "sql/query.twig", 193)->display($context);
            // line 194
            echo "      </div>
    </div>
  ";
        }
        // line 197
        echo "</form>

<div id=\"sqlqueryresultsouter\"></div>
";
    }

    public function getTemplateName()
    {
        return "sql/query.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  429 => 197,  424 => 194,  422 => 193,  418 => 191,  416 => 190,  413 => 189,  406 => 185,  396 => 179,  394 => 178,  386 => 173,  379 => 169,  372 => 165,  365 => 160,  358 => 158,  352 => 156,  350 => 155,  346 => 154,  339 => 153,  335 => 152,  329 => 149,  322 => 145,  319 => 144,  317 => 143,  314 => 142,  305 => 136,  297 => 131,  293 => 130,  285 => 125,  277 => 120,  273 => 119,  266 => 114,  258 => 109,  254 => 108,  249 => 106,  241 => 100,  234 => 96,  226 => 91,  217 => 85,  213 => 83,  211 => 82,  206 => 79,  200 => 76,  195 => 75,  190 => 71,  181 => 68,  177 => 67,  173 => 66,  169 => 65,  165 => 64,  161 => 63,  157 => 61,  155 => 60,  144 => 53,  141 => 52,  131 => 45,  127 => 43,  121 => 41,  119 => 40,  115 => 39,  111 => 37,  101 => 29,  99 => 28,  92 => 23,  90 => 22,  85 => 21,  80 => 20,  71 => 14,  66 => 11,  64 => 10,  59 => 8,  55 => 7,  51 => 6,  45 => 3,  41 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "sql/query.twig", "/var/www/html/phpMyAdmin/templates/sql/query.twig");
    }
}
