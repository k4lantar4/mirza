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

/* error/report_modal.twig */
class __TwigTemplate_e78c23c478216df3c26110a566a98705 extends Template
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
        yield "<div class=\"modal fade\" id=\"errorReportModal\" data-bs-backdrop=\"static\" data-bs-keyboard=\"false\" tabindex=\"-1\" aria-labelledby=\"errorReportModalLabel\" aria-hidden=\"true\">
  <div class=\"modal-dialog modal-dialog-scrollable modal-lg\">
    <div class=\"modal-content\">
      <div class=\"modal-header\">
        <h5 class=\"modal-title\" id=\"errorReportModalLabel\">";
yield _gettext("Submit error report");
        // line 5
        yield "</h5>
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\" aria-label=\"";
yield _gettext("Cancel");
        // line 6
        yield "\"></button>
      </div>
      <div class=\"modal-body\"></div>
      <div class=\"modal-footer\">
        ";
        // line 10
        if (($context["allowed_to_send_error_reports"] ?? null)) {
            // line 11
            yield "          <button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\">";
yield _gettext("Cancel");
            yield "</button>
          <button type=\"button\" class=\"btn btn-primary\" id=\"errorReportModalConfirm\">";
yield _gettext("Send error report");
            // line 12
            yield "</button>
        ";
        } else {
            // line 14
            yield "          <button type=\"button\" class=\"btn btn-primary\" data-bs-dismiss=\"modal\">";
yield _gettext("Close");
            yield "</button>
        ";
        }
        // line 16
        yield "      </div>
    </div>
  </div>
</div>";
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "error/report_modal.twig";
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
        return array (  73 => 16,  67 => 14,  63 => 12,  57 => 11,  55 => 10,  49 => 6,  45 => 5,  38 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "error/report_modal.twig", "/home/bb/public_html/phpmyadmin/templates/error/report_modal.twig");
    }
}
