<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* error/report_form.twig */
class __TwigTemplate_84f7f49b4f1e47428416d23247349e76 extends Template
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
        if (($context["allowed_to_send_error_reports"] ?? null)) {
            // line 2
            yield "<p>
  ";
yield _gettext("This report automatically includes data about the error and information about relevant configuration settings. It will be sent to the phpMyAdmin team for debugging the error.");
            // line 6
            yield "</p>
<form action=\"";
            // line 7
            yield PhpMyAdmin\Url::getFromRoute("/error-report");
            yield "\" method=\"post\" id=\"errorReportForm\" class=\"ajax\">
  <div class=\"mb-3\">
    <label for=\"errorReportDescription\">
      <strong>
        ";
yield _gettext("Can you tell us the steps leading to this error? It decisively helps in debugging:");
            // line 12
            yield "      </strong>
    </label>
    <textarea class=\"form-control\" name=\"description\" id=\"errorReportDescription\"></textarea>
  </div>

  <div class=\"mb-3\">
    ";
yield _gettext("You may examine the data in the error report:");
            // line 19
            yield "    <pre class=\"pre-scrollable\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(json_encode(($context["report_data"] ?? null), (Twig\Extension\CoreExtension::constant("JSON_PRETTY_PRINT") | Twig\Extension\CoreExtension::constant("JSON_UNESCAPED_SLASHES"))), "html", null, true);
            yield "</pre>
  </div>

  <div class=\"form-check\">
    <input class=\"form-check-input\" type=\"checkbox\" name=\"always_send\" id=\"errorReportAlwaysSendCheckbox\">
    <label class=\"form-check-label\" for=\"errorReportAlwaysSendCheckbox\">
      ";
yield _gettext("Automatically send report next time");
            // line 26
            yield "    </label>
  </div>

  ";
            // line 29
            yield ($context["hidden_inputs"] ?? null);
            yield "
  ";
            // line 30
            yield ($context["hidden_fields"] ?? null);
            yield "
</form>
";
        } else {
            // line 33
            yield "<div class=\"mb-3\">
  <pre class=\"pre-scrollable\">";
            // line 34
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(json_encode(($context["report_data"] ?? null), (Twig\Extension\CoreExtension::constant("JSON_PRETTY_PRINT") | Twig\Extension\CoreExtension::constant("JSON_UNESCAPED_SLASHES"))), "html", null, true);
            yield "</pre>
</div>
";
        }
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "error/report_form.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  93 => 34,  90 => 33,  84 => 30,  80 => 29,  75 => 26,  64 => 19,  55 => 12,  47 => 7,  44 => 6,  40 => 2,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "error/report_form.twig", "/home/bb/public_html/phpmyadmin/templates/error/report_form.twig");
    }
}
