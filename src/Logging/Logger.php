<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Logging;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Privacy-conscious logger abstraction for WPCalibrate Invoice Studio.
 */
final class Logger
{
    public const SOURCE = 'wpcalibrate-invoice-studio';

    /**
     * Log an informational message.
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    /**
     * Log a warning message.
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    /**
     * Log an error message.
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    /**
     * Log a debug message if WP_DEBUG is enabled.
     */
    public static function debug(string $message, array $context = []): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log('debug', $message, $context);
        }
    }

    /**
     * Internal sanitized logging mechanism.
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        $sanitizedContext = self::sanitizeContext($context);

        // If WooCommerce is active, use WooCommerce WC_Logger
        if (function_exists('wc_get_logger')) {
            $wcLogger = wc_get_logger();
            if ($wcLogger) {
                $wcLogger->log($level, $message, array_merge(['source' => self::SOURCE], $sanitizedContext));
                return;
            }
        }

        // Fallback to error_log if WP_DEBUG_LOG is on or for severe errors
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $formatted = sprintf(
                '[%s] [%s] %s %s',
                strtoupper($level),
                self::SOURCE,
                $message,
                !empty($sanitizedContext) ? wp_json_encode($sanitizedContext) : ''
            );
            error_log($formatted); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    /**
     * Strips any sensitive credentials, tokens, passwords, and license keys from context.
     */
    public static function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password',
            'pass',
            'secret',
            'token',
            'guest_token',
            'auth',
            'key',
            'license_key',
            'api_key',
            'card',
            'cvv',
            'authorization',
            'cookie',
        ];

        $cleaned = [];
        foreach ($context as $key => $value) {
            $lowerKey = strtolower((string)$key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $cleaned[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $cleaned[$key] = self::sanitizeContext($value);
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }
}
