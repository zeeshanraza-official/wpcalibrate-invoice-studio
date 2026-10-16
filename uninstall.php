<?php
declare(strict_types=1);

/**
 * WPCalibrate Invoice Studio Uninstall Routine.
 *
 * Triggered only when the user deletes the plugin via WordPress admin.
 * Respects the 'delete_data_on_uninstall' setting:
 * - By default (disabled), all invoice financial records, settings, and files are preserved.
 * - When explicitly enabled by an administrator, cleans up plugin-owned data completely.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check administrator configured retention setting
$privacySettings = get_option('wpci_privacy_settings', []);
$shouldDelete = !empty($privacySettings['delete_data_on_uninstall']);

if (!$shouldDelete) {
    // Preserve all financial records and settings as required
    return;
}

global $wpdb;

// 1. Delete all invoices (wpci_invoice custom post type) and postmeta
$invoicePostIds = $wpdb->get_col($wpdb->prepare(
    "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
    'wpci_invoice'
));

if (!empty($invoicePostIds)) {
    foreach ($invoicePostIds as $postId) {
        wp_delete_post((int)$postId, true);
    }
}

// 2. Remove plugin-specific options
$optionsToDelete = [
    'wpci_version',
    'wpci_db_version',
    'wpci_general_settings',
    'wpci_business_settings',
    'wpci_invoice_defaults',
    'wpci_numbering_settings',
    'wpci_template_settings',
    'wpci_email_settings',
    'wpci_pdf_settings',
    'wpci_payment_settings',
    'wpci_woocommerce_settings',
    'wpci_privacy_settings',
    'wpci_license_data',
];

foreach ($optionsToDelete as $opt) {
    delete_option($opt);
}

// 3. Remove plugin transients
delete_transient('wpci_numbering_lock');
delete_transient('wpci_flush_rewrite_rules');

// 4. Remove plugin capabilities from administrator role
$role = get_role('administrator');
if ($role) {
    $caps = [
        'wpci_manage_settings',
        'wpci_view_invoices',
        'wpci_create_invoices',
        'wpci_edit_invoices',
        'wpci_send_invoices',
        'wpci_record_payments',
        'wpci_delete_invoices',
    ];
    foreach ($caps as $cap) {
        $role->remove_cap($cap);
    }
}

// 5. Delete generated PDF files and clean up storage directory
$uploadDir = wp_upload_dir();
$invoiceDir = trailingslashit($uploadDir['basedir']) . 'wpci-invoices';

if (is_dir($invoiceDir)) {
    $files = glob(trailingslashit($invoiceDir) . '*');
    if ($files) {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    @rmdir($invoiceDir);
}
