<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Email;

use WPCalibrate\InvoiceStudio\Customer\GuestTokenService;
use WPCalibrate\InvoiceStudio\Invoice\ActivityLogger;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceStatus;
use WPCalibrate\InvoiceStudio\Logging\Logger;
use WPCalibrate\InvoiceStudio\Pdf\PdfService;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;
use WPCalibrate\InvoiceStudio\Template\InvoiceRenderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service for preparing and dispatching invoice emails and payment reminders.
 */
final class EmailService
{
    private SettingsManager $settingsManager;
    private PdfService $pdfService;
    private InvoiceRenderer $renderer;
    private GuestTokenService $tokenService;
    private InvoiceRepository $repository;

    public function __construct(
        SettingsManager $settingsManager,
        PdfService $pdfService,
        InvoiceRenderer $renderer,
        GuestTokenService $tokenService,
        InvoiceRepository $repository
    ) {
        $this->settingsManager = $settingsManager;
        $this->pdfService = $pdfService;
        $this->renderer = $renderer;
        $this->tokenService = $tokenService;
        $this->repository = $repository;
    }

    /**
     * Send or resend an invoice to customer.
     */
    public function sendInvoice(
        InvoiceEntity $invoice,
        ?string $customSubject = null,
        ?string $customMessage = null
    ): bool {
        $recipient = $invoice->getCustomer()->getEmail();
        if (empty($recipient) || !is_email($recipient)) {
            Logger::warning('Cannot send invoice email: Customer email is missing or invalid.', [
                'invoice_id' => $invoice->getId(),
            ]);
            return false;
        }

        $emailSettings = $this->settingsManager->getEmail();
        $rawSubject = $customSubject ?: ($emailSettings['invoice_subject'] ?? __('Invoice {invoice_number}', 'wpcalibrate-invoice-studio'));
        $rawBody    = $customMessage ?: ($emailSettings['invoice_body'] ?? '');

        $subject = $this->replacePlaceholders($rawSubject, $invoice);
        $body    = $this->replacePlaceholders($rawBody, $invoice);

        $attachments = [];
        if (!empty($emailSettings['attach_pdf'])) {
            try {
                $pdfPath = $this->pdfService->getPdfPath($invoice);
                if (file_exists($pdfPath)) {
                    $attachments[] = $pdfPath;
                }
            } catch (\Throwable $e) {
                Logger::error('Failed to attach PDF to invoice email: ' . $e->getMessage(), [
                    'invoice_id' => $invoice->getId(),
                ]);
            }
        }

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $senderName  = $emailSettings['sender_name'] ?: get_bloginfo('name');
        $senderEmail = $emailSettings['sender_email'] ?: get_bloginfo('admin_email');
        if (is_email($senderEmail)) {
            $headers[] = sprintf('From: %s <%s>', $senderName, $senderEmail);
        }

        $sent = wp_mail($recipient, $subject, $body, $headers, $attachments);

        if ($sent) {
            if ($invoice->getId()) {
                ActivityLogger::log($invoice->getId(), 'sent', sprintf(
                    /* translators: %s: recipient email */
                    __('Invoice sent to %s.', 'wpcalibrate-invoice-studio'),
                    $this->maskEmail($recipient)
                ));

                // Update status to sent if draft or issued
                if (in_array($invoice->getStatus(), [InvoiceStatus::DRAFT, InvoiceStatus::ISSUED], true)) {
                    $invoice->setStatus(InvoiceStatus::SENT);
                    $this->repository->save($invoice);
                }
            }
            Logger::info('Invoice email sent successfully.', [
                'invoice_id' => $invoice->getId(),
                'recipient'  => $this->maskEmail($recipient),
            ]);
            return true;
        }

        Logger::error('wp_mail returned false when sending invoice email.', [
            'invoice_id' => $invoice->getId(),
            'recipient'  => $this->maskEmail($recipient),
        ]);
        return false;
    }

    /**
     * Send a payment reminder email.
     */
    public function sendReminder(
        InvoiceEntity $invoice,
        ?string $customSubject = null,
        ?string $customMessage = null
    ): bool {
        $recipient = $invoice->getCustomer()->getEmail();
        if (empty($recipient) || !is_email($recipient)) {
            return false;
        }

        $emailSettings = $this->settingsManager->getEmail();
        $rawSubject = $customSubject ?: ($emailSettings['reminder_subject'] ?? __('Payment Reminder: Invoice {invoice_number}', 'wpcalibrate-invoice-studio'));
        $rawBody    = $customMessage ?: ($emailSettings['reminder_body'] ?? '');

        $subject = $this->replacePlaceholders($rawSubject, $invoice);
        $body    = $this->replacePlaceholders($rawBody, $invoice);

        $attachments = [];
        if (!empty($emailSettings['attach_pdf'])) {
            try {
                $pdfPath = $this->pdfService->getPdfPath($invoice);
                if (file_exists($pdfPath)) {
                    $attachments[] = $pdfPath;
                }
            } catch (\Throwable $e) {
                // Non-fatal
            }
        }

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
        ];

        $senderName  = $emailSettings['sender_name'] ?: get_bloginfo('name');
        $senderEmail = $emailSettings['sender_email'] ?: get_bloginfo('admin_email');
        if (is_email($senderEmail)) {
            $headers[] = sprintf('From: %s <%s>', $senderName, $senderEmail);
        }

        $sent = wp_mail($recipient, $subject, $body, $headers, $attachments);

        if ($sent && $invoice->getId()) {
            ActivityLogger::log($invoice->getId(), 'reminder_sent', sprintf(
                /* translators: %s: recipient email */
                __('Payment reminder sent to %s.', 'wpcalibrate-invoice-studio'),
                $this->maskEmail($recipient)
            ));
        }

        return $sent;
    }

    /**
     * Replaces standard placeholder tokens in subject and body.
     */
    public function replacePlaceholders(string $text, InvoiceEntity $invoice): string
    {
        $business = $invoice->getBusiness();
        $customer = $invoice->getCustomer();

        $token = '';
        if ($invoice->getId()) {
            $token = $this->tokenService->generateToken($invoice->getId());
        }

        $viewUrl = add_query_arg([
            'wpci_view'  => 'invoice',
            'wpci_uuid'  => $invoice->getUuid(),
            'wpci_token' => $token,
        ], home_url('/'));

        $paymentUrl = $viewUrl;
        if ($invoice->getPaymentOrderId() && function_exists('wc_get_order')) {
            $wcOrder = wc_get_order($invoice->getPaymentOrderId());
            if ($wcOrder) {
                $paymentUrl = $wcOrder->get_checkout_payment_url();
            }
        }

        $replacements = [
            '{invoice_number}' => $invoice->getNumber() ?: __('Draft', 'wpcalibrate-invoice-studio'),
            '{business_name}'  => $business->getName() ?: get_bloginfo('name'),
            '{customer_name}'  => $customer->getName() ?: __('Customer', 'wpcalibrate-invoice-studio'),
            '{invoice_total}'  => $this->renderer->formatPrice($invoice->getTotal(), $invoice->getCurrency()),
            '{amount_due}'     => $this->renderer->formatPrice($invoice->getAmountDue(), $invoice->getCurrency()),
            '{issue_date}'     => $invoice->getIssueDate(),
            '{due_date}'       => $invoice->getDueDate(),
            '{invoice_url}'    => esc_url_raw($viewUrl),
            '{payment_url}'    => esc_url_raw($paymentUrl),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        $name = $parts[0];
        $domain = $parts[1];
        $maskedName = substr($name, 0, 1) . str_repeat('*', max(1, strlen($name) - 2)) . substr($name, -1);
        return $maskedName . '@' . $domain;
    }
}
