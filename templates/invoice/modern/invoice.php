<?php
/**
 * Modern Invoice Template
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
<div class="wpci-invoice-wrap wpci-template-modern">
    <!-- Top Bar with Accent -->
    <div class="wpci-top-accent"></div>

    <table class="wpci-header-table">
        <tr>
            <td class="wpci-brand-col">
                <?php if ($business->getLogoUrl()): ?>
                    <img src="<?php echo esc_url($business->getLogoUrl()); ?>" alt="<?php echo esc_attr($business->getName()); ?>" class="wpci-logo" />
                <?php else: ?>
                    <div class="wpci-brand-name"><?php echo esc_html($business->getName() ?: get_bloginfo('name')); ?></div>
                <?php endif; ?>
                <?php if ($business->getTradingName()): ?>
                    <div class="wpci-trading"><?php echo esc_html($business->getTradingName()); ?></div>
                <?php endif; ?>
            </td>
            <td class="wpci-invoice-title-col">
                <div class="wpci-pill-title"><?php esc_html_e('INVOICE', 'wpcalibrate-invoice-studio'); ?></div>
                <div class="wpci-invoice-num">#<?php echo esc_html($invoice->getNumber() ?: __('Draft', 'wpcalibrate-invoice-studio')); ?></div>
                <div class="wpci-status-wrap">
                    <span class="wpci-status-badge <?php echo esc_attr(\WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus::getBadgeClass($status)); ?>">
                        <?php echo esc_html(\WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus::getLabel($status)); ?>
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Info Cards Row -->
    <table class="wpci-info-cards-table">
        <tr>
            <td class="wpci-card wpci-card-biz">
                <div class="wpci-card-header"><?php esc_html_e('From:', 'wpcalibrate-invoice-studio'); ?></div>
                <div class="wpci-card-body">
                    <strong><?php echo esc_html($business->getName() ?: get_bloginfo('name')); ?></strong>
                    <?php if ($business->getAddress()): ?>
                        <div><?php echo nl2br(esc_html($business->getAddress())); ?></div>
                    <?php endif; ?>
                    <?php if ($business->getEmail()): ?>
                        <div><?php echo esc_html($business->getEmail()); ?></div>
                    <?php endif; ?>
                    <?php if ($business->getTaxNumber()): ?>
                        <div><?php echo esc_html($business->getTaxLabel() ?: 'Tax ID'); ?>: <?php echo esc_html($business->getTaxNumber()); ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="wpci-card wpci-card-cust">
                <div class="wpci-card-header"><?php esc_html_e('Bill To:', 'wpcalibrate-invoice-studio'); ?></div>
                <div class="wpci-card-body">
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
                    <?php if ($customer->getTaxId()): ?>
                        <div><?php esc_html_e('Tax ID:', 'wpcalibrate-invoice-studio'); ?> <?php echo esc_html($customer->getTaxId()); ?></div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="wpci-card wpci-card-dates">
                <div class="wpci-card-header"><?php esc_html_e('Invoice Details:', 'wpcalibrate-invoice-studio'); ?></div>
                <div class="wpci-card-body">
                    <div><strong><?php esc_html_e('Issue Date:', 'wpcalibrate-invoice-studio'); ?></strong> <?php echo esc_html($invoice->getIssueDate()); ?></div>
                    <div><strong><?php esc_html_e('Due Date:', 'wpcalibrate-invoice-studio'); ?></strong> <?php echo esc_html($invoice->getDueDate()); ?></div>
                    <?php if ($invoice->getOrderId()): ?>
                        <div><strong><?php esc_html_e('Order #:', 'wpcalibrate-invoice-studio'); ?></strong> #<?php echo esc_html((string)$invoice->getOrderId()); ?></div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="wpci-modern-items">
        <thead>
            <tr>
                <th class="col-item"><?php esc_html_e('Item Description', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-sku"><?php esc_html_e('SKU', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-qty"><?php esc_html_e('Qty', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-rate"><?php esc_html_e('Rate', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-tax"><?php esc_html_e('Tax', 'wpcalibrate-invoice-studio'); ?></th>
                <th class="col-amount"><?php esc_html_e('Amount', 'wpcalibrate-invoice-studio'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lineItems as $item): ?>
                <tr>
                    <td class="col-item">
                        <div class="wpci-item-title"><?php echo esc_html($item->getDescription()); ?></div>
                    </td>
                    <td class="col-sku"><?php echo esc_html($item->getSku() ?: '-'); ?></td>
                    <td class="col-qty"><?php echo esc_html((string)$item->getQuantity()); ?></td>
                    <td class="col-rate"><?php echo esc_html($formatter($item->getUnitPrice())); ?></td>
                    <td class="col-tax"><?php echo esc_html($formatter($item->getTax())); ?></td>
                    <td class="col-amount"><?php echo esc_html($formatter($item->getTotal())); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Bottom Section -->
    <div class="wpci-bottom-row">
        <div class="wpci-notes-cell">
            <?php if ($invoice->getPaymentInstructions()): ?>
                <div class="wpci-info-panel">
                    <div class="wpci-panel-title"><?php esc_html_e('How to Pay', 'wpcalibrate-invoice-studio'); ?></div>
                    <div><?php echo nl2br(wp_kses_post($invoice->getPaymentInstructions())); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($invoice->getCustomerNotes()): ?>
                <div class="wpci-info-panel">
                    <div class="wpci-panel-title"><?php esc_html_e('Notes', 'wpcalibrate-invoice-studio'); ?></div>
                    <div><?php echo nl2br(esc_html($invoice->getCustomerNotes())); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($invoice->getTerms()): ?>
                <div class="wpci-info-panel">
                    <div class="wpci-panel-title"><?php esc_html_e('Terms & Conditions', 'wpcalibrate-invoice-studio'); ?></div>
                    <div><?php echo nl2br(esc_html($invoice->getTerms())); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="wpci-totals-cell">
            <div class="wpci-totals-card">
                <table class="wpci-totals-list">
                    <tr>
                        <th><?php esc_html_e('Subtotal', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getSubtotal())); ?></td>
                    </tr>
                    <?php if ($invoice->getDiscountTotal() > 0): ?>
                        <tr>
                            <th><?php esc_html_e('Discount', 'wpcalibrate-invoice-studio'); ?></th>
                            <td class="wpci-discount">-<?php echo esc_html($formatter($invoice->getDiscountTotal())); ?></td>
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
                    <tr class="wpci-total-highlight">
                        <th><?php esc_html_e('Total Amount', 'wpcalibrate-invoice-studio'); ?></th>
                        <td><?php echo esc_html($formatter($invoice->getTotal())); ?></td>
                    </tr>
                    <?php if ($invoice->getAmountPaid() > 0): ?>
                        <tr>
                            <th><?php esc_html_e('Paid to Date', 'wpcalibrate-invoice-studio'); ?></th>
                            <td><?php echo esc_html($formatter($invoice->getAmountPaid())); ?></td>
                        </tr>
                        <tr class="wpci-due-highlight">
                            <th><?php esc_html_e('Balance Due', 'wpcalibrate-invoice-studio'); ?></th>
                            <td><?php echo esc_html($formatter($invoice->getAmountDue())); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <?php if ($business->getFooterText()): ?>
        <div class="wpci-modern-footer">
            <?php echo esc_html($business->getFooterText()); ?>
        </div>
    <?php endif; ?>
</div>
