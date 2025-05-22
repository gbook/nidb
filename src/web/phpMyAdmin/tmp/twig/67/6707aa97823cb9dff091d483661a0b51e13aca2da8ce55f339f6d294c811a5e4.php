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

/* columns_definitions/column_attributes.twig */
class __TwigTemplate_a1ea4c641fd103479fadaeaaff280510664f7fa22de3829427c8ddeb424c0134 extends \Twig\Template
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
        // line 2
        $context["ci"] = 0;
        // line 3
        echo "
";
        // line 6
        $context["ci_offset"] =  -1;
        // line 7
        echo "
<td class=\"text-center\">
    ";
        // line 10
        echo "    ";
        $this->loadTemplate("columns_definitions/column_name.twig", "columns_definitions/column_attributes.twig", 10)->display(twig_to_array(["column_number" =>         // line 11
($context["column_number"] ?? null), "ci" =>         // line 12
($context["ci"] ?? null), "ci_offset" =>         // line 13
($context["ci_offset"] ?? null), "column_meta" =>         // line 14
($context["column_meta"] ?? null), "cfg_relation" =>         // line 15
($context["cfg_relation"] ?? null), "max_rows" =>         // line 16
($context["max_rows"] ?? null)]));
        // line 18
        echo "    ";
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 19
        echo "</td>
<td class=\"text-center\">
  <select class=\"column_type\" name=\"field_type[";
        // line 21
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\" id=\"field_";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\"";
        // line 22
        echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "column_status", [], "array", true, true, false, 22) &&  !(($__internal_compile_0 = (($__internal_compile_1 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1["column_status"] ?? null) : null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0["isEditable"] ?? null) : null))) ? (" disabled") : (""));
        echo ">
    ";
        // line 23
        echo PhpMyAdmin\Util::getSupportedDatatypes(true, ($context["type_upper"] ?? null));
        echo "
  </select>
  ";
        // line 25
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 26
        echo "</td>
<td class=\"text-center\">
  <input id=\"field_";
        // line 28
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\" type=\"text\" name=\"field_length[";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\" size=\"";
        // line 29
        echo twig_escape_filter($this->env, ($context["length_values_input_size"] ?? null), "html", null, true);
        echo "\" value=\"";
        echo twig_escape_filter($this->env, ($context["length"] ?? null), "html", null, true);
        echo "\" class=\"textfield\">
  <p class=\"enum_notice\" id=\"enum_notice_";
        // line 30
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\">
    <a href=\"#\" class=\"open_enum_editor\">";
        // line 31
        echo _gettext("Edit ENUM/SET values");
        echo "</a>
  </p>
  ";
        // line 33
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 34
        echo "</td>
<td class=\"text-center\">
  <select name=\"field_default_type[";
        // line 36
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\" id=\"field_";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\" class=\"default_type\">
    <option value=\"NONE\"";
        // line 37
        echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "DefaultType", [], "array", true, true, false, 37) && ((($__internal_compile_2 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2["DefaultType"] ?? null) : null) == "NONE"))) ? (" selected") : (""));
        echo ">
      ";
        // line 38
        echo _pgettext(        "for default", "None");
        // line 39
        echo "    </option>
    <option value=\"USER_DEFINED\"";
        // line 40
        echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "DefaultType", [], "array", true, true, false, 40) && ((($__internal_compile_3 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3["DefaultType"] ?? null) : null) == "USER_DEFINED"))) ? (" selected") : (""));
        echo ">
      ";
        // line 41
        echo _gettext("As defined:");
        // line 42
        echo "    </option>
    <option value=\"NULL\"";
        // line 43
        echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "DefaultType", [], "array", true, true, false, 43) && ((($__internal_compile_4 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4["DefaultType"] ?? null) : null) == "NULL"))) ? (" selected") : (""));
        echo ">
      NULL
    </option>
    <option value=\"CURRENT_TIMESTAMP\"";
        // line 46
        echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "DefaultType", [], "array", true, true, false, 46) && ((($__internal_compile_5 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_5) || $__internal_compile_5 instanceof ArrayAccess ? ($__internal_compile_5["DefaultType"] ?? null) : null) == "CURRENT_TIMESTAMP"))) ? (" selected") : (""));
        echo ">
      CURRENT_TIMESTAMP
    </option>
  </select>
  ";
        // line 50
        if ((($context["char_editing"] ?? null) == "textarea")) {
            // line 51
            echo "    <textarea name=\"field_default_value[";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\" cols=\"15\" class=\"textfield default_value\">";
            echo twig_escape_filter($this->env, ($context["default_value"] ?? null), "html", null, true);
            echo "</textarea>
  ";
        } else {
            // line 53
            echo "    <input type=\"text\" name=\"field_default_value[";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\" size=\"12\" value=\"";
            echo twig_escape_filter($this->env, ($context["default_value"] ?? null), "html", null, true);
            echo "\" class=\"textfield default_value\">
  ";
        }
        // line 55
        echo "  ";
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 56
        echo "</td>
<td class=\"text-center\">
  ";
        // line 59
        echo "  <select lang=\"en\" dir=\"ltr\" name=\"field_collation[";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\" id=\"field_";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\">
    <option value=\"\"></option>
    ";
        // line 61
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["charsets"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["charset"]) {
            // line 62
            echo "      <optgroup label=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "name", [], "any", false, false, false, 62), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "description", [], "any", false, false, false, 62), "html", null, true);
            echo "\">
        ";
            // line 63
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["charset"], "collations", [], "any", false, false, false, 63));
            foreach ($context['_seq'] as $context["_key"] => $context["collation"]) {
                // line 64
                echo "          <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "name", [], "any", false, false, false, 64), "html", null, true);
                echo "\" title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "description", [], "any", false, false, false, 64), "html", null, true);
                echo "\"";
                // line 65
                echo (((twig_get_attribute($this->env, $this->source, $context["collation"], "name", [], "any", false, false, false, 65) == (($__internal_compile_6 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_6) || $__internal_compile_6 instanceof ArrayAccess ? ($__internal_compile_6["Collation"] ?? null) : null))) ? (" selected") : (""));
                echo ">";
                // line 66
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "name", [], "any", false, false, false, 66), "html", null, true);
                // line 67
                echo "</option>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['collation'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 69
            echo "      </optgroup>
    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['charset'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 71
        echo "  </select>
  ";
        // line 72
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 73
        echo "</td>
<td class=\"text-center\">
    ";
        // line 76
        echo "    ";
        $this->loadTemplate("columns_definitions/column_attribute.twig", "columns_definitions/column_attributes.twig", 76)->display(twig_to_array(["column_number" =>         // line 77
($context["column_number"] ?? null), "ci" =>         // line 78
($context["ci"] ?? null), "ci_offset" =>         // line 79
($context["ci_offset"] ?? null), "column_meta" =>         // line 80
($context["column_meta"] ?? null), "extracted_columnspec" =>         // line 81
($context["extracted_columnspec"] ?? null), "submit_attribute" =>         // line 82
($context["submit_attribute"] ?? null), "attribute_types" =>         // line 83
($context["attribute_types"] ?? null)]));
        // line 85
        echo "    ";
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 86
        echo "</td>
<td class=\"text-center\">
    <input name=\"field_null[";
        // line 88
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\" id=\"field_";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\" type=\"checkbox\" value=\"YES\" class=\"allow_null\"";
        // line 89
        echo (((( !twig_test_empty((($__internal_compile_7 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_7) || $__internal_compile_7 instanceof ArrayAccess ? ($__internal_compile_7["Null"] ?? null) : null)) && ((($__internal_compile_8 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_8) || $__internal_compile_8 instanceof ArrayAccess ? ($__internal_compile_8["Null"] ?? null) : null) != "NO")) && ((($__internal_compile_9 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_9) || $__internal_compile_9 instanceof ArrayAccess ? ($__internal_compile_9["Null"] ?? null) : null) != "NOT NULL"))) ? (" checked") : (""));
        echo ">
    ";
        // line 90
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 91
        echo "</td>
";
        // line 92
        if (((isset($context["change_column"]) || array_key_exists("change_column", $context)) &&  !twig_test_empty(($context["change_column"] ?? null)))) {
            // line 93
            echo "    ";
            // line 94
            echo "    <td class=\"text-center\">
      <input name=\"field_adjust_privileges[";
            // line 95
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\" id=\"field_";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" type=\"checkbox\" value=\"NULL\" class=\"allow_null\"";
            // line 96
            if (($context["privs_available"] ?? null)) {
                echo " checked>";
            } else {
                // line 97
                echo " title=\"";
                echo _gettext("You don't have sufficient privileges to perform this operation; Please refer to the documentation for more details");
                echo "\" disabled>";
            }
            // line 99
            echo "      ";
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 100
            echo "    </td>
";
        }
        // line 102
        if ( !($context["is_backup"] ?? null)) {
            // line 103
            echo "    ";
            // line 104
            echo "    <td class=\"text-center\">
      <select name=\"field_key[";
            // line 105
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\" id=\"field_";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" data-index=\"\">
        <option value=\"none_";
            // line 106
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "\">---</option>
        <option value=\"primary_";
            // line 107
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "\" title=\"";
            echo _gettext("Primary");
            echo "\"";
            // line 108
            echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Key", [], "array", true, true, false, 108) && ((($__internal_compile_10 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_10) || $__internal_compile_10 instanceof ArrayAccess ? ($__internal_compile_10["Key"] ?? null) : null) == "PRI"))) ? (" selected") : (""));
            echo ">
          PRIMARY
        </option>
        <option value=\"unique_";
            // line 111
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "\" title=\"";
            echo _gettext("Unique");
            echo "\"";
            // line 112
            echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Key", [], "array", true, true, false, 112) && ((($__internal_compile_11 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_11) || $__internal_compile_11 instanceof ArrayAccess ? ($__internal_compile_11["Key"] ?? null) : null) == "UNI"))) ? (" selected") : (""));
            echo ">
          UNIQUE
        </option>
        <option value=\"index_";
            // line 115
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "\" title=\"";
            echo _gettext("Index");
            echo "\"";
            // line 116
            echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Key", [], "array", true, true, false, 116) && ((($__internal_compile_12 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_12) || $__internal_compile_12 instanceof ArrayAccess ? ($__internal_compile_12["Key"] ?? null) : null) == "MUL"))) ? (" selected") : (""));
            echo ">
          INDEX
        </option>
        <option value=\"fulltext_";
            // line 119
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "\" title=\"";
            echo _gettext("Fulltext");
            echo "\"";
            // line 120
            echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Key", [], "array", true, true, false, 120) && ((($__internal_compile_13 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_13) || $__internal_compile_13 instanceof ArrayAccess ? ($__internal_compile_13["Key"] ?? null) : null) == "FULLTEXT"))) ? (" selected") : (""));
            echo ">
          FULLTEXT
        </option>
        <option value=\"spatial_";
            // line 123
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "\" title=\"";
            echo _gettext("Spatial");
            echo "\"";
            // line 124
            echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Key", [], "array", true, true, false, 124) && ((($__internal_compile_14 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_14) || $__internal_compile_14 instanceof ArrayAccess ? ($__internal_compile_14["Key"] ?? null) : null) == "SPATIAL"))) ? (" selected") : (""));
            echo ">
          SPATIAL
        </option>
      </select>
      ";
            // line 128
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 129
            echo "    </td>
";
        }
        // line 131
        echo "<td class=\"text-center\">
  <input name=\"field_extra[";
        // line 132
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\" id=\"field_";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\" type=\"checkbox\" value=\"AUTO_INCREMENT\"";
        // line 133
        echo (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Extra", [], "array", true, true, false, 133) && (twig_lower_filter($this->env, (($__internal_compile_15 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_15) || $__internal_compile_15 instanceof ArrayAccess ? ($__internal_compile_15["Extra"] ?? null) : null)) == "auto_increment"))) ? (" checked") : (""));
        echo ">
  ";
        // line 134
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 135
        echo "</td>
<td class=\"text-center\">
  <textarea id=\"field_";
        // line 137
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "_";
        echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
        echo "\" rows=\"1\" name=\"field_comments[";
        echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
        echo "]\" maxlength=\"";
        echo twig_escape_filter($this->env, ($context["max_length"] ?? null), "html", null, true);
        echo "\">";
        // line 138
        ((((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Field", [], "array", true, true, false, 138) && twig_test_iterable(($context["comments_map"] ?? null))) && twig_get_attribute($this->env, $this->source, ($context["comments_map"] ?? null), (($__internal_compile_16 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_16) || $__internal_compile_16 instanceof ArrayAccess ? ($__internal_compile_16["Field"] ?? null) : null), [], "array", true, true, false, 138))) ? (print (twig_escape_filter($this->env, (($__internal_compile_17 = ($context["comments_map"] ?? null)) && is_array($__internal_compile_17) || $__internal_compile_17 instanceof ArrayAccess ? ($__internal_compile_17[(($__internal_compile_18 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_18) || $__internal_compile_18 instanceof ArrayAccess ? ($__internal_compile_18["Field"] ?? null) : null)] ?? null) : null), "html", null, true))) : (print ("")));
        // line 139
        echo "</textarea>
  ";
        // line 140
        $context["ci"] = (($context["ci"] ?? null) + 1);
        // line 141
        echo "</td>
 ";
        // line 143
        if (($context["is_virtual_columns_supported"] ?? null)) {
            // line 144
            echo "    <td class=\"text-center\">
      <select name=\"field_virtuality[";
            // line 145
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\" id=\"field_";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" class=\"virtuality\">
        ";
            // line 146
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["options"] ?? null));
            foreach ($context['_seq'] as $context["key"] => $context["value"]) {
                // line 147
                echo "          <option value=\"";
                echo twig_escape_filter($this->env, $context["key"], "html", null, true);
                echo "\"";
                echo ((((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Extra", [], "array", true, true, false, 147) && ($context["key"] != "")) && (twig_slice($this->env, (($__internal_compile_19 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_19) || $__internal_compile_19 instanceof ArrayAccess ? ($__internal_compile_19["Extra"] ?? null) : null), 0, twig_length_filter($this->env, $context["key"])) === $context["key"]))) ? (" selected") : (""));
                echo ">
            ";
                // line 148
                echo twig_escape_filter($this->env, $context["value"], "html", null, true);
                echo "
          </option>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['key'], $context['value'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 151
            echo "      </select>

      ";
            // line 153
            if ((($context["char_editing"] ?? null) == "textarea")) {
                // line 154
                echo "        <textarea name=\"field_expression[";
                echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
                echo "]\" cols=\"15\" class=\"textfield expression\">";
                ((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Expression", [], "array", true, true, false, 154)) ? (print (twig_escape_filter($this->env, (($__internal_compile_20 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_20) || $__internal_compile_20 instanceof ArrayAccess ? ($__internal_compile_20["Expression"] ?? null) : null), "html", null, true))) : (print ("")));
                echo "</textarea>
      ";
            } else {
                // line 156
                echo "        <input type=\"text\" name=\"field_expression[";
                echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
                echo "]\" size=\"12\" value=\"";
                ((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Expression", [], "array", true, true, false, 156)) ? (print (twig_escape_filter($this->env, (($__internal_compile_21 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_21) || $__internal_compile_21 instanceof ArrayAccess ? ($__internal_compile_21["Expression"] ?? null) : null), "html", null, true))) : (print ("")));
                echo "\" placeholder=\"";
                echo _gettext("Expression");
                echo "\" class=\"textfield expression\">
      ";
            }
            // line 158
            echo "      ";
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 159
            echo "    </td>
";
        }
        // line 162
        if ((isset($context["fields_meta"]) || array_key_exists("fields_meta", $context))) {
            // line 163
            echo "    ";
            $context["current_index"] = 0;
            // line 164
            echo "    ";
            $context["cols"] = (twig_length_filter($this->env, ($context["move_columns"] ?? null)) - 1);
            // line 165
            echo "    ";
            $context["break"] = false;
            // line 166
            echo "    ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(range(0, ($context["cols"] ?? null)));
            foreach ($context['_seq'] as $context["_key"] => $context["mi"]) {
                // line 167
                echo "      ";
                if (((twig_get_attribute($this->env, $this->source, (($__internal_compile_22 = ($context["move_columns"] ?? null)) && is_array($__internal_compile_22) || $__internal_compile_22 instanceof ArrayAccess ? ($__internal_compile_22[$context["mi"]] ?? null) : null), "name", [], "any", false, false, false, 167) == (($__internal_compile_23 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_23) || $__internal_compile_23 instanceof ArrayAccess ? ($__internal_compile_23["Field"] ?? null) : null)) &&  !($context["break"] ?? null))) {
                    // line 168
                    echo "        ";
                    $context["current_index"] = $context["mi"];
                    // line 169
                    echo "        ";
                    $context["break"] = true;
                    // line 170
                    echo "      ";
                }
                // line 171
                echo "    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['mi'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 172
            echo "
    <td class=\"text-center\">
      <select id=\"field_";
            // line 174
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" name=\"field_move_to[";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\" size=\"1\" width=\"5em\">
        <option value=\"\" selected=\"selected\">&nbsp;</option>
        <option value=\"-first\"";
            // line 176
            echo (((($context["current_index"] ?? null) == 0)) ? (" disabled=\"disabled\"") : (""));
            echo ">
          ";
            // line 177
            echo _gettext("first");
            // line 178
            echo "        </option>
        ";
            // line 179
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(range(0, (twig_length_filter($this->env, ($context["move_columns"] ?? null)) - 1)));
            foreach ($context['_seq'] as $context["_key"] => $context["mi"]) {
                // line 180
                echo "          <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (($__internal_compile_24 = ($context["move_columns"] ?? null)) && is_array($__internal_compile_24) || $__internal_compile_24 instanceof ArrayAccess ? ($__internal_compile_24[$context["mi"]] ?? null) : null), "name", [], "any", false, false, false, 180), "html", null, true);
                echo "\"";
                // line 181
                echo ((((($context["current_index"] ?? null) == $context["mi"]) || (($context["current_index"] ?? null) == ($context["mi"] + 1)))) ? (" disabled") : (""));
                echo ">
            ";
                // line 182
                echo twig_escape_filter($this->env, twig_sprintf(_gettext("after %s"), PhpMyAdmin\Util::backquote(twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, (($__internal_compile_25 = ($context["move_columns"] ?? null)) && is_array($__internal_compile_25) || $__internal_compile_25 instanceof ArrayAccess ? ($__internal_compile_25[$context["mi"]] ?? null) : null), "name", [], "any", false, false, false, 182)))), "html", null, true);
                echo "
          </option>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['mi'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 185
            echo "      </select>
      ";
            // line 186
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 187
            echo "    </td>
";
        }
        // line 189
        echo "
";
        // line 190
        if ((((($__internal_compile_26 = ($context["cfg_relation"] ?? null)) && is_array($__internal_compile_26) || $__internal_compile_26 instanceof ArrayAccess ? ($__internal_compile_26["mimework"] ?? null) : null) && ($context["browse_mime"] ?? null)) && (($__internal_compile_27 = ($context["cfg_relation"] ?? null)) && is_array($__internal_compile_27) || $__internal_compile_27 instanceof ArrayAccess ? ($__internal_compile_27["commwork"] ?? null) : null))) {
            // line 191
            echo "    <td class=\"text-center\">
      <select id=\"field_";
            // line 192
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" size=\"1\" name=\"field_mimetype[";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\">
        <option value=\"\">&nbsp;</option>
        ";
            // line 194
            if ((twig_get_attribute($this->env, $this->source, ($context["available_mime"] ?? null), "mimetype", [], "array", true, true, false, 194) && twig_test_iterable((($__internal_compile_28 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_28) || $__internal_compile_28 instanceof ArrayAccess ? ($__internal_compile_28["mimetype"] ?? null) : null)))) {
                // line 195
                echo "          ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable((($__internal_compile_29 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_29) || $__internal_compile_29 instanceof ArrayAccess ? ($__internal_compile_29["mimetype"] ?? null) : null));
                foreach ($context['_seq'] as $context["_key"] => $context["media_type"]) {
                    // line 196
                    echo "            <option value=\"";
                    echo twig_escape_filter($this->env, twig_replace_filter($context["media_type"], ["/" => "_"]), "html", null, true);
                    echo "\"";
                    // line 197
                    echo ((((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Field", [], "array", true, true, false, 197) && twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["mime_map"] ?? null), (($__internal_compile_30 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_30) || $__internal_compile_30 instanceof ArrayAccess ? ($__internal_compile_30["Field"] ?? null) : null), [], "array", false, true, false, 197), "mimetype", [], "array", true, true, false, 197)) && ((($__internal_compile_31 = (($__internal_compile_32 =                     // line 198
($context["mime_map"] ?? null)) && is_array($__internal_compile_32) || $__internal_compile_32 instanceof ArrayAccess ? ($__internal_compile_32[(($__internal_compile_33 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_33) || $__internal_compile_33 instanceof ArrayAccess ? ($__internal_compile_33["Field"] ?? null) : null)] ?? null) : null)) && is_array($__internal_compile_31) || $__internal_compile_31 instanceof ArrayAccess ? ($__internal_compile_31["mimetype"] ?? null) : null) == twig_replace_filter($context["media_type"], ["/" => "_"])))) ? (" selected") : (""));
                    echo ">
              ";
                    // line 199
                    echo twig_escape_filter($this->env, twig_lower_filter($this->env, $context["media_type"]), "html", null, true);
                    echo "
            </option>
          ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['media_type'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 202
                echo "        ";
            }
            // line 203
            echo "      </select>
      ";
            // line 204
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 205
            echo "    </td>
    <td class=\"text-center\">
      <select id=\"field_";
            // line 207
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" size=\"1\" name=\"field_transformation[";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\">
        <option value=\"\" title=\"";
            // line 208
            echo _gettext("None");
            echo "\"></option>
        ";
            // line 209
            if ((twig_get_attribute($this->env, $this->source, ($context["available_mime"] ?? null), "transformation", [], "array", true, true, false, 209) && twig_test_iterable((($__internal_compile_34 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_34) || $__internal_compile_34 instanceof ArrayAccess ? ($__internal_compile_34["transformation"] ?? null) : null)))) {
                // line 210
                echo "          ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable((($__internal_compile_35 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_35) || $__internal_compile_35 instanceof ArrayAccess ? ($__internal_compile_35["transformation"] ?? null) : null));
                foreach ($context['_seq'] as $context["mimekey"] => $context["transform"]) {
                    // line 211
                    echo "            ";
                    $context["parts"] = twig_split_filter($this->env, $context["transform"], ":");
                    // line 212
                    echo "            <option value=\"";
                    echo twig_escape_filter($this->env, (($__internal_compile_36 = (($__internal_compile_37 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_37) || $__internal_compile_37 instanceof ArrayAccess ? ($__internal_compile_37["transformation_file"] ?? null) : null)) && is_array($__internal_compile_36) || $__internal_compile_36 instanceof ArrayAccess ? ($__internal_compile_36[$context["mimekey"]] ?? null) : null), "html", null, true);
                    echo "\" title=\"";
                    echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('get_description')->getCallable(), [(($__internal_compile_38 = (($__internal_compile_39 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_39) || $__internal_compile_39 instanceof ArrayAccess ? ($__internal_compile_39["transformation_file"] ?? null) : null)) && is_array($__internal_compile_38) || $__internal_compile_38 instanceof ArrayAccess ? ($__internal_compile_38[$context["mimekey"]] ?? null) : null)]), "html", null, true);
                    echo "\"";
                    // line 213
                    echo ((((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Field", [], "array", true, true, false, 213) && twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["mime_map"] ?? null), (($__internal_compile_40 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_40) || $__internal_compile_40 instanceof ArrayAccess ? ($__internal_compile_40["Field"] ?? null) : null), [], "array", false, true, false, 213), "transformation", [], "array", true, true, false, 213)) && preg_match((("@" . (($__internal_compile_41 = (($__internal_compile_42 =                     // line 214
($context["available_mime"] ?? null)) && is_array($__internal_compile_42) || $__internal_compile_42 instanceof ArrayAccess ? ($__internal_compile_42["transformation_file_quoted"] ?? null) : null)) && is_array($__internal_compile_41) || $__internal_compile_41 instanceof ArrayAccess ? ($__internal_compile_41[$context["mimekey"]] ?? null) : null)) . "3?@i"), (($__internal_compile_43 = (($__internal_compile_44 = ($context["mime_map"] ?? null)) && is_array($__internal_compile_44) || $__internal_compile_44 instanceof ArrayAccess ? ($__internal_compile_44[(($__internal_compile_45 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_45) || $__internal_compile_45 instanceof ArrayAccess ? ($__internal_compile_45["Field"] ?? null) : null)] ?? null) : null)) && is_array($__internal_compile_43) || $__internal_compile_43 instanceof ArrayAccess ? ($__internal_compile_43["transformation"] ?? null) : null)))) ? (" selected") : (""));
                    echo ">
              ";
                    // line 215
                    echo twig_escape_filter($this->env, (((((call_user_func_array($this->env->getFunction('get_name')->getCallable(), [(($__internal_compile_46 = (($__internal_compile_47 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_47) || $__internal_compile_47 instanceof ArrayAccess ? ($__internal_compile_47["transformation_file"] ?? null) : null)) && is_array($__internal_compile_46) || $__internal_compile_46 instanceof ArrayAccess ? ($__internal_compile_46[$context["mimekey"]] ?? null) : null)]) . " (") . twig_lower_filter($this->env, (($__internal_compile_48 = ($context["parts"] ?? null)) && is_array($__internal_compile_48) || $__internal_compile_48 instanceof ArrayAccess ? ($__internal_compile_48[0] ?? null) : null))) . ":") . (($__internal_compile_49 = ($context["parts"] ?? null)) && is_array($__internal_compile_49) || $__internal_compile_49 instanceof ArrayAccess ? ($__internal_compile_49[1] ?? null) : null)) . ")"), "html", null, true);
                    echo "
            </option>
          ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['mimekey'], $context['transform'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 218
                echo "        ";
            }
            // line 219
            echo "      </select>
      ";
            // line 220
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 221
            echo "    </td>
    <td class=\"text-center\">
      <input id=\"field_";
            // line 223
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" type=\"text\" name=\"field_transformation_options[";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\" size=\"16\" class=\"textfield\" value=\"";
            // line 224
            (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Field", [], "array", true, true, false, 224) && twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["mime_map"] ?? null), (($__internal_compile_50 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_50) || $__internal_compile_50 instanceof ArrayAccess ? ($__internal_compile_50["Field"] ?? null) : null), [], "array", false, true, false, 224), "transformation_options", [], "array", true, true, false, 224))) ? (print (twig_escape_filter($this->env, (($__internal_compile_51 = (($__internal_compile_52 = ($context["mime_map"] ?? null)) && is_array($__internal_compile_52) || $__internal_compile_52 instanceof ArrayAccess ? ($__internal_compile_52[(($__internal_compile_53 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_53) || $__internal_compile_53 instanceof ArrayAccess ? ($__internal_compile_53["Field"] ?? null) : null)] ?? null) : null)) && is_array($__internal_compile_51) || $__internal_compile_51 instanceof ArrayAccess ? ($__internal_compile_51["transformation_options"] ?? null) : null), "html", null, true))) : (print ("")));
            echo "\">
      ";
            // line 225
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 226
            echo "    </td>
    <td class=\"text-center\">
      <select id=\"field_";
            // line 228
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" size=\"1\" name=\"field_input_transformation[";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\">
        <option value=\"\" title=\"";
            // line 229
            echo _gettext("None");
            echo "\"></option>
        ";
            // line 230
            if ((twig_get_attribute($this->env, $this->source, ($context["available_mime"] ?? null), "input_transformation", [], "array", true, true, false, 230) && twig_test_iterable((($__internal_compile_54 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_54) || $__internal_compile_54 instanceof ArrayAccess ? ($__internal_compile_54["input_transformation"] ?? null) : null)))) {
                // line 231
                echo "          ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable((($__internal_compile_55 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_55) || $__internal_compile_55 instanceof ArrayAccess ? ($__internal_compile_55["input_transformation"] ?? null) : null));
                foreach ($context['_seq'] as $context["mimekey"] => $context["transform"]) {
                    // line 232
                    echo "            ";
                    $context["parts"] = twig_split_filter($this->env, $context["transform"], ":");
                    // line 233
                    echo "            <option value=\"";
                    echo twig_escape_filter($this->env, (($__internal_compile_56 = (($__internal_compile_57 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_57) || $__internal_compile_57 instanceof ArrayAccess ? ($__internal_compile_57["input_transformation_file"] ?? null) : null)) && is_array($__internal_compile_56) || $__internal_compile_56 instanceof ArrayAccess ? ($__internal_compile_56[$context["mimekey"]] ?? null) : null), "html", null, true);
                    echo "\" title=\"";
                    echo twig_escape_filter($this->env, call_user_func_array($this->env->getFunction('get_description')->getCallable(), [(($__internal_compile_58 = (($__internal_compile_59 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_59) || $__internal_compile_59 instanceof ArrayAccess ? ($__internal_compile_59["input_transformation_file"] ?? null) : null)) && is_array($__internal_compile_58) || $__internal_compile_58 instanceof ArrayAccess ? ($__internal_compile_58[$context["mimekey"]] ?? null) : null)]), "html", null, true);
                    echo "\"";
                    // line 234
                    echo ((((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Field", [], "array", true, true, false, 234) && twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["mime_map"] ?? null), (($__internal_compile_60 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_60) || $__internal_compile_60 instanceof ArrayAccess ? ($__internal_compile_60["Field"] ?? null) : null), [], "array", false, true, false, 234), "input_transformation", [], "array", true, true, false, 234)) && preg_match((("@" . (($__internal_compile_61 = (($__internal_compile_62 =                     // line 235
($context["available_mime"] ?? null)) && is_array($__internal_compile_62) || $__internal_compile_62 instanceof ArrayAccess ? ($__internal_compile_62["input_transformation_file_quoted"] ?? null) : null)) && is_array($__internal_compile_61) || $__internal_compile_61 instanceof ArrayAccess ? ($__internal_compile_61[$context["mimekey"]] ?? null) : null)) . "3?@i"), (($__internal_compile_63 = (($__internal_compile_64 = ($context["mime_map"] ?? null)) && is_array($__internal_compile_64) || $__internal_compile_64 instanceof ArrayAccess ? ($__internal_compile_64[(($__internal_compile_65 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_65) || $__internal_compile_65 instanceof ArrayAccess ? ($__internal_compile_65["Field"] ?? null) : null)] ?? null) : null)) && is_array($__internal_compile_63) || $__internal_compile_63 instanceof ArrayAccess ? ($__internal_compile_63["input_transformation"] ?? null) : null)))) ? (" selected") : (""));
                    echo ">
              ";
                    // line 236
                    echo twig_escape_filter($this->env, (((((call_user_func_array($this->env->getFunction('get_name')->getCallable(), [(($__internal_compile_66 = (($__internal_compile_67 = ($context["available_mime"] ?? null)) && is_array($__internal_compile_67) || $__internal_compile_67 instanceof ArrayAccess ? ($__internal_compile_67["input_transformation_file"] ?? null) : null)) && is_array($__internal_compile_66) || $__internal_compile_66 instanceof ArrayAccess ? ($__internal_compile_66[$context["mimekey"]] ?? null) : null)]) . " (") . twig_lower_filter($this->env, (($__internal_compile_68 = ($context["parts"] ?? null)) && is_array($__internal_compile_68) || $__internal_compile_68 instanceof ArrayAccess ? ($__internal_compile_68[0] ?? null) : null))) . ":") . (($__internal_compile_69 = ($context["parts"] ?? null)) && is_array($__internal_compile_69) || $__internal_compile_69 instanceof ArrayAccess ? ($__internal_compile_69[1] ?? null) : null)) . ")"), "html", null, true);
                    echo "
            </option>
          ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['mimekey'], $context['transform'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 239
                echo "        ";
            }
            // line 240
            echo "      </select>
      ";
            // line 241
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 242
            echo "    </td>
    <td class=\"text-center\">
      <input id=\"field_";
            // line 244
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "_";
            echo twig_escape_filter($this->env, (($context["ci"] ?? null) - ($context["ci_offset"] ?? null)), "html", null, true);
            echo "\" type=\"text\" name=\"field_input_transformation_options[";
            echo twig_escape_filter($this->env, ($context["column_number"] ?? null), "html", null, true);
            echo "]\" size=\"16\" class=\"textfield\" value=\"";
            // line 245
            (((twig_get_attribute($this->env, $this->source, ($context["column_meta"] ?? null), "Field", [], "array", true, true, false, 245) && twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["mime_map"] ?? null), (($__internal_compile_70 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_70) || $__internal_compile_70 instanceof ArrayAccess ? ($__internal_compile_70["Field"] ?? null) : null), [], "array", false, true, false, 245), "input_transformation_options", [], "array", true, true, false, 245))) ? (print (twig_escape_filter($this->env, (($__internal_compile_71 = (($__internal_compile_72 = ($context["mime_map"] ?? null)) && is_array($__internal_compile_72) || $__internal_compile_72 instanceof ArrayAccess ? ($__internal_compile_72[(($__internal_compile_73 = ($context["column_meta"] ?? null)) && is_array($__internal_compile_73) || $__internal_compile_73 instanceof ArrayAccess ? ($__internal_compile_73["Field"] ?? null) : null)] ?? null) : null)) && is_array($__internal_compile_71) || $__internal_compile_71 instanceof ArrayAccess ? ($__internal_compile_71["input_transformation_options"] ?? null) : null), "html", null, true))) : (print ("")));
            echo "\">
      ";
            // line 246
            $context["ci"] = (($context["ci"] ?? null) + 1);
            // line 247
            echo "    </td>
";
        }
    }

    public function getTemplateName()
    {
        return "columns_definitions/column_attributes.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  764 => 247,  762 => 246,  758 => 245,  751 => 244,  747 => 242,  745 => 241,  742 => 240,  739 => 239,  730 => 236,  726 => 235,  725 => 234,  719 => 233,  716 => 232,  711 => 231,  709 => 230,  705 => 229,  697 => 228,  693 => 226,  691 => 225,  687 => 224,  680 => 223,  676 => 221,  674 => 220,  671 => 219,  668 => 218,  659 => 215,  655 => 214,  654 => 213,  648 => 212,  645 => 211,  640 => 210,  638 => 209,  634 => 208,  626 => 207,  622 => 205,  620 => 204,  617 => 203,  614 => 202,  605 => 199,  601 => 198,  600 => 197,  596 => 196,  591 => 195,  589 => 194,  580 => 192,  577 => 191,  575 => 190,  572 => 189,  568 => 187,  566 => 186,  563 => 185,  554 => 182,  550 => 181,  546 => 180,  542 => 179,  539 => 178,  537 => 177,  533 => 176,  524 => 174,  520 => 172,  514 => 171,  511 => 170,  508 => 169,  505 => 168,  502 => 167,  497 => 166,  494 => 165,  491 => 164,  488 => 163,  486 => 162,  482 => 159,  479 => 158,  469 => 156,  461 => 154,  459 => 153,  455 => 151,  446 => 148,  439 => 147,  435 => 146,  427 => 145,  424 => 144,  422 => 143,  419 => 141,  417 => 140,  414 => 139,  412 => 138,  403 => 137,  399 => 135,  397 => 134,  393 => 133,  386 => 132,  383 => 131,  379 => 129,  377 => 128,  370 => 124,  365 => 123,  359 => 120,  354 => 119,  348 => 116,  343 => 115,  337 => 112,  332 => 111,  326 => 108,  321 => 107,  317 => 106,  309 => 105,  306 => 104,  304 => 103,  302 => 102,  298 => 100,  295 => 99,  290 => 97,  286 => 96,  279 => 95,  276 => 94,  274 => 93,  272 => 92,  269 => 91,  267 => 90,  263 => 89,  256 => 88,  252 => 86,  249 => 85,  247 => 83,  246 => 82,  245 => 81,  244 => 80,  243 => 79,  242 => 78,  241 => 77,  239 => 76,  235 => 73,  233 => 72,  230 => 71,  223 => 69,  216 => 67,  214 => 66,  211 => 65,  205 => 64,  201 => 63,  194 => 62,  190 => 61,  180 => 59,  176 => 56,  173 => 55,  165 => 53,  157 => 51,  155 => 50,  148 => 46,  142 => 43,  139 => 42,  137 => 41,  133 => 40,  130 => 39,  128 => 38,  124 => 37,  116 => 36,  112 => 34,  110 => 33,  105 => 31,  99 => 30,  93 => 29,  86 => 28,  82 => 26,  80 => 25,  75 => 23,  71 => 22,  64 => 21,  60 => 19,  57 => 18,  55 => 16,  54 => 15,  53 => 14,  52 => 13,  51 => 12,  50 => 11,  48 => 10,  44 => 7,  42 => 6,  39 => 3,  37 => 2,);
    }

    public function getSourceContext()
    {
        return new Source("", "columns_definitions/column_attributes.twig", "/var/www/html/phpMyAdmin/templates/columns_definitions/column_attributes.twig");
    }
}
