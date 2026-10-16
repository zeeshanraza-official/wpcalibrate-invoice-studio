<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Admin;

use WPCalibrate\InvoiceStudio\Customer\BusinessSnapshot;
use WPCalibrate\InvoiceStudio\Customer\CustomerSnapshot;
use WPCalibrate\InvoiceStudio\Invoice\CalculationEngine;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus;
use WPCalibrate\InvoiceStudio\Invoice\LineItem;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;
use WPCalibrate\InvoiceStudio\Template\InvoiceRenderer;
use WPCalibrate\InvoiceStudio\Template\TemplateManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles live invoice and template design preview generation.
 */
final class PreviewController
{
    private InvoiceRepository $repository;
    private InvoiceRenderer $renderer;
    private SettingsManager $settingsManager;
    private TemplateManager $templateManager;

    public function __construct(
        InvoiceRepository $repository,
        InvoiceRenderer $renderer,
        SettingsManager $settingsManager,
        TemplateManager $templateManager
    ) {
        $this->repository = $repository;
        $this->renderer = $renderer;
        $this->settingsManager = $settingsManager;
        $this->templateManager = $templateManager;
    }

    public function register(): void
    {
        add_action('wp_ajax_wpci_preview_invoice', [$this, 'handleAjaxPreview']);
        add_action('admin_post_wpci_preview_template', [$this, 'handleDirectPreview']);
    }

    /**
     * AJAX endpoint returning rendered invoice preview HTML or iframe payload.
     */
    public function handleAjaxPreview(): void
    {
        if (!current_user_can('wpci_view_invoices') && !current_user_can('wpci_manage_settings')) {
            wp_die(esc_html__('Unauthorized.', 'wpcalibrate-invoice-studio'), 403);
        }

        check_ajax_referer('wpci_admin_action_nonce', 'nonce');

        $uuid = isset($_REQUEST['uuid']) ? sanitize_text_field(wp_unslash($_REQUEST['uuid'])) : '';
        $templateId = isset($_REQUEST['template']) ? sanitize_key($_REQUEST['template']) : '';

        $invoice = null;

        // 1. If UUID is provided and valid, load from repository
        if (!empty($uuid)) {
            $invoice = $this->repository->findByUuid($uuid);
            if ($invoice && !empty($templateId) && $this->templateManager->isValidTemplate($templateId)) {
                $invoice->setTemplate($templateId);
            }
        }

        // 2. If POST contains live editor fields (previewing unsaved draft changes)
        if (!$invoice && isset($_POST['is_editor_preview']) && !empty($_POST['customer_name'])) {
            $invoice = $this->buildInvoiceFromPost($_POST);
        }

        // 3. Fallback: generate a realistic executive sample invoice using store settings
        if (!$invoice) {
            $invoice = $this->createSampleInvoice($templateId);
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo $this->renderer->render($invoice, 'browser'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Direct GET endpoint for preview in a separate window or iframe.
     */
    public function handleDirectPreview(): void
    {
        if (!current_user_can('wpci_view_invoices') && !current_user_can('wpci_manage_settings')) {
            wp_die(esc_html__('Unauthorized access to invoice preview.', 'wpcalibrate-invoice-studio'), 403);
        }

        $templateId = isset($_GET['template']) ? sanitize_key($_GET['template']) : '';
        $uuid = isset($_GET['uuid']) ? sanitize_text_field(wp_unslash($_GET['uuid'])) : '';

        $invoice = null;
        if (!empty($uuid)) {
            $invoice = $this->repository->findByUuid($uuid);
            if ($invoice && !empty($templateId) && $this->templateManager->isValidTemplate($templateId)) {
                $invoice->setTemplate($templateId);
            }
        }

        if (!$invoice) {
            $invoice = $this->createSampleInvoice($templateId);
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo $this->renderer->render($invoice, 'browser'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Build an executive-grade sample invoice populated with current store business profile.
     */
    public function createSampleInvoice(?string $requestedTemplate = null): InvoiceEntity
    {
        $bizSettings = $this->settingsManager->getBusiness();
        $templateSettings = $this->settingsManager->getTemplate();
        $defaults = $this->settingsManager->getDefaults();
        $general = $this->settingsManager->getGeneral();

        $templateId = (!empty($requestedTemplate) && $this->templateManager->isValidTemplate($requestedTemplate))
            ? $requestedTemplate
            : ($templateSettings['default_template'] ?? TemplateManager::MODERN);

        $logoUrl = !empty($bizSettings['logo_id']) ? (string)wp_get_attachment_url((int)$bizSettings['logo_id']) : '';

        $bizSnapshot = new BusinessSnapshot(
            !empty($bizSettings['business_name']) ? (string)$bizSettings['business_name'] : (get_bloginfo('name') ?: 'WPCalibrate Enterprise Ltd'),
            !empty($bizSettings['email']) ? (string)$bizSettings['email'] : (get_bloginfo('admin_email') ?: 'billing@wpcalibrate.com'),
            (string)($bizSettings['trading_name'] ?? 'WPCalibrate Solutions'),
            !empty($bizSettings['address']) ? (string)$bizSettings['address'] : "100 Innovation Boulevard, Suite 400\nLondon, EC2A 4NE, United Kingdom",
            !empty($bizSettings['phone']) ? (string)$bizSettings['phone'] : '+44 7474 795976',
            !empty($bizSettings['website']) ? (string)$bizSettings['website'] : 'https://wpcalibrate.com',
            (string)($bizSettings['tax_label'] ?? 'VAT'),
            !empty($bizSettings['tax_number']) ? (string)$bizSettings['tax_number'] : 'VAT Reg # GB 987 6543 21',
            (string)($bizSettings['company_number'] ?? '12345678'),
            $logoUrl,
            !empty($bizSettings['footer_text']) ? (string)$bizSettings['footer_text'] : 'Thank you for your business. Please contact billing@wpcalibrate.com for any inquiries.'
        );

        $customer = new CustomerSnapshot(
            'Eleanor Vance',
            'eleanor.vance@acmeindustries.com',
            'Acme Global Technologies Ltd',
            '+1 (555) 234-5678',
            "742 Evergreen Terrace, Floor 12\nSpringfield, IL 62704, United States",
            '',
            '',
            '',
            '',
            'US-987654321',
            null
        );

        $lineItems = [
            new LineItem(
                __('Enterprise Cloud Infrastructure Optimization & Security Audit', 'wpcalibrate-invoice-studio'),
                1.0,
                1450.00,
                'SRV-AUD-01',
                0.0,
                20.0,
                'VAT'
            ),
            new LineItem(
                __('Custom WordPress REST API & High-Performance Microservices Development', 'wpcalibrate-invoice-studio'),
                24.0,
                95.00,
                'DEV-REST-HR',
                120.00,
                20.0,
                'VAT'
            ),
            new LineItem(
                __('24/7 Priority SLA Monitoring & Managed Backup Service (Quarterly)', 'wpcalibrate-invoice-studio'),
                1.0,
                480.00,
                'SLA-QTR-PR',
                0.0,
                20.0,
                'VAT'
            ),
        ];

        $calc = CalculationEngine::calculate($lineItems, 50.00, 0.0, 0.0, 0.0, 0.0, 0.0);

        return new InvoiceEntity(
            null,
            wp_generate_uuid4(),
            'INV-' . gmdate('Y') . '-00842',
            InvoiceStatus::ISSUED,
            'manual',
            null,
            $customer,
            $bizSnapshot,
            gmdate('Y-m-d'),
            gmdate('Y-m-d', strtotime('+14 days')),
            null,
            (string)($general['currency'] ?? 'USD'),
            $lineItems,
            $calc['subtotal'],
            $calc['discount_total'],
            $calc['shipping_total'],
            $calc['fee_total'],
            $calc['tax_total'],
            $calc['tax_breakdown'],
            $calc['total'],
            $calc['amount_paid'],
            $calc['amount_due'],
            $templateId,
            (string)($defaults['default_payment_instructions'] ?? "Bank Transfer (BACS/ACH):\nBank Name: Barclays Commercial Bank\nAccount: 83749201 | Sort Code: 20-00-00\nIBAN: GB29BARC20000083749201 | Swift/BIC: BARCGB22"),
            (string)($defaults['default_notes'] ?? 'Prompt payment is appreciated. For queries, contact support@wpcalibrate.com.'),
            (string)($defaults['default_terms'] ?? "Payment is due within 14 calendar days of issue date. Late payments may incur standard statutory interest charges."),
            [],
            false
        );
    }

    /**
     * Build temporary invoice entity from POSTed form fields.
     */
    private function buildInvoiceFromPost(array $post): InvoiceEntity
    {
        $custSnapshot = new CustomerSnapshot(
            sanitize_text_field(wp_unslash($post['customer_name'] ?? '')),
            sanitize_email(wp_unslash($post['customer_email'] ?? '')),
            sanitize_text_field(wp_unslash($post['customer_company'] ?? '')),
            sanitize_text_field(wp_unslash($post['customer_phone'] ?? '')),
            sanitize_textarea_field(wp_unslash($post['customer_address'] ?? '')),
            '',
            '',
            '',
            '',
            sanitize_text_field(wp_unslash($post['customer_tax_id'] ?? '')),
            null
        );

        $bizSnapshot = BusinessSnapshot::fromArray($this->settingsManager->getBusiness());

        $rawItems = isset($post['line_items']) && is_array($post['line_items']) ? $post['line_items'] : [];
        $lineItems = [];
        foreach ($rawItems as $raw) {
            if (empty($raw['description'])) {
                continue;
            }
            $lineItems[] = new LineItem(
                sanitize_text_field($raw['description']),
                max(0.0001, (float)($raw['quantity'] ?? 1.0)),
                max(0.0, (float)($raw['unit_price'] ?? 0.0)),
                sanitize_text_field($raw['sku'] ?? ''),
                max(0.0, (float)($raw['discount'] ?? 0.0)),
                max(0.0, (float)($raw['tax_rate'] ?? 0.0))
            );
        }

        if (empty($lineItems)) {
            $lineItems[] = new LineItem(__('Sample Item', 'wpcalibrate-invoice-studio'), 1.0, 100.0);
        }

        $fixedDiscount = max(0.0, (float)($post['fixed_discount'] ?? 0.0));
        $shipping = max(0.0, (float)($post['shipping_amount'] ?? 0.0));
        $fee = max(0.0, (float)($post['fee_amount'] ?? 0.0));
        $amountPaid = max(0.0, (float)($post['amount_paid'] ?? 0.0));

        $calc = CalculationEngine::calculate($lineItems, $fixedDiscount, $shipping, 0.0, $fee, 0.0, $amountPaid);

        return new InvoiceEntity(
            null,
            wp_generate_uuid4(),
            sanitize_text_field(wp_unslash($post['invoice_number'] ?? 'DRAFT-PREVIEW')),
            InvoiceStatus::DRAFT,
            'manual',
            null,
            $custSnapshot,
            $bizSnapshot,
            sanitize_text_field(wp_unslash($post['issue_date'] ?? gmdate('Y-m-d'))),
            sanitize_text_field(wp_unslash($post['due_date'] ?? gmdate('Y-m-d'))),
            null,
            sanitize_text_field(wp_unslash($post['currency'] ?? 'USD')),
            $lineItems,
            $calc['subtotal'],
            $calc['discount_total'],
            $calc['shipping_total'],
            $calc['fee_total'],
            $calc['tax_total'],
            $calc['tax_breakdown'],
            $calc['total'],
            $calc['amount_paid'],
            $calc['amount_due'],
            sanitize_key($post['template'] ?? 'modern'),
            wp_kses_post(wp_unslash($post['payment_instructions'] ?? '')),
            sanitize_textarea_field(wp_unslash($post['customer_notes'] ?? '')),
            sanitize_textarea_field(wp_unslash($post['terms'] ?? '')),
            [],
            false
        );
    }
}
