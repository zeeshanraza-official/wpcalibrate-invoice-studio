<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Customer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages cryptographically secure, expiring, and revocable guest tokens for invoice viewing.
 */
final class GuestTokenService
{
    public const TOKEN_HASH_META    = '_wpci_guest_token_hash';
    public const TOKEN_CREATED_META = '_wpci_guest_token_created';
    public const TOKEN_EXPIRES_META = '_wpci_guest_token_expires';

    /**
     * Generate a new guest token for an invoice.
     * Returns the raw token string (only returned once to sender/admin; only hash is stored in DB).
     */
    public function generateToken(int $invoiceId, int $expiryDays = 30): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $now = current_time('timestamp');
        $expires = $now + ($expiryDays * DAY_IN_SECONDS);

        update_post_meta($invoiceId, self::TOKEN_HASH_META, $tokenHash);
        update_post_meta($invoiceId, self::TOKEN_CREATED_META, $now);
        update_post_meta($invoiceId, self::TOKEN_EXPIRES_META, $expires);

        return $rawToken;
    }

    /**
     * Validate an invoice guest token.
     */
    public function verifyToken(int $invoiceId, string $rawToken): bool
    {
        if (empty($rawToken)) {
            return false;
        }

        $storedHash = (string)get_post_meta($invoiceId, self::TOKEN_HASH_META, true);
        if (empty($storedHash)) {
            return false;
        }

        $expires = (int)get_post_meta($invoiceId, self::TOKEN_EXPIRES_META, true);
        $now = current_time('timestamp');

        if ($expires > 0 && $now > $expires) {
            return false; // Expired
        }

        $computedHash = hash('sha256', $rawToken);
        return hash_equals($storedHash, $computedHash);
    }

    /**
     * Revoke any existing guest token for an invoice.
     */
    public function revokeToken(int $invoiceId): void
    {
        delete_post_meta($invoiceId, self::TOKEN_HASH_META);
        delete_post_meta($invoiceId, self::TOKEN_CREATED_META);
        delete_post_meta($invoiceId, self::TOKEN_EXPIRES_META);
    }

    /**
     * Check if an invoice has an active, unexpired token.
     */
    public function hasActiveToken(int $invoiceId): bool
    {
        $storedHash = (string)get_post_meta($invoiceId, self::TOKEN_HASH_META, true);
        if (empty($storedHash)) {
            return false;
        }

        $expires = (int)get_post_meta($invoiceId, self::TOKEN_EXPIRES_META, true);
        if ($expires > 0 && current_time('timestamp') > $expires) {
            return false;
        }

        return true;
    }
}
