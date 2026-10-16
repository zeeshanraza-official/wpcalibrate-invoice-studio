<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Licensing;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages licensing UI state, masking, and delegation to configured provider.
 */
final class LicenseManager
{
    public const OPTION_KEY = 'wpci_license_data';

    private LicenseProviderInterface $provider;

    public function __construct(?LicenseProviderInterface $provider = null)
    {
        $this->provider = $provider ?: new UnconfiguredLicenseProvider();
    }

    public function getProvider(): LicenseProviderInterface
    {
        return $this->provider;
    }

    public function setProvider(LicenseProviderInterface $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * Get stored license details.
     */
    public function getData(): array
    {
        $defaults = [
            'license_key' => '',
            'status'      => 'unconfigured', // 'unconfigured' | 'inactive' | 'active' | 'expired'
            'expires'     => null,
            'renewal_url' => null,
            'last_check'  => null,
            'message'     => '',
        ];
        return wp_parse_args(get_option(self::OPTION_KEY, []), $defaults);
    }

    /**
     * Save updated license key and reset status to unconfigured or inactive.
     */
    public function saveKey(string $licenseKey): void
    {
        $data = $this->getData();
        $trimmed = sanitize_text_field(trim($licenseKey));
        $data['license_key'] = $trimmed;

        if (!$this->provider->isConfigured()) {
            $data['status'] = 'unconfigured';
            $data['message'] = __('Licensing service not configured.', 'wpcalibrate-invoice-studio');
        } else {
            $data['status'] = 'inactive';
            $data['message'] = '';
        }

        update_option(self::OPTION_KEY, $data);
    }

    /**
     * Get masked license key for safe display in UI.
     * e.g., "ABCD-EFGH-IJKL-MNOP" -> "••••-••••-••••-MNOP"
     */
    public function getMaskedKey(): string
    {
        $data = $this->getData();
        $key = $data['license_key'] ?? '';
        if (empty($key)) {
            return '';
        }

        $length = strlen($key);
        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        $lastFour = substr($key, -4);
        return str_repeat('•', max(0, $length - 4)) . $lastFour;
    }

    /**
     * Attempt activation via the provider.
     */
    public function activate(): array
    {
        $data = $this->getData();
        if (empty($data['license_key'])) {
            return [
                'success' => false,
                'message' => __('Please enter a license key first.', 'wpcalibrate-invoice-studio'),
            ];
        }

        $result = $this->provider->activate($data['license_key']);
        if (!empty($result['success'])) {
            $data['status'] = 'active';
            $data['expires'] = $result['expires'] ?? null;
            $data['renewal_url'] = $result['renewal_url'] ?? null;
            $data['message'] = $result['message'] ?? '';
            $data['last_check'] = current_time('mysql');
        } else {
            $data['status'] = $this->provider->isConfigured() ? 'inactive' : 'unconfigured';
            $data['message'] = $result['message'] ?? '';
        }

        update_option(self::OPTION_KEY, $data);
        return $result;
    }

    /**
     * Attempt deactivation via the provider.
     */
    public function deactivate(): array
    {
        $data = $this->getData();
        $result = $this->provider->deactivate($data['license_key']);

        $data['status'] = $this->provider->isConfigured() ? 'inactive' : 'unconfigured';
        $data['message'] = $result['message'] ?? '';
        $data['last_check'] = current_time('mysql');

        update_option(self::OPTION_KEY, $data);
        return $result;
    }
}
