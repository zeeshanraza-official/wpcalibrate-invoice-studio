<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Customer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Immutable snapshot of business details stored on the invoice.
 */
final class BusinessSnapshot
{
    private string $name;
    private string $tradingName;
    private string $address;
    private string $email;
    private string $phone;
    private string $website;
    private string $taxLabel;
    private string $taxNumber;
    private string $companyNumber;
    private string $logoUrl;
    private string $footerText;

    public function __construct(
        string $name,
        string $email,
        string $tradingName = '',
        string $address = '',
        string $phone = '',
        string $website = '',
        string $taxLabel = 'VAT',
        string $taxNumber = '',
        string $companyNumber = '',
        string $logoUrl = '',
        string $footerText = ''
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->tradingName = $tradingName;
        $this->address = $address;
        $this->phone = $phone;
        $this->website = $website;
        $this->taxLabel = $taxLabel;
        $this->taxNumber = $taxNumber;
        $this->companyNumber = $companyNumber;
        $this->logoUrl = $logoUrl;
        $this->footerText = $footerText;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTradingName(): string
    {
        return $this->tradingName;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function getTaxLabel(): string
    {
        return $this->taxLabel;
    }

    public function getTaxNumber(): string
    {
        return $this->taxNumber;
    }

    public function getCompanyNumber(): string
    {
        return $this->companyNumber;
    }

    public function getLogoUrl(): string
    {
        return $this->logoUrl;
    }

    public function getFooterText(): string
    {
        return $this->footerText;
    }

    public function toArray(): array
    {
        return [
            'name'           => $this->name,
            'trading_name'   => $this->tradingName,
            'address'        => $this->address,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'website'        => $this->website,
            'tax_label'      => $this->taxLabel,
            'tax_number'     => $this->taxNumber,
            'company_number' => $this->companyNumber,
            'logo_url'       => $this->logoUrl,
            'footer_text'    => $this->footerText,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['name'] ?? ''),
            (string)($data['email'] ?? ''),
            (string)($data['trading_name'] ?? ''),
            (string)($data['address'] ?? ''),
            (string)($data['phone'] ?? ''),
            (string)($data['website'] ?? ''),
            (string)($data['tax_label'] ?? 'VAT'),
            (string)($data['tax_number'] ?? ''),
            (string)($data['company_number'] ?? ''),
            (string)($data['logo_url'] ?? ''),
            (string)($data['footer_text'] ?? '')
        );
    }
}
