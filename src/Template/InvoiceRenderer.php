<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Template;

use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders an invoice entity into clean, print-ready, and PDF-compatible HTML.
 */
final class InvoiceRenderer
{
    private TemplateManager $templateManager;
    private SettingsManager $settingsManager;

    public function __construct(TemplateManager $templateManager, SettingsManager $settingsManager)
    {
        $this->templateManager = $templateManager;
        $this->settingsManager = $settingsManager;
    }

    /**
     * Render complete HTML document for an invoice.
     *
     * @param InvoiceEntity $invoice
     * @param string        $context 'browser' | 'pdf' | 'print'
     * @return string Full HTML string
     */
    public function render(InvoiceEntity $invoice, string $context = 'browser'): string
    {
        $templateId = $invoice->getTemplate() ?: $this->settingsManager->getTemplate()['default_template'] ?? TemplateManager::CLASSIC;
        $templatePath = $this->templateManager->getTemplatePath($templateId);
        $templateCss = $this->templateManager->getTemplateCss($templateId);

        $templateSettings = $this->settingsManager->getTemplate();
        $primaryColor = $templateSettings['primary_color'] ?? '#2b4c7e';
        $accentColor = $templateSettings['accent_color'] ?? '#1f385c';
        $customCss = $templateSettings['custom_css'] ?? '';

        $pdfSettings = $this->settingsManager->getPdf();
        $isRtl = !empty($pdfSettings['enable_rtl']) || is_rtl();

        $currency = $invoice->getCurrency();
        $formatter = fn(float $amount) => $this->formatPrice($amount, $currency);

        ob_start();
        do_action('wpci_before_invoice_template', $invoice, $context);

        // Include isolated template file
        if (file_exists($templatePath)) {
            include $templatePath;
        } else {
            echo '<div class="wpci-error">' . esc_html__('Invoice template not found.', 'wpcalibrate-invoice-studio') . '</div>';
        }

        do_action('wpci_after_invoice_template', $invoice, $context);
        $bodyContent = ob_get_clean();

        // Wrap in full HTML document
        return $this->wrapDocument($bodyContent, $invoice, $templateCss, $primaryColor, $accentColor, $customCss, $isRtl, $context);
    }

    /**
     * Format a price with currency symbol and position.
     */
    public function formatPrice(float $amount, string $currency = 'USD'): string
    {
        // If WooCommerce is active, we can leverage wc_price for perfect store styling
        if (function_exists('wc_price') && !is_admin()) {
            return wp_strip_all_tags(wc_price($amount, ['currency' => $currency]));
        }

        $general = $this->settingsManager->getGeneral();
        $symbol = $general['currency_symbol'] ?? '$';
        $pos = $general['currency_position'] ?? 'left';

        $formatted = number_format($amount, 2, '.', ',');

        return match ($pos) {
            'right'       => $formatted . $symbol,
            'left_space'  => $symbol . ' ' . $formatted,
            'right_space' => $formatted . ' ' . $symbol,
            default       => $symbol . $formatted,
        };
    }

    private function wrapDocument(
        string $body,
        InvoiceEntity $invoice,
        string $css,
        string $primaryColor,
        string $accentColor,
        string $customCss,
        bool $isRtl,
        string $context
    ): string {
        $direction = $isRtl ? 'rtl' : 'ltr';
        $lang = get_bloginfo('language');
        $title = esc_html($invoice->getNumber() ?: __('Invoice', 'wpcalibrate-invoice-studio'));

        $printAutoTrigger = ($context === 'print') ? '<script>window.addEventListener("DOMContentLoaded", () => window.print());</script>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}" dir="{$direction}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        :root {
            --wpci-primary: {$primaryColor};
            --wpci-accent: {$accentColor};
        }
        {$css}
        {$customCss}
    </style>
</head>
<body class="wpci-invoice-body wpci-context-{$context}">
    {$body}
    {$printAutoTrigger}
</body>
</html>
HTML;
    }
}
