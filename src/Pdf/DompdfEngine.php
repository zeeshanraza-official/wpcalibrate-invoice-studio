<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use WPCalibrate\InvoiceStudio\Logging\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dompdf implementation of PdfEngineInterface.
 */
final class DompdfEngine implements PdfEngineInterface
{
    public function renderHtmlToPdf(string $html, array $options = []): string
    {
        if (!class_exists(Dompdf::class)) {
            // Load composer autoloader if not already loaded
            $vendorAutoload = WPCI_PLUGIN_DIR . 'vendor/autoload.php';
            if (file_exists($vendorAutoload)) {
                require_once $vendorAutoload;
            }
        }

        if (!class_exists(Dompdf::class)) {
            Logger::error('Dompdf library is not installed or autoloaded.');
            throw new \RuntimeException('PDF generation library (Dompdf) is not available.');
        }

        $paperSize = $options['paper_size'] ?? 'letter';
        $orientation = $options['orientation'] ?? 'portrait';

        $dompdfOptions = new Options();
        $dompdfOptions->set('isHtml5ParserEnabled', true);
        $dompdfOptions->set('isRemoteEnabled', true); // Allow local/media images
        $dompdfOptions->set('defaultFont', 'Helvetica');
        $dompdfOptions->set('dpi', 150);

        // Security: set chroot to WordPress uploads directory and plugin directory
        $uploadDir = wp_upload_dir();
        $dompdfOptions->set('chroot', [
            WPCI_PLUGIN_DIR,
            $uploadDir['basedir'],
        ]);

        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->setPaper($paperSize, $orientation);

        try {
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            $output = $dompdf->output();

            if (empty($output)) {
                throw new \RuntimeException('Dompdf produced empty output.');
            }

            return $output;
        } catch (\Throwable $e) {
            Logger::error('Dompdf rendering exception: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);
            throw new \RuntimeException('Failed to render PDF: ' . $e->getMessage(), 0, $e);
        }
    }
}
