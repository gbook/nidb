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

/* server/status/processes/index.twig */
class __TwigTemplate_ecbca9c9b689cb1ca2dd6064b70bf82ae1854bc4580ca7f52d2ef89accd345cc extends \Twig\Template
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
        $context["active"] = "processes";
        // line 1
        $this->parent = $this->loadTemplate("server/status/base.twig", "server/status/processes/index.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo "
<div class=\"row\">
<fieldset id=\"tableFilter\">
  <legend>";
        // line 7
        echo _gettext("Filters");
        echo "</legend>
  <form action=\"";
        // line 8
        echo PhpMyAdmin\Url::getFromRoute("/server/status/processes");
        echo "\" method=\"post\">
    ";
        // line 9
        echo PhpMyAdmin\Url::getHiddenInputs(($context["url_params"] ?? null));
        echo "
    <input class=\"btn btn-secondary\" type=\"submit\" value=\"";
        // line 10
        echo _gettext("Refresh");
        echo "\">
    <div class=\"formelement\">
      <input type=\"checkbox\" name=\"showExecuting\" id=\"showExecuting\" class=\"autosubmit\"";
        // line 12
        echo ((($context["is_checked"] ?? null)) ? (" checked") : (""));
        echo ">
      <label for=\"showExecuting\">
        ";
        // line 14
        echo _gettext("Show only active");
        // line 15
        echo "      </label>
    </div>
  </form>
</fieldset>
</div>

";
        // line 21
        echo ($context["server_process_list"] ?? null);
        echo "

<div class=\"row\">
";
        // line 24
        echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [_gettext("Note: Enabling the auto refresh here might cause heavy traffic between the web server and the MySQL server.")]);
        echo "
</div>

<div class=\"tabLinks row\">
  <label>
    ";
        // line 29
        echo _gettext("Refresh rate");
        echo ":

    <select id=\"id_refreshRate\" class=\"refreshRate\" name=\"refreshRate\">
      ";
        // line 32
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable([0 => 2, 1 => 3, 2 => 4, 3 => 5, 4 => 10, 5 => 20, 6 => 40, 7 => 60, 8 => 120, 9 => 300, 10 => 600, 11 => 1200]);
        foreach ($context['_seq'] as $context["_key"] => $context["rate"]) {
            // line 33
            echo "        <option value=\"";
            echo twig_escape_filter($this->env, $context["rate"], "html", null, true);
            echo "\"";
            echo ((($context["rate"] == 5)) ? (" selected") : (""));
            echo ">
          ";
            // line 34
            if (($context["rate"] < 60)) {
                // line 35
                echo "            ";
                if (($context["rate"] == 1)) {
                    // line 36
                    echo "              ";
                    echo twig_escape_filter($this->env, twig_sprintf(_gettext("%d second"), $context["rate"]), "html", null, true);
                    echo "
            ";
                } else {
                    // line 38
                    echo "              ";
                    echo twig_escape_filter($this->env, twig_sprintf(_gettext("%d seconds"), $context["rate"]), "html", null, true);
                    echo "
            ";
                }
                // line 40
                echo "          ";
            } else {
                // line 41
                echo "            ";
                if ((($context["rate"] / 60) == 1)) {
                    // line 42
                    echo "              ";
                    echo twig_escape_filter($this->env, twig_sprintf(_gettext("%d minute"), ($context["rate"] / 60)), "html", null, true);
                    echo "
            ";
                } else {
                    // line 44
                    echo "              ";
                    echo twig_escape_filter($this->env, twig_sprintf(_gettext("%d minutes"), ($context["rate"] / 60)), "html", null, true);
                    echo "
            ";
                }
                // line 46
                echo "          ";
            }
            // line 47
            echo "        </option>
      ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['rate'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 49
        echo "    </select>
  </label>
  <a id=\"toggleRefresh\" href=\"#\">
    ";
        // line 52
        echo \PhpMyAdmin\Html\Generator::getImage("play");
        echo "
    ";
        // line 53
        echo _gettext("Start auto refresh");
        // line 54
        echo "  </a>
</div>

";
    }

    public function getTemplateName()
    {
        return "server/status/processes/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  177 => 54,  175 => 53,  171 => 52,  166 => 49,  159 => 47,  156 => 46,  150 => 44,  144 => 42,  141 => 41,  138 => 40,  132 => 38,  126 => 36,  123 => 35,  121 => 34,  114 => 33,  110 => 32,  104 => 29,  96 => 24,  90 => 21,  82 => 15,  80 => 14,  75 => 12,  70 => 10,  66 => 9,  62 => 8,  58 => 7,  53 => 4,  49 => 3,  44 => 1,  42 => 2,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/status/processes/index.twig", "/var/www/html/phpMyAdmin/templates/server/status/processes/index.twig");
    }
}
