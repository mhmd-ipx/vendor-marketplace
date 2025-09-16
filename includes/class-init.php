<?php
/**
 * Main initialization class for Wholesale Marketplace plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vendor_Marketplace_Init {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        // Load textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Load other classes after WooCommerce is loaded
        add_action('woocommerce_loaded', array($this, 'load_classes'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('vendor-marketplace', false, dirname(plugin_basename(VENDOR_MARKETPLACE_PLUGIN_DIR . 'vendor-marketplace.php')) . '/languages/');
    }

    public function load_classes() {
        // Include and instantiate other classes here
        // Admin panel
        require_once VENDOR_MARKETPLACE_PLUGIN_DIR . 'includes/class-admin.php';
        new Vendor_Marketplace_Admin();

        // Roles management
        require_once VENDOR_MARKETPLACE_PLUGIN_DIR . 'includes/class-roles.php';
        new Vendor_Marketplace_Roles();

        // Placeholder for other classes
    }
}