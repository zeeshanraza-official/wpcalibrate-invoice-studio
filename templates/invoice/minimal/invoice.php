<?php
/**
 * Minimal Invoice Template
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
<div class="wpci-invoice-wrap wpci-template-minimal">
    <!-- Minimal Header -->
    <table class="wpci-header-table">
        <tr>
            <td class="wpci-header-left">
                <div class="wpci-invoice-title"><?php esc_html_e('INVOICE', 'wpcalibrate-invoice-studio'); ?></div>
                <div class="wpci-invoice-sub">#<?php echo esc_html($invoice->getNumber() ?: __('Draft', 'wpcalibrate-invoice-studio')); ?></div>
            </td>
            <td class="wpci-header-right">
                <?php if ($business->getLogoUrl()): ?>
                    <img src="<?php echo esc_url($business->getLogoUrl()); ?>" alt="<?php echo esc_attr($business->getName()); ?>" class="wpci-logo" />
                <?php else: ?>
                    <div class="wpci-biz-name"><?php echo esc_html($business->getName() ?: get_bloginfo('name')); ?></div>
                <?php endif; ?>
                <div class="wpci-biz-meta">
                    <?php if ($business->getEmail()): ?>
                        <div><?php echo esc_html($business->getEmail()); ?></div>
                    <?php endif; ?>
                    <?php if ($business->getWebsite()): ?>
                        <div><?php echo esc_html($business->getWebsite()); ?></div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <hr class="wpci-rule" />

    <!-- Meta Details & Customer Table -->
    <table class="wpci-meta-customer-table">
        <tr>
            <td class="wpci-cust-cell">
                <div class="wpci-label"><?php esc_html_e('INVOICED TO:', 'wpcalibrate-invoice-studio'); ?></div>
                <div class="wpci-cust-info">
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
                </div>
            </td>
            <td class="wpci-dates-cell">
                <table class="wpci-compact-meta">
                    <tr>
                        <th><?php esc_html_e('Issue Date:', 'wpcalibrate-invoice-studio'); ?></th>
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
                        <div><strong><?php echo esc_html($item->getDescription()); ?></strong></div>
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

    <!-- Totals Section -->
    <div class="wpci-bottom-wrapper">
        <div class="wpci-left-notes">
            <?php if ($invoice->getPaymentInstructions()): ?>
                <div class="wpci-instruction-box">
                    <div class="wpci-instruction-title"><?php esc_html_e('Payment Details', 'wpcalibrate-invoice-studio'); ?></div>
                    <div><?php echo nl2br(wp_kses_post($invoice->getPaymentInstructions())); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($invoice->getCustomerNotes()): ?>
                <div class="wpci-instruction-box">
                    <div class="wpci-instruction-title"><?php esc_html_e('Notes', 'wpcalibrate-invoice-studio'); ?></div>
                    <div><?php echo nl2br(esc_html($invoice->getCustomerNotes())); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($invoice->getTerms()): ?>
                <div class="wpci-instruction-box">
                    <div class="wpci-instruction-title"><?php esc_html_e('Terms', 'wpcalibrate-invoice-studio'); ?></div>
                    <div><?php echo nl2br(esc_html($invoice->getTerms())); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="wpci-right-totals">
            <table class="wpci-totals-table">
                <tr>
                    <th><?php esc_html_e('Subtotal', 'wpcalibrate-invoice-studio'); ?></th>
                    <td><?php echo esc_html($formatter($invoice->getSubtotal())); ?></td>
                </tr>
                <?php if ($invoice->getDiscountTotal() > 0): ?>
                    <tr>
                        <th><?php esc_html_e('Discount', 'wpcalibrate-invoice-studio'); ?></th>
                        <td>-<?php echo esc_html($formatter($invoice->getDiscountTotal())); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($invoice->getShippingTotal() > 0): ?>
                    <tr>
                        <th><?php esc_html_e('Shipping', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getShippingTotal())); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($invoice->getFeeTotal() > 0): ?>
                    <tr>
                        <th><?php esc_html_e('Fee', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getFeeTotal())); ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($invoice->getTaxTotal() > 0): ?>
                    <?php foreach ($invoice->getTaxBreakdown() as $label => $taxAmt): ?>
                        <tr>
                            <th><?php echo esc_html($label); ?></th>
                            <td><?php echo esc_html($formatter($taxAmt)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="wpci-final-total">
                    <th><?php esc_html_e('Total', 'wpcalibrate-invoice-studio'); ?></th>
                    <td><?php echo esc_html($formatter($invoice->getTotal())); ?></td>
                </tr>
                <?php if ($invoice->getAmountPaid() > 0): ?>
                    <tr>
                        <th><?php esc_html_e('Paid', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getAmountPaid())); ?></td>
                    </tr>
                    <tr class="wpci-final-due">
                        <th><?php esc_html_e('Due', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getAmountDue())); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <?php if ($business->getFooterText()): ?>
        <div class="wpci-minimal-footer">
            <?php echo esc_html($business->getFooterText()); ?>
        </div>
    <?php endif; ?>
</div>
