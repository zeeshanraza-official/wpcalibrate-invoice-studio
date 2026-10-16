<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\WooCommerce;

use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;
use WPCalibrate\InvoiceStudio\Template\InvoiceRenderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles WooCommerce My Account 'Invoices' endpoint and template rendering.
 */
final class MyAccountInvoices
{
    public const ENDPOINT = 'wpci-invoices';

    private InvoiceRepository $repository;
    private SettingsManager $settingsManager;
    private InvoiceRenderer $renderer;

    public function __construct(
        InvoiceRepository $repository,
        SettingsManager $settingsManager,
        InvoiceRenderer $renderer
    ) {
        $this->repository = $repository;
        $this->settingsManager = $settingsManager;
        $this->renderer = $renderer;
    }

    public function register(): void
    {
        $wcSettings = $this->settingsManager->getWooCommerce();
        if (empty($wcSettings['my_account_tab'])) {
            return;
        }

        add_action('init', [$this, 'addEndpoint']);
        add_filter('woocommerce_account_menu_items', [$this, 'addMenuItem'], 40);
        add_action('woocommerce_account_' . self::ENDPOINT . '_endpoint', [$this, 'renderEndpoint']);
    }

    public function addEndpoint(): void
    {
        add_rewrite_endpoint(self::ENDPOINT, EP_ROOT | EP_PAGES);
    }

    public function addMenuItem(array $items): array
    {
        $newItems = [];
        foreach ($items as $key => $title) {
            $newItems[$key] = $title;
            if ($key === 'orders') {
                $newItems[self::ENDPOINT] = __('Invoices', 'wpcalibrate-invoice-studio');
            }
        }

        if (!isset($newItems[self::ENDPOINT])) {
            $newItems[self::ENDPOINT] = __('Invoices', 'wpcalibrate-invoice-studio');
        }

        return $newItems;
    }

    public function renderEndpoint(): void
    {
        $userId = get_current_user_id();
        if (!$userId) {
            echo '<p>' . esc_html__('Please log in to view your invoices.', 'wpcalibrate-invoice-studio') . '</p>';
            return;
        }

        $invoices = $this->repository->findByCustomerId($userId);

        if (empty($invoices)) {
            echo '<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">' .
                esc_html__('No invoices have been issued to your account yet.', 'wpcalibrate-invoice-studio') .
                '</div>';
            return;
        }

        ?>
        <h3><?php esc_html_e('Your Invoices', 'wpcalibrate-invoice-studio'); ?></h3>
        <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
            <thead>
                <tr>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Invoice', 'wpcalibrate-invoice-studio'); ?></span></th>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Date', 'wpcalibrate-invoice-studio'); ?></span></th>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Status', 'wpcalibrate-invoice-studio'); ?></span></th>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Total', 'wpcalibrate-invoice-studio'); ?></span></th>
                    <th class="woocommerce-orders-table__header"><span class="nobr"><?php esc_html_e('Actions', 'wpcalibrate-invoice-studio'); ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv):
                    $viewUrl = add_query_arg([
                        'wpci_uuid'   => $inv->getUuid(),
                        'wpci_view'   => 'invoice',
                    ], home_url('/'));
                    $downloadUrl = add_query_arg([
                        'wpci_uuid'   => $inv->getUuid(),
                        'wpci_action' => 'download_pdf',
                    ], home_url('/'));
                ?>
                    <tr class="woocommerce-orders-table__row">
                        <td class="woocommerce-orders-table__cell">
                            <strong><?php echo esc_html($inv->getNumber() ?: __('Invoice', 'wpcalibrate-invoice-studio')); ?></strong>
                            <?php if ($inv->getOrderId()): ?>
                                <small style="display:block; color:#64748b;"><?php esc_html_e('Order:', 'wpcalibrate-invoice-studio'); ?> #<?php echo esc_html((string)$inv->getOrderId()); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="woocommerce-orders-table__cell">
                            <time datetime="<?php echo esc_attr($inv->getIssueDate()); ?>"><?php echo esc_html($inv->getIssueDate()); ?></time>
                        </td>
                        <td class="woocommerce-orders-table__cell">
                            <span class="wpci-status-badge <?php echo esc_attr(InvoiceStatus::getBadgeClass($inv->getStatus())); ?>">
                                <?php echo esc_html(InvoiceStatus::getLabel($inv->getStatus())); ?>
                            </span>
                        </td>
                        <td class="woocommerce-orders-table__cell">
                            <?php echo esc_html($this->renderer->formatPrice($inv->getTotal(), $inv->getCurrency())); ?>
                        </td>
                        <td class="woocommerce-orders-table__cell">
                            <a href="<?php echo esc_url($viewUrl); ?>" class="woocommerce-button button view" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View', 'wpcalibrate-invoice-studio'); ?></a>
                            <a href="<?php echo esc_url($downloadUrl); ?>" class="woocommerce-button button download"><?php esc_html_e('PDF', 'wpcalibrate-invoice-studio'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
