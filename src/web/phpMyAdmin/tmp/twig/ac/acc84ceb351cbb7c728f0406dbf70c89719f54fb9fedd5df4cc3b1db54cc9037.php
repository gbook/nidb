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

/* server/status/status/index.twig */
class __TwigTemplate_b406d824f9e29db25d482f00b61a8b3fb65608498e95e9af6bd1893f5704bab6 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "server/status/base.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 2
        $context["active"] = "status";
        // line 1
        $this->parent = $this->loadTemplate("server/status/base.twig", "server/status/status/index.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo "
";
        // line 5
        if (($context["is_data_loaded"] ?? null)) {
            // line 6
            echo "  <div class=\"row\"><h3>";
            echo twig_escape_filter($this->env, twig_sprintf(_gettext("Network traffic since startup: %s"), ($context["network_traffic"] ?? null)), "html", null, true);
            echo "</h3></div>
  <div class=\"row\"><p>";
            // line 7
            echo twig_escape_filter($this->env, twig_sprintf(_gettext("This MySQL server has been running for %1\$s. It started up on %2\$s."), ($context["uptime"] ?? null), ($context["start_time"] ?? null)), "html", null, true);
            echo "</p></div>

<div class=\"row justify-content-between\">
  <table class=\"table table-light table-striped table-hover col-12 col-md-5\">
    <thead class=\"thead-light\">
      <tr>
        <th scope=\"col\">
          ";
            // line 14
            echo _gettext("Traffic");
            // line 15
            echo "          ";
            echo \PhpMyAdmin\Html\Generator::showHint(_gettext("On a busy server, the byte counters may overrun, so those statistics as reported by the MySQL server may be incorrect."));
            echo "
        </th>
        <th scope=\"col\">#</th>
        <th scope=\"col\">";
            // line 18
            echo _gettext("ø per hour");
            echo "</th>
      </tr>
    </thead>

    <tbody>
      ";
            // line 23
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["traffic"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["each_traffic"]) {
                // line 24
                echo "        <tr>
          <th scope=\"row\">";
                // line 25
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["each_traffic"], "name", [], "any", false, false, false, 25), "html", null, true);
                echo "</th>
          <td class=\"text-monospace text-right\">";
                // line 26
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["each_traffic"], "number", [], "any", false, false, false, 26), "html", null, true);
                echo "</td>
          <td class=\"text-monospace text-right\">";
                // line 27
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["each_traffic"], "per_hour", [], "any", false, false, false, 27), "html", null, true);
                echo "</td>
        </tr>
      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['each_traffic'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 30
            echo "    </tbody>
  </table>

  <table class=\"table table-light table-striped table-hover col-12 col-md-6\">
    <thead class=\"thead-light\">
      <tr>
        <th scope=\"col\">";
            // line 36
            echo _gettext("Connections");
            echo "</th>
        <th scope=\"col\">#</th>
        <th scope=\"col\">";
            // line 38
            echo _gettext("ø per hour");
            echo "</th>
        <th scope=\"col\">%</th>
      </tr>
    </thead>

    <tbody>
      ";
            // line 44
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["connections"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["connection"]) {
                // line 45
                echo "        <tr>
          <th>";
                // line 46
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["connection"], "name", [], "any", false, false, false, 46), "html", null, true);
                echo "</th>
          <td class=\"text-monospace text-right\">";
                // line 47
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["connection"], "number", [], "any", false, false, false, 47), "html", null, true);
                echo "</td>
          <td class=\"text-monospace text-right\">";
                // line 48
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["connection"], "per_hour", [], "any", false, false, false, 48), "html", null, true);
                echo "</td>
          <td class=\"text-monospace text-right\">";
                // line 49
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["connection"], "percentage", [], "any", false, false, false, 49), "html", null, true);
                echo "</td>
        </tr>
      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['connection'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 52
            echo "    </tbody>
  </table>
</div>

  ";
            // line 56
            if ((($context["is_master"] ?? null) || ($context["is_slave"] ?? null))) {
                // line 57
                echo "    <p class=\"alert alert-primary clearfloat\" role=\"alert\">
      ";
                // line 58
                if ((($context["is_master"] ?? null) && ($context["is_slave"] ?? null))) {
                    // line 59
                    echo "        ";
                    echo _gettext("This MySQL server works as <b>master</b> and <b>slave</b> in <b>replication</b> process.");
                    // line 60
                    echo "      ";
                } elseif (($context["is_master"] ?? null)) {
                    // line 61
                    echo "        ";
                    echo _gettext("This MySQL server works as <b>master</b> in <b>replication</b> process.");
                    // line 62
                    echo "      ";
                } elseif (($context["is_slave"] ?? null)) {
                    // line 63
                    echo "        ";
                    echo _gettext("This MySQL server works as <b>slave</b> in <b>replication</b> process.");
                    // line 64
                    echo "      ";
                }
                // line 65
                echo "    </p>

    <hr class=\"clearfloat\">

    <h3>";
                // line 69
                echo _gettext("Replication status");
                echo "</h3>

    ";
                // line 71
                echo ($context["replication"] ?? null);
                echo "
  ";
            }
            // line 73
            echo "
";
        } else {
            // line 75
            echo "  ";
            echo call_user_func_array($this->env->getFilter('error')->getCallable(), [_gettext("Not enough privilege to view server status.")]);
            echo "
";
        }
        // line 77
        echo "
";
    }

    public function getTemplateName()
    {
        return "server/status/status/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  221 => 77,  215 => 75,  211 => 73,  206 => 71,  201 => 69,  195 => 65,  192 => 64,  189 => 63,  186 => 62,  183 => 61,  180 => 60,  177 => 59,  175 => 58,  172 => 57,  170 => 56,  164 => 52,  155 => 49,  151 => 48,  147 => 47,  143 => 46,  140 => 45,  136 => 44,  127 => 38,  122 => 36,  114 => 30,  105 => 27,  101 => 26,  97 => 25,  94 => 24,  90 => 23,  82 => 18,  75 => 15,  73 => 14,  63 => 7,  58 => 6,  56 => 5,  53 => 4,  49 => 3,  44 => 1,  42 => 2,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/status/status/index.twig", "/var/www/html/phpMyAdmin/templates/server/status/status/index.twig");
    }
}
