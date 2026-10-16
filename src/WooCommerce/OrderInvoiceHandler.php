<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\WooCommerce;

use WPCalibrate\InvoiceStudio\Customer\BusinessSnapshot;
use WPCalibrate\InvoiceStudio\Customer\CustomerSnapshot;
use WPCalibrate\InvoiceStudio\Invoice\ActivityLogger;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus;
use WPCalibrate\InvoiceStudio\Invoice\LineItem;
use WPCalibrate\InvoiceStudio\Logging\Logger;
use WPCalibrate\InvoiceStudio\Numbering\NumberingService;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Creates invoices from WooCommerce orders using historical order values and CRUD APIs.
 */
final class OrderInvoiceHandler
{
    private InvoiceRepository $repository;
    private NumberingService $numberingService;
    private SettingsManager $settingsManager;

    public function __construct(
        InvoiceRepository $repository,
        NumberingService $numberingService,
        SettingsManager $settingsManager
    ) {
        $this->repository = $repository;
        $this->numberingService = $numberingService;
        $this->settingsManager = $settingsManager;
    }

    /**
     * Create an invoice from a WooCommerce order.
     * Prevents duplicate invoices for the same order.
     *
     * @param int  $orderId
     * @param bool $autoTriggered
     * @return InvoiceEntity|null Existing or newly created invoice
     */
    public function createFromOrder(int $orderId, bool $autoTriggered = false): ?InvoiceEntity
    {
        if (!function_exists('wc_get_order')) {
            return null;
        }

        // Check if invoice already exists for this order
        $existing = $this->repository->findByOrderId($orderId);
        if ($existing) {
            return $existing; // Idempotent: return existing invoice
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            Logger::error("WooCommerce order #{$orderId} not found.");
            return null;
        }

        // Build Customer Snapshot from historical order billing
        $customerSnapshot = new CustomerSnapshot(
            trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            (string)$order->get_billing_email(),
            (string)$order->get_billing_company(),
            (string)$order->get_billing_phone(),
            trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()),
            (string)$order->get_billing_city(),
            (string)$order->get_billing_state(),
            (string)$order->get_billing_postcode(),
            (string)$order->get_billing_country(),
            (string)$order->get_meta('_billing_vat_number', true) ?: (string)$order->get_meta('_billing_tax_id', true),
            $order->get_customer_id() ?: null
        );

        // Build Business Snapshot
        $bizSettings = $this->settingsManager->getBusiness();
        $businessSnapshot = BusinessSnapshot::fromArray($bizSettings);

        // Build Line Items using historical order items
        $lineItems = [];
        foreach ($order->get_items('line_item') as $item) {
            /** @var \WC_Order_Item_Product $item */
            $qty = max(0.001, (float)$item->get_quantity());
            $subtotal = (float)$item->get_subtotal();
            $tax = (float)$item->get_subtotal_tax();
            $total = (float)$item->get_total() + $tax;
            $unitPrice = round($subtotal / $qty, 4);

            $sku = '';
            $product = $item->get_product();
            if ($product) {
                $sku = (string)$product->get_sku();
            }

            $lineItems[] = new LineItem(
                (string)$item->get_name(),
                $qty,
                $unitPrice,
                $sku,
                0.0,
                0.0,
                '',
                null,
                $subtotal,
                $tax,
                $total
            );
        }

        // Totals from historical order
        $subtotal = (float)$order->get_subtotal();
        $discountTotal = (float)$order->get_discount_total();
        $shippingTotal = (float)$order->get_shipping_total();
        $feeTotal = 0.0;
        foreach ($order->get_items('fee') as $fee) {
            $feeTotal += (float)$fee->get_total();
        }
        $taxTotal = (float)$order->get_total_tax();
        $grandTotal = (float)$order->get_total();

        // Tax breakdown
        $taxBreakdown = [];
        foreach ($order->get_tax_totals() as $code => $taxObj) {
            $taxBreakdown[$taxObj->label] = (float)$taxObj->amount;
        }

        // Status mapping
        $status = InvoiceStatus::ISSUED;
        $amountPaid = 0.0;
        $paidDate = null;
        if ($order->is_paid()) {
            $status = InvoiceStatus::PAID;
            $amountPaid = $grandTotal;
            $paidDate = $order->get_date_paid() ? $order->get_date_paid()->date('Y-m-d H:i:s') : current_time('mysql');
        }

        // Generate unique sequential invoice number
        $invoiceNumber = $this->numberingService->generateNextNumber();

        $templateSettings = $this->settingsManager->getTemplate();
        $template = $templateSettings['default_template'] ?? 'classic';

        $orderDate = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : gmdate('Y-m-d');
        $dueDate = gmdate('Y-m-d', strtotime($orderDate . ' +14 days'));

        $invoice = new InvoiceEntity(
            null,
            wp_generate_uuid4(),
            $invoiceNumber,
            $status,
            'woocommerce',
            $orderId,
            $customerSnapshot,
            $businessSnapshot,
            $orderDate,
            $dueDate,
            $paidDate,
            (string)$order->get_currency(),
            $lineItems,
            $subtotal,
            $discountTotal,
            $shippingTotal,
            $feeTotal,
            $taxTotal,
            $taxBreakdown,
            $grandTotal,
            $amountPaid,
            max(0.0, $grandTotal - $amountPaid),
            $template,
            '',
            (string)$order->get_customer_note(),
            '',
            [],
            false,
            null
        );

        $savedId = $this->repository->save($invoice);
        $invoice->setId($savedId);

        // Store cross-reference on WooCommerce order
        $order->update_meta_data('_wpci_invoice_id', $savedId);
        $order->update_meta_data('_wpci_invoice_number', $invoiceNumber);
        $order->save();

        ActivityLogger::log($savedId, 'order_associated', sprintf(
            /* translators: 1: order ID, 2: auto or manual */
            __('Invoice created from WooCommerce order #%1$s (%2$s).', 'wpcalibrate-invoice-studio'),
            $orderId,
            $autoTriggered ? __('Automated', 'wpcalibrate-invoice-studio') : __('Manual', 'wpcalibrate-invoice-studio')
        ));

        Logger::info("Invoice #{$invoiceNumber} successfully created from WooCommerce order #{$orderId}.");

        return $invoice;
    }
}
