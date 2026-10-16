<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Invoice;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Represents a single invoice line item with robust serialization and calculation helpers.
 */
final class LineItem
{
    private string $id;
    private string $description;
    private string $sku;
    private float $quantity;
    private float $unitPrice;
    private float $discount;
    private float $taxRate;
    private string $taxLabel;
    private float $subtotal;
    private float $tax;
    private float $total;

    public function __construct(
        string $description,
        float $quantity,
        float $unitPrice,
        string $sku = '',
        float $discount = 0.0,
        float $taxRate = 0.0,
        string $taxLabel = '',
        ?string $id = null,
        ?float $subtotal = null,
        ?float $tax = null,
        ?float $total = null
    ) {
        $this->id = $id ?: wp_generate_uuid4();
        $this->description = $description;
        $this->sku = $sku;
        $this->quantity = max(0.0001, $quantity);
        $this->unitPrice = max(0.0, $unitPrice);
        $this->discount = max(0.0, $discount);
        $this->taxRate = max(0.0, $taxRate);
        $this->taxLabel = $taxLabel;

        // Calculate if not explicitly supplied
        if ($subtotal === null || $tax === null || $total === null) {
            $this->recalculate();
        } else {
            $this->subtotal = round($subtotal, 4);
            $this->tax = round($tax, 4);
            $this->total = round($total, 4);
        }
    }

    public function recalculate(): void
    {
        $rawSubtotal = $this->quantity * $this->unitPrice;
        $this->subtotal = round($rawSubtotal, 4);

        $discounted = max(0.0, $this->subtotal - $this->discount);
        $rawTax = ($discounted * ($this->taxRate / 100.0));
        $this->tax = round($rawTax, 4);

        $this->total = round($discounted + $this->tax, 4);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPrice;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getTaxLabel(): string
    {
        return $this->taxLabel;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getTax(): float
    {
        return $this->tax;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'description' => $this->description,
            'sku'         => $this->sku,
            'quantity'    => $this->quantity,
            'unit_price'  => $this->unitPrice,
            'discount'    => $this->discount,
            'tax_rate'    => $this->taxRate,
            'tax_label'   => $this->taxLabel,
            'subtotal'    => $this->subtotal,
            'tax'         => $this->tax,
            'total'       => $this->total,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['description'] ?? ''),
            (float)($data['quantity'] ?? 1.0),
            (float)($data['unit_price'] ?? 0.0),
            (string)($data['sku'] ?? ''),
            (float)($data['discount'] ?? 0.0),
            (float)($data['tax_rate'] ?? 0.0),
            (string)($data['tax_label'] ?? ''),
            isset($data['id']) ? (string)$data['id'] : null,
            isset($data['subtotal']) ? (float)$data['subtotal'] : null,
            isset($data['tax']) ? (float)$data['tax'] : null,
            isset($data['total']) ? (float)$data['total'] : null
        );
    }
}
