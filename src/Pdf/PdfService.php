<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Pdf;

use WPCalibrate\InvoiceStudio\Invoice\ActivityLogger;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceEntity;
use WPCalibrate\InvoiceStudio\Logging\Logger;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;
use WPCalibrate\InvoiceStudio\Template\InvoiceRenderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service orchestrating PDF generation, caching, secure storage, and streaming.
 */
final class PdfService
{
    private PdfEngineInterface $engine;
    private InvoiceRenderer $renderer;
    private SettingsManager $settingsManager;

    public function __construct(
        PdfEngineInterface $engine,
        InvoiceRenderer $renderer,
        SettingsManager $settingsManager
    ) {
        $this->engine = $engine;
        $this->renderer = $renderer;
        $this->settingsManager = $settingsManager;
    }

    /**
     * Generate or fetch cached PDF for an invoice.
     *
     * @param InvoiceEntity $invoice
     * @param bool          $forceRegenerate
     * @return string Absolute filesystem path to the PDF file
     */
    public function getPdfPath(InvoiceEntity $invoice, bool $forceRegenerate = false): string
    {
        $targetDir = $this->getStorageDirectory();
        $fileName = $this->getSanitizedFileName($invoice);
        $filePath = trailingslashit($targetDir) . $fileName;

        if (!$forceRegenerate && file_exists($filePath) && filesize($filePath) > 0) {
            return $filePath;
        }

        // Generate PDF
        $html = $this->renderer->render($invoice, 'pdf');
        $pdfSettings = $this->settingsManager->getPdf();

        $options = [
            'paper_size'  => $pdfSettings['paper_size'] ?? 'letter',
            'orientation' => $pdfSettings['orientation'] ?? 'portrait',
            'enable_rtl'  => !empty($pdfSettings['enable_rtl']) || is_rtl(),
        ];

        try {
            $pdfContent = $this->engine->renderHtmlToPdf($html, $options);
            $bytesWritten = @file_put_contents($filePath, $pdfContent);

            if ($bytesWritten === false) {
                throw new \RuntimeException('Failed to write PDF file to storage directory: ' . $filePath);
            }

            if ($invoice->getId()) {
                ActivityLogger::log($invoice->getId(), 'pdf_generated', __('PDF document generated.', 'wpcalibrate-invoice-studio'));
            }

            return $filePath;
        } catch (\Throwable $e) {
            Logger::error('Failed to generate invoice PDF: ' . $e->getMessage(), [
                'invoice_id' => $invoice->getId(),
                'number'     => $invoice->getNumber(),
            ]);
            throw $e;
        }
    }

    /**
     * Stream PDF directly to browser with appropriate headers.
     *
     * @param InvoiceEntity $invoice
     * @param string        $disposition 'inline' or 'attachment'
     */
    public function streamPdf(InvoiceEntity $invoice, string $disposition = 'attachment'): void
    {
        $filePath = $this->getPdfPath($invoice);
        $fileName = $this->getSanitizedFileName($invoice);

        if (!file_exists($filePath)) {
            wp_die(esc_html__('Invoice PDF could not be found or generated.', 'wpcalibrate-invoice-studio'), 404);
        }

        $disp = in_array($disposition, ['inline', 'attachment'], true) ? $disposition : 'attachment';

        // Clear output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disp . '; filename="' . $fileName . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: 0');

        readfile($filePath);
        exit;
    }

    /**
     * Invalidate (delete) cached PDF file for an invoice.
     */
    public function invalidate(InvoiceEntity $invoice): void
    {
        $filePath = trailingslashit($this->getStorageDirectory()) . $this->getSanitizedFileName($invoice);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * Get sanitized, safe filename for an invoice PDF.
     */
    public function getSanitizedFileName(InvoiceEntity $invoice): string
    {
        $rawNumber = $invoice->getNumber() ?: $invoice->getUuid();
        $safeName = sanitize_file_name('invoice-' . $rawNumber . '.pdf');
        return $safeName ?: 'invoice.pdf';
    }

    /**
     * Protected storage directory for PDF files.
     */
    public function getStorageDirectory(): string
    {
        $uploadDir = wp_upload_dir();
        $targetDir = trailingslashit($uploadDir['basedir']) . 'wpci-invoices';

        if (!file_exists($targetDir)) {
            wp_mkdir_p($targetDir);
            @file_put_contents(trailingslashit($targetDir) . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>Directory access forbidden.</body></html>');
            @file_put_contents(trailingslashit($targetDir) . '.htaccess', "Require all denied\n");
        }

        return $targetDir;
    }
}
