<?php
/**
 * Role Management functionality for Wholesale Marketplace Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vendor_Marketplace_Admin_Roles {

    public function roles_management_page() {
        // Handle role change form submission
        if (isset($_POST['change_role_nonce']) && wp_verify_nonce($_POST['change_role_nonce'], 'change_user_role')) {
            if (isset($_POST['user_id']) && isset($_POST['new_role'])) {
                $user_id = intval($_POST['user_id']);
                $new_role = sanitize_text_field($_POST['new_role']);
                if (Vendor_Marketplace_Roles::change_user_role($user_id, $new_role)) {
                    echo '<div class="notice notice-success"><p>' . __('نقش کاربر تغییر یافت.', 'vendor-marketplace') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('خطا در تغییر نقش.', 'vendor-marketplace') . '</p></div>';
                }
            }
        }

        // Get all users
        $users = get_users(array(
            'number' => 20, // Limit for performance
            'orderby' => 'display_name',
        ));

        ?>
        <div class="wrap vendor-marketplace-admin">
            <h1><?php _e('مدیریت نقش‌های کاربری', 'vendor-marketplace'); ?></h1>

            <div class="vm-notice">
                <p><?php _e('این صفحه برای تغییر سریع نقش کاربران طراحی شده است. برای مدیریت کامل کاربران، از صفحه "مدیریت کاربران" استفاده کنید.', 'vendor-marketplace'); ?></p>
            </div>

            <div class="vm-data-table">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('نام کاربر', 'vendor-marketplace'); ?></th>
                            <th><?php _e('ایمیل', 'vendor-marketplace'); ?></th>
                            <th><?php _e('نقش فعلی', 'vendor-marketplace'); ?></th>
                            <th><?php _e('تغییر نقش', 'vendor-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4"><?php _e('هیچ کاربری یافت نشد.', 'vendor-marketplace'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?><br><small><?php echo esc_html($user->user_login); ?></small></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('change_user_role', 'change_role_nonce'); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                        <select name="new_role" style="width: 140px;">
                                            <option value="subscriber" <?php selected($user->roles[0], 'subscriber'); ?>><?php _e('کاربر عادی', 'vendor-marketplace'); ?></option>
                                            <option value="supplier" <?php selected($user->roles[0], 'supplier'); ?>><?php _e('تامین‌کننده', 'vendor-marketplace'); ?></option>
                                            <option value="wholesale_customer" <?php selected($user->roles[0], 'wholesale_customer'); ?>><?php _e('مشتری عمده', 'vendor-marketplace'); ?></option>
                                            <option value="market_manager" <?php selected($user->roles[0], 'market_manager'); ?>><?php _e('مدیربازار', 'vendor-marketplace'); ?></option>
                                        </select>
                                        <input type="submit" class="button button-small" value="<?php _e('تغییر', 'vendor-marketplace'); ?>">
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="vm-dashboard-grid">
                <div class="vm-dashboard-card">
                    <span class="card-icon">👤</span>
                    <h3><?php _e('کاربر عادی', 'vendor-marketplace'); ?></h3>
                    <p><?php _e('دسترسی پایه به سایت ووکامرس', 'vendor-marketplace'); ?></p>
                </div>

                <div class="vm-dashboard-card">
                    <span class="card-icon">🏪</span>
                    <h3><?php _e('تامین‌کننده', 'vendor-marketplace'); ?></h3>
                    <p><?php _e('دسترسی به آپلود محصولات و مدیریت موجودی', 'vendor-marketplace'); ?></p>
                </div>

                <div class="vm-dashboard-card">
                    <span class="card-icon">🛒</span>
                    <h3><?php _e('مشتری عمده', 'vendor-marketplace'); ?></h3>
                    <p><?php _e('دسترسی به خرید عمده با قیمت ویژه', 'vendor-marketplace'); ?></p>
                </div>

                <div class="vm-dashboard-card">
                    <span class="card-icon">👔</span>
                    <h3><?php _e('مدیربازار', 'vendor-marketplace'); ?></h3>
                    <p><?php _e('دسترسی کامل مدیریتی به همه بخش‌ها', 'vendor-marketplace'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}