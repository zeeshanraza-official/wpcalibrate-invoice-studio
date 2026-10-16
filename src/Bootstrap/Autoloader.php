<?php
declare(strict_types=1);

namespace WPCalibrate\InvoiceStudio\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fallback PSR-4 Autoloader for WPCalibrate Invoice Studio.
 */
final class Autoloader
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register([self::class, 'autoload']);
        self::$registered = true;
    }

    public static function autoload(string $class): void
    {
        $prefix = 'WPCalibrate\\InvoiceStudio\\';
        $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
