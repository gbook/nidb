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

/* import.twig */
class __TwigTemplate_cc5299c919ce6946ad412c8e87e3ef8e6e82390c7bdf294ddc42be19debb808b extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'title' => [$this, 'block_title'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo ($context["page_settings_error_html"] ?? null);
        echo "
";
        // line 2
        echo ($context["page_settings_html"] ?? null);
        echo "

<iframe id=\"import_upload_iframe\" name=\"import_upload_iframe\" width=\"1\" height=\"1\" class=\"hide\"></iframe>
<div id=\"import_form_status\" class=\"hide\"></div>
<div id=\"importmain\" class=\"container-fluid\">
    <img src=\"";
        // line 7
        echo twig_escape_filter($this->env, ($context["theme_image_path"] ?? null), "html", null, true);
        echo "ajax_clock_small.gif\" width=\"16\" height=\"16\" alt=\"ajax clock\" class=\"hide\">

    <script type=\"text/javascript\">
        //<![CDATA[
        ";
        // line 11
        $this->loadTemplate("import/javascript.twig", "import.twig", 11)->display(twig_to_array(["upload_id" =>         // line 12
($context["upload_id"] ?? null), "handler" =>         // line 13
($context["handler"] ?? null), "theme_image_path" =>         // line 14
($context["theme_image_path"] ?? null)]));
        // line 16
        echo "        //]]>
    </script>

    <form id=\"import_file_form\"
        action=\"";
        // line 20
        echo PhpMyAdmin\Url::getFromRoute("/import");
        echo "\"
        method=\"post\"
        enctype=\"multipart/form-data\"
        name=\"import\"
        class=\"ajax\"";
        // line 25
        if ((($context["handler"] ?? null) != "PhpMyAdmin\\Plugins\\Import\\Upload\\UploadNoplugin")) {
            // line 26
            echo "            target=\"import_upload_iframe\"";
        }
        // line 27
        echo ">

        ";
        // line 29
        echo PhpMyAdmin\Url::getHiddenInputs(($context["hidden_inputs"] ?? null));
        echo "

        <div class=\"exportoptions\" id=\"header\">
            <h2>
                ";
        // line 33
        echo \PhpMyAdmin\Html\Generator::getImage("b_import", _gettext("Import"));
        echo "
                ";
        // line 34
        $this->displayBlock('title', $context, $blocks);
        // line 35
        echo "            </h2>
        </div>

        <div class=\"importoptions\">
            <h3>";
        // line 39
        echo _gettext("File to import:");
        echo "</h3>

            ";
        // line 42
        echo "            ";
        if ( !twig_test_empty(($context["compressions"] ?? null))) {
            // line 43
            echo "                <div class=\"formelementrow\" id=\"compression_info\">
                    <p>
                        ";
            // line 45
            echo twig_escape_filter($this->env, twig_sprintf(_gettext("File may be compressed (%s) or uncompressed."), twig_join_filter(($context["compressions"] ?? null), ", ")), "html", null, true);
            echo "
                        <br>
                        ";
            // line 47
            echo _gettext("A compressed file's name must end in <strong>.[format].[compression]</strong>. Example: <strong>.sql.zip</strong>");
            // line 48
            echo "                    </p>
                </div>
            ";
        }
        // line 51
        echo "
            <div class=\"formelementrow\" id=\"upload_form\">
                ";
        // line 53
        if ((($context["is_upload"] ?? null) &&  !twig_test_empty(($context["upload_dir"] ?? null)))) {
            // line 54
            echo "                    <ul>
                        <li>
                            <input type=\"radio\" name=\"file_location\" id=\"radio_import_file\" required=\"required\">
                            <label for=\"radio_import_file\">";
            // line 57
            echo _gettext("Browse your computer:");
            echo "</label>

                            <div id=\"upload_form_status\" class=\"hide\"></div>
                            <div id=\"upload_form_status_info\" class=\"hide\"></div>

                            <input type=\"file\" name=\"import_file\" id=\"input_import_file\" aria-label=\"";
            // line 62
            echo _gettext("Browse your computer");
            echo "\">

                            ";
            // line 64
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::getFormattedMaximumUploadSize(($context["max_upload_size"] ?? null)), "html", null, true);
            echo "

                            <input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"";
            // line 66
            echo twig_escape_filter($this->env, ($context["max_upload_size"] ?? null), "html", null, true);
            echo "\">

                            <p>";
            // line 68
            echo _gettext("You may also drag and drop a file on any page.");
            echo "</p>
                        </li>
                        <li>
                          <input type=\"radio\" name=\"file_location\" id=\"radio_local_import_file\"";
            // line 72
            echo ((( !twig_test_empty(($context["timeout_passed_global"] ?? null)) &&  !twig_test_empty(($context["local_import_file"] ?? null)))) ? (" checked") : (""));
            echo ">
                          <label for=\"radio_local_import_file\">
                            ";
            // line 74
            echo PhpMyAdmin\Sanitize::sanitizeMessage(twig_sprintf(_gettext("Select from the web server upload directory [strong]%s[/strong]:"), ($context["user_upload_dir"] ?? null)));
            echo "
                          </label>

                          ";
            // line 77
            if ((($context["local_files"] ?? null) === false)) {
                // line 78
                echo "                            ";
                echo call_user_func_array($this->env->getFilter('error')->getCallable(), [_gettext("The directory you set for upload work cannot be reached.")]);
                echo "
                          ";
            } elseif ( !twig_test_empty(            // line 79
($context["local_files"] ?? null))) {
                // line 80
                echo "                            <select size=\"1\" name=\"local_import_file\" id=\"select_local_import_file\" aria-label=\"";
                echo _gettext("Select file to import");
                echo "\">
                              <option value=\"\"></option>
                              ";
                // line 82
                echo ($context["local_files"] ?? null);
                echo "
                            </select>
                          ";
            } else {
                // line 85
                echo "                            <em>";
                echo _gettext("There are no files to upload!");
                echo "</em>
                          ";
            }
            // line 87
            echo "                        </li>
                    </ul>
                ";
        } elseif (        // line 89
($context["is_upload"] ?? null)) {
            // line 90
            echo "                    <label for=\"input_import_file\">";
            echo _gettext("Browse your computer:");
            echo "</label>

                    <div id=\"upload_form_status\" class=\"hide\"></div>
                    <div id=\"upload_form_status_info\" class=\"hide\"></div>

                    <input type=\"file\" name=\"import_file\" id=\"input_import_file\">

                    ";
            // line 97
            echo twig_escape_filter($this->env, PhpMyAdmin\Util::getFormattedMaximumUploadSize(($context["max_upload_size"] ?? null)), "html", null, true);
            echo "

                    <input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"";
            // line 99
            echo twig_escape_filter($this->env, ($context["max_upload_size"] ?? null), "html", null, true);
            echo "\">

                    <p>";
            // line 101
            echo _gettext("You may also drag and drop a file on any page.");
            echo "</p>
                ";
        } elseif ( !twig_test_empty(        // line 102
($context["upload_dir"] ?? null))) {
            // line 103
            echo "                  <label for=\"select_local_import_file\">
                    ";
            // line 104
            echo PhpMyAdmin\Sanitize::sanitizeMessage(twig_sprintf(_gettext("Select from the web server upload directory [strong]%s[/strong]:"), ($context["user_upload_dir"] ?? null)));
            echo "
                  </label>

                  ";
            // line 107
            if ((($context["local_files"] ?? null) === false)) {
                // line 108
                echo "                    ";
                echo call_user_func_array($this->env->getFilter('error')->getCallable(), [_gettext("The directory you set for upload work cannot be reached.")]);
                echo "
                  ";
            } elseif ( !twig_test_empty(            // line 109
($context["local_files"] ?? null))) {
                // line 110
                echo "                    <select size=\"1\" name=\"local_import_file\" id=\"select_local_import_file\">
                      <option value=\"\"></option>
                      ";
                // line 112
                echo ($context["local_files"] ?? null);
                echo "
                    </select>
                  ";
            } else {
                // line 115
                echo "                    <em>";
                echo _gettext("There are no files to upload!");
                echo "</em>
                  ";
            }
            // line 117
            echo "                ";
        } else {
            // line 118
            echo "                    ";
            echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [_gettext("File uploads are not allowed on this server.")]);
            echo "
                ";
        }
        // line 120
        echo "            </div>

            <div class=\"formelementrow\" id=\"charaset_of_file\">
                ";
        // line 124
        echo "                <label for=\"charset_of_file\">";
        echo _gettext("Character set of the file:");
        echo "</label>
                ";
        // line 125
        if (($context["is_encoding_supported"] ?? null)) {
            // line 126
            echo "                    <select id=\"charset_of_file\" name=\"charset_of_file\" size=\"1\">
                        ";
            // line 127
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["encodings"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["charset"]) {
                // line 128
                echo "                            <option value=\"";
                echo twig_escape_filter($this->env, $context["charset"], "html", null, true);
                echo "\"
                                ";
                // line 129
                if (((twig_test_empty(($context["import_charset"] ?? null)) && ($context["charset"] == "utf-8")) || (                // line 130
$context["charset"] == ($context["import_charset"] ?? null)))) {
                    // line 131
                    echo "                                    selected=\"selected\"
                                ";
                }
                // line 132
                echo ">
                                ";
                // line 133
                echo twig_escape_filter($this->env, $context["charset"], "html", null, true);
                echo "
                            </option>
                        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['charset'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 136
            echo "                    </select>
                ";
        } else {
            // line 138
            echo "                  <select lang=\"en\" dir=\"ltr\" name=\"charset_of_file\" id=\"charset_of_file\">
                    <option value=\"\"></option>
                    ";
            // line 140
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["charsets"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["charset"]) {
                // line 141
                echo "                      <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "name", [], "any", false, false, false, 141), "html", null, true);
                echo "\" title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "description", [], "any", false, false, false, 141), "html", null, true);
                echo "\"";
                // line 142
                echo (((twig_get_attribute($this->env, $this->source, $context["charset"], "name", [], "any", false, false, false, 142) == "utf8")) ? (" selected") : (""));
                echo ">";
                // line 143
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "name", [], "any", false, false, false, 143), "html", null, true);
                // line 144
                echo "</option>
                    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['charset'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 146
            echo "                  </select>
                ";
        }
        // line 148
        echo "            </div>
        </div>

        <div class=\"importoptions\">
            <h3>";
        // line 152
        echo _gettext("Partial import:");
        echo "</h3>

            ";
        // line 154
        if (((isset($context["timeout_passed"]) || array_key_exists("timeout_passed", $context)) && ($context["timeout_passed"] ?? null))) {
            // line 155
            echo "                <div class=\"formelementrow\">
                    <input type=\"hidden\" name=\"skip\" value=\"";
            // line 156
            echo twig_escape_filter($this->env, ($context["offset"] ?? null), "html", null, true);
            echo "\">
                    ";
            // line 157
            echo twig_escape_filter($this->env, twig_sprintf(_gettext("Previous import timed out, after resubmitting will continue from position %d."), ($context["offset"] ?? null)), "html", null, true);
            echo "
                </div>
            ";
        }
        // line 160
        echo "
            <div class=\"formelementrow\">
                <input type=\"checkbox\" name=\"allow_interrupt\" value=\"yes\" id=\"checkbox_allow_interrupt\"
                    ";
        // line 163
        echo PhpMyAdmin\Plugins::checkboxCheck("Import", "allow_interrupt");
        echo ">
                <label for=\"checkbox_allow_interrupt\">
                    ";
        // line 165
        echo _gettext("Allow the interruption of an import in case the script detects it is close to the PHP timeout limit. <em>(This might be a good way to import large files, however it can break transactions.)</em>");
        // line 166
        echo "                </label>
            </div>

            ";
        // line 169
        if ( !((isset($context["timeout_passed"]) || array_key_exists("timeout_passed", $context)) && ($context["timeout_passed"] ?? null))) {
            // line 170
            echo "                <div class=\"formelementrow\">
                    <label for=\"text_skip_queries\">
                        ";
            // line 172
            echo _gettext("Skip this number of queries (for SQL) starting from the first one:");
            // line 173
            echo "                    </label>
                    <input type=\"number\" name=\"skip_queries\" value=\"";
            // line 175
            echo PhpMyAdmin\Plugins::getDefault("Import", "skip_queries");
            // line 176
            echo "\" id=\"text_skip_queries\" min=\"0\">
                </div>
            ";
        } else {
            // line 179
            echo "                ";
            // line 182
            echo "                <input type=\"hidden\" name=\"skip_queries\" value=\"";
            // line 183
            echo PhpMyAdmin\Plugins::getDefault("Import", "skip_queries");
            // line 184
            echo "\" id=\"text_skip_queries\">
            ";
        }
        // line 186
        echo "        </div>

        <div class=\"importoptions\">
            <h3>";
        // line 189
        echo _gettext("Other options:");
        echo "</h3>
            <div class=\"formelementrow\">
                <input type=\"hidden\" name=\"fk_checks\" value=\"0\">
                <input type=\"checkbox\" name=\"fk_checks\" id=\"fk_checks\" value=\"1\"";
        // line 192
        echo ((($context["is_foreign_key_check"] ?? null)) ? (" checked") : (""));
        echo ">
                <label for=\"fk_checks\">";
        // line 193
        echo _gettext("Enable foreign key checks");
        echo "</label>
            </div>
        </div>

        <div class=\"importoptions\">
            <h3>";
        // line 198
        echo _gettext("Format:");
        echo "</h3>
            ";
        // line 199
        echo PhpMyAdmin\Plugins::getChoice("Import", "format", ($context["import_list"] ?? null));
        echo "
            <div id=\"import_notification\"></div>
        </div>

        <div class=\"importoptions\" id=\"format_specific_opts\">
            <h3>";
        // line 204
        echo _gettext("Format-specific options:");
        echo "</h3>
            <p class=\"no_js_msg\" id=\"scroll_to_options_msg\">
                ";
        // line 206
        echo _gettext("Scroll down to fill in the options for the selected format and ignore the options for other formats.");
        // line 207
        echo "            </p>
            ";
        // line 208
        echo PhpMyAdmin\Plugins::getOptions("Import", ($context["import_list"] ?? null));
        echo "
        </div>
        <div class=\"clearfloat\"></div>

        ";
        // line 213
        echo "        ";
        if (($context["can_convert_kanji"] ?? null)) {
            // line 214
            echo "            <div class=\"importoptions\" id=\"kanji_encoding\">
                <h3>";
            // line 215
            echo _gettext("Encoding Conversion:");
            echo "</h3>
                ";
            // line 216
            $this->loadTemplate("encoding/kanji_encoding_form.twig", "import.twig", 216)->display($context);
            // line 217
            echo "            </div>
        ";
        }
        // line 219
        echo "
        <div class=\"importoptions justify-content-end\" id=\"submit\">
            <input id=\"buttonGo\" class=\"btn btn-primary\" type=\"submit\" value=\"";
        // line 221
        echo _gettext("Go");
        echo "\">
        </div>
    </form>
</div>
";
    }

    // line 34
    public function block_title($context, array $blocks = [])
    {
        $macros = $this->macros;
    }

    public function getTemplateName()
    {
        return "import.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  510 => 34,  501 => 221,  497 => 219,  493 => 217,  491 => 216,  487 => 215,  484 => 214,  481 => 213,  474 => 208,  471 => 207,  469 => 206,  464 => 204,  456 => 199,  452 => 198,  444 => 193,  440 => 192,  434 => 189,  429 => 186,  425 => 184,  423 => 183,  421 => 182,  419 => 179,  414 => 176,  412 => 175,  409 => 173,  407 => 172,  403 => 170,  401 => 169,  396 => 166,  394 => 165,  389 => 163,  384 => 160,  378 => 157,  374 => 156,  371 => 155,  369 => 154,  364 => 152,  358 => 148,  354 => 146,  347 => 144,  345 => 143,  342 => 142,  336 => 141,  332 => 140,  328 => 138,  324 => 136,  315 => 133,  312 => 132,  308 => 131,  306 => 130,  305 => 129,  300 => 128,  296 => 127,  293 => 126,  291 => 125,  286 => 124,  281 => 120,  275 => 118,  272 => 117,  266 => 115,  260 => 112,  256 => 110,  254 => 109,  249 => 108,  247 => 107,  241 => 104,  238 => 103,  236 => 102,  232 => 101,  227 => 99,  222 => 97,  211 => 90,  209 => 89,  205 => 87,  199 => 85,  193 => 82,  187 => 80,  185 => 79,  180 => 78,  178 => 77,  172 => 74,  167 => 72,  161 => 68,  156 => 66,  151 => 64,  146 => 62,  138 => 57,  133 => 54,  131 => 53,  127 => 51,  122 => 48,  120 => 47,  115 => 45,  111 => 43,  108 => 42,  103 => 39,  97 => 35,  95 => 34,  91 => 33,  84 => 29,  80 => 27,  77 => 26,  75 => 25,  68 => 20,  62 => 16,  60 => 14,  59 => 13,  58 => 12,  57 => 11,  50 => 7,  42 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "import.twig", "/var/www/html/phpMyAdmin/templates/import.twig");
    }
}
