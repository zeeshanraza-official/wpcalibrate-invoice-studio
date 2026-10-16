<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Customer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Immutable snapshot of customer information stored on the invoice.
 */
final class CustomerSnapshot
{
    private ?int $userId;
    private string $name;
    private string $company;
    private string $email;
    private string $phone;
    private string $address;
    private string $city;
    private string $state;
    private string $postcode;
    private string $country;
    private string $taxId;

    public function __construct(
        string $name,
        string $email,
        string $company = '',
        string $phone = '',
        string $address = '',
        string $city = '',
        string $state = '',
        string $postcode = '',
        string $country = '',
        string $taxId = '',
        ?int $userId = null
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->company = $company;
        $this->phone = $phone;
        $this->address = $address;
        $this->city = $city;
        $this->state = $state;
        $this->postcode = $postcode;
        $this->country = $country;
        $this->taxId = $taxId;
        $this->userId = $userId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getTaxId(): string
    {
        return $this->taxId;
    }

    public function getFormattedAddress(): string
    {
        $lines = array_filter([
            $this->company,
            $this->address,
            implode(' ', array_filter([$this->city, $this->state, $this->postcode])),
            $this->country,
        ]);
        return implode("\n", $lines);
    }

    public function toArray(): array
    {
        return [
            'user_id'  => $this->userId,
            'name'     => $this->name,
            'company'  => $this->company,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'address'  => $this->address,
            'city'     => $this->city,
            'state'    => $this->state,
            'postcode' => $this->postcode,
            'country'  => $this->country,
            'tax_id'   => $this->taxId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['name'] ?? ''),
            (string)($data['email'] ?? ''),
            (string)($data['company'] ?? ''),
            (string)($data['phone'] ?? ''),
            (string)($data['address'] ?? ''),
            (string)($data['city'] ?? ''),
            (string)($data['state'] ?? ''),
            (string)($data['postcode'] ?? ''),
            (string)($data['country'] ?? ''),
            (string)($data['tax_id'] ?? ''),
            !empty($data['user_id']) ? (int)$data['user_id'] : null
        );
    }
}
