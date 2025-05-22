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

/* table/delete/confirm.twig */
class __TwigTemplate_ab11d42a2fad0ee7def6c1762c8d57f30125a4e447fdbdc296e564659cbf4165 extends \Twig\Template
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
        echo PhpMyAdmin\Url::getFromRoute("/table/delete/rows");
        echo "\" method=\"post\">
  ";
        // line 2
        echo PhpMyAdmin\Url::getHiddenInputs(["db" =>         // line 3
($context["db"] ?? null), "table" =>         // line 4
($context["table"] ?? null), "selected" =>         // line 5
($context["selected"] ?? null), "original_sql_query" =>         // line 6
($context["sql_query"] ?? null), "fk_checks" => "0"]);
        // line 8
        echo "

  <fieldset class=\"confirmation\">
    <legend>
      ";
        // line 12
        echo _gettext("Do you really want to execute the following query?");
        // line 13
        echo "    </legend>

    <ul>
      ";
        // line 16
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["selected"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 17
            echo "        <li><code>DELETE FROM ";
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::backquote(($context["table"] ?? null)), "html", null, true);
            echo " WHERE ";
            echo twig_escape_filter($this->env, $context["row"], "html", null, true);
            echo ";</code></li>
      ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 19
        echo "    </ul>
  </fieldset>

  <fieldset class=\"tblFooters\">
    <div id=\"foreignkeychk\" class=\"floatleft\">
      <input type=\"checkbox\" name=\"fk_checks\" id=\"fk_checks\" value=\"1\"";
        // line 24
        echo ((($context["is_foreign_key_check"] ?? null)) ? (" checked") : (""));
        echo ">
      <label for=\"fk_checks\">";
        // line 25
        echo _gettext("Enable foreign key checks");
        echo "</label>
    </div>
    <div class=\"floatright\">
      <input id=\"buttonYes\" class=\"btn btn-secondary\" type=\"submit\" name=\"mult_btn\" value=\"";
        // line 28
        echo _gettext("Yes");
        echo "\">
      <input id=\"buttonNo\" class=\"btn btn-secondary\" type=\"submit\" name=\"mult_btn\" value=\"";
        // line 29
        echo _gettext("No");
        echo "\">
    </div>
  </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "table/delete/confirm.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  97 => 29,  93 => 28,  87 => 25,  83 => 24,  76 => 19,  65 => 17,  61 => 16,  56 => 13,  54 => 12,  48 => 8,  46 => 6,  45 => 5,  44 => 4,  43 => 3,  42 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/delete/confirm.twig", "/var/www/html/phpMyAdmin/templates/table/delete/confirm.twig");
    }
}
