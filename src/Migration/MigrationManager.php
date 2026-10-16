<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Migration;

use WPCalibrate\InvoiceStudio\Logging\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles database and data schema migrations across plugin versions.
 */
final class MigrationManager
{
    public const DB_VERSION_OPTION = 'wpci_db_version';

    public function runIfNeeded(): void
    {
        $currentDbVersion = (string)get_option(self::DB_VERSION_OPTION, '0.0.0');

        if (version_compare($currentDbVersion, WPCI_VERSION, '<')) {
            $this->migrate($currentDbVersion);
            update_option(self::DB_VERSION_OPTION, WPCI_VERSION);
            Logger::info("Database schema migrated from {$currentDbVersion} to " . WPCI_VERSION);
        }
    }

    private function migrate(string $fromVersion): void
    {
        // 1.0.0 initial baseline migration
        if (version_compare($fromVersion, '1.0.0', '<')) {
            // Ensure necessary options exist
            \WPCalibrate\InvoiceStudio\Bootstrap\Activator::initDefaultOptions();
            \WPCalibrate\InvoiceStudio\Bootstrap\Activator::grantCapabilities();
            \WPCalibrate\InvoiceStudio\Bootstrap\Activator::setupUploadsDirectory();
        }
    }
}
