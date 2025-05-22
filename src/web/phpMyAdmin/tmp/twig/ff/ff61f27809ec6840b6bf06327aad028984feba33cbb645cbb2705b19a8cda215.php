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

/* database/triggers/footer.twig */
class __TwigTemplate_ebf3d1c88f2bf44cbcdba48e3761bcb2fc4bc1048b8d9c058ca4f0e59cca9bbf extends \Twig\Template
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
        echo "<fieldset class=\"left\">
  <legend>";
        // line 2
        echo _pgettext(        "Create new trigger", "New");
        echo "</legend>
  <div class='wrap'>
    ";
        // line 4
        if (($context["has_privilege"] ?? null)) {
            // line 5
            echo "      <a class=\"ajax add_anchor\" href=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/triggers", ["db" => ($context["db"] ?? null), "table" => ($context["table"] ?? null), "add_item" => true]);
            echo "\">
        ";
            // line 6
            echo \PhpMyAdmin\Html\Generator::getIcon("b_trigger_add");
            echo "
        ";
            // line 7
            echo _gettext("Add trigger");
            // line 8
            echo "      </a>
    ";
        } else {
            // line 10
            echo "      ";
            echo \PhpMyAdmin\Html\Generator::getIcon("bd_trigger_add");
            echo "
      ";
            // line 11
            echo _gettext("Add trigger");
            // line 12
            echo "    ";
        }
        // line 13
        echo "    ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("CREATE_TRIGGER");
        echo "
  </div>
</fieldset>
";
    }

    public function getTemplateName()
    {
        return "database/triggers/footer.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  72 => 13,  69 => 12,  67 => 11,  62 => 10,  58 => 8,  56 => 7,  52 => 6,  47 => 5,  45 => 4,  40 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/triggers/footer.twig", "/var/www/html/phpMyAdmin/templates/database/triggers/footer.twig");
    }
}
