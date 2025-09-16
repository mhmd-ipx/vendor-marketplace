<?php
/**
 * Plugin Name: Wholesale Marketplace
 * Plugin URI: https://example.com/vendor-marketplace
 * Description: پلاگین بازار عمده فروشی برای WooCommerce با ویژگی‌های احراز هویت، انبارداری، کیف پول و غیره.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: vendor-marketplace
 * Requires at least: 5.0
 * Tested up to: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 * Requires WooCommerce HPOS: no
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'vendor_marketplace_woocommerce_missing_notice');
    return;
}

// Check if HPOS is enabled (since we declared no support)
if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
    add_action('admin_notices', 'vendor_marketplace_hpos_incompatible_notice');
    return;
}

function vendor_marketplace_woocommerce_missing_notice() {
    echo '<div class="error"><p>' . __('پلاگین بازار عمده فروشی نیاز به نصب و فعال بودن WooCommerce دارد.', 'vendor-marketplace') . '</p></div>';
}

function vendor_marketplace_hpos_incompatible_notice() {
    echo '<div class="error"><p>' . __('این پلاگین با High Performance Order Storage (HPOS) ووکامرس سازگار نیست.', 'vendor-marketplace') . '</p></div>';
}

// Define constants
define('VENDOR_MARKETPLACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VENDOR_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VENDOR_MARKETPLACE_VERSION', '1.0.0');

// Include the main initialization class
require_once VENDOR_MARKETPLACE_PLUGIN_DIR . 'includes/class-init.php';

// Initialize the plugin
Vendor_Marketplace_Init::get_instance();

// Activation hook
register_activation_hook(__FILE__, 'vendor_marketplace_activate');
register_deactivation_hook(__FILE__, 'vendor_marketplace_deactivate');

function vendor_marketplace_activate() {
    require_once VENDOR_MARKETPLACE_PLUGIN_DIR . 'includes/class-roles.php';
    Vendor_Marketplace_Roles::add_roles();
}

function vendor_marketplace_deactivate() {
    require_once VENDOR_MARKETPLACE_PLUGIN_DIR . 'includes/class-roles.php';
    Vendor_Marketplace_Roles::remove_roles();
}