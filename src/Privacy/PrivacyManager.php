<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Privacy;

use WPCalibrate\InvoiceStudio\Customer\CustomerSnapshot;
use WPCalibrate\InvoiceStudio\Invoice\ActivityLogger;
use WPCalibrate\InvoiceStudio\Invoice\InvoiceRepository;
use WPCalibrate\InvoiceStudio\Settings\SettingsManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles WordPress Core Privacy Exporter and Eraser tools integration.
 */
final class PrivacyManager
{
    private InvoiceRepository $repository;
    private SettingsManager $settingsManager;

    public function __construct(InvoiceRepository $repository, SettingsManager $settingsManager)
    {
        $this->repository = $repository;
        $this->settingsManager = $settingsManager;
    }

    public function register(): void
    {
        add_filter('wp_privacy_personal_data_exporters', [$this, 'registerExporter'], 10);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'registerEraser'], 10);
    }

    public function registerExporter(array $exporters): array
    {
        $exporters['wpcalibrate-invoice-studio'] = [
            'exporter_friendly_name' => __('WPCalibrate Invoice Studio Invoices', 'wpcalibrate-invoice-studio'),
            'callback'               => [$this, 'exportCustomerData'],
        ];
        return $exporters;
    }

    public function registerEraser(array $erasers): array
    {
        $erasers['wpcalibrate-invoice-studio'] = [
            'eraser_friendly_name' => __('WPCalibrate Invoice Studio Invoices Anonymizer', 'wpcalibrate-invoice-studio'),
            'callback'             => [$this, 'eraseCustomerData'],
        ];
        return $erasers;
    }

    /**
     * Export customer invoice data for privacy requests.
     */
    public function exportCustomerData(string $emailAddress, int $page = 1): array
    {
        $queryResult = $this->repository->query([
            'customer_email' => $emailAddress,
            'paged'          => $page,
            'per_page'       => 50,
        ]);

        $exportItems = [];
        foreach ($queryResult['invoices'] as $invoice) {
            $data = [
                ['name' => __('Invoice Number', 'wpcalibrate-invoice-studio'), 'value' => $invoice->getNumber()],
                ['name' => __('Issue Date', 'wpcalibrate-invoice-studio'),     'value' => $invoice->getIssueDate()],
                ['name' => __('Due Date', 'wpcalibrate-invoice-studio'),       'value' => $invoice->getDueDate()],
                ['name' => __('Status', 'wpcalibrate-invoice-studio'),         'value' => $invoice->getStatus()],
                ['name' => __('Total', 'wpcalibrate-invoice-studio'),          'value' => $invoice->getTotal() . ' ' . $invoice->getCurrency()],
                ['name' => __('Customer Name', 'wpcalibrate-invoice-studio'),  'value' => $invoice->getCustomer()->getName()],
                ['name' => __('Billing Address', 'wpcalibrate-invoice-studio'),'value' => $invoice->getCustomer()->getAddress()],
            ];

            $exportItems[] = [
                'group_id'    => 'wpci_invoices',
                'group_label' => __('Invoices', 'wpcalibrate-invoice-studio'),
                'item_id'     => 'wpci_invoice_' . $invoice->getId(),
                'data'        => $data,
            ];
        }

        return [
            'data' => $exportItems,
            'done' => ($page >= $queryResult['pages']),
        ];
    }

    /**
     * Erase/Anonymize personal data from invoices while preserving legally required accounting totals.
     */
    public function eraseCustomerData(string $emailAddress, int $page = 1): array
    {
        $privacySettings = $this->settingsManager->getPrivacy();
        if (empty($privacySettings['erase_personal_data'])) {
            return [
                'items_removed'  => 0,
                'items_retained' => 0,
                'messages'       => [__('Invoice data anonymization is disabled in Invoice Studio settings.', 'wpcalibrate-invoice-studio')],
                'done'           => true,
            ];
        }

        $queryResult = $this->repository->query([
            'customer_email' => $emailAddress,
            'paged'          => $page,
            'per_page'       => 50,
        ]);

        $itemsRemoved = 0;
        $itemsRetained = 0;
        $messages = [];

        foreach ($queryResult['invoices'] as $invoice) {
            $anonymizedCustomer = new CustomerSnapshot(
                __('Anonymized Customer', 'wpcalibrate-invoice-studio'),
                'anonymized@example.com',
                '',
                '',
                __('Redacted for Privacy', 'wpcalibrate-invoice-studio'),
                '',
                '',
                '',
                '',
                '',
                null
            );

            $invoice->setCustomer($anonymizedCustomer);
            $this->repository->save($invoice);

            ActivityLogger::log($invoice->getId(), 'privacy_anonymized', __('Customer personal information anonymized per GDPR/Privacy erasure request.', 'wpcalibrate-invoice-studio'));
            $itemsRemoved++;
            $itemsRetained++; // Financial record retained with anonymized identity
        }

        if ($itemsRetained > 0) {
            $messages[] = sprintf(
                /* translators: %d: count of invoices */
                __('%d financial invoice records were anonymized and retained for legal accounting compliance.', 'wpcalibrate-invoice-studio'),
                $itemsRetained
            );
        }

        return [
            'items_removed'  => $itemsRemoved,
            'items_retained' => $itemsRetained,
            'messages'       => $messages,
            'done'           => ($page >= $queryResult['pages']),
        ];
    }
}
