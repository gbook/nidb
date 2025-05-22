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

/* login/form.twig */
class __TwigTemplate_7f8111c16485d20147b7673659a6727927fd507f2880b0662a7a404c90006cce extends \Twig\Template
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
        echo ($context["login_header"] ?? null);
        echo "

";
        // line 3
        if (($context["is_demo"] ?? null)) {
            // line 4
            echo "  <fieldset class=\"mb-4\">
    <legend>";
            // line 5
            echo _gettext("phpMyAdmin Demo Server");
            echo "</legend>
    ";
            // line 6
            ob_start(function () { return ''; });
            // line 7
            echo "      ";
            echo _gettext("You are using the demo server. You can do anything here, but please do not change root, debian-sys-maint and pma users. More information is available at %s.");
            // line 10
            echo "    ";
            $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 6
            echo twig_sprintf($___internal_parse_0_, "<a href=\"url.php?url=https://demo.phpmyadmin.net/\" target=\"_blank\" rel=\"noopener noreferrer\">demo.phpmyadmin.net</a>");
            // line 11
            echo "  </fieldset>
";
        }
        // line 13
        echo "
";
        // line 14
        echo ($context["error_messages"] ?? null);
        echo "

";
        // line 16
        if (($context["has_languages"] ?? null)) {
            // line 17
            echo "  <div class='hide js-show'>
    ";
            // line 18
            echo ($context["language_selector"] ?? null);
            echo "
  </div>
";
        }
        // line 21
        echo "
<form method=\"post\" id=\"login_form\" action=\"index.php?route=/\" name=\"login_form\" class=\"";
        // line 23
        echo (( !($context["is_session_expired"] ?? null)) ? ("disableAjax hide ") : (""));
        echo "login js-show form-horizontal\"";
        echo (( !($context["has_autocomplete"] ?? null)) ? (" autocomplete=\"off\"") : (""));
        echo ">
  <fieldset>
    <legend class=\"col-form-label\">
      <input type=\"hidden\" name=\"set_session\" value=\"";
        // line 26
        echo twig_escape_filter($this->env, ($context["session_id"] ?? null), "html", null, true);
        echo "\">
      ";
        // line 27
        if (($context["is_session_expired"] ?? null)) {
            // line 28
            echo "        <input type=\"hidden\" name=\"session_timedout\" value=\"1\">
      ";
        }
        // line 30
        echo "      ";
        echo _gettext("Log in");
        // line 31
        echo "      ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("index");
        echo "
    </legend>

    ";
        // line 34
        if (($context["is_arbitrary_server_allowed"] ?? null)) {
            // line 35
            echo "      <div class=\"item form-row\">
        <label for=\"input_servername\" class=\"col-4 d-flex align-items-center\" title=\"";
            // line 36
            echo _gettext("You can enter hostname/IP address and port separated by space.");
            echo "\">
          ";
            // line 37
            echo _gettext("Server:");
            // line 38
            echo "        </label>
        <div class=\"col-8\">
        <input type=\"text\" name=\"pma_servername\" id=\"input_servername\" value=\"";
            // line 40
            echo twig_escape_filter($this->env, ($context["default_server"] ?? null), "html", null, true);
            echo "\" size=\"24\" class=\"textfield\" title=\"";
            // line 41
            echo _gettext("You can enter hostname/IP address and port separated by space.");
            echo "\">
        </div>
      </div>
    ";
        }
        // line 45
        echo "
    <div class=\"item form-row\">
      <label for=\"input_username\" class=\"col-4 d-flex align-items-center\">
        ";
        // line 48
        echo _gettext("Username:");
        // line 49
        echo "      </label>
      <div class=\"col-8\">
        <input type=\"text\" name=\"pma_username\" id=\"input_username\" value=\"";
        // line 51
        echo twig_escape_filter($this->env, ($context["default_user"] ?? null), "html", null, true);
        echo "\" size=\"24\" class=\"textfield\" autocomplete=\"username\">
      </div>
    </div>

    <div class=\"item form-row\">
      <label for=\"input_password\" class=\"col-4 d-flex align-items-center\">
        ";
        // line 57
        echo _gettext("Password:");
        // line 58
        echo "      </label>
      <div class=\"col-8\">
        <input type=\"password\" name=\"pma_password\" id=\"input_password\" value=\"\" size=\"24\" class=\"textfield\" autocomplete=\"current-password\">
      </div>
    </div>

    ";
        // line 64
        if (($context["has_servers"] ?? null)) {
            // line 65
            echo "      <div class=\"item form-row\">
        <label for=\"select_server\" class=\"col-4 d-flex align-items-center\">
          ";
            // line 67
            echo _gettext("Server choice:");
            // line 68
            echo "        </label>
        <div class=\"col-8\">
          <select name=\"server\" id=\"select_server\"";
            // line 70
            echo ((($context["is_arbitrary_server_allowed"] ?? null)) ? (" onchange=\"document.forms['login_form'].elements['pma_servername'].value = ''\"") : (""));
            echo ">
            ";
            // line 71
            echo ($context["server_options"] ?? null);
            echo "
          </select>
        </div>
      </div>
    ";
        } else {
            // line 76
            echo "      <input type=\"hidden\" name=\"server\" value=\"";
            echo twig_escape_filter($this->env, ($context["server"] ?? null), "html", null, true);
            echo "\">
    ";
        }
        // line 78
        echo "  </fieldset>

  <fieldset class=\"tblFooters\">
    ";
        // line 81
        if (($context["has_captcha"] ?? null)) {
            // line 82
            echo "      <script src=\"";
            echo twig_escape_filter($this->env, ($context["captcha_api"] ?? null), "html", null, true);
            echo "?hl=";
            echo twig_escape_filter($this->env, ($context["lang"] ?? null), "html", null, true);
            echo "\" async defer></script>
      ";
            // line 83
            if (($context["use_captcha_checkbox"] ?? null)) {
                // line 84
                echo "      <div class=\"";
                echo twig_escape_filter($this->env, ($context["captcha_req"] ?? null), "html", null, true);
                echo "\" data-sitekey=\"";
                echo twig_escape_filter($this->env, ($context["captcha_key"] ?? null), "html", null, true);
                echo "\"></div>
      <input class=\"btn btn-primary\" value=\"";
                // line 85
                echo _gettext("Go");
                echo "\" type=\"submit\" id=\"input_go\">
      ";
            } else {
                // line 87
                echo "      <input class=\"btn btn-primary ";
                echo twig_escape_filter($this->env, ($context["captcha_req"] ?? null), "html", null, true);
                echo "\" data-sitekey=\"";
                echo twig_escape_filter($this->env, ($context["captcha_key"] ?? null), "html", null, true);
                echo "\" data-callback=\"Functions_recaptchaCallback\" value=\"";
                echo _gettext("Go");
                echo "\" type=\"submit\" id=\"input_go\">
      ";
            }
            // line 89
            echo "    ";
        } else {
            // line 90
            echo "      <input class=\"btn btn-primary\" value=\"";
            echo _gettext("Go");
            echo "\" type=\"submit\" id=\"input_go\">
    ";
        }
        // line 92
        echo "    ";
        // line 93
        echo "    ";
        echo PhpMyAdmin\Url::getHiddenInputs(($context["form_params"] ?? null), "", 0, "server");
        echo "
  </fieldset>
</form>

";
        // line 97
        if ( !twig_test_empty(($context["errors"] ?? null))) {
            // line 98
            echo "  <div id=\"pma_errors\">
    ";
            // line 99
            echo ($context["errors"] ?? null);
            echo "
  </div>
  </div>
  </div>
";
        }
        // line 104
        echo "
";
        // line 105
        echo ($context["login_footer"] ?? null);
        echo "

";
        // line 107
        echo ($context["config_footer"] ?? null);
        echo "
";
    }

    public function getTemplateName()
    {
        return "login/form.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  278 => 107,  273 => 105,  270 => 104,  262 => 99,  259 => 98,  257 => 97,  249 => 93,  247 => 92,  241 => 90,  238 => 89,  228 => 87,  223 => 85,  216 => 84,  214 => 83,  207 => 82,  205 => 81,  200 => 78,  194 => 76,  186 => 71,  182 => 70,  178 => 68,  176 => 67,  172 => 65,  170 => 64,  162 => 58,  160 => 57,  151 => 51,  147 => 49,  145 => 48,  140 => 45,  133 => 41,  130 => 40,  126 => 38,  124 => 37,  120 => 36,  117 => 35,  115 => 34,  108 => 31,  105 => 30,  101 => 28,  99 => 27,  95 => 26,  87 => 23,  84 => 21,  78 => 18,  75 => 17,  73 => 16,  68 => 14,  65 => 13,  61 => 11,  59 => 6,  56 => 10,  53 => 7,  51 => 6,  47 => 5,  44 => 4,  42 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "login/form.twig", "/var/www/html/phpMyAdmin/templates/login/form.twig");
    }
}
