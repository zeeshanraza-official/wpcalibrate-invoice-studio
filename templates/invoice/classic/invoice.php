<?php
/**
 * Classic Invoice Template
 *
 * @var \WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity $invoice
 * @var callable $formatter
 * @var string $context
 */
if (!defined('ABSPATH')) {
    exit;
}

$business = $invoice->getBusiness();
$customer = $invoice->getCustomer();
$lineItems = $invoice->getLineItems();
$status = $invoice->getStatus();
?>
<div class="wpci-invoice-wrap wpci-template-classic">
    <!-- Header -->
    <table class="wpci-header-table">
        <tr>
            <td class="wpci-header-brand">
                <?php if ($business->getLogoUrl()): ?>
                    <img src="<?php echo esc_url($business->getLogoUrl()); ?>" alt="<?php echo esc_attr($business->getName()); ?>" class="wpci-logo" />
                <?php else: ?>
                    <h1 class="wpci-business-title"><?php echo esc_html($business->getName() ?: get_bloginfo('name')); ?></h1>
                <?php endif; ?>
                <?php if ($business->getTradingName()): ?>
                    <div class="wpci-trading-name"><?php echo esc_html($business->getTradingName()); ?></div>
                <?php endif; ?>
                <div class="wpci-business-details">
                    <?php if ($business->getAddress()): ?>
                        <div><?php echo nl2br(esc_html($business->getAddress())); ?></div>
                    <?php endif; ?>
                    <?php if ($business->getEmail()): ?>
                        <div><?php echo esc_html($business->getEmail()); ?></div>
                    <?php endif; ?>
                    <?php if ($business->getPhone()): ?>
                        <div><?php echo esc_html($business->getPhone()); ?></div>
                    <?php endif; ?>
                    <?php if ($business->getTaxNumber()): ?>
                        <div><strong><?php echo esc_html($business->getTaxLabel() ?: 'VAT'); ?>:</strong> <?php echo esc_html($business->getTaxNumber()); ?></div>
                    <?php endif; ?>
                    <?php if ($business->getCompanyNumber()): ?>
                        <div><strong><?php esc_html_e('Reg #:', 'wpcalibrate-invoice-studio'); ?></strong> <?php echo esc_html($business->getCompanyNumber()); ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="wpci-header-meta">
                <h2 class="wpci-invoice-heading"><?php esc_html_e('INVOICE', 'wpcalibrate-invoice-studio'); ?></h2>
                <div class="wpci-meta-box">
                    <table class="wpci-meta-table">
                        <tr>
                            <th><?php esc_html_e('Invoice No:', 'wpcalibrate-invoice-studio'); ?></th>
                            <td><strong><?php echo esc_html($invoice->getNumber() ?: __('Draft', 'wpcalibrate-invoice-studio')); ?></strong></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Date:', 'wpcalibrate-invoice-studio'); ?></th>
                            <td><?php echo esc_html($invoice->getIssueDate()); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Due Date:', 'wpcalibrate-invoice-studio'); ?></th>
                            <td><?php echo esc_html($invoice->getDueDate()); ?></td>
                        </tr>
                        <?php if ($invoice->getOrderId()): ?>
                            <tr>
                                <th><?php esc_html_e('Order Ref:', 'wpcalibrate-invoice-studio'); ?></th>
                                <td>#<?php echo esc_html((string)$invoice->getOrderId()); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th><?php esc_html_e('Status:', 'wpcalibrate-invoice-studio'); ?></th>
                            <td>
                                <span class="wpci-status-badge <?php echo esc_attr(\WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus::getBadgeClass($status)); ?>">
                                    <?php echo esc_html(\WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus::getLabel($status)); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Billing Parties -->
    <table class="wpci-parties-table">
        <tr>
            <td class="wpci-bill-to">
                <div class="wpci-section-title"><?php esc_html_e('Billed To:', 'wpcalibrate-invoice-studio'); ?></div>
                <div class="wpci-party-content">
                    <strong><?php echo esc_html($customer->getName()); ?></strong>
                    <?php if ($customer->getCompany()): ?>
                        <div><?php echo esc_html($customer->getCompany()); ?></div>
                    <?php endif; ?>
                    <?php if ($customer->getAddress()): ?>
                        <div><?php echo nl2br(esc_html($customer->getFormattedAddress())); ?></div>
                    <?php endif; ?>
                    <?php if ($customer->getEmail()): ?>
                        <div><?php echo esc_html($customer->getEmail()); ?></div>
                    <?php endif; ?>
                    <?php if ($customer->getPhone()): ?>
                        <div><?php echo esc_html($customer->getPhone()); ?></div>
                    <?php endif; ?>
                    <?php if ($customer->getTaxId()): ?>
                        <div><strong><?php esc_html_e('Tax ID:', 'wpcalibrate-invoice-studio'); ?></strong> <?php echo esc_html($customer->getTaxId()); ?></div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="wpci-items-table">
        <thead>
            <tr>
                <th class="col-desc"><?php esc_html_e('Description', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-sku"><?php esc_html_e('SKU', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-qty"><?php esc_html_e('Qty', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-price"><?php esc_html_e('Unit Price', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-tax"><?php esc_html_e('Tax', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-total"><?php esc_html_e('Total', 'wpcalibrate-invoice-studio'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lineItems as $item): ?>
                <tr>
                    <td class="col-desc">
                        <strong><?php echo esc_html($item->getDescription()); ?></strong>
                    </td>
                    <td class="col-sku"><?php echo esc_html($item->getSku() ?: '-'); ?></td>
                    <td class="col-qty"><?php echo esc_html((string)$item->getQuantity()); ?></td>
                    <td class="col-price"><?php echo esc_html($formatter($item->getUnitPrice())); ?></td>
                    <td class="col-tax"><?php echo esc_html($formatter($item->getTax())); ?></td>
                    <td class="col-total"><?php echo esc_html($formatter($item->getTotal())); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals Area -->
    <div class="wpci-bottom-grid">
        <div class="wpci-notes-col">
            <?php if ($invoice->getPaymentInstructions()): ?>
                <div class="wpci-block">
                    <div class="wpci-block-title"><?php esc_html_e('Payment Instructions', 'wpcalibrate-invoice-studio'); ?></div>
                    <div class="wpci-block-body"><?php echo nl2br(wp_kses_post($invoice->getPaymentInstructions())); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($invoice->getCustomerNotes()): ?>
                <div class="wpci-block">
                    <div class="wpci-block-title"><?php esc_html_e('Notes', 'wpcalibrate-invoice-studio'); ?></div>
                    <div class="wpci-block-body"><?php echo nl2br(esc_html($invoice->getCustomerNotes())); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($invoice->getTerms()): ?>
                <div class="wpci-block">
                    <div class="wpci-block-title"><?php esc_html_e('Terms & Conditions', 'wpcalibrate-invoice-studio'); ?></div>
                    <div class="wpci-block-body"><?php echo nl2br(esc_html($invoice->getTerms())); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="wpci-totals-col">
            <table class="wpci-totals-table">
                <tr>
                    <th><?php esc_html_e('Subtotal:', 'wpcalibrate-invoice-studio'); ?></th>
                    <td><?php echo esc_html($formatter($invoice->getSubtotal())); ?></td>
                </tr>
                <?php if ($invoice->getDiscountTotal() > 0): ?>
                    <tr>
                        <th><?php esc_html_e('Discount:', 'wpcalibrate-invoice-studio'); ?></th>
                        <td>-<?php echo esc_html($formatter($invoice->getDiscountTotal())); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($invoice->getShippingTotal() > 0): ?>
                    <tr>
                        <th><?php esc_html_e('Shipping:', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getShippingTotal())); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($invoice->getFeeTotal() > 0): ?>
                    <tr>
                        <th><?php esc_html_e('Fees:', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getFeeTotal())); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($invoice->getTaxTotal() > 0): ?>
                    <?php foreach ($invoice->getTaxBreakdown() as $label => $taxAmt): ?>
                        <tr>
                            <th><?php echo esc_html($label); ?>:</th>
                            <td><?php echo esc_html($formatter($taxAmt)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="wpci-grand-total">
                    <th><?php esc_html_e('Total:', 'wpcalibrate-invoice-studio'); ?></th>
                    <td><?php echo esc_html($formatter($invoice->getTotal())); ?></td>
                </tr>
                <?php if ($invoice->getAmountPaid() > 0): ?>
                    <tr>
                        <th><?php esc_html_e('Amount Paid:', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getAmountPaid())); ?></td>
                    </tr>
                    <tr class="wpci-amount-due">
                        <th><?php esc_html_e('Amount Due:', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getAmountDue())); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <?php if ($business->getFooterText()): ?>
        <div class="wpci-footer-text">
            <?php echo esc_html($business->getFooterText()); ?>
        </div>
    <?php endif; ?>
</div>
