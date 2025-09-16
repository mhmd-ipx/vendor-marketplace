<?php
/**
 * Test script to verify that custom roles are properly added to WordPress
 * This file should be placed in the plugin root and accessed via browser
 * Example: http://your-site.com/wp-content/plugins/vendor-marketplace/test-roles.php
 */

if (!defined('ABSPATH')) {
    // Define WordPress path - adjust this to your WordPress installation path
    define('WP_USE_THEMES', false);
    require_once '../../../wp-load.php';
}

// Check if roles exist
$supplier_role = get_role('supplier');
$wholesale_customer_role = get_role('wholesale_customer');
$market_manager_role = get_role('market_manager');

echo "<h1>WordPress Custom Roles Test</h1>";
echo "<h2>Vendor Marketplace Plugin Roles</h2>";

echo "<h3>Supplier Role:</h3>";
if ($supplier_role) {
    echo "<p style='color: green;'>✓ Supplier role exists</p>";
    echo "<p><strong>Display Name:</strong> " . $supplier_role->name . "</p>";
    echo "<p><strong>Capabilities:</strong></p>";
    echo "<ul>";
    foreach ($supplier_role->capabilities as $cap => $has) {
        echo "<li>" . $cap . ": " . ($has ? 'Yes' : 'No') . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Supplier role does not exist</p>";
}

echo "<h3>Wholesale Customer Role:</h3>";
if ($wholesale_customer_role) {
    echo "<p style='color: green;'>✓ Wholesale Customer role exists</p>";
    echo "<p><strong>Display Name:</strong> " . $wholesale_customer_role->name . "</p>";
    echo "<p><strong>Capabilities:</strong></p>";
    echo "<ul>";
    foreach ($wholesale_customer_role->capabilities as $cap => $has) {
        echo "<li>" . $cap . ": " . ($has ? 'Yes' : 'No') . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Wholesale Customer role does not exist</p>";
}

echo "<h3>Market Manager Role:</h3>";
if ($market_manager_role) {
    echo "<p style='color: green;'>✓ Market Manager role exists</p>";
    echo "<p><strong>Display Name:</strong> " . $market_manager_role->name . "</p>";
    echo "<p><strong>Capabilities:</strong></p>";
    echo "<ul>";
    foreach ($market_manager_role->capabilities as $cap => $has) {
        echo "<li>" . $cap . ": " . ($has ? 'Yes' : 'No') . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Market Manager role does not exist</p>";
}

// Test role assignment
echo "<h2>Role Assignment Test</h2>";
$user_id = get_current_user_id();
if ($user_id) {
    $user = new WP_User($user_id);
    echo "<p><strong>Current User:</strong> " . $user->display_name . "</p>";
    echo "<p><strong>Current Roles:</strong> " . implode(', ', $user->roles) . "</p>";

    // Test if we can check for custom roles
    if (class_exists('Vendor_Marketplace_Roles')) {
        echo "<p><strong>Testing Vendor_Marketplace_Roles::user_has_role():</strong></p>";
        echo "<ul>";
        echo "<li>Has supplier role: " . (Vendor_Marketplace_Roles::user_has_role($user_id, 'supplier') ? 'Yes' : 'No') . "</li>";
        echo "<li>Has wholesale_customer role: " . (Vendor_Marketplace_Roles::user_has_role($user_id, 'wholesale_customer') ? 'Yes' : 'No') . "</li>";
        echo "<li>Has market_manager role: " . (Vendor_Marketplace_Roles::user_has_role($user_id, 'market_manager') ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>Vendor_Marketplace_Roles class not found</p>";
    }
} else {
    echo "<p>No user logged in</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> If roles are missing, try deactivating and reactivating the plugin, or check the plugin activation logs.</p>";
?>