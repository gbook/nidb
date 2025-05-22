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

/* server/variables/index.twig */
class __TwigTemplate_78c9ea1d04b9072e94f6a64e698a16e979ab0ca4cb6f59e7db405829110f2a4e extends \Twig\Template
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
<div class=\"row\">
  <h2>
    ";
        // line 4
        echo \PhpMyAdmin\Html\Generator::getImage("s_vars");
        echo "
    ";
        // line 5
        echo _gettext("Server variables and settings");
        // line 6
        echo "    ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("server_system_variables");
        echo "
  </h2>
</div>

";
        // line 10
        if ( !twig_test_empty(($context["variables"] ?? null))) {
            // line 11
            echo "  <a href=\"#\" class=\"ajax saveLink hide\">
    ";
            // line 12
            echo \PhpMyAdmin\Html\Generator::getIcon("b_save", _gettext("Save"));
            echo "
  </a>
  <a href=\"#\" class=\"cancelLink hide\">
    ";
            // line 15
            echo \PhpMyAdmin\Html\Generator::getIcon("b_close", _gettext("Cancel"));
            echo "
  </a>
  ";
            // line 17
            echo \PhpMyAdmin\Html\Generator::getImage("b_help", _gettext("Documentation"), ["class" => "hide", "id" => "docImage"]);
            // line 20
            echo "

  ";
            // line 22
            $this->loadTemplate("filter.twig", "server/variables/index.twig", 22)->display(twig_to_array(["filter_value" =>             // line 23
($context["filter_value"] ?? null)]));
            // line 25
            echo "
  <div class=\"table-responsive\">
    <table id=\"serverVariables\" class=\"table table-light table-striped table-hover table-sm\">
      <thead class=\"thead-light\">
        <tr>
          <th scope=\"col\">";
            // line 30
            echo _gettext("Action");
            echo "</th>
          <th scope=\"col\">";
            // line 31
            echo _gettext("Variable");
            echo "</th>
          <th scope=\"col\" class=\"text-right\">";
            // line 32
            echo _gettext("Value");
            echo "</th>
        </tr>
      </thead>

      <tbody>
        ";
            // line 37
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["variables"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["variable"]) {
                // line 38
                echo "          <tr class=\"var-row\" data-filter-row=\"";
                echo twig_escape_filter($this->env, twig_upper_filter($this->env, twig_get_attribute($this->env, $this->source, $context["variable"], "name", [], "any", false, false, false, 38)), "html", null, true);
                echo "\">
            <td>
              ";
                // line 40
                if (twig_get_attribute($this->env, $this->source, $context["variable"], "is_editable", [], "any", false, false, false, 40)) {
                    // line 41
                    echo "                <a href=\"#\" data-variable=\"";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["variable"], "name", [], "any", false, false, false, 41), "html", null, true);
                    echo "\" class=\"editLink\">";
                    echo \PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Edit"));
                    echo "</a>
              ";
                } else {
                    // line 43
                    echo "                <span title=\"";
                    echo _gettext("This is a read-only variable and can not be edited");
                    echo "\" class=\"read_only_var\">
                  ";
                    // line 44
                    echo \PhpMyAdmin\Html\Generator::getIcon("bd_edit", _gettext("Edit"));
                    echo "
                </span>
              ";
                }
                // line 47
                echo "            </td>
            <td class=\"var-name font-weight-bold\">
              ";
                // line 49
                if ((twig_get_attribute($this->env, $this->source, $context["variable"], "doc_link", [], "any", false, false, false, 49) != null)) {
                    // line 50
                    echo "                <span title=\"";
                    echo twig_escape_filter($this->env, twig_replace_filter(twig_get_attribute($this->env, $this->source, $context["variable"], "name", [], "any", false, false, false, 50), ["_" => " "]), "html", null, true);
                    echo "\">
                  ";
                    // line 51
                    echo twig_get_attribute($this->env, $this->source, $context["variable"], "doc_link", [], "any", false, false, false, 51);
                    echo "
                </span>
              ";
                } else {
                    // line 54
                    echo "                ";
                    echo twig_escape_filter($this->env, twig_replace_filter(twig_get_attribute($this->env, $this->source, $context["variable"], "name", [], "any", false, false, false, 54), ["_" => " "]), "html", null, true);
                    echo "
              ";
                }
                // line 56
                echo "            </td>
            <td class=\"var-value text-right text-monospace";
                // line 57
                echo ((($context["is_superuser"] ?? null)) ? (" editable") : (""));
                echo "\">
              ";
                // line 58
                if (twig_get_attribute($this->env, $this->source, $context["variable"], "is_escaped", [], "any", false, false, false, 58)) {
                    // line 59
                    echo "                ";
                    echo twig_get_attribute($this->env, $this->source, $context["variable"], "value", [], "any", false, false, false, 59);
                    echo "
              ";
                } else {
                    // line 61
                    echo "                ";
                    echo twig_replace_filter(twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["variable"], "value", [], "any", false, false, false, 61)), ["," => ",&#8203;"]);
                    echo "
              ";
                }
                // line 63
                echo "            </td>
          </tr>

          ";
                // line 66
                if (twig_get_attribute($this->env, $this->source, $context["variable"], "has_session_value", [], "any", false, false, false, 66)) {
                    // line 67
                    echo "            <tr class=\"var-row\" data-filter-row=\"";
                    echo twig_escape_filter($this->env, twig_upper_filter($this->env, twig_get_attribute($this->env, $this->source, $context["variable"], "name", [], "any", false, false, false, 67)), "html", null, true);
                    echo "\">
              <td></td>
              <td class=\"var-name font-italic\">";
                    // line 69
                    echo twig_escape_filter($this->env, twig_replace_filter(twig_get_attribute($this->env, $this->source, $context["variable"], "name", [], "any", false, false, false, 69), ["_" => " "]), "html", null, true);
                    echo " (";
                    echo _gettext("Session value");
                    echo ")</td>
              <td class=\"var-value text-right text-monospace\">";
                    // line 70
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["variable"], "session_value", [], "any", false, false, false, 70), "html", null, true);
                    echo "</td>
            </tr>
          ";
                }
                // line 73
                echo "        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['variable'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 74
            echo "      </tbody>
    </table>
  </div>
</div>

";
        } else {
            // line 80
            echo "  ";
            echo call_user_func_array($this->env->getFilter('error')->getCallable(), [twig_sprintf(_gettext("Not enough privilege to view server variables and settings. %s"), PhpMyAdmin\Html\Generator::linkToVarDocumentation("show_compatibility_56",             // line 81
($context["is_mariadb"] ?? null)))]);
            // line 82
            echo "
";
        }
    }

    public function getTemplateName()
    {
        return "server/variables/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  222 => 82,  220 => 81,  218 => 80,  210 => 74,  204 => 73,  198 => 70,  192 => 69,  186 => 67,  184 => 66,  179 => 63,  173 => 61,  167 => 59,  165 => 58,  161 => 57,  158 => 56,  152 => 54,  146 => 51,  141 => 50,  139 => 49,  135 => 47,  129 => 44,  124 => 43,  116 => 41,  114 => 40,  108 => 38,  104 => 37,  96 => 32,  92 => 31,  88 => 30,  81 => 25,  79 => 23,  78 => 22,  74 => 20,  72 => 17,  67 => 15,  61 => 12,  58 => 11,  56 => 10,  48 => 6,  46 => 5,  42 => 4,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/variables/index.twig", "/var/www/html/phpMyAdmin/templates/server/variables/index.twig");
    }
}
