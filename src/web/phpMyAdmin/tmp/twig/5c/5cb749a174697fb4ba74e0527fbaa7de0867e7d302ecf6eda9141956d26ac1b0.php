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

/* server/privileges/login_information_fields.twig */
class __TwigTemplate_f818729353e0fedd8af7b99883ef22958640bf80ead48dffe29e1a0f12d4e5d8 extends \Twig\Template
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
        echo "<fieldset id=\"fieldset_add_user_login\">
  <legend>";
        // line 2
        echo _gettext("Login Information");
        echo "</legend>
  <div class=\"item\">
    <label for=\"select_pred_username\">";
        // line 4
        echo _gettext("User name:");
        echo "</label>
    <span class=\"options\">
      <select name=\"pred_username\" id=\"select_pred_username\" title=\"";
        // line 6
        echo _gettext("User name");
        echo "\">
        <option value=\"any\"";
        // line 7
        echo (((($context["pred_username"] ?? null) == "any")) ? (" selected") : (""));
        echo ">";
        echo _gettext("Any user");
        echo "</option>
        <option value=\"userdefined\"";
        // line 8
        echo ((((null === ($context["pred_username"] ?? null)) || (($context["pred_username"] ?? null) == "userdefined"))) ? (" selected") : (""));
        echo ">";
        echo _gettext("Use text field");
        echo "</option>
      </select>
    </span>
    <input type=\"text\" name=\"username\" id=\"pma_username\" class=\"autofocus\" maxlength=\"";
        // line 11
        echo twig_escape_filter($this->env, ($context["username_length"] ?? null), "html", null, true);
        echo "\" title=\"";
        echo _gettext("User name");
        echo "\"";
        // line 12
        if ( !twig_test_empty(($context["username"] ?? null))) {
            echo " value=\"";
            echo twig_escape_filter($this->env, (( !(null === ($context["new_username"] ?? null))) ? (($context["new_username"] ?? null)) : (($context["username"] ?? null))), "html", null, true);
            echo "\"";
        }
        // line 13
        echo ((((null === ($context["pred_username"] ?? null)) || (($context["pred_username"] ?? null) == "userdefined"))) ? (" required") : (""));
        echo ">

    <div id=\"user_exists_warning\" class=\"hide\">
      ";
        // line 16
        echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [_gettext("An account already exists with the same username but possibly a different hostname.")]);
        echo "
    </div>
  </div>

  <div class=\"item\">
    <label for=\"select_pred_hostname\">
      ";
        // line 22
        echo _gettext("Host name:");
        // line 23
        echo "    </label>
    <span class=\"options\">
      <select name=\"pred_hostname\" id=\"select_pred_hostname\" title=\"";
        // line 25
        echo _gettext("Host name");
        echo "\"";
        // line 26
        (((( !(null === ($context["this_host"] ?? null)) && (($context["this_host"] ?? null) != "localhost")) && (($context["this_host"] ?? null) != "127.0.0.1"))) ? (print (twig_escape_filter($this->env, ((" data-thishost=\"" . ($context["this_host"] ?? null)) . "\""), "html", null, true))) : (print ("")));
        echo ">
        <option value=\"any\"";
        // line 27
        echo (((($context["pred_hostname"] ?? null) == "any")) ? (" selected") : (""));
        echo ">";
        echo _gettext("Any host");
        echo "</option>
        <option value=\"localhost\"";
        // line 28
        echo (((($context["pred_hostname"] ?? null) == "localhost")) ? (" selected") : (""));
        echo ">";
        echo _gettext("Local");
        echo "</option>
        ";
        // line 29
        if ( !twig_test_empty(($context["this_host"] ?? null))) {
            // line 30
            echo "          <option value=\"thishost\"";
            echo (((($context["pred_hostname"] ?? null) == "thishost")) ? (" selected") : (""));
            echo ">";
            echo _gettext("This host");
            echo "</option>
        ";
        }
        // line 32
        echo "        <option value=\"hosttable\"";
        echo (((($context["pred_hostname"] ?? null) == "hosttable")) ? (" selected") : (""));
        echo ">";
        echo _gettext("Use host table");
        echo "</option>
        <option value=\"userdefined\"";
        // line 33
        echo (((($context["pred_hostname"] ?? null) == "userdefined")) ? (" selected") : (""));
        echo ">";
        echo _gettext("Use text field");
        echo "</option>
      </select>
    </span>
    <input type=\"text\" name=\"hostname\" id=\"pma_hostname\" maxlength=\"";
        // line 36
        echo twig_escape_filter($this->env, ($context["hostname_length"] ?? null), "html", null, true);
        echo "\" value=\"";
        echo twig_escape_filter($this->env, (((isset($context["hostname"]) || array_key_exists("hostname", $context))) ? (_twig_default_filter(($context["hostname"] ?? null), "%")) : ("%")), "html", null, true);
        echo "\" title=\"";
        // line 37
        echo _gettext("Host name");
        echo "\"";
        echo (((($context["pred_hostname"] ?? null) == "userdefined")) ? (" required") : (""));
        echo ">

    ";
        // line 39
        echo \PhpMyAdmin\Html\Generator::showHint(_gettext("When Host table is used, this field is ignored and values stored in Host table are used instead."));
        echo "
  </div>

  <div class=\"item\">
    <label for=\"select_pred_password\">";
        // line 43
        echo _gettext("Password:");
        echo "</label>
    <span class=\"options\">
      <select name=\"pred_password\" id=\"select_pred_password\" title=\"";
        // line 45
        echo _gettext("Password");
        echo "\">
        ";
        // line 46
        if (($context["is_change"] ?? null)) {
            // line 47
            echo "          <option value=\"keep\" selected>";
            echo _gettext("Do not change the password");
            echo "</option>
        ";
        }
        // line 49
        echo "        <option value=\"none\"";
        echo ((( !(null === ($context["username"] ?? null)) &&  !($context["is_change"] ?? null))) ? (" selected") : (""));
        echo ">";
        echo _gettext("No password");
        echo "</option>
        <option value=\"userdefined\"";
        // line 50
        echo (((null === ($context["username"] ?? null))) ? (" selected") : (""));
        echo ">";
        echo _gettext("Use text field");
        echo "</option>
      </select>
    </span>
    <input type=\"password\" id=\"text_pma_pw\" name=\"pma_pw\" title=\"";
        // line 53
        echo _gettext("Password");
        echo "\"";
        echo (((null === ($context["username"] ?? null))) ? (" required") : (""));
        echo ">
    <span>";
        // line 54
        echo _pgettext(        "Password strength", "Strength:");
        echo "</span>
    <meter max=\"4\" id=\"password_strength_meter\" name=\"pw_meter\"></meter>
    <span id=\"password_strength\" name=\"pw_strength\"></span>
  </div>

  <div class=\"item\" id=\"div_element_before_generate_password\">
    <label for=\"text_pma_pw2\">";
        // line 60
        echo _gettext("Re-type:");
        echo "</label>
    <span class=\"options\">&nbsp;</span>
    <input type=\"password\" name=\"pma_pw2\" id=\"text_pma_pw2\" title=\"";
        // line 62
        echo _gettext("Re-type");
        echo "\"";
        echo (((null === ($context["username"] ?? null))) ? (" required") : (""));
        echo ">
  </div>

  <div class=\"item\" id=\"authentication_plugin_div\">
    <label for=\"select_authentication_plugin\">
      ";
        // line 67
        if (($context["is_new"] ?? null)) {
            // line 68
            echo "        ";
            echo _gettext("Authentication plugin");
            // line 69
            echo "      ";
        } else {
            // line 70
            echo "        ";
            echo _gettext("Password hashing method");
            // line 71
            echo "      ";
        }
        // line 72
        echo "    </label>
    <span class=\"options\">&nbsp;</span>

    <select name=\"authentication_plugin\" id=\"select_authentication_plugin\">
      ";
        // line 76
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["active_auth_plugins"] ?? null));
        foreach ($context['_seq'] as $context["plugin_name"] => $context["plugin_description"]) {
            // line 77
            echo "        <option value=\"";
            echo twig_escape_filter($this->env, $context["plugin_name"], "html", null, true);
            echo "\"";
            echo ((($context["plugin_name"] == ($context["auth_plugin"] ?? null))) ? (" selected") : (""));
            echo ">";
            echo twig_escape_filter($this->env, $context["plugin_description"], "html", null, true);
            echo "</option>
      ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['plugin_name'], $context['plugin_description'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 79
        echo "    </select>

    <div id=\"ssl_reqd_warning\"";
        // line 81
        echo (((($context["auth_plugin"] ?? null) != "sha256_password")) ? (" class=\"hide\"") : (""));
        echo ">
      ";
        // line 82
        ob_start(function () { return ''; });
        // line 83
        echo "        ";
        echo _gettext("This method requires using an '<em>SSL connection</em>' or an '<em>unencrypted connection that encrypts the password using RSA</em>'; while connecting to the server.");
        // line 86
        echo "        ";
        echo \PhpMyAdmin\Html\MySQLDocumentation::show("sha256-authentication-plugin");
        echo "
      ";
        $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 82
        echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [$___internal_parse_0_]);
        // line 88
        echo "    </div>
  </div>
  ";
        // line 91
        echo "</fieldset>
";
    }

    public function getTemplateName()
    {
        return "server/privileges/login_information_fields.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  287 => 91,  283 => 88,  281 => 82,  275 => 86,  272 => 83,  270 => 82,  266 => 81,  262 => 79,  249 => 77,  245 => 76,  239 => 72,  236 => 71,  233 => 70,  230 => 69,  227 => 68,  225 => 67,  215 => 62,  210 => 60,  201 => 54,  195 => 53,  187 => 50,  180 => 49,  174 => 47,  172 => 46,  168 => 45,  163 => 43,  156 => 39,  149 => 37,  144 => 36,  136 => 33,  129 => 32,  121 => 30,  119 => 29,  113 => 28,  107 => 27,  103 => 26,  100 => 25,  96 => 23,  94 => 22,  85 => 16,  79 => 13,  73 => 12,  68 => 11,  60 => 8,  54 => 7,  50 => 6,  45 => 4,  40 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/privileges/login_information_fields.twig", "/var/www/html/phpMyAdmin/templates/server/privileges/login_information_fields.twig");
    }
}
