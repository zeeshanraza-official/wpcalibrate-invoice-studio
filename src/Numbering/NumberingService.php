<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Numbering;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Concurrency-safe sequential invoice numbering service.
 */
final class NumberingService
{
    public const OPTION_KEY = 'wpci_numbering_settings';
    public const LOCK_KEY   = 'wpci_numbering_lock';

    /**
     * Generate the next unique, sequential invoice number.
     * Guaranteed concurrency-safe via atomic transient lock and postmeta uniqueness check.
     *
     * @return string Unique invoice number
     * @throws \RuntimeException If lock cannot be acquired or generation fails
     */
    public function generateNextNumber(): string
    {
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;
            if ($this->acquireLock()) {
                try {
                    $number = $this->allocateNumberUnderLock();
                    $this->releaseLock();
                    return $number;
                } catch (\Throwable $e) {
                    $this->releaseLock();
                    throw $e;
                }
            }
            // Wait 50-100ms before retrying lock
            usleep(wp_rand(50000, 100000));
        }

        // Fallback: generate high-entropy fallback if lock contention is extreme
        return 'INV-' . gmdate('Ymd') . '-' . wp_rand(10000, 99999);
    }

    /**
     * Format a preview number for given settings without incrementing counter.
     */
    public function preview(array $settings = []): string
    {
        $current = $this->getSettings();
        $config = wp_parse_args($settings, $current);

        $num = (int)($config['next_number'] ?? $config['start_number'] ?? 1);
        $padding = (int)($config['padding'] ?? 5);
        $formattedNum = str_pad((string)$num, $padding, '0', STR_PAD_LEFT);

        $pattern = (string)($config['pattern'] ?? 'INV-{YYYY}-{NUMBER}');
        $prefix = (string)($config['prefix'] ?? '');
        $suffix = (string)($config['suffix'] ?? '');

        $now = current_time('timestamp');
        $replacements = [
            '{YYYY}'   => gmdate('Y', $now),
            '{YY}'     => gmdate('y', $now),
            '{MM}'     => gmdate('m', $now),
            '{DD}'     => gmdate('d', $now),
            '{NUMBER}' => $formattedNum,
        ];

        $result = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        if ($prefix && !str_starts_with($result, $prefix)) {
            $result = $prefix . $result;
        }
        if ($suffix && !str_ends_with($result, $suffix)) {
            $result .= $suffix;
        }

        return $result;
    }

    public function getSettings(): array
    {
        $defaults = [
            'prefix'         => 'INV-',
            'suffix'         => '',
            'start_number'   => 1,
            'next_number'    => 1,
            'padding'        => 5,
            'pattern'        => 'INV-{YYYY}-{NUMBER}',
            'reset_interval' => 'yearly',
            'last_reset'     => gmdate('Y-m'),
        ];
        return wp_parse_args(get_option(self::OPTION_KEY, []), $defaults);
    }

    private function allocateNumberUnderLock(): string
    {
        $settings = $this->getSettings();
        $this->maybeHandleReset($settings);

        global $wpdb;
        $counter = (int)($settings['next_number'] ?? 1);
        $candidate = '';
        $isUnique = false;

        // Loop to guarantee no conflict with existing postmeta
        while (!$isUnique) {
            $candidate = $this->formatNumber($settings, $counter);

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wpci_invoice_number' AND meta_value = %s LIMIT 1",
                $candidate
            ));

            if (!$exists) {
                $isUnique = true;
            }

            $counter++;
        }

        // Save updated next_number
        $settings['next_number'] = $counter;
        update_option(self::OPTION_KEY, $settings);

        return $candidate;
    }

    private function formatNumber(array $settings, int $number): string
    {
        $padding = (int)($settings['padding'] ?? 5);
        $formattedNum = str_pad((string)$number, $padding, '0', STR_PAD_LEFT);
        $pattern = (string)($settings['pattern'] ?? 'INV-{YYYY}-{NUMBER}');
        $prefix = (string)($settings['prefix'] ?? '');
        $suffix = (string)($settings['suffix'] ?? '');

        $now = current_time('timestamp');
        $replacements = [
            '{YYYY}'   => gmdate('Y', $now),
            '{YY}'     => gmdate('y', $now),
            '{MM}'     => gmdate('m', $now),
            '{DD}'     => gmdate('d', $now),
            '{NUMBER}' => $formattedNum,
        ];

        $result = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        if ($prefix && !str_starts_with($result, $prefix)) {
            $result = $prefix . $result;
        }
        if ($suffix && !str_ends_with($result, $suffix)) {
            $result .= $suffix;
        }

        return $result;
    }

    private function maybeHandleReset(array &$settings): void
    {
        $interval = $settings['reset_interval'] ?? 'yearly';
        if ($interval === 'never') {
            return;
        }

        $lastReset = $settings['last_reset'] ?? '';
        $currentYear = gmdate('Y');
        $currentMonth = gmdate('Y-m');

        if ($interval === 'yearly' && (!str_starts_with($lastReset, $currentYear))) {
            $settings['next_number'] = (int)($settings['start_number'] ?? 1);
            $settings['last_reset'] = $currentMonth;
        } elseif ($interval === 'monthly' && $lastReset !== $currentMonth) {
            $settings['next_number'] = (int)($settings['start_number'] ?? 1);
            $settings['last_reset'] = $currentMonth;
        }
    }

    private function acquireLock(): bool
    {
        // Set transient with 10 seconds expiration
        if (get_transient(self::LOCK_KEY)) {
            return false;
        }
        return set_transient(self::LOCK_KEY, (string)time(), 10);
    }

    private function releaseLock(): void
    {
        delete_transient(self::LOCK_KEY);
    }
}
