<?php
/**
 * Documentation functionality for Wholesale Marketplace Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vendor_Marketplace_Admin_Docs {

    public function documentation_page() {
        ?>
        <div class="wrap vendor-marketplace-admin">
            <h1><?php _e('داکیومنت پلاگین', 'vendor-marketplace'); ?></h1>

            <div class="vm-form-container">
                <?php
                $doc_file = VENDOR_MARKETPLACE_PLUGIN_DIR . 'documentation.md';
                if (file_exists($doc_file)) {
                    $content = file_get_contents($doc_file);
                    // Simple markdown to HTML conversion (basic)
                    $content = nl2br(esc_html($content));
                    $content = preg_replace('/### (.*?)\n/', '<h3>$1</h3>', $content);
                    $content = preg_replace('/## (.*?)\n/', '<h2>$1</h2>', $content);
                    $content = preg_replace('/# (.*?)\n/', '<h1>$1</h1>', $content);
                    echo $content;
                } else {
                    echo '<div class="vm-notice error">';
                    echo '<p>' . __('فایل داکیومنت یافت نشد.', 'vendor-marketplace') . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

            <div class="vm-dashboard-grid">
                <div class="vm-dashboard-card">
                    <span class="card-icon">📚</span>
                    <h3><?php _e('راهنمای شروع سریع', 'vendor-marketplace'); ?></h3>
                    <p><?php _e('آموزش نصب و راه‌اندازی اولیه پلاگین', 'vendor-marketplace'); ?></p>
                </div>

                <div class="vm-dashboard-card">
                    <span class="card-icon">🔧</span>
                    <h3><?php _e('پیکربندی پیشرفته', 'vendor-marketplace'); ?></h3>
                    <p><?php _e('تنظیمات پیشرفته و سفارشی‌سازی پلاگین', 'vendor-marketplace'); ?></p>
                </div>

                <div class="vm-dashboard-card">
                    <span class="card-icon">❓</span>
                    <h3><?php _e('سوالات متداول', 'vendor-marketplace'); ?></h3>
                    <p><?php _e('پاسخ سوالات رایج کاربران', 'vendor-marketplace'); ?></p>
                </div>

                <div class="vm-dashboard-card">
                    <span class="card-icon">🆘</span>
                    <h3><?php _e('پشتیبانی', 'vendor-marketplace'); ?></h3>
                    <p><?php _e('تماس با تیم پشتیبانی و گزارش مشکلات', 'vendor-marketplace'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}