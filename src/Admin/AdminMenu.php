<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Admin;

use WPCalibrate\InvoiceStudio\Support\SupportInfo;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles WordPress Admin Menu registration under shared WPCalibrate menu.
 */
final class AdminMenu
{
    public const PARENT_SLUG = 'wpcalibrate';
    public const INVOICES_SLUG = 'wpcalibrate-invoice-studio';
    public const ADD_INVOICE_SLUG = 'wpcalibrate-invoice-studio-add';
    public const INVOICE_NEW_SLUG = self::ADD_INVOICE_SLUG;
    public const SETTINGS_SLUG = 'wpcalibrate-invoice-studio-settings';

    private InvoiceListTable $listTable;
    private InvoiceEditor $editor;
    private SettingsPage $settingsPage;

    public function __construct(
        InvoiceListTable $listTable,
        InvoiceEditor $editor,
        SettingsPage $settingsPage
    ) {
        $this->listTable = $listTable;
        $this->editor = $editor;
        $this->settingsPage = $settingsPage;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addAdminMenus'], 25);
        add_action('admin_head', [$this, 'addMenuStyles']);
    }

    public function addAdminMenus(): void
    {
        global $menu;

        // Check if top-level 'wpcalibrate' menu already exists
        $parentExists = false;
        if (is_array($menu)) {
            foreach ($menu as $item) {
                if (isset($item[2]) && $item[2] === self::PARENT_SLUG) {
                    $parentExists = true;
                    break;
                }
            }
        }

        $iconUrl = plugins_url('branding/icon-white.png', WPCI_PLUGIN_FILE);

        // Register top-level parent menu if not already registered by a sibling plugin
        if (!$parentExists) {
            add_menu_page(
                __('WPCalibrate', 'wpcalibrate-invoice-studio'),
                __('WPCalibrate', 'wpcalibrate-invoice-studio'),
                'wpci_view_invoices',
                self::PARENT_SLUG,
                [$this->listTable, 'render'],
                $iconUrl,
                58.5
            );
        }

        // Add 'Invoices' submenu
        add_submenu_page(
            self::PARENT_SLUG,
            __('Invoices &lsaquo; Invoice Studio &lsaquo; WPCalibrate', 'wpcalibrate-invoice-studio'),
            __('Invoices', 'wpcalibrate-invoice-studio'),
            'wpci_view_invoices',
            self::INVOICES_SLUG,
            [$this->listTable, 'render']
        );

        // Add 'Add Invoice' submenu
        add_submenu_page(
            self::PARENT_SLUG,
            __('Add Invoice &lsaquo; Invoice Studio &lsaquo; WPCalibrate', 'wpcalibrate-invoice-studio'),
            __('Add Invoice', 'wpcalibrate-invoice-studio'),
            'wpci_create_invoices',
            self::ADD_INVOICE_SLUG,
            [$this->editor, 'renderAdd']
        );

        // Add 'Settings' submenu
        add_submenu_page(
            self::PARENT_SLUG,
            __('Invoice Studio Settings &lsaquo; WPCalibrate', 'wpcalibrate-invoice-studio'),
            __('Invoice Studio Settings', 'wpcalibrate-invoice-studio'),
            'wpci_manage_settings',
            self::SETTINGS_SLUG,
            [$this->settingsPage, 'render']
        );
    }

    public function addMenuStyles(): void
    {
        ?>
        <style>
            #toplevel_page_wpcalibrate .wp-menu-image img {
                max-width: 20px;
                max-height: 20px;
                padding-top: 7px;
                opacity: 0.9;
            }
            #toplevel_page_wpcalibrate:hover .wp-menu-image img {
                opacity: 1;
            }
        </style>
        <?php
    }
}
