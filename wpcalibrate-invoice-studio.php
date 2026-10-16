<?php
/**
 * Plugin Name:       WPCalibrate Invoice Studio
 * Plugin URI:        https://wpcalibrate.com
 * Description:       Create, manage, generate, email, download, print, and collect online payment for professional invoices in WordPress and WooCommerce.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-invoice-studio
 * Domain Path:       /languages
 *
 * WC requires at least: 8.0
 * WC tested up to:      11.2.1
 * Tested up to:         7.1.3
 *
 * GitHub Plugin URI: https://github.com/zeeshanraza-official/wpcalibrate-invoice-studio
 * Primary Branch:    main
 * Release Asset:     true
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Define core constants
define('WPCI_VERSION', '1.0.0');
define('WPCI_PLUGIN_FILE', __FILE__);
define('WPCI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPCI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloading
if (file_exists(WPCI_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WPCI_PLUGIN_DIR . 'vendor/autoload.php';
}

require_once WPCI_PLUGIN_DIR . 'src/Bootstrap/Autoloader.php';
\WPCalibrate\InvoiceStudio\Bootstrap\Autoloader::register();

// Register Activation and Deactivation hooks
register_activation_hook(__FILE__, [\WPCalibrate\InvoiceStudio\Bootstrap\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [\WPCalibrate\InvoiceStudio\Bootstrap\Deactivator::class, 'deactivate']);

// Declare early HPOS compatibility before WooCommerce initializes
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Bootstrap application on plugins_loaded
add_action('plugins_loaded', function () {
    load_plugin_textdomain('wpcalibrate-invoice-studio', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Check PHP version requirement
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' .
                esc_html__('WPCalibrate Invoice Studio requires PHP 8.2 or higher to function properly.', 'wpcalibrate-invoice-studio') .
                '</p></div>';
        });
        return;
    }

    // Initialize core plugin singleton
    \WPCalibrate\InvoiceStudio\Bootstrap\Plugin::getInstance()->init();
});
