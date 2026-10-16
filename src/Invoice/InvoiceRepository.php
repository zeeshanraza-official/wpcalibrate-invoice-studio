<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Invoice;

use WPCalibrate\InvoiceStudio\Customer\BusinessSnapshot;
use WPCalibrate\InvoiceStudio\Customer\CustomerSnapshot;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository for storing, querying, and persisting invoice entities.
 */
final class InvoiceRepository
{
    /**
     * Persist an InvoiceEntity to the database.
     *
     * @return int Saved invoice post ID
     */
    public function save(InvoiceEntity $invoice): int
    {
        $id = $invoice->getId();
        $title = $invoice->getNumber() ?: sprintf(__('Draft Invoice (%s)', 'wpcalibrate-invoice-studio'), $invoice->getUuid());

        $postData = [
            'post_type'   => InvoicePostType::POST_TYPE,
            'post_status' => $invoice->getStatus(),
            'post_title'  => $title,
        ];

        if ($id && $id > 0) {
            $postData['ID'] = $id;
            wp_update_post($postData);
        } else {
            $id = wp_insert_post($postData);
            if (is_wp_error($id) || !$id) {
                throw new \RuntimeException('Failed to create invoice record in database.');
            }
            $invoice->setId($id);
            ActivityLogger::log($id, 'created', __('Invoice record created.', 'wpcalibrate-invoice-studio'));
        }

        // Persist metadata
        update_post_meta($id, '_wpci_invoice_number', sanitize_text_field($invoice->getNumber()));
        update_post_meta($id, '_wpci_invoice_uuid', sanitize_text_field($invoice->getUuid()));
        update_post_meta($id, '_wpci_source', sanitize_text_field($invoice->getSource()));
        update_post_meta($id, '_wpci_order_id', $invoice->getOrderId());
        update_post_meta($id, '_wpci_customer_snapshot', $invoice->getCustomer()->toArray());
        update_post_meta($id, '_wpci_business_snapshot', $invoice->getBusiness()->toArray());
        update_post_meta($id, '_wpci_customer_user_id', $invoice->getCustomer()->getUserId());
        update_post_meta($id, '_wpci_customer_email', sanitize_email($invoice->getCustomer()->getEmail()));
        update_post_meta($id, '_wpci_issue_date', sanitize_text_field($invoice->getIssueDate()));
        update_post_meta($id, '_wpci_due_date', sanitize_text_field($invoice->getDueDate()));
        update_post_meta($id, '_wpci_paid_date', $invoice->getPaidDate() ? sanitize_text_field($invoice->getPaidDate()) : null);
        update_post_meta($id, '_wpci_currency', sanitize_text_field($invoice->getCurrency()));

        // Line items
        $serializedItems = array_map(fn(LineItem $item) => $item->toArray(), $invoice->getLineItems());
        update_post_meta($id, '_wpci_line_items', $serializedItems);

        // Monetary totals
        update_post_meta($id, '_wpci_subtotal', (float)$invoice->getSubtotal());
        update_post_meta($id, '_wpci_discount_total', (float)$invoice->getDiscountTotal());
        update_post_meta($id, '_wpci_shipping_total', (float)$invoice->getShippingTotal());
        update_post_meta($id, '_wpci_fee_total', (float)$invoice->getFeeTotal());
        update_post_meta($id, '_wpci_tax_total', (float)$invoice->getTaxTotal());
        update_post_meta($id, '_wpci_tax_breakdown', (array)$invoice->getTaxBreakdown());
        update_post_meta($id, '_wpci_total', (float)$invoice->getTotal());
        update_post_meta($id, '_wpci_amount_paid', (float)$invoice->getAmountPaid());
        update_post_meta($id, '_wpci_amount_due', (float)$invoice->getAmountDue());

        // Presentation & Notes
        update_post_meta($id, '_wpci_template', sanitize_key($invoice->getTemplate()));
        update_post_meta($id, '_wpci_payment_instructions', wp_kses_post($invoice->getPaymentInstructions()));
        update_post_meta($id, '_wpci_customer_notes', sanitize_textarea_field($invoice->getCustomerNotes()));
        update_post_meta($id, '_wpci_terms', sanitize_textarea_field($invoice->getTerms()));
        update_post_meta($id, '_wpci_custom_fields', (array)$invoice->getCustomFields());

        // Online payment
        update_post_meta($id, '_wpci_allow_online_payment', $invoice->allowOnlinePayment() ? '1' : '0');
        update_post_meta($id, '_wpci_payment_order_id', $invoice->getPaymentOrderId());

        if ($invoice->getPdfPath()) {
            update_post_meta($id, '_wpci_pdf_path', sanitize_text_field($invoice->getPdfPath()));
            update_post_meta($id, '_wpci_pdf_generated_time', (int)$invoice->getPdfGeneratedTime());
        }

        return $id;
    }

    /**
     * Find invoice by Post ID.
     */
    public function findById(int $id): ?InvoiceEntity
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== InvoicePostType::POST_TYPE) {
            return null;
        }

        return $this->hydrateFromPost($post);
    }

    /**
     * Find invoice by public UUID.
     */
    public function findByUuid(string $uuid): ?InvoiceEntity
    {
        $args = [
            'post_type'      => InvoicePostType::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => '_wpci_invoice_uuid',
            'meta_value'     => $uuid,
        ];

        $posts = get_posts($args);
        if (empty($posts)) {
            return null;
        }

        return $this->hydrateFromPost($posts[0]);
    }

    /**
     * Find invoice by Invoice Number.
     */
    public function findByNumber(string $number): ?InvoiceEntity
    {
        $args = [
            'post_type'      => InvoicePostType::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => '_wpci_invoice_number',
            'meta_value'     => $number,
        ];

        $posts = get_posts($args);
        if (empty($posts)) {
            return null;
        }

        return $this->hydrateFromPost($posts[0]);
    }

    /**
     * Find invoice by associated WooCommerce Order ID.
     */
    public function findByOrderId(int $orderId): ?InvoiceEntity
    {
        $args = [
            'post_type'      => InvoicePostType::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => '_wpci_order_id',
            'meta_value'     => $orderId,
        ];

        $posts = get_posts($args);
        if (empty($posts)) {
            return null;
        }

        return $this->hydrateFromPost($posts[0]);
    }

    /**
     * Find all invoices for a given registered Customer user ID.
     *
     * @return InvoiceEntity[]
     */
    public function findByCustomerId(int $userId): array
    {
        $args = [
            'post_type'      => InvoicePostType::POST_TYPE,
            'post_status'    => [
                InvoiceStatus::ISSUED,
                InvoiceStatus::SENT,
                InvoiceStatus::PARTIALLY_PAID,
                InvoiceStatus::PAID,
                InvoiceStatus::OVERDUE,
            ],
            'posts_per_page' => 50,
            'meta_query'     => [
                [
                    'key'   => '_wpci_customer_user_id',
                    'value' => $userId,
                ],
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $posts = get_posts($args);
        return array_map([$this, 'hydrateFromPost'], $posts);
    }

    /**
     * Query invoices with safe allowlisted sorting and filtering.
     *
     * @return array ['invoices' => InvoiceEntity[], 'total' => int, 'pages' => int]
     */
    public function query(array $params = []): array
    {
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 20)));
        $paged = max(1, (int)($params['paged'] ?? 1));

        $allowedOrderBy = [
            'date'           => 'post_date',
            'id'             => 'ID',
            'number'         => 'meta_value',
            'total'          => 'meta_value_num',
            'due_date'       => 'meta_value',
            'status'         => 'post_status',
        ];

        $reqOrderBy = $params['orderby'] ?? 'date';
        $orderBy = $allowedOrderBy[$reqOrderBy] ?? 'post_date';
        $order = (strtoupper((string)($params['order'] ?? 'DESC')) === 'ASC') ? 'ASC' : 'DESC';

        $postStatus = 'any';
        if (!empty($params['status']) && in_array($params['status'], InvoiceStatus::all(), true)) {
            $postStatus = $params['status'];
        }

        $args = [
            'post_type'      => InvoicePostType::POST_TYPE,
            'post_status'    => $postStatus,
            'posts_per_page' => $perPage,
            'paged'          => $paged,
            'orderby'        => $orderBy,
            'order'          => $order,
        ];

        if ($reqOrderBy === 'number') {
            $args['meta_key'] = '_wpci_invoice_number';
        } elseif ($reqOrderBy === 'total') {
            $args['meta_key'] = '_wpci_total';
        } elseif ($reqOrderBy === 'due_date') {
            $args['meta_key'] = '_wpci_due_date';
        }

        $metaQuery = [];

        if (!empty($params['source']) && in_array($params['source'], ['manual', 'woocommerce'], true)) {
            $metaQuery[] = [
                'key'   => '_wpci_source',
                'value' => $params['source'],
            ];
        }

        if (!empty($params['customer_email'])) {
            $metaQuery[] = [
                'key'     => '_wpci_customer_email',
                'value'   => sanitize_email($params['customer_email']),
                'compare' => 'LIKE',
            ];
        }

        if (!empty($metaQuery)) {
            $args['meta_query'] = $metaQuery;
        }

        if (!empty($params['search'])) {
            $args['s'] = sanitize_text_field($params['search']);
        }

        $wpQuery = new \WP_Query($args);
        $invoices = array_map([$this, 'hydrateFromPost'], $wpQuery->posts);

        return [
            'invoices' => $invoices,
            'total'    => (int)$wpQuery->found_posts,
            'pages'    => (int)$wpQuery->max_num_pages,
        ];
    }

    /**
     * Get invoice count grouped by status.
     */
    public function countByStatus(): array
    {
        $counts = [
            'all'                        => 0,
            InvoiceStatus::DRAFT          => 0,
            InvoiceStatus::ISSUED         => 0,
            InvoiceStatus::SENT           => 0,
            InvoiceStatus::PARTIALLY_PAID => 0,
            InvoiceStatus::PAID           => 0,
            InvoiceStatus::OVERDUE        => 0,
            InvoiceStatus::CANCELLED      => 0,
        ];

        $wpCounts = wp_count_posts(InvoicePostType::POST_TYPE);

        foreach (InvoiceStatus::all() as $st) {
            $c = (int)($wpCounts->$st ?? 0);
            $counts[$st] = $c;
            $counts['all'] += $c;
        }

        return $counts;
    }

    /**
     * Get aggregate KPI metrics for dashboard display.
     *
     * @return array ['counts' => array, 'paid_sum' => float, 'due_sum' => float, 'currency' => string]
     */
    public function getSummaryStats(): array
    {
        global $wpdb;

        $counts = $this->countByStatus();
        $paidSum = 0.0;
        $dueSum = 0.0;

        // In WordPress environment, calculate monetary totals
        if (isset($wpdb->postmeta) && isset($wpdb->posts)) {
            $paidRes = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COALESCE(SUM(CAST(pm.meta_value AS DECIMAL(12,2))), 0)
                     FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                     WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_key = '_wpci_total'",
                    InvoicePostType::POST_TYPE,
                    InvoiceStatus::PAID
                )
            );
            $paidSum = (float)($paidRes ?? 0.0);

            $dueRes = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COALESCE(SUM(CAST(pm.meta_value AS DECIMAL(12,2))), 0)
                     FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                     WHERE p.post_type = %s AND p.post_status IN (%s, %s, %s, %s) AND pm.meta_key = '_wpci_amount_due'",
                    InvoicePostType::POST_TYPE,
                    InvoiceStatus::ISSUED,
                    InvoiceStatus::SENT,
                    InvoiceStatus::PARTIALLY_PAID,
                    InvoiceStatus::OVERDUE
                )
            );
            $dueSum = (float)($dueRes ?? 0.0);
        }

        return [
            'counts'   => $counts,
            'paid_sum' => $paidSum,
            'due_sum'  => $dueSum,
        ];
    }

    /**
     * Delete an invoice post and its metadata.
     */
    public function delete(int $id, bool $force = false): bool
    {
        $res = wp_delete_post($id, $force);
        return $res !== false && $res !== null;
    }

    private function hydrateFromPost(\WP_Post $post): InvoiceEntity
    {
        $id = $post->ID;
        $uuid = (string)get_post_meta($id, '_wpci_invoice_uuid', true);
        $number = (string)get_post_meta($id, '_wpci_invoice_number', true);
        $source = (string)get_post_meta($id, '_wpci_source', true) ?: 'manual';
        $orderId = ($oid = get_post_meta($id, '_wpci_order_id', true)) ? (int)$oid : null;

        $custMeta = get_post_meta($id, '_wpci_customer_snapshot', true);
        $customer = is_array($custMeta) ? CustomerSnapshot::fromArray($custMeta) : new CustomerSnapshot('', '');

        $bizMeta = get_post_meta($id, '_wpci_business_snapshot', true);
        $business = is_array($bizMeta) ? BusinessSnapshot::fromArray($bizMeta) : new BusinessSnapshot('', '');

        $issueDate = (string)get_post_meta($id, '_wpci_issue_date', true) ?: gmdate('Y-m-d', strtotime($post->post_date));
        $dueDate = (string)get_post_meta($id, '_wpci_due_date', true);
        $paidDate = ($pd = get_post_meta($id, '_wpci_paid_date', true)) ? (string)$pd : null;
        $currency = (string)get_post_meta($id, '_wpci_currency', true) ?: 'USD';

        $rawItems = get_post_meta($id, '_wpci_line_items', true);
        $lineItems = [];
        if (is_array($rawItems)) {
            foreach ($rawItems as $itemData) {
                if (is_array($itemData)) {
                    $lineItems[] = LineItem::fromArray($itemData);
                }
            }
        }

        $subtotal = (float)get_post_meta($id, '_wpci_subtotal', true);
        $discountTotal = (float)get_post_meta($id, '_wpci_discount_total', true);
        $shippingTotal = (float)get_post_meta($id, '_wpci_shipping_total', true);
        $feeTotal = (float)get_post_meta($id, '_wpci_fee_total', true);
        $taxTotal = (float)get_post_meta($id, '_wpci_tax_total', true);
        $taxBreakdown = (array)get_post_meta($id, '_wpci_tax_breakdown', true);
        $total = (float)get_post_meta($id, '_wpci_total', true);
        $amountPaid = (float)get_post_meta($id, '_wpci_amount_paid', true);
        $amountDue = (float)get_post_meta($id, '_wpci_amount_due', true);

        $template = (string)get_post_meta($id, '_wpci_template', true) ?: 'classic';
        $paymentInstructions = (string)get_post_meta($id, '_wpci_payment_instructions', true);
        $customerNotes = (string)get_post_meta($id, '_wpci_customer_notes', true);
        $terms = (string)get_post_meta($id, '_wpci_terms', true);
        $customFields = (array)get_post_meta($id, '_wpci_custom_fields', true);

        $allowOnlinePay = (bool)get_post_meta($id, '_wpci_allow_online_payment', true);
        $paymentOrderId = ($poid = get_post_meta($id, '_wpci_payment_order_id', true)) ? (int)$poid : null;

        $pdfPath = ($pp = get_post_meta($id, '_wpci_pdf_path', true)) ? (string)$pp : null;
        $pdfGenTime = ($pgt = get_post_meta($id, '_wpci_pdf_generated_time', true)) ? (int)$pgt : null;

        return new InvoiceEntity(
            $id,
            $uuid,
            $number,
            $post->post_status,
            $source,
            $orderId,
            $customer,
            $business,
            $issueDate,
            $dueDate,
            $paidDate,
            $currency,
            $lineItems,
            $subtotal,
            $discountTotal,
            $shippingTotal,
            $feeTotal,
            $taxTotal,
            $taxBreakdown,
            $total,
            $amountPaid,
            $amountDue,
            $template,
            $paymentInstructions,
            $customerNotes,
            $terms,
            $customFields,
            $allowOnlinePay,
            $paymentOrderId,
            $pdfPath,
            $pdfGenTime
        );
    }
}
