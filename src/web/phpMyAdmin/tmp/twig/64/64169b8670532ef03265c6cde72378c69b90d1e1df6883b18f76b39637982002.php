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

/* server/status/processes/list.twig */
class __TwigTemplate_3cb9b230b410d4e21124a88cc44c2f46041bb1dac82ef561233d3729b161ed49 extends \Twig\Template
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
        echo "<div class=\"responsivetable row\">
  <table id=\"tableprocesslist\" class=\"table table-light table-striped table-hover sortable w-auto\">
    <thead class=\"thead-light\">
      <tr>
        <th>";
        // line 5
        echo _gettext("Processes");
        echo "</th>
        ";
        // line 6
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["columns"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["column"]) {
            // line 7
            echo "          <th scope=\"col\">
            <a href=\"";
            // line 8
            echo PhpMyAdmin\Url::getFromRoute("/server/status/processes");
            echo "\" data-post=\"";
            echo PhpMyAdmin\Url::getCommon(twig_get_attribute($this->env, $this->source, $context["column"], "params", [], "any", false, false, false, 8), "", false);
            echo "\" class=\"sortlink\">
              ";
            // line 9
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["column"], "name", [], "any", false, false, false, 9), "html", null, true);
            echo "
              ";
            // line 10
            if (twig_get_attribute($this->env, $this->source, $context["column"], "is_sorted", [], "any", false, false, false, 10)) {
                // line 11
                echo "                <img class=\"icon ic_s_desc soimg\" alt=\"";
                // line 12
                echo _gettext("Descending");
                echo "\" src=\"themes/dot.gif\" style=\"display: ";
                echo (((twig_get_attribute($this->env, $this->source, $context["column"], "sort_order", [], "any", false, false, false, 12) == "DESC")) ? ("none") : ("inline"));
                echo "\">
                <img class=\"icon ic_s_asc soimg hide\" alt=\"";
                // line 14
                echo _gettext("Ascending");
                echo "\" src=\"themes/dot.gif\" style=\"display: ";
                echo (((twig_get_attribute($this->env, $this->source, $context["column"], "sort_order", [], "any", false, false, false, 14) == "DESC")) ? ("inline") : ("none"));
                echo "\">
              ";
            }
            // line 16
            echo "            </a>
            ";
            // line 17
            if (twig_get_attribute($this->env, $this->source, $context["column"], "has_full_query", [], "any", false, false, false, 17)) {
                // line 18
                echo "              <a href=\"";
                echo PhpMyAdmin\Url::getFromRoute("/server/status/processes");
                echo "\" data-post=\"";
                echo PhpMyAdmin\Url::getCommon(($context["refresh_params"] ?? null), "", false);
                echo "\">
                ";
                // line 19
                if (twig_get_attribute($this->env, $this->source, $context["column"], "is_full", [], "any", false, false, false, 19)) {
                    // line 20
                    echo "                  ";
                    echo \PhpMyAdmin\Html\Generator::getImage("s_partialtext", _gettext("Truncate shown queries"), ["class" => "icon_fulltext"]);
                    // line 24
                    echo "
                ";
                } else {
                    // line 26
                    echo "                  ";
                    echo \PhpMyAdmin\Html\Generator::getImage("s_fulltext", _gettext("Show full queries"), ["class" => "icon_fulltext"]);
                    // line 30
                    echo "
                ";
                }
                // line 32
                echo "              </a>
            ";
            }
            // line 34
            echo "          </th>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['column'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 36
        echo "      </tr>
    </thead>

    <tbody>
      ";
        // line 40
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["rows"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 41
            echo "        <tr>
          <td>
            <a class=\"ajax kill_process\" href=\"";
            // line 43
            echo PhpMyAdmin\Url::getFromRoute(("/server/status/processes/kill/" . twig_get_attribute($this->env, $this->source, $context["row"], "id", [], "any", false, false, false, 43)));
            echo "\" data-post=\"";
            echo PhpMyAdmin\Url::getCommon(["kill" => twig_get_attribute($this->env, $this->source, $context["row"], "id", [], "any", false, false, false, 43)], "", false);
            echo "\">
              ";
            // line 44
            echo _gettext("Kill");
            // line 45
            echo "            </a>
          </td>
          <td class=\"text-monospace text-right\">";
            // line 47
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["row"], "id", [], "any", false, false, false, 47), "html", null, true);
            echo "</td>
          <td>";
            // line 48
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["row"], "user", [], "any", false, false, false, 48), "html", null, true);
            echo "</td>
          <td>";
            // line 49
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["row"], "host", [], "any", false, false, false, 49), "html", null, true);
            echo "</td>
          <td>
            ";
            // line 51
            if ((twig_get_attribute($this->env, $this->source, $context["row"], "db", [], "any", false, false, false, 51) != "")) {
                // line 52
                echo "              ";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["row"], "db", [], "any", false, false, false, 52), "html", null, true);
                echo "
            ";
            } else {
                // line 54
                echo "              <em>";
                echo _gettext("None");
                echo "</em>
            ";
            }
            // line 56
            echo "          </td>
          <td>";
            // line 57
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["row"], "command", [], "any", false, false, false, 57), "html", null, true);
            echo "</td>
          <td class=\"text-monospace text-right\">";
            // line 58
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["row"], "time", [], "any", false, false, false, 58), "html", null, true);
            echo "</td>
          <td>";
            // line 59
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["row"], "state", [], "any", false, false, false, 59), "html", null, true);
            echo "</td>
          <td>";
            // line 60
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["row"], "progress", [], "any", false, false, false, 60), "html", null, true);
            echo "</td>
          <td>";
            // line 61
            echo twig_get_attribute($this->env, $this->source, $context["row"], "info", [], "any", false, false, false, 61);
            echo "</td>
      ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 63
        echo "    </tbody>
  </table>
</div>
";
    }

    public function getTemplateName()
    {
        return "server/status/processes/list.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  200 => 63,  192 => 61,  188 => 60,  184 => 59,  180 => 58,  176 => 57,  173 => 56,  167 => 54,  161 => 52,  159 => 51,  154 => 49,  150 => 48,  146 => 47,  142 => 45,  140 => 44,  134 => 43,  130 => 41,  126 => 40,  120 => 36,  113 => 34,  109 => 32,  105 => 30,  102 => 26,  98 => 24,  95 => 20,  93 => 19,  86 => 18,  84 => 17,  81 => 16,  74 => 14,  68 => 12,  66 => 11,  64 => 10,  60 => 9,  54 => 8,  51 => 7,  47 => 6,  43 => 5,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/status/processes/list.twig", "/var/www/html/phpMyAdmin/templates/server/status/processes/list.twig");
    }
}
