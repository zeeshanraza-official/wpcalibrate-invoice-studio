<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Admin;

use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus;
use WPCalibrate\InvoiceStudio\Template\InvoiceRenderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the Invoice Management List Screen.
 */
final class InvoiceListTable
{
    private InvoiceRepository $repository;
    private InvoiceRenderer $renderer;

    public function __construct(InvoiceRepository $repository, InvoiceRenderer $renderer)
    {
        $this->repository = $repository;
        $this->renderer = $renderer;
    }

    public function render(): void
    {
        if (!current_user_can('wpci_view_invoices')) {
            wp_die(esc_html__('You do not have permission to access invoices.', 'wpcalibrate-invoice-studio'));
        }

        // Handle quick actions if any
        $this->handleActions();

        $currentStatus = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
        $searchTerm = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $paged = max(1, isset($_GET['paged']) ? (int)$_GET['paged'] : 1);
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
        $order = (isset($_GET['order']) && strtoupper((string)$_GET['order']) === 'ASC') ? 'ASC' : 'DESC';

        $queryResult = $this->repository->query([
            'status'   => $currentStatus,
            'search'   => $searchTerm,
            'paged'    => $paged,
            'per_page' => 20,
            'orderby'  => $orderby,
            'order'    => $order,
        ]);

        /** @var InvoiceEntity[] $invoices */
        $invoices = $queryResult['invoices'];
        $totalItems = $queryResult['total'];
        $totalPages = $queryResult['pages'];

        $stats = $this->repository->getSummaryStats();
        $counts = $stats['counts'];
        $paidSum = $stats['paid_sum'];
        $dueSum = $stats['due_sum'];
        $brandIconUrl = plugins_url('branding/icon-dark.png', WPCI_PLUGIN_FILE);
        ?>
        <div class="wrap wpci-wrap">
            <!-- Executive Header -->
            <div class="wpci-header">
                <div class="wpci-header-left">
                    <div class="wpci-brand-badge">
                        <img src="<?php echo esc_url($brandIconUrl); ?>" alt="WPCalibrate" />
                    </div>
                    <div>
                        <h1 class="wpci-header-title">
                            <?php esc_html_e('Invoice Studio', 'wpcalibrate-invoice-studio'); ?>
                            <span class="wpci-version-tag">v<?php echo esc_html(WPCI_VERSION); ?></span>
                        </h1>
                        <div class="wpci-header-subtitle">
                            <?php esc_html_e('Manage, generate, preview, and collect payment for professional invoices.', 'wpcalibrate-invoice-studio'); ?>
                        </div>
                    </div>
                </div>
                <div class="wpci-header-right">
                    <?php if (current_user_can('wpci_manage_settings')): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . AdminMenu::SETTINGS_SLUG)); ?>" class="button">
                            <span class="dashicons dashicons-admin-generic" style="font-size: 15px; width: 15px; height: 15px; vertical-align: middle;"></span>
                            <?php esc_html_e('Settings', 'wpcalibrate-invoice-studio'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (current_user_can('wpci_create_invoices')): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . AdminMenu::ADD_INVOICE_SLUG)); ?>" class="button button-primary">
                            + <?php esc_html_e('New Invoice', 'wpcalibrate-invoice-studio'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KPI Metric Summary Strip -->
            <div class="wpci-kpi-grid">
                <div class="wpci-kpi-card wpci-kpi-blue">
                    <div class="wpci-kpi-icon"><span class="dashicons dashicons-media-document"></span></div>
                    <div class="wpci-kpi-content">
                        <div class="wpci-kpi-label"><?php esc_html_e('Total Invoices', 'wpcalibrate-invoice-studio'); ?></div>
                        <div class="wpci-kpi-value"><?php echo (int)$counts['all']; ?></div>
                        <div class="wpci-kpi-sub"><?php esc_html_e('All lifetime records', 'wpcalibrate-invoice-studio'); ?></div>
                    </div>
                </div>

                <div class="wpci-kpi-card wpci-kpi-green">
                    <div class="wpci-kpi-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="wpci-kpi-content">
                        <div class="wpci-kpi-label"><?php esc_html_e('Paid Invoices', 'wpcalibrate-invoice-studio'); ?></div>
                        <div class="wpci-kpi-value"><?php echo esc_html($this->renderer->formatPrice($paidSum)); ?></div>
                        <div class="wpci-kpi-sub"><?php printf(esc_html__('%d invoices settled', 'wpcalibrate-invoice-studio'), (int)$counts[InvoiceStatus::PAID]); ?></div>
                    </div>
                </div>

                <div class="wpci-kpi-card wpci-kpi-amber">
                    <div class="wpci-kpi-icon"><span class="dashicons dashicons-warning"></span></div>
                    <div class="wpci-kpi-content">
                        <div class="wpci-kpi-label"><?php esc_html_e('Outstanding Due', 'wpcalibrate-invoice-studio'); ?></div>
                        <div class="wpci-kpi-value" style="color: <?php echo $dueSum > 0 ? '#dc2626' : 'inherit'; ?>;"><?php echo esc_html($this->renderer->formatPrice($dueSum)); ?></div>
                        <div class="wpci-kpi-sub"><?php printf(esc_html__('%d awaiting settlement', 'wpcalibrate-invoice-studio'), (int)($counts[InvoiceStatus::ISSUED] + $counts[InvoiceStatus::SENT] + $counts[InvoiceStatus::PARTIALLY_PAID] + $counts[InvoiceStatus::OVERDUE])); ?></div>
                    </div>
                </div>

                <div class="wpci-kpi-card wpci-kpi-slate">
                    <div class="wpci-kpi-icon"><span class="dashicons dashicons-edit"></span></div>
                    <div class="wpci-kpi-content">
                        <div class="wpci-kpi-label"><?php esc_html_e('Drafts in Progress', 'wpcalibrate-invoice-studio'); ?></div>
                        <div class="wpci-kpi-value"><?php echo (int)$counts[InvoiceStatus::DRAFT]; ?></div>
                        <div class="wpci-kpi-sub"><?php esc_html_e('Unissued working drafts', 'wpcalibrate-invoice-studio'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Notices -->
            <?php settings_errors('wpci_invoice_notices'); ?>

            <!-- Status Tabs and Search Toolbar -->
            <div class="wpci-toolbar">
                <ul class="wpci-pill-nav">
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=' . AdminMenu::INVOICES_SLUG)); ?>" class="<?php echo empty($currentStatus) ? 'active' : ''; ?>">
                            <?php esc_html_e('All', 'wpcalibrate-invoice-studio'); ?>
                            <span class="wpci-pill-count"><?php echo (int)$counts['all']; ?></span>
                        </a>
                    </li>
                    <?php foreach (InvoiceStatus::all() as $st):
                        $label = InvoiceStatus::getLabel($st);
                        $count = $counts[$st] ?? 0;
                        $url = add_query_arg(['page' => AdminMenu::INVOICES_SLUG, 'status' => $st], admin_url('admin.php'));
                    ?>
                        <li>
                            <a href="<?php echo esc_url($url); ?>" class="<?php echo ($currentStatus === $st) ? 'active' : ''; ?>">
                                <?php echo esc_html($label); ?>
                                <span class="wpci-pill-count"><?php echo (int)$count; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <form method="get" class="wpci-search-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr(AdminMenu::INVOICES_SLUG); ?>" />
                    <?php if ($currentStatus): ?>
                        <input type="hidden" name="status" value="<?php echo esc_attr($currentStatus); ?>" />
                    <?php endif; ?>
                    <div class="wpci-search-box-group">
                        <input type="search" name="s" class="wpci-search-input" value="<?php echo esc_attr($searchTerm); ?>" placeholder="<?php esc_attr_e('Search invoices...', 'wpcalibrate-invoice-studio'); ?>" />
                        <button type="submit" class="wpci-search-btn" title="<?php esc_attr_e('Search invoices', 'wpcalibrate-invoice-studio'); ?>">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Elevated Table Card -->
            <div class="wpci-table-card">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column" style="width: 18%;"><?php esc_html_e('Invoice #', 'wpcalibrate-invoice-studio'); ?></th>
                            <th scope="col" class="manage-column" style="width: 22%;"><?php esc_html_e('Customer', 'wpcalibrate-invoice-studio'); ?></th>
                            <th scope="col" class="manage-column" style="width: 12%;"><?php esc_html_e('Source', 'wpcalibrate-invoice-studio'); ?></th>
                            <th scope="col" class="manage-column" style="width: 10%;"><?php esc_html_e('Issue Date', 'wpcalibrate-invoice-studio'); ?></th>
                            <th scope="col" class="manage-column" style="width: 10%;"><?php esc_html_e('Due Date', 'wpcalibrate-invoice-studio'); ?></th>
                            <th scope="col" class="manage-column" style="width: 12%;"><?php esc_html_e('Status', 'wpcalibrate-invoice-studio'); ?></th>
                            <th scope="col" class="manage-column" style="text-align: right; width: 10%;"><?php esc_html_e('Total', 'wpcalibrate-invoice-studio'); ?></th>
                            <th scope="col" class="manage-column" style="text-align: right; width: 10%;"><?php esc_html_e('Balance Due', 'wpcalibrate-invoice-studio'); ?></th>
                            <th scope="col" class="manage-column" style="text-align: center; width: 120px;"><?php esc_html_e('Actions', 'wpcalibrate-invoice-studio'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px; color: var(--wpci-slate-500);">
                                    <div style="font-size: 32px; margin-bottom: 8px;">📄</div>
                                    <strong style="font-size: 15px; color: var(--wpci-slate-700);"><?php esc_html_e('No invoices found.', 'wpcalibrate-invoice-studio'); ?></strong>
                                    <div style="font-size: 13px; margin-top: 4px;"><?php esc_html_e('Create your first manual invoice or receive an order through WooCommerce.', 'wpcalibrate-invoice-studio'); ?></div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv):
                                $id = $inv->getId();
                                $viewUrl = add_query_arg(['wpci_uuid' => $inv->getUuid(), 'wpci_view' => 'invoice'], home_url('/'));
                                $printUrl = add_query_arg(['wpci_uuid' => $inv->getUuid(), 'wpci_view' => 'print'], home_url('/'));
                                $downloadUrl = add_query_arg(['wpci_uuid' => $inv->getUuid(), 'wpci_action' => 'download_pdf'], home_url('/'));
                                $editUrl = add_query_arg(['page' => AdminMenu::ADD_INVOICE_SLUG, 'invoice_id' => $id], admin_url('admin.php'));

                                $sendNonceUrl = wp_nonce_url(admin_url("admin-post.php?action=wpci_send_invoice&invoice_id={$id}"), "wpci_send_{$id}");
                                $paidNonceUrl = wp_nonce_url(admin_url("admin-post.php?action=wpci_mark_paid&invoice_id={$id}"), "wpci_paid_{$id}");
                                $cancelNonceUrl = wp_nonce_url(admin_url("admin-post.php?action=wpci_cancel_invoice&invoice_id={$id}"), "wpci_cancel_{$id}");
                                $deleteNonceUrl = wp_nonce_url(admin_url("admin-post.php?action=wpci_delete_invoice&invoice_id={$id}"), "wpci_delete_{$id}");
                            ?>
                                <tr>
                                    <td>
                                        <strong style="font-size: 13.5px;">
                                             <a href="<?php echo esc_url($editUrl); ?>" style="color: var(--wpci-navy-700); text-decoration: none;">
                                                <?php echo esc_html($inv->getNumber() ?: __('(Draft)', 'wpcalibrate-invoice-studio')); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions" style="margin-top: 5px;">
                                            <span class="preview"><a href="#" class="wpci-trigger-preview" data-uuid="<?php echo esc_attr($inv->getUuid()); ?>" data-title="<?php echo esc_attr($inv->getNumber() ?: __('Draft Invoice', 'wpcalibrate-invoice-studio')); ?>"><?php esc_html_e('Preview', 'wpcalibrate-invoice-studio'); ?></a> | </span>
                                            <span class="view"><a href="<?php echo esc_url($viewUrl); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View', 'wpcalibrate-invoice-studio'); ?></a> | </span>
                                            <span class="pdf"><a href="<?php echo esc_url($downloadUrl); ?>"><?php esc_html_e('PDF', 'wpcalibrate-invoice-studio'); ?></a> | </span>
                                            <span class="print"><a href="<?php echo esc_url($printUrl); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Print', 'wpcalibrate-invoice-studio'); ?></a></span>
                                            <?php if (current_user_can('wpci_edit_invoices') && $inv->getStatus() !== InvoiceStatus::CANCELLED): ?>
                                                | <span class="edit"><a href="<?php echo esc_url($editUrl); ?>"><?php esc_html_e('Edit', 'wpcalibrate-invoice-studio'); ?></a></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--wpci-slate-900);"><?php echo esc_html($inv->getCustomer()->getName() ?: __('(Unnamed Customer)', 'wpcalibrate-invoice-studio')); ?></strong>
                                        <?php if ($inv->getCustomer()->getCompany()): ?>
                                            <div style="font-size: 11.5px; color: var(--wpci-slate-500);"><?php echo esc_html($inv->getCustomer()->getCompany()); ?></div>
                                        <?php elseif ($inv->getCustomer()->getEmail()): ?>
                                            <div style="font-size: 11.5px; color: var(--wpci-slate-500);"><?php echo esc_html($inv->getCustomer()->getEmail()); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($inv->getSource() === 'woocommerce'): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; background: #eef2ff; color: #4338ca; border-radius: 4px; font-size: 11.5px; font-weight: 600;">
                                                <span class="dashicons dashicons-cart" style="font-size: 14px; width: 14px; height: 14px; line-height: 1;"></span>
                                                <?php if ($inv->getOrderId()): ?>
                                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $inv->getOrderId() . '&action=edit')); ?>" style="color: inherit; text-decoration: none;">
                                                        #<?php echo esc_html((string)$inv->getOrderId()); ?>
                                                    </a>
                                                <?php else: ?>
                                                    WooCommerce
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px; background: var(--wpci-slate-100); color: var(--wpci-slate-600); border-radius: 4px; font-size: 11.5px; font-weight: 600;">
                                                <span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; line-height: 1;"></span>
                                                <?php esc_html_e('Manual', 'wpcalibrate-invoice-studio'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($inv->getIssueDate()); ?></td>
                                    <td>
                                        <span style="<?php echo ($inv->getStatus() === InvoiceStatus::OVERDUE) ? 'color: #dc2626; font-weight: 700;' : ''; ?>">
                                            <?php echo esc_html($inv->getDueDate()); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="wpci-status-badge <?php echo esc_attr(InvoiceStatus::getBadgeClass($inv->getStatus())); ?>">
                                            <?php echo esc_html(InvoiceStatus::getLabel($inv->getStatus())); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: var(--wpci-slate-800);">
                                        <?php echo esc_html($this->renderer->formatPrice($inv->getTotal(), $inv->getCurrency())); ?>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: <?php echo $inv->getAmountDue() > 0 ? 'var(--wpci-rose-600)' : 'var(--wpci-emerald-600)'; ?>;">
                                        <?php echo esc_html($this->renderer->formatPrice($inv->getAmountDue(), $inv->getCurrency())); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="wpci-action-btn-group">
                                            <button type="button" class="wpci-action-btn wpci-trigger-preview" data-uuid="<?php echo esc_attr($inv->getUuid()); ?>" data-title="<?php echo esc_attr($inv->getNumber() ?: __('Draft Invoice', 'wpcalibrate-invoice-studio')); ?>" title="<?php esc_attr_e('Live Preview', 'wpcalibrate-invoice-studio'); ?>">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </button>
                                            <?php if (current_user_can('wpci_send_invoices') && $inv->getCustomer()->getEmail()): ?>
                                                <a href="<?php echo esc_url($sendNonceUrl); ?>" class="wpci-action-btn" title="<?php esc_attr_e('Send by Email', 'wpcalibrate-invoice-studio'); ?>">
                                                    <span class="dashicons dashicons-email"></span>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (current_user_can('wpci_record_payments') && !$inv->isPaid() && $inv->getStatus() !== InvoiceStatus::CANCELLED): ?>
                                                <a href="<?php echo esc_url($paidNonceUrl); ?>" class="wpci-action-btn btn-pay" title="<?php esc_attr_e('Mark as Paid', 'wpcalibrate-invoice-studio'); ?>" onclick="return confirm('<?php esc_attr_e('Mark this invoice as fully paid?', 'wpcalibrate-invoice-studio'); ?>');">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (current_user_can('wpci_edit_invoices') && $inv->getStatus() !== InvoiceStatus::CANCELLED): ?>
                                                <a href="<?php echo esc_url($cancelNonceUrl); ?>" class="wpci-action-btn btn-cancel" title="<?php esc_attr_e('Cancel Invoice', 'wpcalibrate-invoice-studio'); ?>" onclick="return confirm('<?php esc_attr_e('Cancel this invoice?', 'wpcalibrate-invoice-studio'); ?>');">
                                                    <span class="dashicons dashicons-no-alt"></span>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (current_user_can('wpci_delete_invoices') && $inv->getStatus() === InvoiceStatus::DRAFT): ?>
                                                <a href="<?php echo esc_url($deleteNonceUrl); ?>" class="wpci-action-btn btn-delete" title="<?php esc_attr_e('Delete Draft', 'wpcalibrate-invoice-studio'); ?>" onclick="return confirm('<?php esc_attr_e('Permanently delete this draft invoice?', 'wpcalibrate-invoice-studio'); ?>');">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <span class="displaying-num">
                            <?php printf(esc_html(_n('%s item', '%s items', $totalItems, 'wpcalibrate-invoice-studio')), number_format_i18n($totalItems)); ?>
                        </span>
                        <?php
                        echo paginate_links([
                            'base'      => add_query_arg('paged', '%#%'),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => $totalPages,
                            'current'   => $paged,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function handleActions(): void
    {
        // Safe GET notice messages
        if (isset($_GET['wpci_notice'])) {
            $notice = sanitize_key($_GET['wpci_notice']);
            if ($notice === 'sent') {
                add_settings_error('wpci_invoice_notices', 'wpci_sent', __('Invoice email sent successfully.', 'wpcalibrate-invoice-studio'), 'success');
            } elseif ($notice === 'send_failed') {
                add_settings_error('wpci_invoice_notices', 'wpci_send_failed', __('Failed to send invoice email.', 'wpcalibrate-invoice-studio'), 'error');
            } elseif ($notice === 'paid') {
                add_settings_error('wpci_invoice_notices', 'wpci_paid', __('Invoice marked as paid.', 'wpcalibrate-invoice-studio'), 'success');
            } elseif ($notice === 'cancelled') {
                add_settings_error('wpci_invoice_notices', 'wpci_cancelled', __('Invoice cancelled.', 'wpcalibrate-invoice-studio'), 'info');
            } elseif ($notice === 'deleted') {
                add_settings_error('wpci_invoice_notices', 'wpci_deleted', __('Draft invoice deleted.', 'wpcalibrate-invoice-studio'), 'info');
            }
        }
    }
}
