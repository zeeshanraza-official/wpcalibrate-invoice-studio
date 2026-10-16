<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Invoice;

use WPCalibrate\InvoiceStudio\Customer\BusinessSnapshot;
use WPCalibrate\InvoiceStudio\Customer\CustomerSnapshot;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Complete domain entity representing an invoice.
 */
final class InvoiceEntity
{
    private ?int $id;
    private string $uuid;
    private string $number;
    private string $status;
    private string $source; // 'manual' | 'woocommerce'
    private ?int $orderId;
    private CustomerSnapshot $customer;
    private BusinessSnapshot $business;
    private string $issueDate;
    private string $dueDate;
    private ?string $paidDate;
    private string $currency;
    /** @var LineItem[] */
    private array $lineItems;
    private float $subtotal;
    private float $discountTotal;
    private float $shippingTotal;
    private float $feeTotal;
    private float $taxTotal;
    private array $taxBreakdown;
    private float $total;
    private float $amountPaid;
    private float $amountDue;
    private string $template;
    private string $paymentInstructions;
    private string $customerNotes;
    private string $terms;
    private array $customFields;
    private bool $allowOnlinePayment;
    private ?int $paymentOrderId;
    private ?string $pdfPath;
    private ?int $pdfGeneratedTime;

    public function __construct(
        ?int $id = null,
        ?string $uuid = null,
        string $number = '',
        string $status = InvoiceStatus::DRAFT,
        string $source = 'manual',
        ?int $orderId = null,
        ?CustomerSnapshot $customer = null,
        ?BusinessSnapshot $business = null,
        string $issueDate = '',
        string $dueDate = '',
        ?string $paidDate = null,
        string $currency = 'USD',
        array $lineItems = [],
        float $subtotal = 0.0,
        float $discountTotal = 0.0,
        float $shippingTotal = 0.0,
        float $feeTotal = 0.0,
        float $taxTotal = 0.0,
        array $taxBreakdown = [],
        float $total = 0.0,
        float $amountPaid = 0.0,
        float $amountDue = 0.0,
        string $template = 'classic',
        string $paymentInstructions = '',
        string $customerNotes = '',
        string $terms = '',
        array $customFields = [],
        bool $allowOnlinePayment = false,
        ?int $paymentOrderId = null,
        ?string $pdfPath = null,
        ?int $pdfGeneratedTime = null
    ) {
        $this->id = $id;
        $this->uuid = $uuid ?: wp_generate_uuid4();
        $this->number = $number;
        $this->status = $status;
        $this->source = $source;
        $this->orderId = $orderId;
        $this->customer = $customer ?: new CustomerSnapshot('', '');
        $this->business = $business ?: new BusinessSnapshot('', '');
        $this->issueDate = $issueDate ?: gmdate('Y-m-d');
        $this->dueDate = $dueDate ?: gmdate('Y-m-d', strtotime('+14 days'));
        $this->paidDate = $paidDate;
        $this->currency = strtoupper($currency ?: 'USD');
        $this->lineItems = $lineItems;
        $this->subtotal = $subtotal;
        $this->discountTotal = $discountTotal;
        $this->shippingTotal = $shippingTotal;
        $this->feeTotal = $feeTotal;
        $this->taxTotal = $taxTotal;
        $this->taxBreakdown = $taxBreakdown;
        $this->total = $total;
        $this->amountPaid = $amountPaid;
        $this->amountDue = $amountDue;
        $this->template = in_array($template, ['classic', 'modern', 'minimal'], true) ? $template : 'classic';
        $this->paymentInstructions = $paymentInstructions;
        $this->customerNotes = $customerNotes;
        $this->terms = $terms;
        $this->customFields = $customFields;
        $this->allowOnlinePayment = $allowOnlinePayment;
        $this->paymentOrderId = $paymentOrderId;
        $this->pdfPath = $pdfPath;
        $this->pdfGeneratedTime = $pdfGeneratedTime;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function getCustomer(): CustomerSnapshot
    {
        return $this->customer;
    }

    public function setCustomer(CustomerSnapshot $customer): void
    {
        $this->customer = $customer;
    }

    public function getBusiness(): BusinessSnapshot
    {
        return $this->business;
    }

    public function setBusiness(BusinessSnapshot $business): void
    {
        $this->business = $business;
    }

    public function getIssueDate(): string
    {
        return $this->issueDate;
    }

    public function setIssueDate(string $date): void
    {
        $this->issueDate = $date;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function setDueDate(string $date): void
    {
        $this->dueDate = $date;
    }

    public function getPaidDate(): ?string
    {
        return $this->paidDate;
    }

    public function setPaidDate(?string $date): void
    {
        $this->paidDate = $date;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = strtoupper($currency);
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function setLineItems(array $lineItems): void
    {
        $this->lineItems = $lineItems;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function setSubtotal(float $subtotal): void
    {
        $this->subtotal = $subtotal;
    }

    public function getDiscountTotal(): float
    {
        return $this->discountTotal;
    }

    public function setDiscountTotal(float $discountTotal): void
    {
        $this->discountTotal = $discountTotal;
    }

    public function getShippingTotal(): float
    {
        return $this->shippingTotal;
    }

    public function setShippingTotal(float $shippingTotal): void
    {
        $this->shippingTotal = $shippingTotal;
    }

    public function getFeeTotal(): float
    {
        return $this->feeTotal;
    }

    public function setFeeTotal(float $feeTotal): void
    {
        $this->feeTotal = $feeTotal;
    }

    public function getTaxTotal(): float
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(float $taxTotal): void
    {
        $this->taxTotal = $taxTotal;
    }

    public function getTaxBreakdown(): array
    {
        return $this->taxBreakdown;
    }

    public function setTaxBreakdown(array $breakdown): void
    {
        $this->taxBreakdown = $breakdown;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function getAmountPaid(): float
    {
        return $this->amountPaid;
    }

    public function setAmountPaid(float $amountPaid): void
    {
        $this->amountPaid = $amountPaid;
    }

    public function getAmountDue(): float
    {
        return $this->amountDue;
    }

    public function setAmountDue(float $amountDue): void
    {
        $this->amountDue = $amountDue;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getPaymentInstructions(): string
    {
        return $this->paymentInstructions;
    }

    public function setPaymentInstructions(string $instructions): void
    {
        $this->paymentInstructions = $instructions;
    }

    public function getCustomerNotes(): string
    {
        return $this->customerNotes;
    }

    public function setCustomerNotes(string $notes): void
    {
        $this->customerNotes = $notes;
    }

    public function getTerms(): string
    {
        return $this->terms;
    }

    public function setTerms(string $terms): void
    {
        $this->terms = $terms;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function setCustomFields(array $customFields): void
    {
        $this->customFields = $customFields;
    }

    public function allowOnlinePayment(): bool
    {
        return $this->allowOnlinePayment;
    }

    public function setAllowOnlinePayment(bool $allow): void
    {
        $this->allowOnlinePayment = $allow;
    }

    public function getPaymentOrderId(): ?int
    {
        return $this->paymentOrderId;
    }

    public function setPaymentOrderId(?int $orderId): void
    {
        $this->paymentOrderId = $orderId;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $path, ?int $timestamp = null): void
    {
        $this->pdfPath = $path;
        $this->pdfGeneratedTime = $timestamp ?: time();
    }

    public function getPdfGeneratedTime(): ?int
    {
        return $this->pdfGeneratedTime;
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::PAID;
    }

    public function isFinalized(): bool
    {
        return InvoiceStatus::isFinalized($this->status);
    }
}
