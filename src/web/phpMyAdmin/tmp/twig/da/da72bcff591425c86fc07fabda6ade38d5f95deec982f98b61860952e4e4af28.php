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

/* database/structure/favorite_anchor.twig */
class __TwigTemplate_a4688bcc5aad77ece8e95fe592230aa076d9d43561f7e12e221584ef4e7b74ae extends \Twig\Template
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
        echo "<a id=\"";
        echo twig_escape_filter($this->env, ($context["table_name_hash"] ?? null), "html", null, true);
        echo "_favorite_anchor\"
    class=\"ajax favorite_table_anchor\"
    href=\"";
        // line 3
        echo PhpMyAdmin\Url::getFromRoute("/database/structure/favorite-table", ($context["fav_params"] ?? null));
        echo "\"
    title=\"";
        // line 4
        echo twig_escape_filter($this->env, ((($context["already_favorite"] ?? null)) ? (_gettext("Remove from Favorites")) : (_gettext("Add to Favorites"))), "html", null, true);
        echo "\"
    data-favtargets=\"";
        // line 5
        echo twig_escape_filter($this->env, ($context["db_table_name_hash"] ?? null), "html", null, true);
        echo "\">
    ";
        // line 6
        echo ((($context["already_favorite"] ?? null)) ? (\PhpMyAdmin\Html\Generator::getIcon("b_favorite")) : (\PhpMyAdmin\Html\Generator::getIcon("b_no_favorite")));
        echo "
</a>
";
    }

    public function getTemplateName()
    {
        return "database/structure/favorite_anchor.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  55 => 6,  51 => 5,  47 => 4,  43 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/structure/favorite_anchor.twig", "/var/www/html/phpMyAdmin/templates/database/structure/favorite_anchor.twig");
    }
}
