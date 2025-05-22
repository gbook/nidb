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

/* server/privileges/change_password.twig */
class __TwigTemplate_d49d478271a107b203e8ca2d3e1c41e8c8bc3ba1d494b00aa2772040bf46df4a extends \Twig\Template
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
        echo "<form method=\"post\" id=\"change_password_form\" action=\"";
        // line 2
        echo ((($context["is_privileges"] ?? null)) ? (PhpMyAdmin\Url::getFromRoute("/server/privileges")) : (PhpMyAdmin\Url::getFromRoute("/user-password")));
        echo "\" name=\"chgPassword\" class=\"";
        echo ((($context["is_privileges"] ?? null)) ? ("submenu-item") : (""));
        echo "\">
  ";
        // line 3
        echo PhpMyAdmin\Url::getHiddenInputs();
        echo "
  ";
        // line 4
        if (($context["is_privileges"] ?? null)) {
            // line 5
            echo "    <input type=\"hidden\" name=\"username\" value=\"";
            echo twig_escape_filter($this->env, ($context["username"] ?? null), "html", null, true);
            echo "\">
    <input type=\"hidden\" name=\"hostname\" value=\"";
            // line 6
            echo twig_escape_filter($this->env, ($context["hostname"] ?? null), "html", null, true);
            echo "\">
  ";
        }
        // line 8
        echo "
  <fieldset id=\"fieldset_change_password\">
    <legend";
        // line 10
        echo ((($context["is_privileges"] ?? null)) ? (" data-submenu-label=\"Change password\"") : (""));
        echo ">";
        echo _gettext("Change password");
        echo "</legend>
    <table class=\"table table-borderless w-auto\">
      <tr>
        <td colspan=\"2\">
          <input type=\"radio\" name=\"nopass\" value=\"1\" id=\"nopass_1\">
          <label for=\"nopass_1\">";
        // line 15
        echo _gettext("No Password");
        echo "</label>
        </td>
      </tr>
      <tr class=\"vmiddle\">
        <td>
          <input type=\"radio\" name=\"nopass\" value=\"0\" id=\"nopass_0\" checked=\"checked\">
          <label for=\"nopass_0\">";
        // line 21
        echo _gettext("Password:");
        echo "&nbsp;</label>
        </td>
        <td>
          ";
        // line 24
        echo _gettext("Enter:");
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          <input type=\"password\" name=\"pma_pw\" id=\"text_pma_change_pw\" size=\"10\" class=\"textfield\"
                 onkeyup=\"Functions.checkPasswordStrength(\$(this).val(), \$('#change_password_strength_meter'), meter_obj_label = \$('#change_password_strength'), CommonParams.get('user'));\"
                 onchange=\"nopass[1].checked = true\">
          <span>";
        // line 28
        echo _pgettext(        "Password strength", "Strength:");
        echo "</span>
          <meter max=\"4\" id=\"change_password_strength_meter\" name=\"pw_meter\"></meter>
          <span id=\"change_password_strength\" name=\"pw_strength\"></span>
          <br>
          ";
        // line 32
        echo _gettext("Re-type:");
        echo "&nbsp;
          <input type=\"password\" name=\"pma_pw2\" id=\"text_pma_change_pw2\" size=\"10\" class=\"textfield\" onchange=\"nopass[1].checked = true\">
        </td>
      </tr>

      ";
        // line 37
        if (( !($context["is_new"] ?? null) || (($context["is_new"] ?? null) && ($context["has_more_auth_plugins"] ?? null)))) {
            // line 38
            echo "        <tr class=\"vmiddle\">
          <td>
            <label for=\"select_authentication_plugin_cp\">";
            // line 40
            echo _gettext("Password Hashing:");
            echo "</label>
          </td>
          <td>
            <select name=\"authentication_plugin\" id=\"select_authentication_plugin_cp\">
              ";
            // line 44
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["active_auth_plugins"] ?? null));
            foreach ($context['_seq'] as $context["plugin_name"] => $context["plugin_description"]) {
                // line 45
                echo "                <option value=\"";
                echo twig_escape_filter($this->env, $context["plugin_name"], "html", null, true);
                echo "\"";
                echo ((($context["plugin_name"] == ($context["orig_auth_plugin"] ?? null))) ? (" selected") : (""));
                echo ">";
                echo twig_escape_filter($this->env, $context["plugin_description"], "html", null, true);
                echo "</option>
              ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['plugin_name'], $context['plugin_description'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 47
            echo "            </select>
          </td>
        </tr>
      ";
        }
        // line 51
        echo "
      <tr id=\"tr_element_before_generate_password\"></tr>
    </table>

    ";
        // line 55
        if ((($context["is_new"] ?? null) && ($context["has_more_auth_plugins"] ?? null))) {
            // line 56
            echo "      <div";
            echo (((($context["orig_auth_plugin"] ?? null) != "sha256_password")) ? (" class=\"hide\"") : (""));
            echo " id=\"ssl_reqd_warning_cp\">
        ";
            // line 57
            ob_start(function () { return ''; });
            // line 58
            echo "          ";
            echo _gettext("This method requires using an '<i>SSL connection</i>' or an '<i>unencrypted connection that encrypts the password using RSA</i>'; while connecting to the server.");
            // line 61
            echo "          ";
            echo \PhpMyAdmin\Html\MySQLDocumentation::show("sha256-authentication-plugin");
            echo "
        ";
            $___internal_parse_0_ = ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
            // line 57
            echo call_user_func_array($this->env->getFilter('notice')->getCallable(), [$___internal_parse_0_]);
            // line 63
            echo "      </div>
    ";
        }
        // line 65
        echo "
  </fieldset>

  <fieldset id=\"fieldset_change_password_footer\" class=\"tblFooters\">
    <input type=\"hidden\" name=\"change_pw\" value=\"1\">
    <input class=\"btn btn-primary\" type=\"submit\" value=\"";
        // line 70
        echo _gettext("Go");
        echo "\">
  </fieldset>
</form>
";
    }

    public function getTemplateName()
    {
        return "server/privileges/change_password.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  185 => 70,  178 => 65,  174 => 63,  172 => 57,  166 => 61,  163 => 58,  161 => 57,  156 => 56,  154 => 55,  148 => 51,  142 => 47,  129 => 45,  125 => 44,  118 => 40,  114 => 38,  112 => 37,  104 => 32,  97 => 28,  90 => 24,  84 => 21,  75 => 15,  65 => 10,  61 => 8,  56 => 6,  51 => 5,  49 => 4,  45 => 3,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "server/privileges/change_password.twig", "/var/www/html/phpMyAdmin/templates/server/privileges/change_password.twig");
    }
}
