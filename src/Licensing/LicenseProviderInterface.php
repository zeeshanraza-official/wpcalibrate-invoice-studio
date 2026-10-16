<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Licensing;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface contract for external licensing providers.
 */
interface LicenseProviderInterface
{
    /**
     * Check if provider is configured and available.
     */
    public function isConfigured(): bool;

    /**
     * Provider identification name.
     */
    public function getName(): string;

    /**
     * Activate a license key.
     *
     * @return array ['success' => bool, 'message' => string, 'expires' => ?string, 'renewal_url' => ?string]
     */
    public function activate(string $licenseKey): array;

    /**
     * Deactivate a license key.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function deactivate(string $licenseKey): array;

    /**
     * Check current license status remotely.
     *
     * @return array ['status' => string, 'expires' => ?string, 'renewal_url' => ?string, 'message' => string]
     */
    public function checkStatus(string $licenseKey): array;
}
