<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Payment;

use WPCalibrate\InvoiceStudio\Invoice\ActivityLogger;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus;
use WPCalibrate\InvoiceStudio\Logging\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles online payment generation and payment completion synchronization via WooCommerce.
 */
final class PaymentService
{
    private InvoiceRepository $repository;

    public function __construct(InvoiceRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get or create a payable WooCommerce order for an unpaid manual invoice.
     * Idempotent: reuses existing payment order if already created.
     *
     * @return int|null WooCommerce Order ID
     */
    public function getOrCreatePaymentOrder(InvoiceEntity $invoice): ?int
    {
        if (!function_exists('wc_create_order')) {
            return null;
        }

        // If invoice already has a payment order, verify and reuse
        if ($invoice->getPaymentOrderId()) {
            $existingOrder = wc_get_order($invoice->getPaymentOrderId());
            if ($existingOrder && !$existingOrder->is_paid()) {
                return $invoice->getPaymentOrderId();
            }
        }

        // Amount due must be greater than zero
        if ($invoice->getAmountDue() <= 0.0) {
            return null;
        }

        try {
            $customer = $invoice->getCustomer();
            $order = wc_create_order([
                'customer_id'   => $customer->getUserId() ?: 0,
                'status'        => 'pending',
                'customer_note' => sprintf(__('Payment for Invoice %s', 'wpcalibrate-invoice-studio'), $invoice->getNumber()),
            ]);

            if (is_wp_error($order) || !$order) {
                Logger::error('Failed to create WooCommerce payment order: ' . ($order ? $order->get_error_message() : 'Unknown error'));
                return null;
            }

            // Add single fee/item line representing the invoice amount due
            $item = new \WC_Order_Item_Fee();
            $item->set_name(sprintf(__('Invoice %s Balance', 'wpcalibrate-invoice-studio'), $invoice->getNumber()));
            $item->set_total($invoice->getAmountDue());
            $item->set_tax_status('none');
            $order->add_item($item);

            // Set billing details
            $order->set_billing_first_name($customer->getName());
            $order->set_billing_email($customer->getEmail());
            $order->set_billing_phone($customer->getPhone());
            $order->set_billing_company($customer->getCompany());
            $order->set_billing_address_1($customer->getAddress());
            $order->set_billing_city($customer->getCity());
            $order->set_billing_state($customer->getState());
            $order->set_billing_postcode($customer->getPostcode());
            $order->set_billing_country($customer->getCountry());

            $order->calculate_totals();

            // Link order to invoice
            $order->update_meta_data('_wpci_is_invoice_payment', '1');
            $order->update_meta_data('_wpci_invoice_id', $invoice->getId());
            $order->update_meta_data('_wpci_invoice_number', $invoice->getNumber());
            $order->save();

            $orderId = $order->get_id();
            $invoice->setPaymentOrderId($orderId);
            $this->repository->save($invoice);

            ActivityLogger::log($invoice->getId(), 'payment_order_created', sprintf(
                /* translators: %s: WooCommerce order ID */
                __('Payable WooCommerce order #%s generated.', 'wpcalibrate-invoice-studio'),
                $orderId
            ));

            return $orderId;
        } catch (\Throwable $e) {
            Logger::error('Exception creating WooCommerce payment order: ' . $e->getMessage(), [
                'invoice_id' => $invoice->getId(),
            ]);
            return null;
        }
    }

    /**
     * Get the online payment checkout URL for an invoice.
     */
    public function getPaymentUrl(InvoiceEntity $invoice): ?string
    {
        $orderId = $this->getOrCreatePaymentOrder($invoice);
        if (!$orderId || !function_exists('wc_get_order')) {
            return null;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return null;
        }

        return $order->get_checkout_payment_url();
    }

    /**
     * Handle payment completion from WooCommerce order.
     * Synchronizes invoice status to Paid.
     */
    public function handleWooCommerceOrderPayment(int $orderId): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        $invoiceId = (int)$order->get_meta('_wpci_invoice_id', true);
        if (!$invoiceId) {
            return;
        }

        $invoice = $this->repository->findById($invoiceId);
        if (!$invoice) {
            return;
        }

        // If invoice is already paid, do nothing (idempotent)
        if ($invoice->isPaid()) {
            return;
        }

        $paidAmount = (float)$order->get_total();
        $invoice->setAmountPaid($invoice->getTotal());
        $invoice->setAmountDue(0.0);
        $invoice->setStatus(InvoiceStatus::PAID);
        $invoice->setPaidDate(current_time('mysql'));

        $this->repository->save($invoice);

        $gateway = $order->get_payment_method_title() ?: $order->get_payment_method();
        ActivityLogger::log($invoiceId, 'payment_recorded', sprintf(
            /* translators: 1: amount, 2: gateway title, 3: order ID */
            __('Full payment of %1$s confirmed via %2$s (Order #%3$s).', 'wpcalibrate-invoice-studio'),
            $paidAmount,
            $gateway,
            $orderId
        ));

        Logger::info("Invoice #{$invoice->getNumber()} marked as PAID via WooCommerce order #{$orderId}.");
    }
}
