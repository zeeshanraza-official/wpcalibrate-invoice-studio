<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Invoice;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the non-public Custom Post Type and custom post statuses for invoices.
 */
final class InvoicePostType
{
    public const POST_TYPE = 'wpci_invoice';

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType'], 5);
        add_action('init', [$this, 'registerCustomStatuses'], 6);
    }

    public function registerPostType(): void
    {
        $labels = [
            'name'                  => _x('Invoices', 'Post type general name', 'wpcalibrate-invoice-studio'),
            'singular_name'         => _x('Invoice', 'Post type singular name', 'wpcalibrate-invoice-studio'),
            'menu_name'             => _x('Invoices', 'Admin Menu text', 'wpcalibrate-invoice-studio'),
            'name_admin_bar'        => _x('Invoice', 'Add New on Toolbar', 'wpcalibrate-invoice-studio'),
            'add_new'               => __('Add New', 'wpcalibrate-invoice-studio'),
            'add_new_item'          => __('Add New Invoice', 'wpcalibrate-invoice-studio'),
            'new_item'              => __('New Invoice', 'wpcalibrate-invoice-studio'),
            'edit_item'             => __('Edit Invoice', 'wpcalibrate-invoice-studio'),
            'view_item'             => __('View Invoice', 'wpcalibrate-invoice-studio'),
            'all_items'             => __('All Invoices', 'wpcalibrate-invoice-studio'),
            'search_items'          => __('Search Invoices', 'wpcalibrate-invoice-studio'),
            'not_found'             => __('No invoices found.', 'wpcalibrate-invoice-studio'),
            'not_found_in_trash'    => __('No invoices found in Trash.', 'wpcalibrate-invoice-studio'),
        ];

        $args = [
            'labels'             => $labels,
            'description'        => __('WPCalibrate Invoice Studio internal records', 'wpcalibrate-invoice-studio'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false, // Managed strictly via custom plugin screens
            'show_in_menu'       => false,
            'show_in_nav_menus'  => false,
            'show_in_admin_bar'  => false,
            'show_in_rest'       => false, // Prevent public REST endpoint exposure
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => ['title'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function registerCustomStatuses(): void
    {
        $statuses = [
            InvoiceStatus::ISSUED => [
                'label'                     => _x('Issued', 'invoice status', 'wpcalibrate-invoice-studio'),
                'public'                    => false,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: number of issued invoices */
                'label_count'               => _n_noop('Issued <span class="count">(%s)</span>', 'Issued <span class="count">(%s)</span>', 'wpcalibrate-invoice-studio'),
            ],
            InvoiceStatus::SENT => [
                'label'                     => _x('Sent', 'invoice status', 'wpcalibrate-invoice-studio'),
                'public'                    => false,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: number of sent invoices */
                'label_count'               => _n_noop('Sent <span class="count">(%s)</span>', 'Sent <span class="count">(%s)</span>', 'wpcalibrate-invoice-studio'),
            ],
            InvoiceStatus::PARTIALLY_PAID => [
                'label'                     => _x('Partially Paid', 'invoice status', 'wpcalibrate-invoice-studio'),
                'public'                    => false,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: number of partially paid invoices */
                'label_count'               => _n_noop('Partially Paid <span class="count">(%s)</span>', 'Partially Paid <span class="count">(%s)</span>', 'wpcalibrate-invoice-studio'),
            ],
            InvoiceStatus::PAID => [
                'label'                     => _x('Paid', 'invoice status', 'wpcalibrate-invoice-studio'),
                'public'                    => false,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: number of paid invoices */
                'label_count'               => _n_noop('Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', 'wpcalibrate-invoice-studio'),
            ],
            InvoiceStatus::OVERDUE => [
                'label'                     => _x('Overdue', 'invoice status', 'wpcalibrate-invoice-studio'),
                'public'                    => false,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: number of overdue invoices */
                'label_count'               => _n_noop('Overdue <span class="count">(%s)</span>', 'Overdue <span class="count">(%s)</span>', 'wpcalibrate-invoice-studio'),
            ],
            InvoiceStatus::CANCELLED => [
                'label'                     => _x('Cancelled', 'invoice status', 'wpcalibrate-invoice-studio'),
                'public'                    => false,
                'exclude_from_search'       => true,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                /* translators: %s: number of cancelled invoices */
                'label_count'               => _n_noop('Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'wpcalibrate-invoice-studio'),
            ],
        ];

        foreach ($statuses as $statusKey => $args) {
            register_post_status($statusKey, $args);
        }
    }
}
