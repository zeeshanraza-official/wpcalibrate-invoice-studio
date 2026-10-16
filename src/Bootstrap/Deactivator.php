<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin deactivation routines.
 */
final class Deactivator
{
    public static function deactivate(): void
    {
        // Clear any scheduled cron events
        wp_clear_scheduled_hook('wpci_daily_maintenance_cron');

        // Clear locks and temporary transients
        delete_transient('wpci_flush_rewrite_rules');
        delete_transient('wpci_numbering_lock');

        // Note: We preserve all invoice CPT records, options, settings, and capabilities
        // as required by WordPress plugin guidelines and prompt requirements.

        flush_rewrite_rules();
    }
}
