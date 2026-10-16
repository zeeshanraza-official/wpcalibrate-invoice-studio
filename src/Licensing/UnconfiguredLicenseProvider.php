<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Licensing;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default fallback provider when no remote licensing API is configured.
 * Does NOT fake or simulate activation.
 */
final class UnconfiguredLicenseProvider implements LicenseProviderInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return 'Not Configured';
    }

    public function activate(string $licenseKey): array
    {
        return [
            'success'     => false,
            'message'     => __('Licensing service not configured. Remote activation is not currently available.', 'wpcalibrate-invoice-studio'),
            'expires'     => null,
            'renewal_url' => null,
        ];
    }

    public function deactivate(string $licenseKey): array
    {
        return [
            'success' => false,
            'message' => __('Licensing service not configured.', 'wpcalibrate-invoice-studio'),
        ];
    }

    public function checkStatus(string $licenseKey): array
    {
        return [
            'status'      => 'unconfigured',
            'expires'     => null,
            'renewal_url' => null,
            'message'     => __('Licensing service not configured.', 'wpcalibrate-invoice-studio'),
        ];
    }
}
