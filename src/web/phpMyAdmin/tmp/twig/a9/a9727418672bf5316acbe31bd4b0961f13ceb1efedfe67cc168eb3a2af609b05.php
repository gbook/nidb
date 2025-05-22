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

/* table/structure/drop_confirm.twig */
class __TwigTemplate_53761dcbe88867d9844173694af4048ad8aca7bd4c88da7ae01699391e2293e6 extends \Twig\Template
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
        echo "<form action=\"";
        echo PhpMyAdmin\Url::getFromRoute("/table/structure/drop");
        echo "\" method=\"post\">
  ";
        // line 2
        echo PhpMyAdmin\Url::getHiddenInputs(["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "selected" => ($context["fields"] ?? null)]);
        echo "

  <fieldset class=\"confirmation\">
    <legend>
      ";
        // line 6
        echo _gettext("Do you really want to execute the following query?");
        // line 7
        echo "    </legend>

    <code>
      ALTER TABLE ";
        // line 10
        echo twig_escape_filter($this->env, PhpMyAdmin\Util::backquote(($context["table"] ?? null)), "html", null, true);
        echo "<br>
      ";
        // line 11
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["fields"] ?? null));
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
        foreach ($context['_seq'] as $context["_key"] => $context["field"]) {
            // line 12
            echo "        &nbsp;&nbsp;DROP ";
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::backquote($context["field"]), "html", null, true);
            // line 13
            if (twig_get_attribute($this->env, $this->source, $context["loop"], "last", [], "any", false, false, false, 13)) {
                echo ";";
            } else {
                echo ",<br>";
            }
            // line 14
            echo "      ";
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
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['field'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 15
        echo "    </code>
  </fieldset>

  <fieldset class=\"tblFooters\">
    <input id=\"buttonYes\" class=\"btn btn-secondary\" type=\"submit\" name=\"mult_btn\" value=\"";
        // line 19
        echo _gettext("Yes");
        echo "\">
    <input id=\"buttonNo\" class=\"btn btn-secondary\" type=\"submit\" name=\"mult_btn\" value=\"";
        // line 20
        echo _gettext("No");
        echo "\">
  </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "table/structure/drop_confirm.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  110 => 20,  106 => 19,  100 => 15,  86 => 14,  80 => 13,  77 => 12,  60 => 11,  56 => 10,  51 => 7,  49 => 6,  42 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/structure/drop_confirm.twig", "/var/www/html/phpMyAdmin/templates/table/structure/drop_confirm.twig");
    }
}
