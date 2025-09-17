<?php
/**
 * Inventory Management functionality for Wholesale Marketplace Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vendor_Marketplace_Admin_Inventory {

    public function __construct() {
        // Ensure inventory table exists
        $this->ensure_inventory_table();

        // Handle AJAX requests
        add_action('wp_ajax_add_inventory_item', array($this, 'add_inventory_item'));
        add_action('wp_ajax_edit_inventory_item', array($this, 'edit_inventory_item'));
        add_action('wp_ajax_delete_inventory_item', array($this, 'delete_inventory_item'));
        add_action('wp_ajax_get_inventory_items', array($this, 'get_inventory_items'));
        add_action('wp_ajax_get_central_inventory_vendors', array($this, 'get_central_inventory_vendors'));
        add_action('wp_ajax_get_inventory_transactions', array($this, 'get_inventory_transactions'));
    }

    private function log_inventory_transaction($product_id, $user_id, $type, $quantity, $inventory_type, $source = 'manual', $reason = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'inventory_transactions';

        $wpdb->insert(
            $table_name,
            array(
                'product_id' => $product_id,
                'user_id' => $user_id,
                'type' => $type,
                'quantity' => $quantity,
                'inventory_type' => $inventory_type,
                'source' => $source,
                'reason' => $reason,
                'created_by' => get_current_user_id(),
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d')
        );
    }

    private function ensure_inventory_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vendor_inventory';
        $transactions_table = $wpdb->prefix . 'inventory_transactions';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
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
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$transactions_table'") != $transactions_table) {
            $charset_collate = $wpdb->get_charset_collate();

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

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql2);
        } else {
            // Check if columns exist and add if missing
            $columns = $wpdb->get_results("DESCRIBE $transactions_table");
            $column_names = array_column($columns, 'Field');

            if (!in_array('inventory_type', $column_names)) {
                $wpdb->query("ALTER TABLE $transactions_table ADD COLUMN inventory_type enum('self','central','product') DEFAULT 'product' AFTER quantity");
            }
            if (!in_array('source', $column_names)) {
                $wpdb->query("ALTER TABLE $transactions_table ADD COLUMN source enum('manual','purchase','sale','adjustment','other') DEFAULT 'manual' AFTER inventory_type");
            }
            if (!in_array('reason', $column_names)) {
                $wpdb->query("ALTER TABLE $transactions_table ADD COLUMN reason varchar(255) DEFAULT '' AFTER source");
            }
            if (!in_array('created_by', $column_names)) {
                $wpdb->query("ALTER TABLE $transactions_table ADD COLUMN created_by bigint(20) unsigned NOT NULL AFTER created_at");
            }
        }
    }

    public function inventory_management_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'central';
        ?>
        <div class="wrap vendor-marketplace-admin">
            <h1><?php _e('مدیریت انبار', 'vendor-marketplace'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=vendor-marketplace-inventory&tab=central" class="nav-tab <?php echo $current_tab === 'central' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('انبار مرکزی', 'vendor-marketplace'); ?>
                </a>
                <a href="?page=vendor-marketplace-inventory&tab=vendors" class="nav-tab <?php echo $current_tab === 'vendors' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('انبار فروشندگان', 'vendor-marketplace'); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php if ($current_tab === 'central'): ?>
                    <?php $this->render_central_inventory_tab(); ?>
                <?php elseif ($current_tab === 'vendors'): ?>
                    <?php $this->render_vendors_inventory_tab(); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modals -->
        <?php $this->render_add_edit_modal(); ?>
        <?php $this->render_transactions_modal(); ?>
        <?php
    }

    private function render_central_inventory_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vendor_inventory';

        // Get current page and search parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

        // Build query
        $where = "p.post_type = 'product' AND p.post_status = 'publish'";
        if (!empty($search)) {
            $where .= $wpdb->prepare(" AND p.post_title LIKE %s", '%' . $wpdb->esc_like($search) . '%');
        }

        $products = $wpdb->get_results("
            SELECT p.ID, p.post_title,
                   SUM(vi.quantity_central) as central_quantity,
                   COUNT(vi.user_id) as vendor_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$table_name} vi ON p.ID = vi.product_id
            WHERE {$where}
            GROUP BY p.ID, p.post_title
            HAVING central_quantity > 0
            ORDER BY p.post_title
            LIMIT " . (($current_page - 1) * 20) . ", 20
        ");

        $total_products = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            LEFT JOIN {$table_name} vi ON p.ID = vi.product_id
            WHERE {$where}
            GROUP BY p.ID
            HAVING SUM(vi.quantity_self + vi.quantity_central) > 0
        ");

        ?>
        <div class="vm-inventory-tab">
            <h2><?php _e('انبار مرکزی - موجودی کلی محصولات', 'vendor-marketplace'); ?></h2>
            <p><?php _e('این بخش موجودی کلی هر محصول از همه فروشندگان را نمایش می‌دهد.', 'vendor-marketplace'); ?></p>

            <!-- Search Form -->
            <div class="vm-search-filters">
                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="vendor-marketplace-inventory">
                    <input type="hidden" name="tab" value="central">
                    <p class="search-box">
                        <label class="screen-reader-text" for="product-search-input"><?php _e('جستجوی محصول:', 'vendor-marketplace'); ?></label>
                        <input type="search" id="product-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('جستجو بر اساس نام محصول', 'vendor-marketplace'); ?>">
                        <input type="submit" id="search-submit" class="button" value="<?php _e('جستجو', 'vendor-marketplace'); ?>">
                    </p>
                </form>
            </div>

            <!-- Products Table -->
            <div class="vm-data-table">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('محصول', 'vendor-marketplace'); ?></th>
                            <th><?php _e('موجودی انبار مرکزی', 'vendor-marketplace'); ?></th>
                            <th><?php _e('تعداد فروشندگان', 'vendor-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="3"><?php _e('هیچ محصولی در انبار یافت نشد.', 'vendor-marketplace'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo esc_html($product->post_title); ?></td>
                                    <td><?php echo intval($product->central_quantity); ?></td>
                                    <td>
                                        <?php echo intval($product->vendor_count); ?>
                                        <span class="dashicons dashicons-info vm-central-info-icon" style="cursor: pointer; color: #007cba;" data-product-id="<?php echo $product->ID; ?>" title="<?php _e('مشاهده فروشندگان انبار مرکزی', 'vendor-marketplace'); ?>"></span>
                                        <button type="button" class="button button-small view-central-transactions-btn"
                                                data-product-id="<?php echo $product->ID; ?>"
                                                data-product-name="<?php echo esc_attr($product->post_title); ?>">
                                            <?php _e('تراکنش‌ها', 'vendor-marketplace'); ?>
                                        </button>
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

    private function render_vendors_inventory_tab() {
        // Get all suppliers
        $suppliers = get_users(array(
            'role' => 'supplier',
            'orderby' => 'display_name'
        ));

        $selected_supplier = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : (empty($suppliers) ? 0 : $suppliers[0]->ID);

        ?>
        <div class="vm-inventory-tab">
            <h2><?php _e('انبار فروشندگان', 'vendor-marketplace'); ?></h2>
            <p><?php _e('محصولات هر فروشنده را مدیریت کنید.', 'vendor-marketplace'); ?></p>

            <?php if (!empty($suppliers)): ?>
                <div class="vm-search-filters">
                    <form method="get" class="search-form">
                        <input type="hidden" name="page" value="vendor-marketplace-inventory">
                        <input type="hidden" name="tab" value="vendors">
                        <div class="actions">
                            <select name="supplier_id" onchange="this.form.submit()">
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier->ID; ?>" <?php selected($selected_supplier, $supplier->ID); ?>>
                                        <?php echo esc_html($supplier->display_name . ' (' . $supplier->user_login . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button button-primary" id="add-product-btn" data-supplier-id="<?php echo $selected_supplier; ?>">
                                <?php _e('افزودن محصول', 'vendor-marketplace'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="vm-data-table">
                    <?php $this->render_supplier_inventory_table($selected_supplier); ?>
                </div>
            <?php else: ?>
                <p><?php _e('هیچ فروشنده‌ای یافت نشد.', 'vendor-marketplace'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_supplier_inventory_table($supplier_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vendor_inventory';

        $inventory_items = $wpdb->get_results($wpdb->prepare("
            SELECT vi.*, p.post_title as product_name
            FROM {$table_name} vi
            JOIN {$wpdb->posts} p ON vi.product_id = p.ID
            WHERE vi.user_id = %d
            ORDER BY p.post_title
        ", $supplier_id));

        ?>
        <table>
            <thead>
                <tr>
                    <th><?php _e('محصول', 'vendor-marketplace'); ?></th>
                    <th><?php _e('تعداد انبار خود', 'vendor-marketplace'); ?></th>
                    <th><?php _e('تعداد انبار مرکزی', 'vendor-marketplace'); ?></th>
                    <th><?php _e('قیمت', 'vendor-marketplace'); ?></th>
                    <th><?php _e('عملیات', 'vendor-marketplace'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventory_items)): ?>
                    <tr>
                        <td colspan="5"><?php _e('هیچ محصولی برای این فروشنده یافت نشد.', 'vendor-marketplace'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inventory_items as $item): ?>
                        <tr>
                            <td><?php echo esc_html($item->product_name); ?></td>
                            <td><?php echo intval($item->quantity_self); ?></td>
                            <td><?php echo intval($item->quantity_central); ?></td>
                            <td><?php echo wc_price($item->price); ?></td>
                            <td>
                                <div class="vm-action-buttons">
                                    <button type="button" class="button button-small edit-inventory-btn"
                                            data-id="<?php echo $item->id; ?>"
                                            data-product-id="<?php echo $item->product_id; ?>"
                                            data-product-name="<?php echo esc_attr($item->product_name); ?>"
                                            data-quantity-self="<?php echo $item->quantity_self; ?>"
                                            data-quantity-central="<?php echo $item->quantity_central; ?>"
                                            data-price="<?php echo $item->price; ?>">
                                        <?php _e('ویرایش', 'vendor-marketplace'); ?>
                                    </button>
                                    <button type="button" class="button button-small view-transactions-btn"
                                            data-product-id="<?php echo $item->product_id; ?>"
                                            data-user-id="<?php echo $supplier_id; ?>"
                                            data-product-name="<?php echo esc_attr($item->product_name); ?>">
                                        <?php _e('تراکنش‌ها', 'vendor-marketplace'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete delete-inventory-btn"
                                            data-id="<?php echo $item->id; ?>"
                                            data-product-name="<?php echo esc_attr($item->product_name); ?>">
                                        <?php _e('حذف', 'vendor-marketplace'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_add_edit_modal() {
        // Get all products for selection
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        ?>
        <div id="inventory-modal" class="vm-modal" style="display: none;">
            <div class="vm-modal-content">
                <div class="vm-modal-header">
                    <h3 id="modal-title"><?php _e('افزودن محصول به انبار', 'vendor-marketplace'); ?></h3>
                    <span class="vm-modal-close">&times;</span>
                </div>
                <div class="vm-modal-body">
                    <form id="inventory-form">
                        <input type="hidden" id="inventory-id" name="id" value="">
                        <input type="hidden" id="supplier-id" name="supplier_id" value="">

                        <div class="form-group">
                            <label for="product-select"><?php _e('انتخاب محصول:', 'vendor-marketplace'); ?></label>
                            <select id="product-select" name="product_id" required>
                                <option value=""><?php _e('انتخاب محصول...', 'vendor-marketplace'); ?></option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product->ID; ?>"><?php echo esc_html($product->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantity-self"><?php _e('تعداد در انبار خود:', 'vendor-marketplace'); ?></label>
                            <input type="number" id="quantity-self" name="quantity_self" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="quantity-central"><?php _e('تعداد در انبار مرکزی:', 'vendor-marketplace'); ?></label>
                            <input type="number" id="quantity-central" name="quantity_central" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="product-price"><?php _e('قیمت:', 'vendor-marketplace'); ?></label>
                            <input type="number" id="product-price" name="price" min="0" step="0.01" required>
                        </div>
                    </form>
                </div>
                <div class="vm-modal-footer">
                    <button type="button" class="button" id="cancel-btn"><?php _e('انصراف', 'vendor-marketplace'); ?></button>
                    <button type="button" class="button button-primary" id="save-btn"><?php _e('ذخیره', 'vendor-marketplace'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    // AJAX handlers
    public function add_inventory_item() {
        check_ajax_referer('inventory_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'vendor-marketplace'));
        }

        $supplier_id = intval($_POST['supplier_id']);
        $product_id = intval($_POST['product_id']);
        $quantity_self = intval($_POST['quantity_self']);
        $quantity_central = intval($_POST['quantity_central']);
        $price = floatval($_POST['price']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'vendor_inventory';

        // Check if product already exists for this supplier
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE user_id = %d AND product_id = %d",
            $supplier_id,
            $product_id
        ));

        if ($exists) {
            wp_send_json_error(__('این محصول قبلاً برای این فروشنده اضافه شده است.', 'vendor-marketplace'));
            return;
        }

        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $supplier_id,
                'product_id' => $product_id,
                'quantity_self' => $quantity_self,
                'quantity_central' => $quantity_central,
                'price' => $price,
            ),
            array('%d', '%d', '%d', '%d', '%f')
        );

        if ($result) {
            // Log transactions
            if ($quantity_self > 0) {
                $this->log_inventory_transaction($product_id, $supplier_id, 'ورود', $quantity_self, 'self', 'manual', "افزودن محصول به انبار خود: {$quantity_self} عدد");
            }
            if ($quantity_central > 0) {
                $this->log_inventory_transaction($product_id, $supplier_id, 'ورود', $quantity_central, 'central', 'manual', "افزودن محصول به انبار مرکزی: {$quantity_central} عدد");
            }

            wp_send_json_success(__('محصول با موفقیت اضافه شد.', 'vendor-marketplace'));
        } else {
            wp_send_json_error(__('خطا در افزودن محصول: ' . $wpdb->last_error, 'vendor-marketplace'));
        }
    }

    public function edit_inventory_item() {
        check_ajax_referer('inventory_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'vendor-marketplace'));
        }

        $id = intval($_POST['id']);
        $quantity_self = intval($_POST['quantity_self']);
        $quantity_central = intval($_POST['quantity_central']);
        $price = floatval($_POST['price']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'vendor_inventory';

        // Get current values
        $current_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id));

        $result = $wpdb->update(
            $table_name,
            array(
                'quantity_self' => $quantity_self,
                'quantity_central' => $quantity_central,
                'price' => $price,
            ),
            array('id' => $id),
            array('%d', '%d', '%f'),
            array('%d')
        );

        if ($result !== false) {
            // Log transactions for quantity changes
            if ($quantity_self != $current_item->quantity_self) {
                $diff = $quantity_self - $current_item->quantity_self;
                $type = $diff > 0 ? 'ورود' : 'خروج';
                $this->log_inventory_transaction($current_item->product_id, $current_item->user_id, $type, abs($diff), 'self', 'manual', "تغییر موجودی انبار خود: " . ($diff > 0 ? '+' : '') . $diff . " عدد");
            }
            if ($quantity_central != $current_item->quantity_central) {
                $diff = $quantity_central - $current_item->quantity_central;
                $type = $diff > 0 ? 'ورود' : 'خروج';
                $this->log_inventory_transaction($current_item->product_id, $current_item->user_id, $type, abs($diff), 'central', 'manual', "تغییر موجودی انبار مرکزی: " . ($diff > 0 ? '+' : '') . $diff . " عدد");
            }

            wp_send_json_success(__('محصول با موفقیت ویرایش شد.', 'vendor-marketplace'));
        } else {
            wp_send_json_error(__('خطا در ویرایش محصول.', 'vendor-marketplace'));
        }
    }

    public function delete_inventory_item() {
        check_ajax_referer('inventory_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'vendor-marketplace'));
        }

        $id = intval($_POST['id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'vendor_inventory';

        // Get current item before deletion
        $current_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id));

        $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));

        if ($result) {
            // Log transactions for removal
            if ($current_item->quantity_self > 0) {
                $this->log_inventory_transaction($current_item->product_id, $current_item->user_id, 'خروج', $current_item->quantity_self, 'self', 'manual', "حذف محصول از انبار خود: {$current_item->quantity_self} عدد");
            }
            if ($current_item->quantity_central > 0) {
                $this->log_inventory_transaction($current_item->product_id, $current_item->user_id, 'خروج', $current_item->quantity_central, 'central', 'manual', "حذف محصول از انبار مرکزی: {$current_item->quantity_central} عدد");
            }

            wp_send_json_success(__('محصول با موفقیت حذف شد.', 'vendor-marketplace'));
        } else {
            wp_send_json_error(__('خطا در حذف محصول.', 'vendor-marketplace'));
        }
    }

    public function get_inventory_items() {
        check_ajax_referer('inventory_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'vendor-marketplace'));
        }

        $supplier_id = intval($_POST['supplier_id']);

        ob_start();
        $this->render_supplier_inventory_table($supplier_id);
        $table_html = ob_get_clean();

        wp_send_json_success(array('html' => $table_html));
    }

    public function get_central_inventory_vendors() {
        check_ajax_referer('inventory_nonce', 'nonce');

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
            WHERE vi.product_id = %d AND vi.quantity_central > 0
            ORDER BY u.display_name
        ", $product_id));

        $formatted_vendors = array();
        foreach ($vendors as $vendor) {
            $formatted_vendors[] = array(
                'supplier_name' => $vendor->supplier_name,
                'price' => wc_price($vendor->price),
                'quantity_central' => intval($vendor->quantity_central),
            );
        }

        wp_send_json_success($formatted_vendors);
    }

    public function get_inventory_transactions() {
        check_ajax_referer('inventory_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'vendor-marketplace'));
        }

        $product_id = intval($_POST['product_id']);
        $user_id = intval($_POST['user_id']);
        $inventory_type = sanitize_text_field($_POST['inventory_type']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'inventory_transactions';

        $where_parts = array();
        $where_parts[] = $wpdb->prepare("product_id = %d", $product_id);

        if ($inventory_type === 'central') {
            $where_parts[] = $wpdb->prepare("inventory_type = %s", 'central');
        } elseif ($inventory_type === 'vendor') {
            $where_parts[] = $wpdb->prepare("user_id = %d", $user_id);
        } else {
            $where_parts[] = $wpdb->prepare("user_id = %d", $user_id);
        }

        if (!empty($date_from)) {
            $where_parts[] = $wpdb->prepare("DATE(created_at) >= %s", $date_from);
        }
        if (!empty($date_to)) {
            $where_parts[] = $wpdb->prepare("DATE(created_at) <= %s", $date_to);
        }

        $where = implode(' AND ', $where_parts);

        if ($inventory_type === 'central') {
            $transactions = $wpdb->get_results("
                SELECT t.*, u.display_name as supplier_name
                FROM {$table_name} t
                LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
                WHERE {$where} ORDER BY t.created_at DESC
            ");
        } else {
            $transactions = $wpdb->get_results("SELECT * FROM {$table_name} WHERE {$where} ORDER BY created_at DESC");
        }

        ob_start();
        $show_supplier = ($inventory_type === 'central');
        $colspan = $show_supplier ? 7 : 6;
        ?>
        <table class="wp-list-table widefat fixed striped" style="margin: 0;">
            <thead>
                <tr>
                    <th><?php _e('تاریخ', 'vendor-marketplace'); ?></th>
                    <th><?php _e('نوع', 'vendor-marketplace'); ?></th>
                    <th><?php _e('تعداد', 'vendor-marketplace'); ?></th>
                    <th><?php _e('نوع انبار', 'vendor-marketplace'); ?></th>
                    <?php if ($show_supplier): ?>
                        <th><?php _e('فروشنده', 'vendor-marketplace'); ?></th>
                    <?php endif; ?>
                    <th><?php _e('منبع', 'vendor-marketplace'); ?></th>
                    <th><?php _e('دلیل', 'vendor-marketplace'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="<?php echo $colspan; ?>"><?php _e('تراکنشی یافت نشد.', 'vendor-marketplace'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo date_i18n('Y/m/d H:i', strtotime($transaction->created_at)); ?></td>
                            <td><?php echo esc_html($transaction->type); ?></td>
                            <td><?php echo intval($transaction->quantity); ?></td>
                            <td><?php
                                switch ($transaction->inventory_type) {
                                    case 'self':
                                        echo __('انبار خود', 'vendor-marketplace');
                                        break;
                                    case 'central':
                                        echo __('انبار مرکزی', 'vendor-marketplace');
                                        break;
                                    case 'product':
                                        echo __('محصول اصلی', 'vendor-marketplace');
                                        break;
                                    default:
                                        echo esc_html($transaction->inventory_type);
                                }
                            ?></td>
                            <?php if ($show_supplier): ?>
                                <td><?php echo isset($transaction->supplier_name) ? esc_html($transaction->supplier_name) : __('---', 'vendor-marketplace'); ?></td>
                            <?php endif; ?>
                            <td><?php
                                switch ($transaction->source) {
                                    case 'manual':
                                        echo __('دستی', 'vendor-marketplace');
                                        break;
                                    case 'purchase':
                                        echo __('خرید', 'vendor-marketplace');
                                        break;
                                    case 'sale':
                                        echo __('فروش', 'vendor-marketplace');
                                        break;
                                    case 'adjustment':
                                        echo __('تنظیم', 'vendor-marketplace');
                                        break;
                                    default:
                                        echo __('سایر', 'vendor-marketplace');
                                }
                            ?></td>
                            <td><?php echo esc_html($transaction->reason); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        $table_html = ob_get_clean();

        wp_send_json_success(array('html' => $table_html));
    }

    private function render_transactions_modal() {
        ?>
        <div id="transactions-modal" class="vm-modal" style="display: none;">
            <div class="vm-modal-content" style="max-width: 800px;">
                <div class="vm-modal-header">
                    <h3><?php _e('تراکنش‌های انبار', 'vendor-marketplace'); ?> - <span id="transaction-product-name"></span></h3>
                    <span class="vm-modal-close" id="close-transactions-modal">&times;</span>
                </div>
                <div class="vm-modal-body">
                    <!-- Filters -->
                    <div class="transaction-filters" style="margin-bottom: 20px;">
                        <label><?php _e('از تاریخ:', 'vendor-marketplace'); ?> <input type="date" id="date-from"></label>
                        <label><?php _e('تا تاریخ:', 'vendor-marketplace'); ?> <input type="date" id="date-to"></label>
                        <button type="button" class="button" id="filter-transactions"><?php _e('فیلتر', 'vendor-marketplace'); ?></button>
                        <button type="button" class="button" id="clear-filters"><?php _e('پاک کردن فیلتر', 'vendor-marketplace'); ?></button>
                    </div>

                    <div id="transactions-content">
                        <!-- Transactions table will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}