<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Bootstrap;

use WPCalibrate\InvoiceStudio\Admin\AdminAssets;
use WPCalibrate\InvoiceStudio\Admin\AdminMenu;
use WPCalibrate\InvoiceStudio\Admin\InvoiceEditor;
use WPCalibrate\InvoiceStudio\Admin\InvoiceListTable;
use WPCalibrate\InvoiceStudio\Admin\PreviewController;
use WPCalibrate\InvoiceStudio\Admin\SettingsPage;
use WPCalibrate\InvoiceStudio\Customer\CustomerAccessController;
use WPCalibrate\InvoiceStudio\Customer\GuestTokenService;
use WPCalibrate\InvoiceStudio\Email\EmailService;
use WPCalibrate\InvoiceStudio\Invoice\ActivityLogger;
use WPCalibrate\InvoiceStudio\Invoice\InvoicePostType;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus;
use WPCalibrate\InvoiceStudio\Licensing\LicenseManager;
use WPCalibrate\InvoiceStudio\Migration\MigrationManager;
use WPCalibrate\InvoiceStudio\Numbering\NumberingService;
use WPCalibrate\InvoiceStudio\Payment\PaymentService;
use WPCalibrate\InvoiceStudio\Pdf\DompdfEngine;
use WPCalibrate\InvoiceStudio\Pdf\PdfService;
use WPCalibrate\InvoiceStudio\Privacy\PrivacyManager;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;
use WPCalibrate\InvoiceStudio\Template\InvoiceRenderer;
use WPCalibrate\InvoiceStudio\Template\TemplateManager;
use WPCalibrate\InvoiceStudio\WooCommerce\MyAccountInvoices;
use WPCalibrate\InvoiceStudio\WooCommerce\OrderInvoiceHandler;
use WPCalibrate\InvoiceStudio\WooCommerce\WooCommerceIntegration;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main application orchestrator for WPCalibrate Invoice Studio.
 */
final class Plugin
{
    private static ?self $instance = null;

    private SettingsManager $settingsManager;
    private InvoiceRepository $repository;
    private NumberingService $numberingService;
    private TemplateManager $templateManager;
    private InvoiceRenderer $renderer;
    private DompdfEngine $pdfEngine;
    private PdfService $pdfService;
    private GuestTokenService $tokenService;
    private EmailService $emailService;
    private PaymentService $paymentService;
    private LicenseManager $licenseManager;
    private CustomerAccessController $customerController;
    private InvoiceListTable $listTable;
    private InvoiceEditor $editor;
    private SettingsPage $settingsPage;
    private PreviewController $previewController;
    private AdminMenu $adminMenu;
    private AdminAssets $adminAssets;
    private InvoicePostType $postType;
    private PrivacyManager $privacyManager;
    private MigrationManager $migrationManager;
    private ?WooCommerceIntegration $wooCommerceIntegration = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->compose();
    }

    private function compose(): void
    {
        // Settings & Infrastructure
        $this->settingsManager = new SettingsManager();
        $this->repository = new InvoiceRepository();
        $this->numberingService = new NumberingService();
        $this->templateManager = new TemplateManager();
        $this->renderer = new InvoiceRenderer($this->templateManager, $this->settingsManager);

        // PDF & Tokens
        $this->pdfEngine = new DompdfEngine();
        $this->pdfService = new PdfService($this->pdfEngine, $this->renderer, $this->settingsManager);
        $this->tokenService = new GuestTokenService();

        // Email & Payments
        $this->emailService = new EmailService(
            $this->settingsManager,
            $this->pdfService,
            $this->renderer,
            $this->tokenService,
            $this->repository
        );
        $this->paymentService = new PaymentService($this->repository);

        // Customer Access
        $this->customerController = new CustomerAccessController(
            $this->repository,
            $this->renderer,
            $this->pdfService,
            $this->tokenService
        );

        // Licensing & Admin
        $this->licenseManager = new LicenseManager();
        $this->listTable = new InvoiceListTable($this->repository, $this->renderer);
        $this->editor = new InvoiceEditor(
            $this->repository,
            $this->numberingService,
            $this->settingsManager,
            $this->templateManager
        );
        $this->settingsPage = new SettingsPage(
            $this->settingsManager,
            $this->numberingService,
            $this->licenseManager,
            $this->templateManager
        );
        $this->previewController = new PreviewController(
            $this->repository,
            $this->renderer,
            $this->settingsManager,
            $this->templateManager
        );
        $this->adminMenu = new AdminMenu($this->listTable, $this->editor, $this->settingsPage);
        $this->adminAssets = new AdminAssets();

        // Post type, privacy & migrations
        $this->postType = new InvoicePostType();
        $this->privacyManager = new PrivacyManager($this->repository, $this->settingsManager);
        $this->migrationManager = new MigrationManager();

        // WooCommerce Integration
        $orderHandler = new OrderInvoiceHandler($this->repository, $this->numberingService, $this->settingsManager);
        $myAccount = new MyAccountInvoices($this->repository, $this->settingsManager, $this->renderer);
        $this->wooCommerceIntegration = new WooCommerceIntegration(
            $orderHandler,
            $this->paymentService,
            $myAccount,
            $this->settingsManager,
            $this->repository,
            $this->pdfService
        );
    }

    public function init(): void
    {
        // Run any needed data migrations
        $this->migrationManager->runIfNeeded();

        // Register post type and custom statuses
        $this->postType->register();

        // Register customer view & download controller
        $this->customerController->register();

        // Register preview controller
        $this->previewController->register();

        // Register admin menu and assets
        $this->adminMenu->register();
        $this->adminAssets->register();

        // Register privacy tools integration
        $this->privacyManager->register();

        // Register WooCommerce integration
        if ($this->wooCommerceIntegration) {
            $this->wooCommerceIntegration->register();
        }

        // Register admin-post handlers
        $this->registerActionHandlers();

        // Flush rewrite rules if flagged on activation
        add_action('init', [$this, 'maybeFlushRewriteRules'], 99);
    }

    private function registerActionHandlers(): void
    {
        add_action('admin_post_wpci_save_invoice', [$this->editor, 'handleSave']);
        add_action('admin_post_wpci_send_invoice', [$this, 'handleSendInvoice']);
        add_action('admin_post_wpci_mark_paid', [$this, 'handleMarkPaid']);
        add_action('admin_post_wpci_cancel_invoice', [$this, 'handleCancelInvoice']);
        add_action('admin_post_wpci_delete_invoice', [$this, 'handleDeleteInvoice']);
        add_action('admin_post_wpci_generate_order_invoice', [$this, 'handleGenerateOrderInvoice']);
    }

    public function handleSendInvoice(): void
    {
        $id = isset($_GET['invoice_id']) ? absint($_GET['invoice_id']) : 0;
        check_admin_referer("wpci_send_{$id}");

        if (!current_user_can('wpci_send_invoices')) {
            wp_die(esc_html__('Unauthorized.', 'wpcalibrate-invoice-studio'), 403);
        }

        $invoice = $this->repository->findById($id);
        if ($invoice) {
            $sent = $this->emailService->sendInvoice($invoice);
            $notice = $sent ? 'sent' : 'send_failed';
        } else {
            $notice = 'send_failed';
        }

        wp_safe_redirect(add_query_arg(['page' => AdminMenu::INVOICES_SLUG, 'wpci_notice' => $notice], admin_url('admin.php')));
        exit;
    }

    public function handleMarkPaid(): void
    {
        $id = isset($_GET['invoice_id']) ? absint($_GET['invoice_id']) : 0;
        check_admin_referer("wpci_paid_{$id}");

        if (!current_user_can('wpci_record_payments')) {
            wp_die(esc_html__('Unauthorized.', 'wpcalibrate-invoice-studio'), 403);
        }

        $invoice = $this->repository->findById($id);
        if ($invoice && !$invoice->isPaid()) {
            $invoice->setStatus(InvoiceStatus::PAID);
            $invoice->setAmountPaid($invoice->getTotal());
            $invoice->setAmountDue(0.0);
            $invoice->setPaidDate(current_time('mysql'));
            $this->repository->save($invoice);

            ActivityLogger::log($id, 'marked_paid', __('Invoice marked as paid manually by administrator.', 'wpcalibrate-invoice-studio'));
        }

        wp_safe_redirect(add_query_arg(['page' => AdminMenu::INVOICES_SLUG, 'wpci_notice' => 'paid'], admin_url('admin.php')));
        exit;
    }

    public function handleCancelInvoice(): void
    {
        $id = isset($_GET['invoice_id']) ? absint($_GET['invoice_id']) : 0;
        check_admin_referer("wpci_cancel_{$id}");

        if (!current_user_can('wpci_edit_invoices')) {
            wp_die(esc_html__('Unauthorized.', 'wpcalibrate-invoice-studio'), 403);
        }

        $invoice = $this->repository->findById($id);
        if ($invoice && $invoice->getStatus() !== InvoiceStatus::CANCELLED) {
            $invoice->setStatus(InvoiceStatus::CANCELLED);
            $this->repository->save($invoice);

            ActivityLogger::log($id, 'cancelled', __('Invoice cancelled by administrator.', 'wpcalibrate-invoice-studio'));
        }

        wp_safe_redirect(add_query_arg(['page' => AdminMenu::INVOICES_SLUG, 'wpci_notice' => 'cancelled'], admin_url('admin.php')));
        exit;
    }

    public function handleDeleteInvoice(): void
    {
        $id = isset($_GET['invoice_id']) ? absint($_GET['invoice_id']) : 0;
        check_admin_referer("wpci_delete_{$id}");

        if (!current_user_can('wpci_delete_invoices')) {
            wp_die(esc_html__('Unauthorized.', 'wpcalibrate-invoice-studio'), 403);
        }

        $invoice = $this->repository->findById($id);
        if ($invoice && $invoice->getStatus() === InvoiceStatus::DRAFT) {
            $this->pdfService->invalidate($invoice);
            $this->repository->delete($id, true);
        }

        wp_safe_redirect(add_query_arg(['page' => AdminMenu::INVOICES_SLUG, 'wpci_notice' => 'deleted'], admin_url('admin.php')));
        exit;
    }

    public function handleGenerateOrderInvoice(): void
    {
        $orderId = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        check_admin_referer("wpci_generate_order_invoice_{$orderId}");

        if (!current_user_can('wpci_create_invoices')) {
            wp_die(esc_html__('Unauthorized.', 'wpcalibrate-invoice-studio'), 403);
        }

        $orderHandler = new OrderInvoiceHandler($this->repository, $this->numberingService, $this->settingsManager);
        $invoice = $orderHandler->createFromOrder($orderId, false);

        $redirect = wp_get_referer() ?: admin_url('post.php?post=' . $orderId . '&action=edit');
        wp_safe_redirect($redirect);
        exit;
    }

    public function maybeFlushRewriteRules(): void
    {
        if (get_transient('wpci_flush_rewrite_rules')) {
            delete_transient('wpci_flush_rewrite_rules');
            flush_rewrite_rules();
        }
    }

    // Getters for testing and external integrations
    public function getRepository(): InvoiceRepository { return $this->repository; }
    public function getSettingsManager(): SettingsManager { return $this->settingsManager; }
    public function getNumberingService(): NumberingService { return $this->numberingService; }
    public function getPdfService(): PdfService { return $this->pdfService; }
    public function getEmailService(): EmailService { return $this->emailService; }
    public function getTokenService(): GuestTokenService { return $this->tokenService; }
    public function getRenderer(): InvoiceRenderer { return $this->renderer; }
}
