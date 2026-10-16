<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\WooCommerce;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Pdf\PdfService;
use WPCalibrate\InvoiceStudio\Payment\PaymentService;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main WooCommerce integration hub. Handles HPOS declaration, order hooks, and admin actions.
 */
final class WooCommerceIntegration
{
    private OrderInvoiceHandler $orderHandler;
    private PaymentService $paymentService;
    private MyAccountInvoices $myAccount;
    private SettingsManager $settingsManager;
    private InvoiceRepository $repository;
    private PdfService $pdfService;

    public function __construct(
        OrderInvoiceHandler $orderHandler,
        PaymentService $paymentService,
        MyAccountInvoices $myAccount,
        SettingsManager $settingsManager,
        InvoiceRepository $repository,
        PdfService $pdfService
    ) {
        $this->orderHandler = $orderHandler;
        $this->paymentService = $paymentService;
        $this->myAccount = $myAccount;
        $this->settingsManager = $settingsManager;
        $this->repository = $repository;
        $this->pdfService = $pdfService;
    }

    public function register(): void
    {
        // Declare HPOS and Blocks compatibility
        add_action('before_woocommerce_init', [$this, 'declareCompatibility']);

        // Check if WooCommerce is installed and active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Auto-generation hook on order status change
        add_action('woocommerce_order_status_changed', [$this, 'onOrderStatusChanged'], 10, 4);

        // Payment completion hooks
        add_action('woocommerce_payment_complete', [$this, 'onPaymentComplete'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'onPaymentComplete'], 10, 1);

        // Order admin metabox / actions (both HPOS and classic)
        add_action('add_meta_boxes', [$this, 'addOrderMetaBox'], 20, 2);

        // WooCommerce transactional email PDF attachment
        add_filter('woocommerce_email_attachments', [$this, 'attachPdfToOrderEmails'], 10, 3);

        // Register My Account endpoint
        $this->myAccount->register();
    }

    /**
     * Declare WooCommerce HPOS and Cart & Checkout Blocks compatibility.
     */
    public function declareCompatibility(): void
    {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility('custom_order_tables', WPCI_PLUGIN_FILE, true);
            FeaturesUtil::declare_compatibility('cart_checkout_blocks', WPCI_PLUGIN_FILE, true);
        }
    }

    /**
     * Automatically generate invoice when order transitions to a configured trigger status.
     */
    public function onOrderStatusChanged(int $orderId, string $oldStatus, string $newStatus, \WC_Order $order): void
    {
        $settings = $this->settingsManager->getWooCommerce();
        if (empty($settings['auto_generate'])) {
            return;
        }

        $triggers = (array)($settings['trigger_statuses'] ?? ['wc-processing', 'wc-completed']);
        // Normalize status with or without 'wc-'
        $normalizedNew = str_starts_with($newStatus, 'wc-') ? $newStatus : 'wc-' . $newStatus;

        if (in_array($normalizedNew, $triggers, true)) {
            $this->orderHandler->createFromOrder($orderId, true);
        }
    }

    /**
     * Synchronize online payment back to invoice.
     */
    public function onPaymentComplete(int $orderId): void
    {
        $this->paymentService->handleWooCommerceOrderPayment($orderId);
    }

    /**
     * Add Invoice Metabox on Order Screen.
     */
    public function addOrderMetaBox(string $postType, $postOrOrder): void
    {
        // Support both classic post type and HPOS screen
        $screen = function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop_order') : 'shop_order';
        $screens = ['shop_order', $screen];

        if (in_array($postType, $screens, true) || (is_object($postOrOrder) && is_a($postOrOrder, 'WC_Order'))) {
            add_meta_box(
                'wpci_order_invoice_box',
                __('WPCalibrate Invoice', 'wpcalibrate-invoice-studio'),
                [$this, 'renderOrderMetaBox'],
                $postType,
                'side',
                'default'
            );
        }
    }

    /**
     * Render Order Metabox content.
     */
    public function renderOrderMetaBox($postOrOrder): void
    {
        $order = is_a($postOrOrder, 'WC_Order') ? $postOrOrder : wc_get_order($postOrOrder->ID ?? 0);
        if (!$order) {
            echo '<p>' . esc_html__('Order not found.', 'wpcalibrate-invoice-studio') . '</p>';
            return;
        }

        $orderId = $order->get_id();
        $invoice = $this->repository->findByOrderId($orderId);

        if ($invoice):
            $viewUrl = add_query_arg(['wpci_uuid' => $invoice->getUuid(), 'wpci_view' => 'invoice'], home_url('/'));
            $downloadUrl = add_query_arg(['wpci_uuid' => $invoice->getUuid(), 'wpci_action' => 'download_pdf'], home_url('/'));
        ?>
            <p><strong><?php esc_html_e('Invoice Number:', 'wpcalibrate-invoice-studio'); ?></strong> <?php echo esc_html($invoice->getNumber()); ?></p>
            <p><strong><?php esc_html_e('Status:', 'wpcalibrate-invoice-studio'); ?></strong> <?php echo esc_html(\WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus::getLabel($invoice->getStatus())); ?></p>
            <p><strong><?php esc_html_e('Total:', 'wpcalibrate-invoice-studio'); ?></strong> <?php echo esc_html(wc_price($invoice->getTotal(), ['currency' => $invoice->getCurrency()])); ?></p>
            <div style="margin-top: 10px;">
                <a href="<?php echo esc_url($viewUrl); ?>" class="button button-secondary" target="_blank"><?php esc_html_e('View Invoice', 'wpcalibrate-invoice-studio'); ?></a>
                <a href="<?php echo esc_url($downloadUrl); ?>" class="button button-secondary"><?php esc_html_e('Download PDF', 'wpcalibrate-invoice-studio'); ?></a>
            </div>
        <?php else:
            $generateUrl = wp_nonce_url(
                admin_url('admin-post.php?action=wpci_generate_order_invoice&order_id=' . $orderId),
                'wpci_generate_order_invoice_' . $orderId
            );
        ?>
            <p><?php esc_html_e('No invoice has been generated for this order yet.', 'wpcalibrate-invoice-studio'); ?></p>
            <a href="<?php echo esc_url($generateUrl); ?>" class="button button-primary"><?php esc_html_e('Generate Invoice Now', 'wpcalibrate-invoice-studio'); ?></a>
        <?php endif;
    }

    /**
     * Attach invoice PDF to WooCommerce transactional customer emails.
     */
    public function attachPdfToOrderEmails(array $attachments, string $emailId, $order): array
    {
        if (!is_a($order, 'WC_Order')) {
            return $attachments;
        }

        $settings = $this->settingsManager->getWooCommerce();
        $configuredEmailIds = (array)($settings['attach_to_emails'] ?? ['customer_completed_order', 'customer_invoice']);

        if (!in_array($emailId, $configuredEmailIds, true)) {
            return $attachments;
        }

        $orderId = $order->get_id();
        $invoice = $this->repository->findByOrderId($orderId);

        if (!$invoice) {
            // If configured, generate on the fly
            $invoice = $this->orderHandler->createFromOrder($orderId, true);
        }

        if ($invoice) {
            try {
                $pdfPath = $this->pdfService->getPdfPath($invoice);
                if (file_exists($pdfPath)) {
                    $attachments[] = $pdfPath;
                }
            } catch (\Throwable $e) {
                // Do not block email dispatch on PDF error
            }
        }

        return $attachments;
    }
}
