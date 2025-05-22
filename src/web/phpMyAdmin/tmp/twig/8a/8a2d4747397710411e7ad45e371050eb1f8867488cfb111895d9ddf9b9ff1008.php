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

/* columns_definitions/table_fields_definitions.twig */
class __TwigTemplate_7f9612c726be0f65bf73350b7282c3f30130f9ab3bf7029a9ed9ee2171c187ce extends \Twig\Template
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
        echo "<div class=\"responsivetable\">
<table id=\"table_columns\" class=\"pma-table noclick\">
    <caption class=\"tblHeaders\">
        ";
        // line 4
        echo _gettext("Structure");
        // line 5
        echo "        ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("CREATE_TABLE");
        echo "
    </caption>
    <tr>
        <th>
            ";
        // line 9
        echo _gettext("Name");
        // line 10
        echo "        </th>
        <th>
            ";
        // line 12
        echo _gettext("Type");
        // line 13
        echo "            ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("data-types");
        echo "
        </th>
        <th>
            ";
        // line 16
        echo _gettext("Length/Values");
        // line 17
        echo "            ";
        echo \PhpMyAdmin\Html\Generator::showHint(_gettext("If column type is \"enum\" or \"set\", please enter the values using this format: 'a','b','c'…<br>If you ever need to put a backslash (\"\\\") or a single quote (\"'\") amongst those values, precede it with a backslash (for example '\\\\xyz' or 'a\\'b')."));
        echo "
        </th>
        <th>
            ";
        // line 20
        echo _gettext("Default");
        // line 21
        echo "            ";
        echo \PhpMyAdmin\Html\Generator::showHint(_gettext("For default values, please enter just a single value, without backslash escaping or quotes, using this format: a"));
        echo "
        </th>
        <th>
            ";
        // line 24
        echo _gettext("Collation");
        // line 25
        echo "        </th>
        <th>
            ";
        // line 27
        echo _gettext("Attributes");
        // line 28
        echo "        </th>
        <th>
            ";
        // line 30
        echo _gettext("Null");
        // line 31
        echo "        </th>

        ";
        // line 34
        echo "        ";
        if (((isset($context["change_column"]) || array_key_exists("change_column", $context)) &&  !twig_test_empty(($context["change_column"] ?? null)))) {
            // line 35
            echo "            <th>
                ";
            // line 36
            echo _gettext("Adjust privileges");
            // line 37
            echo "                ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-39");
            echo "
            </th>
        ";
        }
        // line 40
        echo "
        ";
        // line 44
        echo "        ";
        if ( !($context["is_backup"] ?? null)) {
            // line 45
            echo "            <th>
                ";
            // line 46
            echo _gettext("Index");
            // line 47
            echo "            </th>
        ";
        }
        // line 49
        echo "
        <th>
            <abbr title=\"AUTO_INCREMENT\">A_I</abbr>
        </th>
        <th>
            ";
        // line 54
        echo _gettext("Comments");
        // line 55
        echo "        </th>

        ";
        // line 57
        if (($context["is_virtual_columns_supported"] ?? null)) {
            // line 58
            echo "            <th>
                ";
            // line 59
            echo _gettext("Virtuality");
            // line 60
            echo "            </th>
        ";
        }
        // line 62
        echo "
        ";
        // line 63
        if ((isset($context["fields_meta"]) || array_key_exists("fields_meta", $context))) {
            // line 64
            echo "            <th>
                ";
            // line 65
            echo _gettext("Move column");
            // line 66
            echo "            </th>
        ";
        }
        // line 68
        echo "
        ";
        // line 69
        if ((($context["mimework"] ?? null) && ($context["browse_mime"] ?? null))) {
            // line 70
            echo "            <th>
                ";
            // line 71
            echo _gettext("Media type");
            // line 72
            echo "            </th>
            <th>
                <a href=\"";
            // line 74
            echo PhpMyAdmin\Url::getFromRoute("/transformation/overview");
            echo "#transformation\" title=\"";
            // line 75
            echo _gettext("List of available transformations and their options");
            // line 76
            echo "\" target=\"_blank\">
                    ";
            // line 77
            echo _gettext("Browser display transformation");
            // line 78
            echo "                </a>
            </th>
            <th>
                ";
            // line 81
            echo _gettext("Browser display transformation options");
            // line 82
            echo "                ";
            echo \PhpMyAdmin\Html\Generator::showHint(_gettext("Please enter the values for transformation options using this format: 'a', 100, b,'c'…<br>If you ever need to put a backslash (\"\\\") or a single quote (\"'\") amongst those values, precede it with a backslash (for example '\\\\xyz' or 'a\\'b')."));
            echo "
            </th>
            <th>
                <a href=\"";
            // line 85
            echo PhpMyAdmin\Url::getFromRoute("/transformation/overview");
            echo "#input_transformation\"
                   title=\"";
            // line 86
            echo _gettext("List of available transformations and their options");
            echo "\"
                   target=\"_blank\">
                    ";
            // line 88
            echo _gettext("Input transformation");
            // line 89
            echo "                </a>
            </th>
            <th>
                ";
            // line 92
            echo _gettext("Input transformation options");
            // line 93
            echo "                ";
            echo \PhpMyAdmin\Html\Generator::showHint(_gettext("Please enter the values for transformation options using this format: 'a', 100, b,'c'…<br>If you ever need to put a backslash (\"\\\") or a single quote (\"'\") amongst those values, precede it with a backslash (for example '\\\\xyz' or 'a\\'b')."));
            echo "
            </th>
        ";
        }
        // line 96
        echo "    </tr>
    ";
        // line 97
        $context["options"] = ["" => "", "VIRTUAL" => "VIRTUAL"];
        // line 98
        echo "    ";
        if (((($context["server_type"] ?? null) == "MariaDB") && (($context["server_version"] ?? null) <= 100200))) {
            // line 99
            echo "        ";
            $context["options"] = twig_array_merge(($context["options"] ?? null), ["PERSISTENT" => "PERSISTENT"]);
            // line 100
            echo "    ";
        } else {
            // line 101
            echo "        ";
            $context["options"] = twig_array_merge(($context["options"] ?? null), ["STORED" => "STORED"]);
            // line 102
            echo "    ";
        }
        // line 103
        echo "    ";
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["content_cells"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["content_row"]) {
            // line 104
            echo "        <tr>
            ";
            // line 105
            $this->loadTemplate("columns_definitions/column_attributes.twig", "columns_definitions/table_fields_definitions.twig", 105)->display(twig_to_array(twig_array_merge($context["content_row"], ["options" =>             // line 106
($context["options"] ?? null), "change_column" =>             // line 107
($context["change_column"] ?? null), "is_virtual_columns_supported" =>             // line 108
($context["is_virtual_columns_supported"] ?? null), "browse_mime" =>             // line 109
($context["browse_mime"] ?? null), "max_rows" =>             // line 110
($context["max_rows"] ?? null), "char_editing" =>             // line 111
($context["char_editing"] ?? null), "attribute_types" =>             // line 112
($context["attribute_types"] ?? null), "privs_available" =>             // line 113
($context["privs_available"] ?? null), "max_length" =>             // line 114
($context["max_length"] ?? null), "charsets" =>             // line 115
($context["charsets"] ?? null)])));
            // line 117
            echo "        </tr>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['content_row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 119
        echo "</table>
</div>
";
    }

    public function getTemplateName()
    {
        return "columns_definitions/table_fields_definitions.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  281 => 119,  274 => 117,  272 => 115,  271 => 114,  270 => 113,  269 => 112,  268 => 111,  267 => 110,  266 => 109,  265 => 108,  264 => 107,  263 => 106,  262 => 105,  259 => 104,  254 => 103,  251 => 102,  248 => 101,  245 => 100,  242 => 99,  239 => 98,  237 => 97,  234 => 96,  227 => 93,  225 => 92,  220 => 89,  218 => 88,  213 => 86,  209 => 85,  202 => 82,  200 => 81,  195 => 78,  193 => 77,  190 => 76,  188 => 75,  185 => 74,  181 => 72,  179 => 71,  176 => 70,  174 => 69,  171 => 68,  167 => 66,  165 => 65,  162 => 64,  160 => 63,  157 => 62,  153 => 60,  151 => 59,  148 => 58,  146 => 57,  142 => 55,  140 => 54,  133 => 49,  129 => 47,  127 => 46,  124 => 45,  121 => 44,  118 => 40,  111 => 37,  109 => 36,  106 => 35,  103 => 34,  99 => 31,  97 => 30,  93 => 28,  91 => 27,  87 => 25,  85 => 24,  78 => 21,  76 => 20,  69 => 17,  67 => 16,  60 => 13,  58 => 12,  54 => 10,  52 => 9,  44 => 5,  42 => 4,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "columns_definitions/table_fields_definitions.twig", "/var/www/html/phpMyAdmin/templates/columns_definitions/table_fields_definitions.twig");
    }
}
