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

/* table/privileges/index.twig */
class __TwigTemplate_a9f25620c840c7fe6f062092244bb6f21157865da0628939af036e6849bccf84 extends \Twig\Template
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
        if (($context["is_superuser"] ?? null)) {
            // line 2
            echo "  <form id=\"usersForm\" action=\"";
            echo PhpMyAdmin\Url::getFromRoute("/server/privileges");
            echo "\">
    ";
            // line 3
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null), ($context["table"] ?? null));
            echo "

    <fieldset>
      <legend>
        ";
            // line 7
            echo \PhpMyAdmin\Html\Generator::getIcon("b_usrcheck");
            echo "
        ";
            // line 8
            echo twig_sprintf(_gettext("Users having access to \"%s\""), ((((((("<a href=\"" . ($context["table_url"] ?? null)) . PhpMyAdmin\Url::getCommon(["db" =>             // line 9
($context["db"] ?? null), "table" =>             // line 10
($context["table"] ?? null)], "&")) . "\">") . twig_escape_filter($this->env,             // line 11
($context["db"] ?? null), "html")) . ".") . twig_escape_filter($this->env, ($context["table"] ?? null), "html")) . "</a>"));
            echo "
      </legend>

      <div class=\"table-responsive-md jsresponsive\">
        <table class=\"table table-light table-striped table-hover w-auto\">
          <thead class=\"thead-light\">
            <tr>
              <th></th>
              <th>";
            // line 19
            echo _gettext("User name");
            echo "</th>
              <th>";
            // line 20
            echo _gettext("Host name");
            echo "</th>
              <th>";
            // line 21
            echo _gettext("Type");
            echo "</th>
              <th>";
            // line 22
            echo _gettext("Privileges");
            echo "</th>
              <th>";
            // line 23
            echo _gettext("Grant");
            echo "</th>
              <th colspan=\"2\">";
            // line 24
            echo _gettext("Action");
            echo "</th>
            </tr>
          </thead>

          <tbody>
            ";
            // line 29
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["privileges"] ?? null));
            $context['_iterated'] = false;
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
            foreach ($context['_seq'] as $context["_key"] => $context["privilege"]) {
                // line 30
                echo "              ";
                $context["privileges_amount"] = twig_length_filter($this->env, twig_get_attribute($this->env, $this->source, $context["privilege"], "privileges", [], "any", false, false, false, 30));
                // line 31
                echo "              <tr>
                <td";
                // line 32
                if ((($context["privileges_amount"] ?? null) > 1)) {
                    echo " class=\"align-middle\" rowspan=\"";
                    echo twig_escape_filter($this->env, ($context["privileges_amount"] ?? null), "html", null, true);
                    echo "\"";
                }
                echo ">
                  <input type=\"checkbox\" class=\"checkall\" name=\"selected_usr[]\" id=\"checkbox_sel_users_";
                // line 33
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["loop"], "index0", [], "any", false, false, false, 33), "html", null, true);
                echo "\" value=\"";
                // line 34
                echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["privilege"], "user", [], "any", false, false, false, 34) . "&amp;#27;") . twig_get_attribute($this->env, $this->source, $context["privilege"], "host", [], "any", false, false, false, 34)), "html", null, true);
                echo "\">
                </td>
                <td";
                // line 36
                if ((($context["privileges_amount"] ?? null) > 1)) {
                    echo " class=\"align-middle\" rowspan=\"";
                    echo twig_escape_filter($this->env, ($context["privileges_amount"] ?? null), "html", null, true);
                    echo "\"";
                }
                echo ">
                  ";
                // line 37
                if (twig_test_empty(twig_get_attribute($this->env, $this->source, $context["privilege"], "user", [], "any", false, false, false, 37))) {
                    // line 38
                    echo "                    <span class=\"text-danger\">";
                    echo _gettext("Any");
                    echo "</span>
                  ";
                } else {
                    // line 40
                    echo "                    ";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["privilege"], "user", [], "any", false, false, false, 40), "html", null, true);
                    echo "
                  ";
                }
                // line 42
                echo "                </td>
                <td";
                // line 43
                if ((($context["privileges_amount"] ?? null) > 1)) {
                    echo " class=\"align-middle\" rowspan=\"";
                    echo twig_escape_filter($this->env, ($context["privileges_amount"] ?? null), "html", null, true);
                    echo "\"";
                }
                echo ">
                  ";
                // line 44
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["privilege"], "host", [], "any", false, false, false, 44), "html", null, true);
                echo "
                </td>
                ";
                // line 46
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["privilege"], "privileges", [], "any", false, false, false, 46));
                foreach ($context['_seq'] as $context["_key"] => $context["priv"]) {
                    // line 47
                    echo "                  <td>
                    ";
                    // line 48
                    if ((twig_get_attribute($this->env, $this->source, $context["priv"], "type", [], "any", false, false, false, 48) == "g")) {
                        // line 49
                        echo "                      ";
                        echo _gettext("global");
                        // line 50
                        echo "                    ";
                    } elseif ((twig_get_attribute($this->env, $this->source, $context["priv"], "type", [], "any", false, false, false, 50) == "d")) {
                        // line 51
                        echo "                      ";
                        if ((twig_get_attribute($this->env, $this->source, $context["priv"], "database", [], "any", false, false, false, 51) == twig_replace_filter(($context["db"] ?? null), ["_" => "\\_", "%" => "\\%"]))) {
                            // line 52
                            echo "                        ";
                            echo _gettext("database-specific");
                            // line 53
                            echo "                      ";
                        } else {
                            // line 54
                            echo "                        ";
                            echo _gettext("wildcard");
                            echo ": <code>";
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["priv"], "database", [], "any", false, false, false, 54), "html", null, true);
                            echo "</code>
                      ";
                        }
                        // line 56
                        echo "                    ";
                    } elseif ((twig_get_attribute($this->env, $this->source, $context["priv"], "type", [], "any", false, false, false, 56) == "t")) {
                        // line 57
                        echo "                      ";
                        echo _gettext("table-specific");
                        // line 58
                        echo "                    ";
                    } elseif ((twig_get_attribute($this->env, $this->source, $context["priv"], "type", [], "any", false, false, false, 58) == "r")) {
                        // line 59
                        echo "                      ";
                        echo _gettext("routine");
                        // line 60
                        echo "                    ";
                    }
                    // line 61
                    echo "                  </td>
                  <td>
                    <code>
                      ";
                    // line 64
                    if ((twig_get_attribute($this->env, $this->source, $context["priv"], "type", [], "any", false, false, false, 64) == "r")) {
                        // line 65
                        echo "                        ";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["priv"], "routine", [], "any", false, false, false, 65), "html", null, true);
                        echo "
                        (";
                        // line 66
                        echo twig_escape_filter($this->env, twig_upper_filter($this->env, twig_join_filter(twig_get_attribute($this->env, $this->source, $context["priv"], "privileges", [], "any", false, false, false, 66), ", ")), "html", null, true);
                        echo ")
                      ";
                    } else {
                        // line 68
                        echo "                        ";
                        echo twig_join_filter(twig_get_attribute($this->env, $this->source, $context["priv"], "privileges", [], "any", false, false, false, 68), ", ");
                        echo "
                      ";
                    }
                    // line 70
                    echo "                    </code>
                  </td>
                  <td>
                    ";
                    // line 73
                    echo twig_escape_filter($this->env, ((twig_get_attribute($this->env, $this->source, $context["priv"], "has_grant", [], "any", false, false, false, 73)) ? (_gettext("Yes")) : (_gettext("No"))), "html", null, true);
                    echo "
                  </td>
                  <td>
                    ";
                    // line 76
                    if (($context["is_grantuser"] ?? null)) {
                        // line 77
                        echo "                      <a class=\"edit_user_anchor\" href=\"";
                        echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["username" => twig_get_attribute($this->env, $this->source,                         // line 78
$context["privilege"], "user", [], "any", false, false, false, 78), "hostname" => twig_get_attribute($this->env, $this->source,                         // line 79
$context["privilege"], "host", [], "any", false, false, false, 79), "dbname" => (((twig_get_attribute($this->env, $this->source,                         // line 80
$context["priv"], "database", [], "any", false, false, false, 80) != "*")) ? (twig_get_attribute($this->env, $this->source, $context["priv"], "database", [], "any", false, false, false, 80)) : ("")), "tablename" => (((twig_get_attribute($this->env, $this->source,                         // line 81
$context["priv"], "table", [], "any", true, true, false, 81) && (twig_get_attribute($this->env, $this->source, $context["priv"], "table", [], "any", false, false, false, 81) != "*"))) ? (twig_get_attribute($this->env, $this->source, $context["priv"], "table", [], "any", false, false, false, 81)) : ("")), "routinename" => (((twig_get_attribute($this->env, $this->source,                         // line 82
$context["priv"], "routine", [], "any", true, true, false, 82) &&  !(null === twig_get_attribute($this->env, $this->source, $context["priv"], "routine", [], "any", false, false, false, 82)))) ? (twig_get_attribute($this->env, $this->source, $context["priv"], "routine", [], "any", false, false, false, 82)) : (""))]);
                        // line 83
                        echo "\">
                        ";
                        // line 84
                        echo \PhpMyAdmin\Html\Generator::getIcon("b_usredit", _gettext("Edit privileges"));
                        echo "
                      </a>
                    ";
                    }
                    // line 87
                    echo "                  </td>
                  <td class=\"text-center\">
                    <a class=\"export_user_anchor ajax\" href=\"";
                    // line 89
                    echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["username" => twig_get_attribute($this->env, $this->source,                     // line 90
$context["privilege"], "user", [], "any", false, false, false, 90), "hostname" => twig_get_attribute($this->env, $this->source,                     // line 91
$context["privilege"], "host", [], "any", false, false, false, 91), "export" => true, "initial" => ""]);
                    // line 94
                    echo "\">
                      ";
                    // line 95
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_tblexport", _gettext("Export"));
                    echo "
                    </a>
                  </td>
                </tr>
                  ";
                    // line 99
                    if ((($context["privileges_amount"] ?? null) > 1)) {
                        // line 100
                        echo "                    <tr class=\"noclick\">
                  ";
                    }
                    // line 102
                    echo "                ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['priv'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 103
                echo "            ";
                $context['_iterated'] = true;
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['length'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            if (!$context['_iterated']) {
                // line 104
                echo "              <tr>
                <td colspan=\"7\">
                  ";
                // line 106
                echo _gettext("No user found.");
                // line 107
                echo "                </td>
              </tr>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['privilege'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 110
            echo "          </tbody>
        </table>
      </div>

      <div class=\"floatleft\">
        <img class=\"selectallarrow\" src=\"";
            // line 115
            echo twig_escape_filter($this->env, ($context["theme_image_path"] ?? null), "html", null, true);
            echo "arrow_";
            echo twig_escape_filter($this->env, ($context["text_dir"] ?? null), "html", null, true);
            echo ".png\" alt=\"";
            // line 116
            echo _gettext("With selected:");
            echo "\" width=\"38\" height=\"22\">
        <input type=\"checkbox\" id=\"usersForm_checkall\" class=\"checkall_box\" title=\"";
            // line 117
            echo _gettext("Check all");
            echo "\">
        <label for=\"usersForm_checkall\">";
            // line 118
            echo _gettext("Check all");
            echo "</label>
        <em class=\"with-selected\">";
            // line 119
            echo _gettext("With selected:");
            echo "</em>
        <button class=\"btn btn-link mult_submit\" type=\"submit\" name=\"submit_mult\" value=\"export\" title=\"";
            // line 120
            echo _gettext("Export");
            echo "\">
          ";
            // line 121
            echo \PhpMyAdmin\Html\Generator::getIcon("b_tblexport", _gettext("Export"));
            echo "
        </button>
      </div>
    </fieldset>
  </form>
";
        } else {
            // line 127
            echo "  ";
            echo call_user_func_array($this->env->getFilter('error')->getCallable(), [_gettext("Not enough privilege to view users.")]);
            echo "
";
        }
        // line 129
        echo "
";
        // line 130
        if (($context["is_createuser"] ?? null)) {
            // line 131
            echo "  <div class=\"row\">
    <div class=\"col-12\">
      <fieldset id=\"fieldset_add_user\">
        <legend>";
            // line 134
            echo _pgettext(            "Create new user", "New");
            echo "</legend>
        <a id=\"add_user_anchor\" href=\"";
            // line 135
            echo PhpMyAdmin\Url::getFromRoute("/server/privileges", ["adduser" => true, "dbname" =>             // line 137
($context["db"] ?? null), "tablename" =>             // line 138
($context["table"] ?? null)]);
            // line 139
            echo "\" rel=\"";
            echo PhpMyAdmin\Url::getCommon(["checkprivsdb" => ($context["db"] ?? null), "checkprivstable" => ($context["table"] ?? null)]);
            echo "\">
          ";
            // line 140
            echo \PhpMyAdmin\Html\Generator::getIcon("b_usradd", _gettext("Add user account"));
            echo "
        </a>
      </fieldset>
    </div>
  </div>
";
        }
    }

    public function getTemplateName()
    {
        return "table/privileges/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  402 => 140,  397 => 139,  395 => 138,  394 => 137,  393 => 135,  389 => 134,  384 => 131,  382 => 130,  379 => 129,  373 => 127,  364 => 121,  360 => 120,  356 => 119,  352 => 118,  348 => 117,  344 => 116,  339 => 115,  332 => 110,  324 => 107,  322 => 106,  318 => 104,  305 => 103,  299 => 102,  295 => 100,  293 => 99,  286 => 95,  283 => 94,  281 => 91,  280 => 90,  279 => 89,  275 => 87,  269 => 84,  266 => 83,  264 => 82,  263 => 81,  262 => 80,  261 => 79,  260 => 78,  258 => 77,  256 => 76,  250 => 73,  245 => 70,  239 => 68,  234 => 66,  229 => 65,  227 => 64,  222 => 61,  219 => 60,  216 => 59,  213 => 58,  210 => 57,  207 => 56,  199 => 54,  196 => 53,  193 => 52,  190 => 51,  187 => 50,  184 => 49,  182 => 48,  179 => 47,  175 => 46,  170 => 44,  162 => 43,  159 => 42,  153 => 40,  147 => 38,  145 => 37,  137 => 36,  132 => 34,  129 => 33,  121 => 32,  118 => 31,  115 => 30,  97 => 29,  89 => 24,  85 => 23,  81 => 22,  77 => 21,  73 => 20,  69 => 19,  58 => 11,  57 => 10,  56 => 9,  55 => 8,  51 => 7,  44 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/privileges/index.twig", "/var/www/html/phpMyAdmin/templates/table/privileges/index.twig");
    }
}
