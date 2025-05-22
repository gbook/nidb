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

/* server/privileges/users_overview.twig */
class __TwigTemplate_7ef407491a3e3af5398de96ebec15b0921a6db03e9eb0f997c0fbd912e1011c5 extends \Twig\Template
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
        echo "<form name=\"usersForm\" id=\"usersForm\" action=\"";
        echo PhpMyAdmin\Url::getFromRoute("/server/privileges");
        echo "\" method=\"post\">
  ";
        // line 2
        echo PhpMyAdmin\Url::getHiddenInputs();
        echo "
  <div class=\"table-responsive\">
    <table id=\"userRightsTable\" class=\"table table-light table-striped table-hover w-auto\">
      <thead class=\"thead-light\">
        <tr>
          <th></th>
          <th scope=\"col\">";
        // line 8
        echo _gettext("User name");
        echo "</th>
          <th scope=\"col\">";
        // line 9
        echo _gettext("Host name");
        echo "</th>
          <th scope=\"col\">";
        // line 10
        echo _gettext("Password");
        echo "</th>
          <th scope=\"col\">
            ";
        // line 12
        echo _gettext("Global privileges");
        // line 13
        echo "            ";
        echo \PhpMyAdmin\Html\Generator::showHint("Note: MySQL privilege names are expressed in English.");
        echo "
          </th>
          ";
        // line 15
        if (($context["menus_work"] ?? null)) {
            // line 16
            echo "            <th scope=\"col\">";
            echo _gettext("User group");
            echo "</th>
          ";
        }
        // line 18
        echo "          <th scope=\"col\">";
        echo _gettext("Grant");
        echo "</th>
          <th scope=\"col\" colspan=\"";
        // line 19
        echo (((($context["user_group_count"] ?? null) > 0)) ? ("3") : ("2"));
        echo "\">";
        echo _gettext("Action");
        echo "</th>
        </tr>
      </thead>

      <tbody>
        ";
        // line 24
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["hosts"] ?? null));
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
        foreach ($context['_seq'] as $context["_key"] => $context["host"]) {
            // line 25
            echo "          <tr>
            <td>
              <input type=\"checkbox\" class=\"checkall\" id=\"checkbox_sel_users_";
            // line 27
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, false, 27), "html", null, true);
            echo "\" value=\"";
            // line 28
            echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["host"], "user", [], "any", false, false, false, 28) . "&amp;#27;") . twig_get_attribute($this->env, $this->source, $context["host"], "host", [], "any", false, false, false, 28)), "html", null, true);
            echo "\" name=\"selected_usr[]\">
            </td>
            <td>
              <label for=\"checkbox_sel_users_";
            // line 31
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["loop"], "index", [], "any", false, false, false, 31), "html", null, true);
            echo "\">
                ";
            // line 32
            if (twig_test_empty(twig_get_attribute($this->env, $this->source, $context["host"], "user", [], "any", false, false, false, 32))) {
                // line 33
                echo "                  <span class=\"text-danger\">";
                echo _gettext("Any");
                echo "</span>
                ";
            } else {
                // line 35
                echo "                 <a class=\"edit_user_anchor\" href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["username" => twig_get_attribute($this->env, $this->source,                 // line 36
$context["host"], "user", [], "any", false, false, false, 36), "hostname" => twig_get_attribute($this->env, $this->source,                 // line 37
$context["host"], "host", [], "any", false, false, false, 37), "dbname" => "", "tablename" => "", "routinename" => ""]);
                // line 41
                echo "\">
                 ";
                // line 42
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["host"], "user", [], "any", false, false, false, 42), "html", null, true);
                echo "
                 </a>
                ";
            }
            // line 45
            echo "              </label>
            </td>
            <td>";
            // line 47
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["host"], "host", [], "any", false, false, false, 47), "html", null, true);
            echo "</td>
            <td>
              ";
            // line 49
            if (twig_get_attribute($this->env, $this->source, $context["host"], "has_password", [], "any", false, false, false, 49)) {
                // line 50
                echo "                ";
                echo _gettext("Yes");
                // line 51
                echo "              ";
            } else {
                // line 52
                echo "                <span class=\"text-danger\">";
                echo _gettext("No");
                echo "</span>
              ";
            }
            // line 54
            echo "              ";
            echo (( !twig_get_attribute($this->env, $this->source, $context["host"], "has_select_priv", [], "any", false, false, false, 54)) ? (\PhpMyAdmin\Html\Generator::showHint(_gettext("The selected user was not found in the privilege table."))) : (""));
            echo "
            </td>
            <td>
              <code>";
            // line 57
            echo twig_join_filter(twig_get_attribute($this->env, $this->source, $context["host"], "privileges", [], "any", false, false, false, 57), ", ");
            echo "</code>
            </td>
            ";
            // line 59
            if (($context["menus_work"] ?? null)) {
                // line 60
                echo "              <td class=\"usrGroup\">";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["host"], "group", [], "any", false, false, false, 60), "html", null, true);
                echo "</td>
            ";
            }
            // line 62
            echo "            <td>";
            echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["host"], "has_grant", [], "any", false, false, false, 62)) ? (_gettext("Yes")) : (_gettext("No"))), "html", null, true);
            echo "</td>
            ";
            // line 63
            if (($context["is_grantuser"] ?? null)) {
                // line 64
                echo "              <td class=\"text-center\">
                <a class=\"edit_user_anchor\" href=\"";
                // line 65
                echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["username" => twig_get_attribute($this->env, $this->source,                 // line 66
$context["host"], "user", [], "any", false, false, false, 66), "hostname" => twig_get_attribute($this->env, $this->source,                 // line 67
$context["host"], "host", [], "any", false, false, false, 67), "dbname" => "", "tablename" => "", "routinename" => ""]);
                // line 71
                echo "\">
                  ";
                // line 72
                echo \PhpMyAdmin\Html\Generator::getIcon("b_usredit", _gettext("Edit privileges"));
                echo "
                </a>
              </td>
            ";
            }
            // line 76
            echo "            ";
            if ((($context["menus_work"] ?? null) && (($context["user_group_count"] ?? null) > 0))) {
                // line 77
                echo "              <td class=\"text-center\">
                ";
                // line 78
                if ( !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["host"], "user", [], "any", false, false, false, 78))) {
                    // line 79
                    echo "                  <a class=\"edit_user_group_anchor ajax\" href=\"";
                    echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["username" => twig_get_attribute($this->env, $this->source, $context["host"], "user", [], "any", false, false, false, 79)]);
                    echo "\">
                    ";
                    // line 80
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_usrlist", _gettext("Edit user group"));
                    echo "
                  </a>
                ";
                }
                // line 83
                echo "              </td>
            ";
            }
            // line 85
            echo "            <td class=\"text-center\">
              <a class=\"export_user_anchor ajax\" href=\"";
            // line 86
            echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["username" => twig_get_attribute($this->env, $this->source,             // line 87
$context["host"], "user", [], "any", false, false, false, 87), "hostname" => twig_get_attribute($this->env, $this->source,             // line 88
$context["host"], "host", [], "any", false, false, false, 88), "initial" =>             // line 89
($context["initial"] ?? null), "export" => true]);
            // line 91
            echo "\">
                ";
            // line 92
            echo \PhpMyAdmin\Html\Generator::getIcon("b_tblexport", _gettext("Export"));
            echo "
              </a>
            </td>
          </tr>
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
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['host'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 97
        echo "      </tbody>
    </table>
  </div>

  <div class=\"floatleft row\">
    <div class=\"col-12\">
      <img class=\"selectallarrow\" width=\"38\" height=\"22\" src=\"";
        // line 104
        echo twig_escape_filter($this->env, ($context["theme_image_path"] ?? null), "html", null, true);
        echo "arrow_";
        echo twig_escape_filter($this->env, ($context["text_dir"] ?? null), "html", null, true);
        echo ".png\" alt=\"";
        echo _gettext("With selected:");
        echo "\">
      <input type=\"checkbox\" id=\"usersForm_checkall\" class=\"checkall_box\" title=\"";
        // line 105
        echo _gettext("Check all");
        echo "\">
      <label for=\"usersForm_checkall\">";
        // line 106
        echo _gettext("Check all");
        echo "</label>
      <em class=\"with-selected\">";
        // line 107
        echo _gettext("With selected:");
        echo "</em>

      <button class=\"btn btn-link mult_submit\" type=\"submit\" name=\"submit_mult\" value=\"export\" title=\"";
        // line 109
        echo _gettext("Export");
        echo "\">
        ";
        // line 110
        echo \PhpMyAdmin\Html\Generator::getIcon("b_tblexport", _gettext("Export"));
        echo "
      </button>

      <input type=\"hidden\" name=\"initial\" value=\"";
        // line 113
        echo twig_escape_filter($this->env, ($context["initial"] ?? null), "html", null, true);
        echo "\">
    </div>
  </div>

  <div class=\"clearfloat\"></div>

  ";
        // line 119
        if (($context["is_createuser"] ?? null)) {
            // line 120
            echo "    <div class=\"card mb-3\">
      <div class=\"card-header\">";
            // line 121
            echo _pgettext(            "Create new user", "New");
            echo "</div>
      <div class=\"card-body\">
        <a id=\"add_user_anchor\" href=\"";
            // line 123
            echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["adduser" => true]);
            echo "\">
          ";
            // line 124
            echo \PhpMyAdmin\Html\Generator::getIcon("b_usradd", _gettext("Add user account"));
            echo "
        </a>
      </div>
    </div>
  ";
        }
        // line 129
        echo "
  <div id=\"deleteUserCard\" class=\"card mb-3\">
    <div class=\"card-header\">";
        // line 131
        echo \PhpMyAdmin\Html\Generator::getIcon("b_usrdrop", _gettext("Remove selected user accounts"));
        echo "</div>
    <div class=\"card-body\">
      <p class=\"card-text\">";
        // line 133
        echo _gettext("Revoke all active privileges from the users and delete them afterwards.");
        echo "</p>
      <div class=\"form-check\">
        <input class=\"form-check-input\" type=\"checkbox\" id=\"dropUsersDbCheckbox\" name=\"drop_users_db\">
        <label class=\"form-check-label\" for=\"dropUsersDbCheckbox\">
          ";
        // line 137
        echo _gettext("Drop the databases that have the same names as the users.");
        // line 138
        echo "        </label>
      </div>
    </div>
    <div class=\"card-footer text-right\">
      <input type=\"hidden\" name=\"mode\" value=\"2\">
      <input id=\"buttonGo\" class=\"btn btn-primary ajax\" type=\"submit\" name=\"delete\" value=\"";
        // line 143
        echo _gettext("Go");
        echo "\">
    </div>
  </div>
</form>
";
    }

    public function getTemplateName()
    {
        return "server/privileges/users_overview.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  366 => 143,  359 => 138,  357 => 137,  350 => 133,  345 => 131,  341 => 129,  333 => 124,  329 => 123,  324 => 121,  321 => 120,  319 => 119,  310 => 113,  304 => 110,  300 => 109,  295 => 107,  291 => 106,  287 => 105,  279 => 104,  271 => 97,  252 => 92,  249 => 91,  247 => 89,  246 => 88,  245 => 87,  244 => 86,  241 => 85,  237 => 83,  231 => 80,  226 => 79,  224 => 78,  221 => 77,  218 => 76,  211 => 72,  208 => 71,  206 => 67,  205 => 66,  204 => 65,  201 => 64,  199 => 63,  194 => 62,  188 => 60,  186 => 59,  181 => 57,  174 => 54,  168 => 52,  165 => 51,  162 => 50,  160 => 49,  155 => 47,  151 => 45,  145 => 42,  142 => 41,  140 => 37,  139 => 36,  137 => 35,  131 => 33,  129 => 32,  125 => 31,  119 => 28,  116 => 27,  112 => 25,  95 => 24,  85 => 19,  80 => 18,  74 => 16,  72 => 15,  66 => 13,  64 => 12,  59 => 10,  55 => 9,  51 => 8,  42 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/privileges/users_overview.twig", "/var/www/html/phpMyAdmin/templates/server/privileges/users_overview.twig");
    }
}
