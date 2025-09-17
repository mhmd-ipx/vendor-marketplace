<?php
/**
 * Product Management functionality for Wholesale Marketplace Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vendor_Marketplace_Admin_Products {

    public function __construct() {
        // Add metabox to product edit page
        add_action('add_meta_boxes_product', array($this, 'add_product_metabox'));
        add_action('save_post_product', array($this, 'save_product_metabox'));

        // Handle AJAX requests
        add_action('wp_ajax_get_product_vendors', array($this, 'get_product_vendors'));

        // Track stock changes
        add_action('save_post_product', array($this, 'track_stock_changes'), 20, 3);
    }

    public function product_management_page() {
        // Get current page and search parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $category_filter = isset($_GET['category_filter']) ? sanitize_text_field($_GET['category_filter']) : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

        // Build product query arguments
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 20,
            'paged' => $current_page,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($category_filter)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_filter,
                ),
            );
        }

        if (!empty($status_filter)) {
            $args['post_status'] = $status_filter;
        }

        $products_query = new WP_Query($args);
        $products = $products_query->posts;
        $total_products = $products_query->found_posts;

        // Get product categories for filter
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        ?>
        <div class="wrap vendor-marketplace-admin">
            <h1><?php _e('مدیریت محصولات', 'vendor-marketplace'); ?></h1>

            <div style="margin-bottom: 20px;">
                <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary">
                    <?php _e('افزودن محصول جدید', 'vendor-marketplace'); ?>
                </a>
            </div>

            <!-- Search and Filter Form -->
            <div class="vm-search-filters">
                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="vendor-marketplace-products">
                    <p class="search-box">
                        <label class="screen-reader-text" for="product-search-input"><?php _e('جستجوی محصول:', 'vendor-marketplace'); ?></label>
                        <input type="search" id="product-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('جستجو بر اساس نام محصول', 'vendor-marketplace'); ?>">
                        <input type="submit" id="search-submit" class="button" value="<?php _e('جستجو', 'vendor-marketplace'); ?>">
                    </p>

                    <div class="actions">
                        <select name="category_filter">
                            <option value=""><?php _e('همه دسته‌بندی‌ها', 'vendor-marketplace'); ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category->term_id; ?>" <?php selected($category_filter, $category->term_id); ?>><?php echo esc_html($category->name); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select name="status_filter">
                            <option value=""><?php _e('همه وضعیت‌ها', 'vendor-marketplace'); ?></option>
                            <option value="publish" <?php selected($status_filter, 'publish'); ?>><?php _e('منتشر شده', 'vendor-marketplace'); ?></option>
                            <option value="draft" <?php selected($status_filter, 'draft'); ?>><?php _e('پیش‌نویس', 'vendor-marketplace'); ?></option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('در انتظار بررسی', 'vendor-marketplace'); ?></option>
                        </select>

                        <input type="submit" class="button" value="<?php _e('فیلتر', 'vendor-marketplace'); ?>">
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <div class="vm-data-table">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('تصویر', 'vendor-marketplace'); ?></th>
                            <th><?php _e('نام محصول', 'vendor-marketplace'); ?></th>
                            <th><?php _e('دسته‌بندی', 'vendor-marketplace'); ?></th>
                            <th><?php _e('کمیسیون', 'vendor-marketplace'); ?></th>
                            <th><?php _e('کد یکتا', 'vendor-marketplace'); ?></th>
                            <th><?php _e('فروشندگان', 'vendor-marketplace'); ?></th>
                            <th><?php _e('موجودی کل', 'vendor-marketplace'); ?></th>
                            <th><?php _e('وضعیت', 'vendor-marketplace'); ?></th>
                            <th><?php _e('عملیات', 'vendor-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9"><?php _e('هیچ محصولی یافت نشد.', 'vendor-marketplace'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product_post): ?>
                                <?php
                                $product = wc_get_product($product_post->ID);
                                if (!$product) continue;

                                $thumbnail = $product->get_image(array(50, 50));
                                $categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'));
                                $commission_percentage = get_post_meta($product->get_id(), '_vendor_commission_percentage', true);
                                $unique_product_code = get_post_meta($product->get_id(), '_vendor_unique_product_code', true);

                                // Get inventory data
                                global $wpdb;
                                $inventory_table = $wpdb->prefix . 'vendor_inventory';
                                $inventory_data = $wpdb->get_row($wpdb->prepare("
                                    SELECT COUNT(*) as vendor_count, SUM(quantity_self + quantity_central) as total_quantity
                                    FROM {$inventory_table}
                                    WHERE product_id = %d
                                ", $product->get_id()));

                                $vendor_count = $inventory_data ? intval($inventory_data->vendor_count) : 0;
                                $total_quantity = $inventory_data ? intval($inventory_data->total_quantity) : 0;
                                ?>
                                <tr>
                                    <td><?php echo $thumbnail; ?></td>
                                    <td>
                                        <strong><?php echo esc_html($product->get_name()); ?></strong>
                                        <br><small>ID: <?php echo $product->get_id(); ?></small>
                                    </td>
                                    <td><?php echo esc_html(implode(', ', $categories)); ?></td>
                                    <td><?php echo $commission_percentage ? esc_html($commission_percentage) . '%' : __('---', 'vendor-marketplace'); ?></td>
                                    <td><?php echo $unique_product_code ? esc_html($unique_product_code) : __('---', 'vendor-marketplace'); ?></td>
                                    <td>
                                        <?php if ($vendor_count > 0): ?>
                                            <span><?php echo $vendor_count; ?></span>
                                            <span class="dashicons dashicons-info vm-info-icon" style="cursor: pointer; color: #007cba;" data-product-id="<?php echo $product->get_id(); ?>" title="<?php _e('مشاهده جزئیات فروشندگان', 'vendor-marketplace'); ?>"></span>
                                        <?php else: ?>
                                            <?php _e('---', 'vendor-marketplace'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $total_quantity > 0 ? $total_quantity : __('---', 'vendor-marketplace'); ?></td>
                                    <td>
                                        <?php
                                        switch ($product_post->post_status) {
                                            case 'publish':
                                                echo '<span class="dashicons dashicons-visibility" style="color: #00a32a; font-size: 18px;" title="' . __('منتشر شده', 'vendor-marketplace') . '"></span>';
                                                break;
                                            case 'draft':
                                                echo '<span class="dashicons dashicons-edit" style="color: #dba617; font-size: 18px;" title="' . __('پیش‌نویس', 'vendor-marketplace') . '"></span>';
                                                break;
                                            case 'pending':
                                                echo '<span class="dashicons dashicons-clock" style="color: #dba617; font-size: 18px;" title="' . __('در انتظار بررسی', 'vendor-marketplace') . '"></span>';
                                                break;
                                            default:
                                                echo '<span class="dashicons dashicons-minus" style="color: #646970; font-size: 18px;" title="' . esc_attr($product_post->post_status) . '"></span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="vm-action-buttons">
                                            <button type="button" class="button button-small vm-quick-edit-btn"
                                                    data-product-id="<?php echo $product->get_id(); ?>"
                                                    data-product-name="<?php echo esc_attr($product->get_name()); ?>"
                                                    data-commission="<?php echo esc_attr($commission_percentage); ?>"
                                                    data-unique-code="<?php echo esc_attr($unique_product_code); ?>">
                                                <?php _e('ویرایش سریع', 'vendor-marketplace'); ?>
                                            </button>
                                            <a href="<?php echo get_edit_post_link($product->get_id()); ?>" class="button button-small" target="_blank">
                                                <?php _e('ویرایش', 'vendor-marketplace'); ?>
                                            </a>
                                            <a href="<?php echo get_permalink($product->get_id()); ?>" class="button button-small" target="_blank">
                                                <?php _e('مشاهده', 'vendor-marketplace'); ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_products > 20): ?>
                <div class="vm-pagination">
                    <?php
                    $total_pages = ceil($total_products / 20);
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; قبلی', 'vendor-marketplace'),
                        'next_text' => __('بعدی &raquo;', 'vendor-marketplace'),
                        'total' => $total_pages,
                        'current' => $current_page,
                    ));
                    if ($page_links) {
                        echo $page_links;
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function add_product_metabox() {
        add_meta_box(
            'vendor-marketplace-product-data',
            __('بازار عمده فروشی', 'vendor-marketplace'),
            array($this, 'render_product_metabox'),
            'product',
            'side',
            'default'
        );
    }

    public function render_product_metabox($post) {
        // Add nonce for security
        wp_nonce_field('vendor_marketplace_product_data', 'vendor_marketplace_product_nonce');

        // Get current values
        $commission_percentage = get_post_meta($post->ID, '_vendor_commission_percentage', true);
        $unique_product_code = get_post_meta($post->ID, '_vendor_unique_product_code', true);

        // Get inventory data
        global $wpdb;
        $table_name = $wpdb->prefix . 'vendor_inventory';
        $inventory_items = $wpdb->get_results($wpdb->prepare("
            SELECT vi.*, u.display_name as supplier_name
            FROM {$table_name} vi
            JOIN {$wpdb->users} u ON vi.user_id = u.ID
            WHERE vi.product_id = %d
            ORDER BY u.display_name
        ", $post->ID));

        ?>
        <div class="vm-form-container" style="padding: 15px; margin: -15px;">
            <p>
                <label for="vendor_commission_percentage" style="display: block; margin-bottom: 5px; font-weight: 600;">
                    <?php _e('درصد کمیسیون:', 'vendor-marketplace'); ?>
                </label>
                <input type="number"
                        id="vendor_commission_percentage"
                        name="vendor_commission_percentage"
                        value="<?php echo esc_attr($commission_percentage); ?>"
                        min="0"
                        max="100"
                        step="0.01"
                        placeholder="0.00"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <span style="font-size: 12px; color: #666;"><?php _e('درصد (مثال: 10.50)', 'vendor-marketplace'); ?></span>
            </p>

            <p>
                <label for="vendor_unique_product_code" style="display: block; margin-bottom: 5px; font-weight: 600;">
                    <?php _e('کد یکتا محصول:', 'vendor-marketplace'); ?>
                </label>
                <input type="text"
                        id="vendor_unique_product_code"
                        name="vendor_unique_product_code"
                        value="<?php echo esc_attr($unique_product_code); ?>"
                        placeholder="<?php _e('کد یکتا را وارد کنید', 'vendor-marketplace'); ?>"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <span style="font-size: 12px; color: #666;"><?php _e('کد منحصر به فرد برای محصول', 'vendor-marketplace'); ?></span>
            </p>

            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
                <h4 style="margin: 0 0 10px 0; color: #1d2327;"><?php _e('موجودی فروشندگان', 'vendor-marketplace'); ?></h4>
                <?php if (!empty($inventory_items)): ?>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 8px; border: 1px solid #ddd; text-align: right;"><?php _e('فروشنده', 'vendor-marketplace'); ?></th>
                                    <th style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php _e('قیمت', 'vendor-marketplace'); ?></th>
                                    <th style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php _e('انبار خود', 'vendor-marketplace'); ?></th>
                                    <th style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php _e('انبار مرکزی', 'vendor-marketplace'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory_items as $item): ?>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($item->supplier_name); ?></td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo wc_price($item->price); ?></td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo intval($item->quantity_self); ?></td>
                                        <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><?php echo intval($item->quantity_central); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: #666; font-style: italic; margin: 0;"><?php _e('هیچ فروشنده‌ای این محصول را اضافه نکرده است.', 'vendor-marketplace'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function save_product_metabox($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['vendor_marketplace_product_nonce']) ||
            !wp_verify_nonce($_POST['vendor_marketplace_product_nonce'], 'vendor_marketplace_product_data')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if this is a product
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        // Save commission percentage
        if (isset($_POST['vendor_commission_percentage'])) {
            $commission = sanitize_text_field($_POST['vendor_commission_percentage']);
            // Validate it's a number between 0 and 100
            $commission = floatval($commission);
            if ($commission >= 0 && $commission <= 100) {
                update_post_meta($post_id, '_vendor_commission_percentage', $commission);
            }
        }

        // Save unique product code
        if (isset($_POST['vendor_unique_product_code'])) {
            $unique_code = sanitize_text_field($_POST['vendor_unique_product_code']);
            update_post_meta($post_id, '_vendor_unique_product_code', $unique_code);
        }
    }

    public function get_product_vendors() {
        check_ajax_referer('products_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'vendor-marketplace'));
        }

        $product_id = intval($_POST['product_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'vendor_inventory';

        $vendors = $wpdb->get_results($wpdb->prepare("
            SELECT vi.*, u.display_name as supplier_name
            FROM {$table_name} vi
            JOIN {$wpdb->users} u ON vi.user_id = u.ID
            WHERE vi.product_id = %d
            ORDER BY u.display_name
        ", $product_id));

        $formatted_vendors = array();
        foreach ($vendors as $vendor) {
            $formatted_vendors[] = array(
                'supplier_name' => $vendor->supplier_name,
                'price' => wc_price($vendor->price),
                'quantity_self' => intval($vendor->quantity_self),
                'quantity_central' => intval($vendor->quantity_central),
            );
        }

        wp_send_json_success($formatted_vendors);
    }

    public function track_stock_changes($post_id, $post, $update) {
        if ($post->post_type !== 'product' || !$update) {
            return;
        }

        $product = wc_get_product($post_id);
        if (!$product) {
            return;
        }

        $current_stock = $product->get_stock_quantity();
        $previous_stock = get_post_meta($post_id, '_previous_stock', true);

        if ($previous_stock !== '' && $current_stock != $previous_stock) {
            $diff = $current_stock - $previous_stock;
            $type = $diff > 0 ? 'ورود' : 'خروج';

            // Log transaction
            global $wpdb;
            $table_name = $wpdb->prefix . 'inventory_transactions';

            $wpdb->insert(
                $table_name,
                array(
                    'product_id' => $post_id,
                    'user_id' => 0, // System change
                    'type' => $type,
                    'quantity' => abs($diff),
                    'inventory_type' => 'product',
                    'source' => 'manual', // Can be changed based on context
                    'reason' => "تغییر موجودی محصول: " . ($diff > 0 ? '+' : '') . $diff . " عدد",
                    'created_by' => get_current_user_id(),
                ),
                array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d')
            );
        }

        // Update previous stock
        update_post_meta($post_id, '_previous_stock', $current_stock);
    }
}