<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Pdf;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface contract for PDF rendering engines.
 */
interface PdfEngineInterface
{
    /**
     * Render HTML to raw PDF binary string.
     *
     * @param string $html Full HTML document
     * @param array  $options ['paper_size' => 'letter'|'a4', 'orientation' => 'portrait'|'landscape', 'enable_rtl' => bool]
     * @return string Raw PDF binary content
     * @throws \RuntimeException If PDF generation fails
     */
    public function renderHtmlToPdf(string $html, array $options = []): string;
}
