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

/* database/events/index.twig */
class __TwigTemplate_3b9e53fe2d6f885b65c491d0b2800049c0f796e41d6866d0bd039f464ef945bd extends \Twig\Template
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
        echo "<form id=\"rteListForm\" class=\"ajax\" action=\"";
        echo PhpMyAdmin\Url::getFromRoute("/database/events");
        echo "\">
  ";
        // line 2
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
        echo "

  <fieldset>
    <legend>
      ";
        // line 6
        echo _gettext("Events");
        // line 7
        echo "      ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("EVENTS");
        echo "
    </legend>

    <div id=\"nothing2display\"";
        // line 10
        echo (( !twig_test_empty(($context["items"] ?? null))) ? (" class=\"hide\"") : (""));
        echo ">
      ";
        // line 11
        echo _gettext("There are no events to display.");
        // line 12
        echo "    </div>

    <table id=\"eventsTable\" class=\"table table-light table-striped table-hover";
        // line 14
        echo ((twig_test_empty(($context["items"] ?? null))) ? (" hide") : (""));
        echo " w-auto data\">
      <thead class=\"thead-light\">
      <tr>
        <th></th>
        <th>";
        // line 18
        echo _gettext("Name");
        echo "</th>
        <th>";
        // line 19
        echo _gettext("Status");
        echo "</th>
        <th colspan=\"3\">";
        // line 20
        echo _gettext("Action");
        echo "</th>
        <th>";
        // line 21
        echo _gettext("Type");
        echo "</th>
      </tr>
      </thead>
      <tbody>
      <tr class=\"hide\">";
        // line 25
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(range(0, 6));
        foreach ($context['_seq'] as $context["_key"] => $context["i"]) {
            echo "<td></td>";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['i'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        echo "</tr>

      ";
        // line 27
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["items"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["event"]) {
            // line 28
            echo "        <tr";
            echo ((($context["is_ajax"] ?? null)) ? (" class=\"ajaxInsert hide\"") : (""));
            echo ">
          <td>
            <input type=\"checkbox\" class=\"checkall\" name=\"item_name[]\" value=\"";
            // line 30
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "name", [], "any", false, false, false, 30), "html", null, true);
            echo "\">
          </td>
          <td>
            <span class=\"drop_sql hide\">";
            // line 33
            echo twig_escape_filter($this->env, twig_sprintf("DROP EVENT IF EXISTS %s", PhpMyAdmin\Util::backquote(twig_get_attribute($this->env, $this->source, $context["event"], "name", [], "any", false, false, false, 33))), "html", null, true);
            echo "</span>
            <strong>";
            // line 34
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "name", [], "any", false, false, false, 34), "html", null, true);
            echo "</strong>
          </td>
          <td>
            ";
            // line 37
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "status", [], "any", false, false, false, 37), "html", null, true);
            echo "
          </td>
          <td>
            ";
            // line 40
            if (($context["has_privilege"] ?? null)) {
                // line 41
                echo "              <a class=\"ajax edit_anchor\" href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/database/events", ["db" =>                 // line 42
($context["db"] ?? null), "edit_item" => true, "item_name" => twig_get_attribute($this->env, $this->source,                 // line 44
$context["event"], "name", [], "any", false, false, false, 44)]);
                // line 45
                echo "\">
                ";
                // line 46
                echo \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Edit"));
                echo "
              </a>
            ";
            } else {
                // line 49
                echo "              ";
                echo \PhpMyAdmin\Html\Generator::getIcon("bd_edit", _gettext("Edit"));
                echo "
            ";
            }
            // line 51
            echo "          </td>
          <td>
            <a class=\"ajax export_anchor\" href=\"";
            // line 53
            echo PhpMyAdmin\Url::getFromRoute("/database/events", ["db" =>             // line 54
($context["db"] ?? null), "export_item" => true, "item_name" => twig_get_attribute($this->env, $this->source,             // line 56
$context["event"], "name", [], "any", false, false, false, 56)]);
            // line 57
            echo "\">
              ";
            // line 58
            echo \PhpMyAdmin\Html\Generator::getIcon("b_export", _gettext("Export"));
            echo "
            </a>
          </td>
          <td>
            ";
            // line 62
            if (($context["has_privilege"] ?? null)) {
                // line 63
                echo "              ";
                echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), ["db" =>                 // line 66
($context["db"] ?? null), "sql_query" => twig_sprintf("DROP EVENT IF EXISTS %s", PhpMyAdmin\Util::backquote(twig_get_attribute($this->env, $this->source,                 // line 67
$context["event"], "name", [], "any", false, false, false, 67))), "goto" => PhpMyAdmin\Url::getFromRoute("/database/events", ["db" =>                 // line 68
($context["db"] ?? null)])], \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop")), ["class" => "ajax drop_anchor"]);
                // line 72
                echo "
            ";
            } else {
                // line 74
                echo "              ";
                echo \PhpMyAdmin\Html\Generator::getIcon("bd_drop", _gettext("Drop"));
                echo "
            ";
            }
            // line 76
            echo "          </td>
          <td>
            ";
            // line 78
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "type", [], "any", false, false, false, 78), "html", null, true);
            echo "
          </td>
        </tr>
      ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['event'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 82
        echo "      </tbody>
    </table>

    ";
        // line 85
        if ( !twig_test_empty(($context["items"] ?? null))) {
            // line 86
            echo "      <div class=\"withSelected\">
        <img class=\"selectallarrow\" src=\"";
            // line 87
            echo twig_escape_filter($this->env, ($context["select_all_arrow_src"] ?? null), "html", null, true);
            echo "\" width=\"38\" height=\"22\" alt=\"";
            echo _gettext("With selected:");
            echo "\">
        <input type=\"checkbox\" id=\"rteListForm_checkall\" class=\"checkall_box\" title=\"";
            // line 88
            echo _gettext("Check all");
            echo "\">
        <label for=\"rteListForm_checkall\">";
            // line 89
            echo _gettext("Check all");
            echo "</label>
        <em class=\"with-selected\">";
            // line 90
            echo _gettext("With selected:");
            echo "</em>

        <button class=\"btn btn-link mult_submit\" type=\"submit\" name=\"submit_mult\" value=\"export\" title=\"";
            // line 92
            echo _gettext("Export");
            echo "\">
          ";
            // line 93
            echo \PhpMyAdmin\Html\Generator::getIcon("b_export", _gettext("Export"));
            echo "
        </button>
        <button class=\"btn btn-link mult_submit\" type=\"submit\" name=\"submit_mult\" value=\"drop\" title=\"";
            // line 95
            echo _gettext("Drop");
            echo "\">
          ";
            // line 96
            echo \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop"));
            echo "
        </button>
      </div>
    ";
        }
        // line 100
        echo "  </fieldset>
</form>

<div class=\"doubleFieldset\">
  <fieldset class=\"left\">
    <legend>";
        // line 105
        echo _pgettext(        "Create new event", "New");
        echo "</legend>
    <div class=\"wrap\">
      ";
        // line 107
        if (($context["has_privilege"] ?? null)) {
            // line 108
            echo "        <a class=\"ajax add_anchor\" href=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/events", ["db" => ($context["db"] ?? null), "add_item" => true]);
            echo "\">
          ";
            // line 109
            echo \PhpMyAdmin\Html\Generator::getIcon("b_event_add");
            echo "
          ";
            // line 110
            echo _gettext("Add event");
            // line 111
            echo "        </a>
      ";
        } else {
            // line 113
            echo "        ";
            echo \PhpMyAdmin\Html\Generator::getIcon("bd_event_add");
            echo "
        ";
            // line 114
            echo _gettext("Add event");
            // line 115
            echo "      ";
        }
        // line 116
        echo "      ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("CREATE_EVENT");
        echo "
    </div>
  </fieldset>

  <fieldset class=\"right\">
    <legend>";
        // line 121
        echo _gettext("Event scheduler status");
        echo "</legend>
    <div class=\"wrap\">
      <div class=\"wrapper toggleAjax hide\">
        <div class=\"toggleButton\">
          <div title=\"";
        // line 125
        echo _gettext("Click to toggle");
        echo "\" class=\"toggle-container ";
        echo ((($context["scheduler_state"] ?? null)) ? ("on") : ("off"));
        echo "\">
            <img src=\"";
        // line 126
        echo twig_escape_filter($this->env, ($context["theme_image_path"] ?? null), "html", null, true);
        echo "toggle-";
        echo twig_escape_filter($this->env, ($context["text_dir"] ?? null), "html", null, true);
        echo ".png\">
            <table class=\"pma-table nospacing nopadding\">
              <tbody>
              <tr>
                <td class=\"toggleOn\">
                  <span class=\"hide\">";
        // line 132
        echo PhpMyAdmin\Url::getFromRoute("/sql", ["db" =>         // line 133
($context["db"] ?? null), "goto" => PhpMyAdmin\Url::getFromRoute("/database/events", ["db" =>         // line 134
($context["db"] ?? null)]), "sql_query" => "SET GLOBAL event_scheduler=\"ON\""]);
        // line 137
        echo "</span>
                  <div>";
        // line 138
        echo _gettext("ON");
        echo "</div>
                </td>
                <td><div>&nbsp;</div></td>
                <td class=\"toggleOff\">
                  <span class=\"hide\">";
        // line 143
        echo PhpMyAdmin\Url::getFromRoute("/sql", ["db" =>         // line 144
($context["db"] ?? null), "goto" => PhpMyAdmin\Url::getFromRoute("/database/events", ["db" =>         // line 145
($context["db"] ?? null)]), "sql_query" => "SET GLOBAL event_scheduler=\"OFF\""]);
        // line 148
        echo "</span>
                  <div>";
        // line 149
        echo _gettext("OFF");
        echo "</div>
                </td>
              </tr>
              </tbody>
            </table>
            <span class=\"hide callback\">Functions.slidingMessage(data.sql_query);</span>
            <span class=\"hide text_direction\">";
        // line 155
        echo twig_escape_filter($this->env, ($context["text_dir"] ?? null), "html", null, true);
        echo "</span>
          </div>
        </div>
      </div>
    </div>
  </fieldset>
  <div class=\"clearfloat\"></div>
</div>
";
    }

    public function getTemplateName()
    {
        return "database/events/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  360 => 155,  351 => 149,  348 => 148,  346 => 145,  345 => 144,  344 => 143,  337 => 138,  334 => 137,  332 => 134,  331 => 133,  330 => 132,  320 => 126,  314 => 125,  307 => 121,  298 => 116,  295 => 115,  293 => 114,  288 => 113,  284 => 111,  282 => 110,  278 => 109,  273 => 108,  271 => 107,  266 => 105,  259 => 100,  252 => 96,  248 => 95,  243 => 93,  239 => 92,  234 => 90,  230 => 89,  226 => 88,  220 => 87,  217 => 86,  215 => 85,  210 => 82,  200 => 78,  196 => 76,  190 => 74,  186 => 72,  184 => 68,  183 => 67,  182 => 66,  180 => 63,  178 => 62,  171 => 58,  168 => 57,  166 => 56,  165 => 54,  164 => 53,  160 => 51,  154 => 49,  148 => 46,  145 => 45,  143 => 44,  142 => 42,  140 => 41,  138 => 40,  132 => 37,  126 => 34,  122 => 33,  116 => 30,  110 => 28,  106 => 27,  94 => 25,  87 => 21,  83 => 20,  79 => 19,  75 => 18,  68 => 14,  64 => 12,  62 => 11,  58 => 10,  51 => 7,  49 => 6,  42 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/events/index.twig", "/var/www/html/phpMyAdmin/templates/database/events/index.twig");
    }
}
