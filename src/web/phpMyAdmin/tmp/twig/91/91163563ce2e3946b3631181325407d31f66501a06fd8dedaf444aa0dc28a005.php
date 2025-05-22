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

/* columns_definitions/column_name.twig */
class __TwigTemplate_27b1a8955842234c84ff025735338d082d677ffa2b66ce1890fdc7f983e5776f extends \Twig\Template
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
        $context["title"] = "";
        // line 2
        if (twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "column_status", [], "array", true, true, false, 2)) {
            // line 3
            echo "    ";
            if ((($__internal_compile_0 = (($__internal_compile_1 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["column_status"] ?? null) : null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["isReferenced"] ?? null) : null)) {
                // line 4
                echo "        ";
                $context["title"] = (($context["title"] ?? null) . twig_sprintf(_gettext("Referenced by %s."), twig_join_filter((($__internal_compile_2 = (($__internal_compile_3 =                 // line 5
($context["column_meta"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["column_status"] ?? null) : null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["references"] ?? null) : null), ",")));
                // line 7
                echo "    ";
            }
            // line 8
            echo "    ";
            if ((($__internal_compile_4 = (($__internal_compile_5 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["column_status"] ?? null) : null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["isForeignKey"] ?? null) : null)) {
                // line 9
                echo "        ";
                if ( !twig_test_empty(($context["title"] ?? null))) {
                    // line 10
                    echo "            ";
                    $context["title"] = (($context["title"] ?? null) . "
");
                    // line 11
                    echo "        ";
                }
                // line 12
                echo "        ";
                $context["title"] = (($context["title"] ?? null) . _gettext("Is a foreign key."));
                // line 13
                echo "    ";
            }
        }
        // line 15
        if (twig_test_empty(($context["title"] ?? null))) {
            // line 16
            echo "    ";
            $context["title"] = _gettext("Column");
        }
        // line 18
        echo "
<input id=\"field_";
        // line 19
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\"
    ";
        // line 20
        if ((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "column_status", [], "array", true, true, false, 20) &&  !(($__internal_compile_6 = (($__internal_compile_7 =         // line 21
($context["column_meta"] ?? null)) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7["column_status"] ?? null) : null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6["isEditable"] ?? null) : null))) {
            // line 22
            echo "        disabled=\"disabled\"
    ";
        }
        // line 24
        echo "    type=\"text\"
    name=\"field_name[";
        // line 25
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\"
    maxlength=\"64\"
    class=\"textfield\"
    title=\"";
        // line 28
        echo twig_escape_filter($this->env, ($context["title"] ?? null), "html", null, true);
        echo "\"
    size=\"10\"
    value=\"";
        // line 30
        ((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Field", [], "array", true, true, false, 30)) ? (print (twig_escape_filter($this->env, (($__internal_compile_8 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_8) || $__internal_compile_8 instanceof ArrayAccess ? ($__internal_compile_8["Field"] ?? null) : null), "html", null, true))) : (print ("")));
        echo "\">

";
        // line 32
        if (((($__internal_compile_9 = ($context["cfg_relation"] ?? null)) && is_array($__internal_compile_9) || $__internal_compile_9 instanceof ArrayAccess ? ($__internal_compile_9["centralcolumnswork"] ?? null) : null) &&  !(twig_get_attribute($this->env, $this->source,         // line 33
($context["column_meta"] ?? null), "column_status", [], "array", true, true, false, 33) &&  !(($__internal_compile_10 = (($__internal_compile_11 =         // line 34
($context["column_meta"] ?? null)) && is_array($__internal_compile_11) || $__internal_compile_11 instanceof ArrayAccess ? ($__internal_compile_11["column_status"] ?? null) : null)) && is_array($__internal_compile_10) || $__internal_compile_10 instanceof ArrayAccess ? ($__internal_compile_10["isEditable"] ?? null) : null)))) {
            // line 35
            echo "    <p class=\"column_name\" id=\"central_columns_";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\">
        <a data-maxrows=\"";
            // line 36
            echo twig_escape_filter($this->env, ($context["max_rows"] ?? null), "html", null, true);
            echo "\"
            href=\"#\"
            class=\"central_columns_dialog\">
            ";
            // line 39
            echo _gettext("Pick from Central Columns");
            // line 40
            echo "        </a>
    </p>
";
        }
    }

    public function getTemplateName()
    {
        return "columns_definitions/column_name.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  131 => 40,  129 => 39,  123 => 36,  116 => 35,  114 => 34,  113 => 33,  112 => 32,  107 => 30,  102 => 28,  96 => 25,  93 => 24,  89 => 22,  87 => 21,  86 => 20,  80 => 19,  77 => 18,  73 => 16,  71 => 15,  67 => 13,  64 => 12,  61 => 11,  57 => 10,  54 => 9,  51 => 8,  48 => 7,  46 => 5,  44 => 4,  41 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "columns_definitions/column_name.twig", "/var/www/html/phpMyAdmin/templates/columns_definitions/column_name.twig");
    }
}
