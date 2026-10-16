<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Invoice;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Robust monetary calculation engine for invoices.
 * Prevents floating-point inaccuracies by rounding and calculating in cents/fixed precision.
 */
final class CalculationEngine
{
    /**
     * Calculate all financial totals for an invoice.
     *
     * @param LineItem[] $lineItems
     * @param float      $fixedDiscount Additional fixed order-level discount
     * @param float      $shippingAmount
     * @param float      $shippingTax
     * @param float      $feeAmount
     * @param float      $feeTax
     * @param float      $amountPaid
     * @param int        $decimals Standard currency decimal places (default 2)
     * @return array Calculated summary totals
     */
    public static function calculate(
        array $lineItems,
        float $fixedDiscount = 0.0,
        float $shippingAmount = 0.0,
        float $shippingTax = 0.0,
        float $feeAmount = 0.0,
        float $feeTax = 0.0,
        float $amountPaid = 0.0,
        int $decimals = 2
    ): array {
        $subtotal = 0.0;
        $itemsDiscount = 0.0;
        $itemsTax = 0.0;
        $taxBreakdown = [];

        foreach ($lineItems as $item) {
            $item->recalculate();
            $subtotal += $item->getSubtotal();
            $itemsDiscount += $item->getDiscount();
            $taxAmount = $item->getTax();
            $itemsTax += $taxAmount;

            $taxLabel = $item->getTaxLabel() ?: sprintf(__('Tax (%.1f%%)', 'wpcalibrate-invoice-studio'), $item->getTaxRate());
            if ($taxAmount > 0.0001) {
                if (!isset($taxBreakdown[$taxLabel])) {
                    $taxBreakdown[$taxLabel] = 0.0;
                }
                $taxBreakdown[$taxLabel] += $taxAmount;
            }
        }

        $totalDiscount = round($itemsDiscount + max(0.0, $fixedDiscount), $decimals);
        $totalShipping = round(max(0.0, $shippingAmount), $decimals);
        $totalShippingTax = round(max(0.0, $shippingTax), $decimals);
        $totalFee = round(max(0.0, $feeAmount), $decimals);
        $totalFeeTax = round(max(0.0, $feeTax), $decimals);

        if ($totalShippingTax > 0.0001) {
            $shippingTaxLabel = __('Shipping Tax', 'wpcalibrate-invoice-studio');
            $taxBreakdown[$shippingTaxLabel] = ($taxBreakdown[$shippingTaxLabel] ?? 0.0) + $totalShippingTax;
        }

        if ($totalFeeTax > 0.0001) {
            $feeTaxLabel = __('Fee Tax', 'wpcalibrate-invoice-studio');
            $taxBreakdown[$feeTaxLabel] = ($taxBreakdown[$feeTaxLabel] ?? 0.0) + $totalFeeTax;
        }

        $totalTax = round($itemsTax + $totalShippingTax + $totalFeeTax, $decimals);
        $subtotal = round($subtotal, $decimals);

        // Grand Total = Subtotal - Fixed Discount (if items discount not already deducted) + Shipping + Fees + Tax
        // Notice items line subtotal is qty * unit price.
        // Net items total = Subtotal - Items Discount.
        $netSubtotal = max(0.0, $subtotal - $itemsDiscount);
        $discountedNet = max(0.0, $netSubtotal - max(0.0, $fixedDiscount));

        $grandTotal = round($discountedNet + $totalTax + $totalShipping + $totalFee, $decimals);
        $amountPaid = round(max(0.0, $amountPaid), $decimals);
        $amountDue  = round(max(0.0, $grandTotal - $amountPaid), $decimals);

        return [
            'subtotal'       => $subtotal,
            'discount_total' => $totalDiscount,
            'tax_total'      => $totalTax,
            'tax_breakdown'  => array_map(fn($val) => round($val, $decimals), $taxBreakdown),
            'shipping_total' => $totalShipping,
            'fee_total'      => $totalFee,
            'total'          => $grandTotal,
            'amount_paid'    => $amountPaid,
            'amount_due'     => $amountDue,
        ];
    }
}
