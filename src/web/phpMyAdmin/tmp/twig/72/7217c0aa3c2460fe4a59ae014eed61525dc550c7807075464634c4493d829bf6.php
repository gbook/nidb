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

/* table/relation/foreign_key_row.twig */
class __TwigTemplate_ab39ddfb836aaffe441850e65e3697a7741edb14a69835512de61322db1f4f88 extends \Twig\Template
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
        echo "<tr>
    ";
        // line 3
        echo "    <td>
        ";
        // line 4
        $context["js_msg"] = "";
        // line 5
        echo "        ";
        $context["this_params"] = null;
        // line 6
        echo "        ";
        if (twig_get_attribute($this->env, $this->source, ($context["one_key"] ?? null), "constraint", [], "array", true, true, false, 6)) {
            // line 7
            echo "            ";
            $context["drop_fk_query"] = (((((("ALTER TABLE " . PhpMyAdmin\Util::backquote(($context["db"] ?? null))) . ".") . PhpMyAdmin\Util::backquote(($context["table"] ?? null))) . " DROP FOREIGN KEY ") . PhpMyAdmin\Util::backquote((($__internal_compile_0 =             // line 9
($context["one_key"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["constraint"] ?? null) : null))) . ";");
            // line 11
            echo "            ";
            $context["this_params"] = ($context["url_params"] ?? null);
            // line 12
            echo "            ";
            $context["this_params"] = ["goto" => PhpMyAdmin\Url::getFromRoute("/table/relation"), "back" => PhpMyAdmin\Url::getFromRoute("/table/relation"), "sql_query" =>             // line 15
($context["drop_fk_query"] ?? null), "message_to_show" => twig_sprintf(_gettext("Foreign key constraint %s has been dropped"), (($__internal_compile_1 =             // line 17
($context["one_key"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["constraint"] ?? null) : null))];
            // line 20
            echo "            ";
            $context["js_msg"] = (((((("ALTER TABLE " . ($context["db"] ?? null)) . ".") . ($context["table"] ?? null)) . " DROP FOREIGN KEY ") . (($__internal_compile_2 = ($context["one_key"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["constraint"] ?? null) : null)) . ";");
            // line 21
            echo "        ";
        }
        // line 22
        echo "        ";
        if (twig_get_attribute($this->env, $this->source, ($context["one_key"] ?? null), "constraint", [], "array", true, true, false, 22)) {
            // line 23
            echo "            <input type=\"hidden\" class=\"drop_foreign_key_msg\" value=\"";
            // line 24
            echo twig_escape_filter($this->env, ($context["js_msg"] ?? null), "html", null, true);
            echo "\">
            ";
            // line 25
            $context["drop_str"] = \PhpMyAdmin\Html\Generator::getIcon("b_drop", _gettext("Drop"));
            // line 26
            echo "            ";
            echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), ($context["this_params"] ?? null), ($context["drop_str"] ?? null), ["class" => "drop_foreign_key_anchor ajax"]);
            echo "
        ";
        }
        // line 28
        echo "    </td>
    <td>
        <span class=\"formelement clearfloat\">
            <input type=\"text\" name=\"constraint_name[";
        // line 31
        echo twig_escape_filter($this->env, ($context["i"] ?? null), "html", null, true);
        echo "]\" value=\"";
        // line 32
        ((twig_get_attribute($this->env, $this->source, ($context["one_key"] ?? null), "constraint", [], "array", true, true, false, 32)) ? (print (twig_escape_filter($this->env, (($__internal_compile_3 = ($context["one_key"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["constraint"] ?? null) : null), "html", null, true))) : (print ("")));
        // line 33
        echo "\" placeholder=\"";
        echo _gettext("Constraint name");
        echo "\" maxlength=\"64\">
        </span>
        <div class=\"floatleft\">
            ";
        // line 39
        echo "            ";
        $context["on_delete"] = ((twig_get_attribute($this->env, $this->source, ($context["one_key"] ?? null), "on_delete", [], "array", true, true, false, 39)) ? ((($__internal_compile_4 =         // line 40
($context["one_key"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["on_delete"] ?? null) : null)) : ("RESTRICT"));
        // line 41
        echo "            ";
        $context["on_update"] = ((twig_get_attribute($this->env, $this->source, ($context["one_key"] ?? null), "on_update", [], "array", true, true, false, 41)) ? ((($__internal_compile_5 =         // line 42
($context["one_key"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["on_update"] ?? null) : null)) : ("RESTRICT"));
        // line 43
        echo "            <span class=\"formelement\">
                ";
        // line 44
        $this->loadTemplate("table/relation/dropdown_generate.twig", "table/relation/foreign_key_row.twig", 44)->display(twig_to_array(["dropdown_question" => "ON DELETE", "select_name" => (("on_delete[" .         // line 46
($context["i"] ?? null)) . "]"), "choices" =>         // line 47
($context["options_array"] ?? null), "selected_value" =>         // line 48
($context["on_delete"] ?? null)]));
        // line 50
        echo "            </span>
            <span class=\"formelement\">
                ";
        // line 52
        $this->loadTemplate("table/relation/dropdown_generate.twig", "table/relation/foreign_key_row.twig", 52)->display(twig_to_array(["dropdown_question" => "ON UPDATE", "select_name" => (("on_update[" .         // line 54
($context["i"] ?? null)) . "]"), "choices" =>         // line 55
($context["options_array"] ?? null), "selected_value" =>         // line 56
($context["on_update"] ?? null)]));
        // line 58
        echo "            </span>
        </div>
    </td>
    <td>
        ";
        // line 62
        if (twig_get_attribute($this->env, $this->source, ($context["one_key"] ?? null), "index_list", [], "array", true, true, false, 62)) {
            // line 63
            echo "            ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((($__internal_compile_6 = ($context["one_key"] ?? null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6["index_list"] ?? null) : null));
            foreach ($context['_seq'] as $context["key"] => $context["column"]) {
                // line 64
                echo "                <span class=\"formelement clearfloat\">
                    ";
                // line 65
                $this->loadTemplate("table/relation/dropdown_generate.twig", "table/relation/foreign_key_row.twig", 65)->display(twig_to_array(["dropdown_question" => "", "select_name" => (("foreign_key_fields_name[" .                 // line 67
($context["i"] ?? null)) . "][]"), "choices" =>                 // line 68
($context["column_array"] ?? null), "selected_value" =>                 // line 69
$context["column"]]));
                // line 71
                echo "                </span>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['key'], $context['column'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 73
            echo "        ";
        } else {
            // line 74
            echo "            <span class=\"formelement clearfloat\">
                ";
            // line 75
            $this->loadTemplate("table/relation/dropdown_generate.twig", "table/relation/foreign_key_row.twig", 75)->display(twig_to_array(["dropdown_question" => "", "select_name" => (("foreign_key_fields_name[" .             // line 77
($context["i"] ?? null)) . "][]"), "choices" =>             // line 78
($context["column_array"] ?? null), "selected_value" => ""]));
            // line 81
            echo "            </span>
        ";
        }
        // line 83
        echo "        <a class=\"formelement clearfloat add_foreign_key_field\" data-index=\"";
        // line 84
        echo twig_escape_filter($this->env, ($context["i"] ?? null), "html", null, true);
        echo "\" href=\"\">
            ";
        // line 85
        echo _gettext("+ Add column");
        // line 86
        echo "        </a>
    </td>
    ";
        // line 88
        $context["tables"] = [];
        // line 89
        echo "    ";
        if (($context["foreign_db"] ?? null)) {
            // line 90
            echo "        ";
            $context["tables"] = call_user_func_array($this->env->getFunction('get_tables')->getCallable(), [($context["foreign_db"] ?? null), ($context["tbl_storage_engine"] ?? null)]);
            // line 91
            echo "    ";
        } else {
            // line 92
            echo "        ";
            $context["tables"] = call_user_func_array($this->env->getFunction('get_tables')->getCallable(), [($context["db"] ?? null), ($context["tbl_storage_engine"] ?? null)]);
            // line 93
            echo "    ";
        }
        // line 94
        echo "    <td>
        <span class=\"formelement clearfloat\">
            ";
        // line 96
        $this->loadTemplate("table/relation/relational_dropdown.twig", "table/relation/foreign_key_row.twig", 96)->display(twig_to_array(["name" => (("destination_foreign_db[" .         // line 97
($context["i"] ?? null)) . "]"), "title" => _gettext("Database"), "values" =>         // line 99
($context["databases"] ?? null), "foreign" =>         // line 100
($context["foreign_db"] ?? null), "db" =>         // line 101
($context["db"] ?? null)]));
        // line 103
        echo "        </span>
    </td>
    <td>
        <span class=\"formelement clearfloat\">
            ";
        // line 107
        $this->loadTemplate("table/relation/relational_dropdown.twig", "table/relation/foreign_key_row.twig", 107)->display(twig_to_array(["name" => (("destination_foreign_table[" .         // line 108
($context["i"] ?? null)) . "]"), "title" => _gettext("Table"), "values" =>         // line 110
($context["tables"] ?? null), "foreign" =>         // line 111
($context["foreign_table"] ?? null)]));
        // line 113
        echo "        </span>
    </td>
    <td>
        ";
        // line 116
        if ((($context["foreign_db"] ?? null) && ($context["foreign_table"] ?? null))) {
            // line 117
            echo "            ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((($__internal_compile_7 = ($context["one_key"] ?? null)) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7["ref_index_list"] ?? null) : null));
            foreach ($context['_seq'] as $context["_key"] => $context["foreign_column"]) {
                // line 118
                echo "                <span class=\"formelement clearfloat\">
                    ";
                // line 119
                $this->loadTemplate("table/relation/relational_dropdown.twig", "table/relation/foreign_key_row.twig", 119)->display(twig_to_array(["name" => (("destination_foreign_column[" .                 // line 120
($context["i"] ?? null)) . "][]"), "title" => _gettext("Column"), "values" =>                 // line 122
($context["unique_columns"] ?? null), "foreign" =>                 // line 123
$context["foreign_column"]]));
                // line 125
                echo "                </span>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['foreign_column'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 127
            echo "        ";
        } else {
            // line 128
            echo "            <span class=\"formelement clearfloat\">
                ";
            // line 129
            $this->loadTemplate("table/relation/relational_dropdown.twig", "table/relation/foreign_key_row.twig", 129)->display(twig_to_array(["name" => (("destination_foreign_column[" .             // line 130
($context["i"] ?? null)) . "][]"), "title" => _gettext("Column"), "values" => [], "foreign" => ""]));
            // line 135
            echo "            </span>
        ";
        }
        // line 137
        echo "    </td>
</tr>
";
    }

    public function getTemplateName()
    {
        return "table/relation/foreign_key_row.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  260 => 137,  256 => 135,  254 => 130,  253 => 129,  250 => 128,  247 => 127,  240 => 125,  238 => 123,  237 => 122,  236 => 120,  235 => 119,  232 => 118,  227 => 117,  225 => 116,  220 => 113,  218 => 111,  217 => 110,  216 => 108,  215 => 107,  209 => 103,  207 => 101,  206 => 100,  205 => 99,  204 => 97,  203 => 96,  199 => 94,  196 => 93,  193 => 92,  190 => 91,  187 => 90,  184 => 89,  182 => 88,  178 => 86,  176 => 85,  172 => 84,  170 => 83,  166 => 81,  164 => 78,  163 => 77,  162 => 75,  159 => 74,  156 => 73,  149 => 71,  147 => 69,  146 => 68,  145 => 67,  144 => 65,  141 => 64,  136 => 63,  134 => 62,  128 => 58,  126 => 56,  125 => 55,  124 => 54,  123 => 52,  119 => 50,  117 => 48,  116 => 47,  115 => 46,  114 => 44,  111 => 43,  109 => 42,  107 => 41,  105 => 40,  103 => 39,  96 => 33,  94 => 32,  91 => 31,  86 => 28,  80 => 26,  78 => 25,  74 => 24,  72 => 23,  69 => 22,  66 => 21,  63 => 20,  61 => 17,  60 => 15,  58 => 12,  55 => 11,  53 => 9,  51 => 7,  48 => 6,  45 => 5,  43 => 4,  40 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "table/relation/foreign_key_row.twig", "/var/www/html/phpMyAdmin/templates/table/relation/foreign_key_row.twig");
    }
}
