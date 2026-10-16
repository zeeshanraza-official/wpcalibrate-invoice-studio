<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin activation routines.
 */
final class Activator
{
    public const REQUIRED_PHP_VERSION = '8.2.0';
    public const REQUIRED_WP_VERSION  = '6.4.0';

    public static function activate(): void
    {
        // Check PHP Version
        if (version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(WPCI_PLUGIN_FILE));
            wp_die(
                esc_html(
                    sprintf(
                        /* translators: 1: Required PHP version, 2: Current PHP version */
                        __('WPCalibrate Invoice Studio requires PHP %1$s or higher. Your server is running PHP %2$s.', 'wpcalibrate-invoice-studio'),
                        self::REQUIRED_PHP_VERSION,
                        PHP_VERSION
                    )
                ),
                esc_html__('Plugin Activation Error', 'wpcalibrate-invoice-studio'),
                ['back_link' => true]
            );
        }

        self::grantCapabilities();
        self::initDefaultOptions();
        self::setupUploadsDirectory();

        // Store version info
        update_option('wpci_version', WPCI_VERSION);
        if (!get_option('wpci_db_version')) {
            update_option('wpci_db_version', WPCI_VERSION);
        }

        // Set transient to flush rewrites on next init
        set_transient('wpci_flush_rewrite_rules', 1, 60);
    }

    public static function getCapabilities(): array
    {
        return [
            'wpci_manage_settings',
            'wpci_view_invoices',
            'wpci_create_invoices',
            'wpci_edit_invoices',
            'wpci_send_invoices',
            'wpci_record_payments',
            'wpci_delete_invoices',
        ];
    }

    public static function grantCapabilities(): void
    {
        $role = get_role('administrator');
        if ($role) {
            foreach (self::getCapabilities() as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    public static function removeCapabilities(): void
    {
        $role = get_role('administrator');
        if ($role) {
            foreach (self::getCapabilities() as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    public static function initDefaultOptions(): void
    {
        $defaults = [
            'wpci_general_settings' => [
                'currency'          => 'USD',
                'currency_position' => 'left',
                'date_format'       => 'Y-m-d',
                'default_due_days'  => 14,
            ],
            'wpci_business_settings' => [
                'business_name'  => get_bloginfo('name'),
                'trading_name'   => '',
                'address'        => '',
                'email'          => get_bloginfo('admin_email'),
                'phone'          => '',
                'website'        => home_url(),
                'tax_label'      => 'VAT',
                'tax_number'     => '',
                'company_number' => '',
                'logo_id'        => 0,
                'footer_text'    => __('Thank you for your business!', 'wpcalibrate-invoice-studio'),
            ],
            'wpci_numbering_settings' => [
                'prefix'         => 'INV-',
                'suffix'         => '',
                'start_number'   => 1,
                'next_number'    => 1,
                'padding'        => 5,
                'pattern'        => 'INV-{YYYY}-{NUMBER}',
                'reset_interval' => 'yearly', // never, yearly, monthly
                'last_reset'     => gmdate('Y-m'),
            ],
            'wpci_template_settings' => [
                'default_template' => 'classic',
                'primary_color'    => '#2b4c7e',
                'accent_color'     => '#1f385c',
                'font_family'      => 'Helvetica',
                'show_tax_column'  => true,
                'show_sku_column'  => true,
            ],
            'wpci_email_settings' => [
                'sender_name'     => get_bloginfo('name'),
                'sender_email'    => get_bloginfo('admin_email'),
                'invoice_subject' => __('Invoice {invoice_number} from {business_name}', 'wpcalibrate-invoice-studio'),
                'invoice_body'    => __("Dear {customer_name},\n\nPlease find attached invoice {invoice_number} for {invoice_total}.\nDue date: {due_date}.\n\nYou can view and pay your invoice online here:\n{invoice_url}\n\nThank you,\n{business_name}", 'wpcalibrate-invoice-studio'),
                'reminder_subject'=> __('Payment Reminder: Invoice {invoice_number}', 'wpcalibrate-invoice-studio'),
                'reminder_body'   => __("Dear {customer_name},\n\nThis is a friendly reminder that invoice {invoice_number} for {invoice_total} is due on {due_date}.\n\nView and pay online:\n{invoice_url}\n\nThank you,\n{business_name}", 'wpcalibrate-invoice-studio'),
                'attach_pdf'      => true,
            ],
            'wpci_woocommerce_settings' => [
                'auto_generate'     => false,
                'trigger_statuses'  => ['wc-processing', 'wc-completed'],
                'attach_to_emails'  => ['customer_completed_order', 'customer_invoice'],
                'my_account_tab'    => true,
                'allow_order_pay'   => true,
            ],
            'wpci_privacy_settings' => [
                'erase_personal_data'       => true,
                'delete_data_on_uninstall'  => false,
                'token_expiry_days'         => 30,
            ],
        ];

        foreach ($defaults as $optKey => $defaultVal) {
            if (get_option($optKey) === false) {
                add_option($optKey, $defaultVal);
            }
        }
    }

    public static function setupUploadsDirectory(): void
    {
        $uploadDir = wp_upload_dir();
        $targetDir = trailingslashit($uploadDir['basedir']) . 'wpci-invoices';

        if (!file_exists($targetDir)) {
            wp_mkdir_p($targetDir);
        }

        // Add index.html to prevent directory listing
        $indexFile = trailingslashit($targetDir) . 'index.html';
        if (!file_exists($indexFile)) {
            @file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        // Add .htaccess to block direct web access to PDF files
        $htaccessFile = trailingslashit($targetDir) . '.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = "# Prevent direct access to invoice PDFs\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n";
            @file_put_contents($htaccessFile, $htaccessContent);
        }
    }
}
