<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Invoice;

use WPCalibrate\InvoiceStudio\Logging\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages chronological audit history for invoice lifecycle events.
 */
final class ActivityLogger
{
    public const HISTORY_META = '_wpci_invoice_history';

    /**
     * Record an event in the invoice activity log.
     *
     * @param int    $invoiceId
     * @param string $eventType e.g. 'created', 'issued', 'sent', 'pdf_generated', 'payment_recorded', 'status_changed', 'guest_link_generated', 'order_associated'
     * @param string $description Human-readable note
     * @param array  $context Additional non-sensitive context
     * @param int|null $actorUserId Defaults to current logged-in user
     */
    public static function log(
        int $invoiceId,
        string $eventType,
        string $description,
        array $context = [],
        ?int $actorUserId = null
    ): void {
        if ($actorUserId === null) {
            $actorUserId = get_current_user_id() ?: null;
        }

        $history = self::getHistory($invoiceId);

        $sanitizedContext = Logger::sanitizeContext($context);

        $entry = [
            'id'          => wp_generate_uuid4(),
            'event'       => sanitize_key($eventType),
            'description' => sanitize_text_field($description),
            'timestamp'   => current_time('mysql'),
            'actor_id'    => $actorUserId,
            'actor_name'  => $actorUserId ? (get_userdata($actorUserId)->display_name ?? 'User #' . $actorUserId) : __('System / Guest', 'wpcalibrate-invoice-studio'),
            'context'     => $sanitizedContext,
        ];

        // Prepend so latest appears first
        array_unshift($history, $entry);

        // Cap history to 100 entries per invoice
        if (count($history) > 100) {
            $history = array_slice($history, 0, 100);
        }

        update_post_meta($invoiceId, self::HISTORY_META, $history);
    }

    /**
     * Get all history entries for an invoice.
     *
     * @return array[]
     */
    public static function getHistory(int $invoiceId): array
    {
        $data = get_post_meta($invoiceId, self::HISTORY_META, true);
        return is_array($data) ? $data : [];
    }
}
