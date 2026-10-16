<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Invoice;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defines invoice status constants, labels, and transition logic.
 */
final class InvoiceStatus
{
    public const DRAFT          = 'draft';
    public const ISSUED         = 'wpci-issued';
    public const SENT           = 'wpci-sent';
    public const PARTIALLY_PAID = 'wpci-partially-paid';
    public const PAID           = 'wpci-paid';
    public const OVERDUE        = 'wpci-overdue';
    public const CANCELLED      = 'wpci-cancelled';

    /**
     * Return all supported status keys.
     */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::ISSUED,
            self::SENT,
            self::PARTIALLY_PAID,
            self::PAID,
            self::OVERDUE,
            self::CANCELLED,
        ];
    }

    /**
     * Return human-readable label for a status.
     */
    public static function getLabel(string $status): string
    {
        $labels = self::getLabels();
        return $labels[$status] ?? ucfirst(str_replace('wpci-', '', $status));
    }

    /**
     * Return associative array of status => label.
     */
    public static function getLabels(): array
    {
        return [
            self::DRAFT          => __('Draft', 'wpcalibrate-invoice-studio'),
            self::ISSUED         => __('Issued', 'wpcalibrate-invoice-studio'),
            self::SENT           => __('Sent', 'wpcalibrate-invoice-studio'),
            self::PARTIALLY_PAID => __('Partially Paid', 'wpcalibrate-invoice-studio'),
            self::PAID           => __('Paid', 'wpcalibrate-invoice-studio'),
            self::OVERDUE        => __('Overdue', 'wpcalibrate-invoice-studio'),
            self::CANCELLED      => __('Cancelled', 'wpcalibrate-invoice-studio'),
        ];
    }

    /**
     * Return status badge CSS class.
     */
    public static function getBadgeClass(string $status): string
    {
        return match ($status) {
            self::DRAFT          => 'wpci-badge-draft',
            self::ISSUED         => 'wpci-badge-issued',
            self::SENT           => 'wpci-badge-sent',
            self::PARTIALLY_PAID => 'wpci-badge-partially-paid',
            self::PAID           => 'wpci-badge-paid',
            self::OVERDUE        => 'wpci-badge-overdue',
            self::CANCELLED      => 'wpci-badge-cancelled',
            default              => 'wpci-badge-default',
        };
    }

    /**
     * Check if status can transition to new status.
     */
    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        // Cancelled invoices cannot be changed except back to draft or reissue if allowed
        if ($from === self::CANCELLED) {
            return false;
        }

        // Paid invoices can only transition to cancelled (e.g. refunded/voided)
        if ($from === self::PAID) {
            return $to === self::CANCELLED;
        }

        return match ($from) {
            self::DRAFT => in_array($to, [self::ISSUED, self::SENT, self::CANCELLED], true),
            self::ISSUED => in_array($to, [self::SENT, self::PARTIALLY_PAID, self::PAID, self::OVERDUE, self::CANCELLED], true),
            self::SENT => in_array($to, [self::PARTIALLY_PAID, self::PAID, self::OVERDUE, self::CANCELLED], true),
            self::OVERDUE => in_array($to, [self::SENT, self::PARTIALLY_PAID, self::PAID, self::CANCELLED], true),
            self::PARTIALLY_PAID => in_array($to, [self::PAID, self::OVERDUE, self::CANCELLED], true),
            default => true,
        };
    }

    /**
     * Check if invoice is finalized (cannot alter financial line items silently).
     */
    public static function isFinalized(string $status): bool
    {
        return $status !== self::DRAFT;
    }
}
