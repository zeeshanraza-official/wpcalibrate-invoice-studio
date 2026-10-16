<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Admin;

use WPCalibrate\InvoiceStudio\Customer\BusinessSnapshot;
use WPCalibrate\InvoiceStudio\Customer\CustomerSnapshot;
use WPCalibrate\InvoiceStudio\Invoice\ActivityLogger;
use WPCalibrate\InvoiceStudio\Invoice\CalculationEngine;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus;
use WPCalibrate\InvoiceStudio\Invoice\LineItem;
use WPCalibrate\InvoiceStudio\Numbering\NumberingService;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;
use WPCalibrate\InvoiceStudio\Template\TemplateManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles creation and editing of manual invoices.
 */
final class InvoiceEditor
{
    private InvoiceRepository $repository;
    private NumberingService $numberingService;
    private SettingsManager $settingsManager;
    private TemplateManager $templateManager;

    public function __construct(
        InvoiceRepository $repository,
        NumberingService $numberingService,
        SettingsManager $settingsManager,
        TemplateManager $templateManager
    ) {
        $this->repository = $repository;
        $this->numberingService = $numberingService;
        $this->settingsManager = $settingsManager;
        $this->templateManager = $templateManager;
    }

    public function renderAdd(): void
    {
        $invoiceId = isset($_GET['invoice_id']) ? absint($_GET['invoice_id']) : 0;
        $invoice = null;

        if ($invoiceId > 0) {
            $invoice = $this->repository->findById($invoiceId);
        }

        if (!$invoice) {
            // New Draft Invoice
            $general = $this->settingsManager->getGeneral();
            $templateSettings = $this->settingsManager->getTemplate();
            $defaults = $this->settingsManager->getDefaults();

            $now = gmdate('Y-m-d');
            $dueDays = (int)($general['default_due_days'] ?? 14);
            $dueDate = gmdate('Y-m-d', strtotime("+{$dueDays} days"));

            $bizSnapshot = BusinessSnapshot::fromArray($this->settingsManager->getBusiness());

            $defaultLineItem = new LineItem(
                __('Item Description', 'wpcalibrate-invoice-studio'),
                1.0,
                0.0,
                '',
                0.0,
                (float)($defaults['default_tax_rate'] ?? 0.0),
                (string)($defaults['default_tax_label'] ?? 'VAT')
            );

            $invoice = new InvoiceEntity(
                null,
                wp_generate_uuid4(),
                '',
                InvoiceStatus::DRAFT,
                'manual',
                null,
                new CustomerSnapshot('', ''),
                $bizSnapshot,
                $now,
                $dueDate,
                null,
                (string)($general['currency'] ?? 'USD'),
                [$defaultLineItem],
                0.0,
                0.0,
                0.0,
                0.0,
                0.0,
                [],
                0.0,
                0.0,
                0.0,
                (string)($templateSettings['default_template'] ?? 'classic'),
                (string)($defaults['default_payment_instructions'] ?? ''),
                (string)($defaults['default_notes'] ?? ''),
                (string)($defaults['default_terms'] ?? ''),
                [],
                (bool)($this->settingsManager->getPayment()['allow_online_payment_default'] ?? false)
            );
        }

        $this->renderForm($invoice);
    }

    private function renderForm(InvoiceEntity $invoice): void
    {
        if (!current_user_can('wpci_create_invoices') && !current_user_can('wpci_edit_invoices')) {
            wp_die(esc_html__('You do not have permission to edit invoices.', 'wpcalibrate-invoice-studio'));
        }

        $isDraft = ($invoice->getStatus() === InvoiceStatus::DRAFT);
        $isPaid = ($invoice->getStatus() === InvoiceStatus::PAID);
        $templates = $this->templateManager->getTemplates();
        $brandIconUrl = plugins_url('branding/icon-dark.png', WPCI_PLUGIN_FILE);

        // Fetch registered customers for easy selection
        $users = get_users(['number' => 100, 'orderby' => 'display_name']);
        ?>
        <div class="wrap wpci-wrap">
            <div class="wpci-header">
                <div class="wpci-header-left">
                    <div class="wpci-brand-badge">
                        <img src="<?php echo esc_url($brandIconUrl); ?>" alt="WPCalibrate" />
                    </div>
                    <div>
                        <h1 class="wpci-header-title">
                            <?php echo $invoice->getId() ? sprintf(esc_html__('Edit Invoice %s', 'wpcalibrate-invoice-studio'), esc_html($invoice->getNumber() ?: __('(Draft)', 'wpcalibrate-invoice-studio'))) : esc_html__('Create New Invoice', 'wpcalibrate-invoice-studio'); ?>
                            <span class="wpci-version-tag"><?php echo esc_html(InvoiceStatus::getLabel($invoice->getStatus())); ?></span>
                        </h1>
                        <div class="wpci-header-subtitle">
                            <?php esc_html_e('Fill in the customer and line item details below to generate an invoice.', 'wpcalibrate-invoice-studio'); ?>
                        </div>
                    </div>
                </div>
                <div class="wpci-header-right">
                    <button type="button" class="button wpci-preview-editor-btn" data-uuid="<?php echo esc_attr($invoice->getUuid()); ?>">
                        <span class="dashicons dashicons-visibility" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                        <?php esc_html_e('Preview Design', 'wpcalibrate-invoice-studio'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . AdminMenu::INVOICES_SLUG)); ?>" class="button">
                        &lsaquo; <?php esc_html_e('Back to Invoices', 'wpcalibrate-invoice-studio'); ?>
                    </a>
                </div>
            </div>

            <?php settings_errors('wpci_editor_notices'); ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wpci-invoice-form">
                <input type="hidden" name="action" value="wpci_save_invoice" />
                <input type="hidden" name="invoice_id" value="<?php echo esc_attr((string)($invoice->getId() ?? 0)); ?>" />
                <?php wp_nonce_field('wpci_save_invoice_action', 'wpci_editor_nonce'); ?>

                <div class="wpci-editor-container">
                    <!-- Left Column: Primary Details & Line Items -->
                    <div class="wpci-editor-main">
                        <!-- Customer Card -->
                        <div class="wpci-card">
                            <h2 class="wpci-card-title"><?php esc_html_e('Customer Information', 'wpcalibrate-invoice-studio'); ?></h2>
                            <div class="wpci-form-group">
                                <label for="wpci_customer_user_id"><?php esc_html_e('Select Existing Customer (Optional):', 'wpcalibrate-invoice-studio'); ?></label>
                                <select name="customer_user_id" id="wpci_customer_user_id" class="widefat">
                                    <option value=""><?php esc_html_e('-- Guest / New Customer --', 'wpcalibrate-invoice-studio'); ?></option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?php echo esc_attr((string)$u->ID); ?>"
                                                data-name="<?php echo esc_attr($u->display_name); ?>"
                                                data-email="<?php echo esc_attr($u->user_email); ?>"
                                                <?php selected($invoice->getCustomer()->getUserId(), $u->ID); ?>>
                                            <?php echo esc_html($u->display_name . ' (' . $u->user_email . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="wpci-form-row">
                                <div class="wpci-form-group">
                                    <label for="wpci_customer_name"><?php esc_html_e('Customer / Contact Name *', 'wpcalibrate-invoice-studio'); ?></label>
                                    <input type="text" name="customer_name" id="wpci_customer_name" value="<?php echo esc_attr($invoice->getCustomer()->getName()); ?>" required />
                                </div>
                                <div class="wpci-form-group">
                                    <label for="wpci_customer_email"><?php esc_html_e('Customer Email *', 'wpcalibrate-invoice-studio'); ?></label>
                                    <input type="email" name="customer_email" id="wpci_customer_email" value="<?php echo esc_attr($invoice->getCustomer()->getEmail()); ?>" required />
                                </div>
                            </div>

                            <div class="wpci-form-row">
                                <div class="wpci-form-group">
                                    <label for="wpci_customer_company"><?php esc_html_e('Company / Organization', 'wpcalibrate-invoice-studio'); ?></label>
                                    <input type="text" name="customer_company" id="wpci_customer_company" value="<?php echo esc_attr($invoice->getCustomer()->getCompany()); ?>" />
                                </div>
                                <div class="wpci-form-group">
                                    <label for="wpci_customer_phone"><?php esc_html_e('Phone Number', 'wpcalibrate-invoice-studio'); ?></label>
                                    <input type="text" name="customer_phone" id="wpci_customer_phone" value="<?php echo esc_attr($invoice->getCustomer()->getPhone()); ?>" />
                                </div>
                            </div>

                            <div class="wpci-form-row">
                                <div class="wpci-form-group">
                                    <label for="wpci_customer_address"><?php esc_html_e('Billing Address', 'wpcalibrate-invoice-studio'); ?></label>
                                    <textarea name="customer_address" id="wpci_customer_address" rows="2"><?php echo esc_textarea($invoice->getCustomer()->getAddress()); ?></textarea>
                                </div>
                                <div class="wpci-form-group">
                                    <label for="wpci_customer_tax_id"><?php esc_html_e('Tax ID / VAT Number', 'wpcalibrate-invoice-studio'); ?></label>
                                    <input type="text" name="customer_tax_id" id="wpci_customer_tax_id" value="<?php echo esc_attr($invoice->getCustomer()->getTaxId()); ?>" />
                                </div>
                            </div>
                        </div>

                        <!-- Line Items Card -->
                        <div class="wpci-card">
                            <h2 class="wpci-card-title"><?php esc_html_e('Line Items', 'wpcalibrate-invoice-studio'); ?></h2>
                            <table class="wpci-items-editor-table" id="wpci-items-table">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;"><?php esc_html_e('Description *', 'wpcalibrate-invoice-studio'); ?></th>
                                        <th style="width: 12%;"><?php esc_html_e('SKU', 'wpcalibrate-invoice-studio'); ?></th>
                                        <th style="width: 10%;"><?php esc_html_e('Qty *', 'wpcalibrate-invoice-studio'); ?></th>
                                        <th style="width: 13%;"><?php esc_html_e('Unit Price *', 'wpcalibrate-invoice-studio'); ?></th>
                                        <th style="width: 10%;"><?php esc_html_e('Disc ($)', 'wpcalibrate-invoice-studio'); ?></th>
                                        <th style="width: 10%;"><?php esc_html_e('Tax (%)', 'wpcalibrate-invoice-studio'); ?></th>
                                        <th style="width: 12%; text-align: right;"><?php esc_html_e('Total', 'wpcalibrate-invoice-studio'); ?></th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoice->getLineItems() as $idx => $item): ?>
                                        <tr class="wpci-item-row" data-index="<?php echo esc_attr((string)$idx); ?>">
                                            <td>
                                                <input type="text" name="line_items[<?php echo esc_attr((string)$idx); ?>][description]" value="<?php echo esc_attr($item->getDescription()); ?>" required />
                                            </td>
                                            <td>
                                                <input type="text" name="line_items[<?php echo esc_attr((string)$idx); ?>][sku]" value="<?php echo esc_attr($item->getSku()); ?>" style="width: 80px;" />
                                            </td>
                                            <td>
                                                <input type="number" step="any" min="0.0001" name="line_items[<?php echo esc_attr((string)$idx); ?>][quantity]" value="<?php echo esc_attr((string)$item->getQuantity()); ?>" class="wpci-calc-trigger wpci-qty" style="width: 65px;" required />
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="line_items[<?php echo esc_attr((string)$idx); ?>][unit_price]" value="<?php echo esc_attr(number_format($item->getUnitPrice(), 2, '.', '')); ?>" class="wpci-calc-trigger wpci-price" style="width: 85px;" required />
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="line_items[<?php echo esc_attr((string)$idx); ?>][discount]" value="<?php echo esc_attr(number_format($item->getDiscount(), 2, '.', '')); ?>" class="wpci-calc-trigger wpci-discount" style="width: 65px;" />
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="100" name="line_items[<?php echo esc_attr((string)$idx); ?>][tax_rate]" value="<?php echo esc_attr((string)$item->getTaxRate()); ?>" class="wpci-calc-trigger wpci-tax-rate" style="width: 60px;" />
                                            </td>
                                            <td class="wpci-row-total" style="font-weight: 600; text-align: right; padding-top: 12px;">
                                                $<?php echo esc_html(number_format($item->getTotal(), 2)); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <button type="button" class="wpci-row-actions-btn wpci-remove-row" title="<?php esc_attr_e('Remove Item', 'wpcalibrate-invoice-studio'); ?>">&times;</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" class="button" id="wpci-add-item-row">
                                + <?php esc_html_e('Add Line Item', 'wpcalibrate-invoice-studio'); ?>
                            </button>
                        </div>

                        <!-- Notes & Terms -->
                        <div class="wpci-card">
                            <h2 class="wpci-card-title"><?php esc_html_e('Payment Instructions & Notes', 'wpcalibrate-invoice-studio'); ?></h2>
                            <div class="wpci-form-group">
                                <label for="wpci_payment_instructions"><?php esc_html_e('Payment Instructions (Bank details, wire info, etc.):', 'wpcalibrate-invoice-studio'); ?></label>
                                <textarea name="payment_instructions" id="wpci_payment_instructions" rows="3"><?php echo esc_textarea($invoice->getPaymentInstructions()); ?></textarea>
                            </div>
                            <div class="wpci-form-group">
                                <label for="wpci_customer_notes"><?php esc_html_e('Customer Notes:', 'wpcalibrate-invoice-studio'); ?></label>
                                <textarea name="customer_notes" id="wpci_customer_notes" rows="2"><?php echo esc_textarea($invoice->getCustomerNotes()); ?></textarea>
                            </div>
                            <div class="wpci-form-group">
                                <label for="wpci_terms"><?php esc_html_e('Terms & Conditions:', 'wpcalibrate-invoice-studio'); ?></label>
                                <textarea name="terms" id="wpci_terms" rows="2"><?php echo esc_textarea($invoice->getTerms()); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Settings & Totals Summary -->
                    <div class="wpci-editor-sidebar">
                        <!-- Invoice Actions Card -->
                        <div class="wpci-card">
                            <h2 class="wpci-card-title"><?php esc_html_e('Actions', 'wpcalibrate-invoice-studio'); ?></h2>
                            <div class="wpci-form-group">
                                <strong><?php esc_html_e('Status:', 'wpcalibrate-invoice-studio'); ?></strong>
                                <span class="wpci-status-badge <?php echo esc_attr(InvoiceStatus::getBadgeClass($invoice->getStatus())); ?>" style="margin-top: 5px;">
                                    <?php echo esc_html(InvoiceStatus::getLabel($invoice->getStatus())); ?>
                                </span>
                            </div>

                            <?php if ($invoice->getNumber()): ?>
                                <div class="wpci-form-group">
                                    <strong><?php esc_html_e('Invoice #:', 'wpcalibrate-invoice-studio'); ?></strong>
                                    <span><?php echo esc_html($invoice->getNumber()); ?></span>
                                </div>
                            <?php endif; ?>

                            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 15px;">
                                <button type="submit" name="wpci_save_action" value="save_draft" class="button button-large">
                                    <?php esc_html_e('Save Draft', 'wpcalibrate-invoice-studio'); ?>
                                </button>

                                <?php if ($isDraft): ?>
                                    <button type="submit" name="wpci_save_action" value="issue_invoice" class="button button-primary button-large" onclick="return confirm('<?php esc_attr_e('Issuing this invoice will allocate an official sequential invoice number. Proceed?', 'wpcalibrate-invoice-studio'); ?>');">
                                        <?php esc_html_e('Issue Invoice', 'wpcalibrate-invoice-studio'); ?>
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="button button-secondary wpci-preview-editor-btn" data-uuid="<?php echo esc_attr($invoice->getUuid()); ?>">
                                    <span class="dashicons dashicons-visibility" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                                    <?php esc_html_e('Preview Invoice Design', 'wpcalibrate-invoice-studio'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Dates & Template -->
                        <div class="wpci-card">
                            <h2 class="wpci-card-title"><?php esc_html_e('Invoice Details', 'wpcalibrate-invoice-studio'); ?></h2>
                            <div class="wpci-form-group">
                                <label for="wpci_issue_date"><?php esc_html_e('Issue Date:', 'wpcalibrate-invoice-studio'); ?></label>
                                <input type="date" name="issue_date" id="wpci_issue_date" value="<?php echo esc_attr($invoice->getIssueDate()); ?>" required />
                            </div>
                            <div class="wpci-form-group">
                                <label for="wpci_due_date"><?php esc_html_e('Due Date:', 'wpcalibrate-invoice-studio'); ?></label>
                                <input type="date" name="due_date" id="wpci_due_date" value="<?php echo esc_attr($invoice->getDueDate()); ?>" required />
                            </div>
                            <div class="wpci-form-group">
                                <label for="wpci_currency"><?php esc_html_e('Currency:', 'wpcalibrate-invoice-studio'); ?></label>
                                <input type="text" name="currency" id="wpci_currency" value="<?php echo esc_attr($invoice->getCurrency()); ?>" style="text-transform: uppercase;" required />
                            </div>
                            <div class="wpci-form-group">
                                <label for="wpci_template"><?php esc_html_e('Template:', 'wpcalibrate-invoice-studio'); ?></label>
                                <select name="template" id="wpci_template" class="widefat">
                                    <?php foreach ($templates as $tKey => $t): ?>
                                        <option value="<?php echo esc_attr($tKey); ?>" <?php selected($invoice->getTemplate(), $tKey); ?>>
                                            <?php echo esc_html($t['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (class_exists('WooCommerce')): ?>
                                <div class="wpci-form-group">
                                    <label>
                                        <input type="checkbox" name="allow_online_payment" value="1" <?php checked($invoice->allowOnlinePayment()); ?> />
                                        <?php esc_html_e('Allow Online Payment via WooCommerce', 'wpcalibrate-invoice-studio'); ?>
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Summary & Totals -->
                        <div class="wpci-card">
                            <h2 class="wpci-card-title"><?php esc_html_e('Invoice Totals', 'wpcalibrate-invoice-studio'); ?></h2>
                            <table class="wpci-summary-table">
                                <tr>
                                    <th><?php esc_html_e('Subtotal:', 'wpcalibrate-invoice-studio'); ?></th>
                                    <td id="wpci-summary-subtotal">$<?php echo esc_html(number_format($invoice->getSubtotal(), 2)); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Fixed Discount ($):', 'wpcalibrate-invoice-studio'); ?></th>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="fixed_discount" id="wpci_fixed_discount" value="0.00" style="width: 80px; text-align: right;" />
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Shipping ($):', 'wpcalibrate-invoice-studio'); ?></th>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="shipping_amount" id="wpci_shipping_amount" value="<?php echo esc_attr((string)$invoice->getShippingTotal()); ?>" style="width: 80px; text-align: right;" />
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Fee ($):', 'wpcalibrate-invoice-studio'); ?></th>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="fee_amount" id="wpci_fee_amount" value="<?php echo esc_attr((string)$invoice->getFeeTotal()); ?>" style="width: 80px; text-align: right;" />
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Total Tax:', 'wpcalibrate-invoice-studio'); ?></th>
                                    <td id="wpci-summary-tax">$<?php echo esc_html(number_format($invoice->getTaxTotal(), 2)); ?></td>
                                </tr>
                                <tr>
                                    <th class="wpci-summary-total"><?php esc_html_e('Grand Total:', 'wpcalibrate-invoice-studio'); ?></th>
                                    <td class="wpci-summary-total" id="wpci-summary-total">$<?php echo esc_html(number_format($invoice->getTotal(), 2)); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e('Amount Paid:', 'wpcalibrate-invoice-studio'); ?></th>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="amount_paid" id="wpci_amount_paid" value="<?php echo esc_attr((string)$invoice->getAmountPaid()); ?>" style="width: 80px; text-align: right;" />
                                    </td>
                                </tr>
                                <tr>
                                    <th style="font-weight: bold; color: #b91c1c;"><?php esc_html_e('Amount Due:', 'wpcalibrate-invoice-studio'); ?></th>
                                    <td style="font-weight: bold; color: #b91c1c;" id="wpci-summary-due">$<?php echo esc_html(number_format($invoice->getAmountDue(), 2)); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Handle submission of invoice form.
     */
    public function handleSave(): void
    {
        if (!current_user_can('wpci_create_invoices') && !current_user_can('wpci_edit_invoices')) {
            wp_die(esc_html__('Unauthorized.', 'wpcalibrate-invoice-studio'), 403);
        }

        check_admin_referer('wpci_save_invoice_action', 'wpci_editor_nonce');

        $invoiceId = isset($_POST['invoice_id']) ? absint($_POST['invoice_id']) : 0;
        $action = isset($_POST['wpci_save_action']) ? sanitize_key($_POST['wpci_save_action']) : 'save_draft';

        $invoice = null;
        if ($invoiceId > 0) {
            $invoice = $this->repository->findById($invoiceId);
        }

        // Build Customer Snapshot
        $custSnapshot = new CustomerSnapshot(
            sanitize_text_field(wp_unslash($_POST['customer_name'] ?? '')),
            sanitize_email(wp_unslash($_POST['customer_email'] ?? '')),
            sanitize_text_field(wp_unslash($_POST['customer_company'] ?? '')),
            sanitize_text_field(wp_unslash($_POST['customer_phone'] ?? '')),
            sanitize_textarea_field(wp_unslash($_POST['customer_address'] ?? '')),
            '',
            '',
            '',
            '',
            sanitize_text_field(wp_unslash($_POST['customer_tax_id'] ?? '')),
            !empty($_POST['customer_user_id']) ? absint($_POST['customer_user_id']) : null
        );

        // Build Line Items
        $rawItems = isset($_POST['line_items']) && is_array($_POST['line_items']) ? $_POST['line_items'] : [];
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
            $lineItems[] = new LineItem(__('Services rendered', 'wpcalibrate-invoice-studio'), 1.0, 0.0);
        }

        // Server-Side Monetary Calculation
        $fixedDiscount = max(0.0, (float)($_POST['fixed_discount'] ?? 0.0));
        $shipping = max(0.0, (float)($_POST['shipping_amount'] ?? 0.0));
        $fee = max(0.0, (float)($_POST['fee_amount'] ?? 0.0));
        $amountPaid = max(0.0, (float)($_POST['amount_paid'] ?? 0.0));

        $calc = CalculationEngine::calculate($lineItems, $fixedDiscount, $shipping, 0.0, $fee, 0.0, $amountPaid);

        $issueDate = sanitize_text_field(wp_unslash($_POST['issue_date'] ?? gmdate('Y-m-d')));
        $dueDate   = sanitize_text_field(wp_unslash($_POST['due_date'] ?? gmdate('Y-m-d', strtotime('+14 days'))));
        $currency  = sanitize_text_field(wp_unslash($_POST['currency'] ?? 'USD'));
        $template  = sanitize_key($_POST['template'] ?? 'classic');
        $allowOnlinePay = !empty($_POST['allow_online_payment']);

        $paymentInstructions = wp_kses_post(wp_unslash($_POST['payment_instructions'] ?? ''));
        $customerNotes = sanitize_textarea_field(wp_unslash($_POST['customer_notes'] ?? ''));
        $terms = sanitize_textarea_field(wp_unslash($_POST['terms'] ?? ''));

        if (!$invoice) {
            $bizSnapshot = BusinessSnapshot::fromArray($this->settingsManager->getBusiness());
            $invoice = new InvoiceEntity(
                null,
                wp_generate_uuid4(),
                '',
                InvoiceStatus::DRAFT,
                'manual',
                null,
                $custSnapshot,
                $bizSnapshot,
                $issueDate,
                $dueDate,
                null,
                $currency,
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
                $template,
                $paymentInstructions,
                $customerNotes,
                $terms,
                [],
                $allowOnlinePay
            );
        } else {
            // Updating existing invoice
            $invoice->setCustomer($custSnapshot);
            $invoice->setIssueDate($issueDate);
            $invoice->setDueDate($dueDate);
            $invoice->setCurrency($currency);
            $invoice->setTemplate($template);
            $invoice->setLineItems($lineItems);
            $invoice->setSubtotal($calc['subtotal']);
            $invoice->setDiscountTotal($calc['discount_total']);
            $invoice->setShippingTotal($calc['shipping_total']);
            $invoice->setFeeTotal($calc['fee_total']);
            $invoice->setTaxTotal($calc['tax_total']);
            $invoice->setTaxBreakdown($calc['tax_breakdown']);
            $invoice->setTotal($calc['total']);
            $invoice->setAmountPaid($calc['amount_paid']);
            $invoice->setAmountDue($calc['amount_due']);
            $invoice->setPaymentInstructions($paymentInstructions);
            $invoice->setCustomerNotes($customerNotes);
            $invoice->setTerms($terms);
            $invoice->setAllowOnlinePayment($allowOnlinePay);

            if ($invoice->isFinalized()) {
                ActivityLogger::log($invoice->getId(), 'edited', __('Issued invoice details updated by administrator.', 'wpcalibrate-invoice-studio'));
            }
        }

        // If issuing invoice, allocate unique sequential number
        if ($action === 'issue_invoice') {
            if (!$invoice->getNumber()) {
                $number = $this->numberingService->generateNextNumber();
                $invoice->setNumber($number);
            }
            $invoice->setStatus(InvoiceStatus::ISSUED);
        }

        $savedId = $this->repository->save($invoice);

        if ($action === 'issue_invoice') {
            ActivityLogger::log($savedId, 'issued', sprintf(
                /* translators: %s: invoice number */
                __('Invoice #%s officially issued.', 'wpcalibrate-invoice-studio'),
                $invoice->getNumber()
            ));
        }

        $redirectUrl = add_query_arg([
            'page'       => AdminMenu::INVOICES_SLUG,
            'wpci_saved' => 1,
        ], admin_url('admin.php'));

        wp_safe_redirect($redirectUrl);
        exit;
    }
}
