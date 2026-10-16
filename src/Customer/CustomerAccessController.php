<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Customer;

use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Logging\Logger;
use WPCalibrate\InvoiceStudio\Pdf\PdfService;
use WPCalibrate\InvoiceStudio\Template\InvoiceRenderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controller handling public and authenticated customer invoice viewing and downloading.
 */
final class CustomerAccessController
{
    private InvoiceRepository $repository;
    private InvoiceRenderer $renderer;
    private PdfService $pdfService;
    private GuestTokenService $tokenService;

    public function __construct(
        InvoiceRepository $repository,
        InvoiceRenderer $renderer,
        PdfService $pdfService,
        GuestTokenService $tokenService
    ) {
        $this->repository = $repository;
        $this->renderer = $renderer;
        $this->pdfService = $pdfService;
        $this->tokenService = $tokenService;
    }

    public function register(): void
    {
        add_action('template_redirect', [$this, 'handleCustomerRequest']);
    }

    public function handleCustomerRequest(): void
    {
        // Check if this is an invoice view or download request
        $uuid = isset($_GET['wpci_uuid']) ? sanitize_text_field(wp_unslash($_GET['wpci_uuid'])) : '';
        if (empty($uuid)) {
            return;
        }

        $invoice = $this->repository->findByUuid($uuid);
        if (!$invoice) {
            wp_die(
                esc_html__('Invoice not found.', 'wpcalibrate-invoice-studio'),
                esc_html__('Error', 'wpcalibrate-invoice-studio'),
                ['response' => 404]
            );
        }

        // Authorization check
        if (!$this->canAccessInvoice($invoice)) {
            Logger::warning('Unauthorized invoice access attempt.', [
                'invoice_id' => $invoice->getId(),
                'user_id'    => get_current_user_id(),
                'ip'         => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
            wp_die(
                esc_html__('You do not have permission to view this invoice or the secure access link has expired.', 'wpcalibrate-invoice-studio'),
                esc_html__('Access Denied', 'wpcalibrate-invoice-studio'),
                ['response' => 403]
            );
        }

        $action = isset($_GET['wpci_action']) ? sanitize_key($_GET['wpci_action']) : '';
        $view = isset($_GET['wpci_view']) ? sanitize_key($_GET['wpci_view']) : 'invoice';

        // 1. Download PDF
        if ($action === 'download_pdf') {
            $this->pdfService->streamPdf($invoice, 'attachment');
            exit;
        }

        // 2. View PDF inline
        if ($action === 'view_pdf') {
            $this->pdfService->streamPdf($invoice, 'inline');
            exit;
        }

        // 3. Print view
        if ($view === 'print') {
            echo $this->renderer->render($invoice, 'print'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        // 4. Default Browser HTML view
        echo $this->renderer->render($invoice, 'browser'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Check if current visitor is authorized to access the given invoice.
     */
    public function canAccessInvoice(InvoiceEntity $invoice): bool
    {
        // 1. Administrators or authorized staff
        if (current_user_can('wpci_view_invoices')) {
            return true;
        }

        // 2. Authenticated user who owns this invoice
        $currentUserId = get_current_user_id();
        if ($currentUserId > 0) {
            $invoiceUserId = $invoice->getCustomer()->getUserId();
            if ($invoiceUserId && (int)$invoiceUserId === $currentUserId) {
                return true;
            }
        }

        // 3. Guest token verification
        $rawToken = isset($_GET['wpci_token']) ? sanitize_text_field(wp_unslash($_GET['wpci_token'])) : '';
        if (!empty($rawToken) && $invoice->getId()) {
            return $this->tokenService->verifyToken($invoice->getId(), $rawToken);
        }

        return false;
    }
}
