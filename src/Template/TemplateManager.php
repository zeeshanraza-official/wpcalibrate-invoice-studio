<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Template;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages registered invoice templates and their metadata.
 */
final class TemplateManager
{
    public const CLASSIC = 'classic';
    public const MODERN  = 'modern';
    public const MINIMAL = 'minimal';

    /**
     * Get list of all available templates.
     */
    public function getTemplates(): array
    {
        return [
            self::CLASSIC => [
                'id'          => self::CLASSIC,
                'name'        => __('Classic', 'wpcalibrate-invoice-studio'),
                'description' => __('Traditional, structured corporate invoice with clear headers and formal borders.', 'wpcalibrate-invoice-studio'),
                'thumbnail'   => 'classic-preview.png',
            ],
            self::MODERN => [
                'id'          => self::MODERN,
                'name'        => __('Modern', 'wpcalibrate-invoice-studio'),
                'description' => __('Contemporary design featuring vibrant accents, clean typography, and balanced card layouts.', 'wpcalibrate-invoice-studio'),
                'thumbnail'   => 'modern-preview.png',
            ],
            self::MINIMAL => [
                'id'          => self::MINIMAL,
                'name'        => __('Minimal', 'wpcalibrate-invoice-studio'),
                'description' => __('Sleek, uncluttered monochromatic layout maximizing readability and fast printing.', 'wpcalibrate-invoice-studio'),
                'thumbnail'   => 'minimal-preview.png',
            ],
        ];
    }

    /**
     * Check if a template ID is valid.
     */
    public function isValidTemplate(string $templateId): bool
    {
        return array_key_exists($templateId, $this->getTemplates());
    }

    /**
     * Locate template PHP file safely without allowing arbitrary path traversal.
     */
    public function getTemplatePath(string $templateId): string
    {
        $id = $this->isValidTemplate($templateId) ? $templateId : self::CLASSIC;
        $path = WPCI_PLUGIN_DIR . 'templates/invoice/' . $id . '/invoice.php';

        /**
         * Allow filtering template path for developer extensions.
         */
        return apply_filters('wpci_invoice_template_path', $path, $id);
    }

    /**
     * Locate template CSS stylesheet safely.
     */
    public function getTemplateCss(string $templateId): string
    {
        $id = $this->isValidTemplate($templateId) ? $templateId : self::CLASSIC;
        $cssFile = WPCI_PLUGIN_DIR . 'templates/invoice/' . $id . '/style.css';

        if (file_exists($cssFile)) {
            return (string)file_get_contents($cssFile);
        }

        return '';
    }
}
