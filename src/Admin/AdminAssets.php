<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueues admin stylesheets and scripts only on plugin-owned admin pages.
 */
final class AdminAssets
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        $allowedHooks = [
            'toplevel_page_wpcalibrate',
            'wpcalibrate_page_wpcalibrate-invoice-studio',
            'wpcalibrate_page_wpcalibrate-invoice-studio-add',
            'wpcalibrate_page_wpcalibrate-invoice-studio-settings',
        ];

        // Only enqueue on our specific plugin admin screens
        if (!in_array($hook, $allowedHooks, true)) {
            return;
        }

        // Enqueue WordPress media scripts for logo picker on settings page
        if ($hook === 'wpcalibrate_page_wpcalibrate-invoice-studio-settings') {
            wp_enqueue_media();
        }

        wp_enqueue_style(
            'wpci-admin-css',
            plugins_url('assets/admin/css/admin.css', WPCI_PLUGIN_FILE),
            [],
            WPCI_VERSION
        );

        wp_enqueue_script(
            'wpci-admin-editor-js',
            plugins_url('assets/admin/js/editor.js', WPCI_PLUGIN_FILE),
            ['jquery'],
            WPCI_VERSION,
            true
        );

        wp_localize_script('wpci-admin-editor-js', 'wpciEditorData', [
            'ajaxUrl'          => admin_url('admin-ajax.php'),
            'directPreviewUrl' => admin_url('admin-post.php?action=wpci_preview_template'),
            'nonce'            => wp_create_nonce('wpci_admin_action_nonce'),
            'i18n'             => [
                'confirmDelete' => __('Are you sure you want to delete this invoice?', 'wpcalibrate-invoice-studio'),
                'confirmCancel' => __('Are you sure you want to cancel this invoice?', 'wpcalibrate-invoice-studio'),
                'confirmIssue'  => __('Issuing this invoice will assign an official, immutable invoice number. Proceed?', 'wpcalibrate-invoice-studio'),
                'previewTitle'  => __('Invoice Output Preview', 'wpcalibrate-invoice-studio'),
                'loading'       => __('Rendering preview...', 'wpcalibrate-invoice-studio'),
            ],
        ]);
    }
}
