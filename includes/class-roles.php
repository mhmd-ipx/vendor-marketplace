<?php
/**
 * User roles management for Wholesale Marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vendor_Marketplace_Roles {

    public function __construct() {
        // Add roles on plugin load to ensure they exist
        add_action('init', array($this, 'ensure_roles_exist'), 10);
    }

    /**
     * Ensure roles exist on every plugin load
     */
    public function ensure_roles_exist() {
        // Check if roles already exist
        if (!get_role('supplier')) {
            $this->add_supplier_role();
        }
        if (!get_role('wholesale_customer')) {
            $this->add_wholesale_customer_role();
        }
        if (!get_role('market_manager')) {
            $this->add_market_manager_role();
        }
    }

    /**
     * Add Supplier role
     */
    private function add_supplier_role() {
        add_role(
            'supplier',
            __('تامین‌کننده', 'vendor-marketplace'),
            array(
                'read' => true,
                'edit_posts' => false,
                'publish_posts' => false,
                'edit_published_posts' => false,
                'delete_posts' => false,
                'upload_files' => true,
                // Add more capabilities as needed for supplier features
            )
        );
    }

    /**
     * Add Wholesale Customer role
     */
    private function add_wholesale_customer_role() {
        add_role(
            'wholesale_customer',
            __('مشتری عمده', 'vendor-marketplace'),
            array(
                'read' => true,
                'edit_posts' => false,
                'publish_posts' => false,
                'edit_published_posts' => false,
                'delete_posts' => false,
                // Add more capabilities as needed for customer features
            )
        );
    }

    /**
     * Add Market Manager role
     */
    private function add_market_manager_role() {
        add_role(
            'market_manager',
            __('مدیربازار', 'vendor-marketplace'),
            array(
                'read' => true,
                'edit_posts' => true,
                'publish_posts' => true,
                'edit_published_posts' => true,
                'delete_posts' => true,
                'upload_files' => true,
                'edit_others_posts' => true,
                'delete_others_posts' => true,
                'manage_categories' => true,
                // Add more capabilities as needed for market manager features
            )
        );
    }

    /**
     * Add custom user roles on plugin activation
     */
    public static function add_roles() {
        // Remove existing roles if they exist (for re-activation)
        remove_role('supplier');
        remove_role('wholesale_customer');
        remove_role('market_manager');

        // Add roles using the private methods
        $instance = new self();
        $instance->add_supplier_role();
        $instance->add_wholesale_customer_role();
        $instance->add_market_manager_role();
    }

    /**
     * Remove custom roles on plugin deactivation
     */
    public static function remove_roles() {
        remove_role('supplier');
        remove_role('wholesale_customer');
        remove_role('market_manager');
    }

    /**
     * Check if user has specific role
     */
    public static function user_has_role($user_id, $role) {
        $user = new WP_User($user_id);
        return in_array($role, (array) $user->roles);
    }

    /**
     * Change user role
     */
    public static function change_user_role($user_id, $new_role) {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $user = new WP_User($user_id);
        $user->set_role($new_role);
        return true;
    }
}