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

    // Create inventory table
    vendor_marketplace_create_inventory_table();
}

function vendor_marketplace_create_inventory_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'vendor_inventory';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        product_id bigint(20) unsigned NOT NULL,
        quantity_self int(11) NOT NULL DEFAULT 0,
        quantity_central int(11) NOT NULL DEFAULT 0,
        price decimal(10,2) NOT NULL DEFAULT 0.00,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY user_product (user_id, product_id),
        KEY user_id (user_id),
        KEY product_id (product_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Create transactions table
    $transactions_table = $wpdb->prefix . 'inventory_transactions';
    $sql2 = "CREATE TABLE $transactions_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned DEFAULT 0,
        type enum('ورود','خروج') NOT NULL,
        quantity int(11) NOT NULL,
        inventory_type enum('self','central','product') DEFAULT 'product',
        source enum('manual','purchase','sale','adjustment','other') DEFAULT 'manual',
        reason varchar(255) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        created_by bigint(20) unsigned NOT NULL,
        PRIMARY KEY (id),
        KEY product_user (product_id, user_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    dbDelta($sql2);
}

function vendor_marketplace_deactivate() {
    require_once VENDOR_MARKETPLACE_PLUGIN_DIR . 'includes/class-roles.php';
    Vendor_Marketplace_Roles::remove_roles();
}