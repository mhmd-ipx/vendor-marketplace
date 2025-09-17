<?php
/**
 * Modal components for Wholesale Marketplace Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vendor_Marketplace_Admin_Modals {

    public function __construct() {
        add_action('admin_footer', array($this, 'render_quick_edit_modal'));
        add_action('wp_ajax_vendor_quick_edit_product', array($this, 'handle_quick_edit_product'));
        add_action('wp_ajax_vendor_get_product_data', array($this, 'handle_get_product_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function render_quick_edit_modal() {
        // Render modal on all admin pages for reliability

        ?>
        <div id="vm-quick-edit-modal" class="vm-modal">
            <div class="vm-modal-content">
                <div class="vm-modal-header">
                    <h2><?php _e('ویرایش سریع محصول', 'vendor-marketplace'); ?></h2>
                    <button type="button" id="vm-close-quick-edit-modal" class="vm-modal-close">&times;</button>
                </div>
                <div class="vm-modal-body">
                    <form id="vm-quick-edit-form">
                        <input type="hidden" name="action" value="vendor_quick_edit_product">
                        <?php wp_nonce_field('vm_quick_edit_product', 'vm_quick_edit_nonce'); ?>
                        <input type="hidden" id="vm-edit-product-id" name="product_id" value="">

                        <div class="vm-form-row">
                            <label for="vm-edit-product-name"><?php _e('نام محصول:', 'vendor-marketplace'); ?></label>
                            <input type="text" id="vm-edit-product-name" name="product_name" class="regular-text" readonly>
                        </div>

                        <div class="vm-form-row">
                            <label for="vm-edit-commission"><?php _e('درصد کمیسیون:', 'vendor-marketplace'); ?></label>
                            <input type="number" id="vm-edit-commission" name="commission_percentage" min="0" max="100" step="0.01" placeholder="0.00">
                            <span class="description"><?php _e('درصد (مثال: 10.50)', 'vendor-marketplace'); ?></span>
                        </div>

                        <div class="vm-form-row">
                            <label for="vm-edit-unique-code"><?php _e('کد یکتا محصول:', 'vendor-marketplace'); ?></label>
                            <input type="text" id="vm-edit-unique-code" name="unique_product_code" class="regular-text" placeholder="<?php _e('کد یکتا را وارد کنید', 'vendor-marketplace'); ?>">
                            <span class="description"><?php _e('کد منحصر به فرد برای محصول', 'vendor-marketplace'); ?></span>
                        </div>

                        <div class="vm-form-row">
                            <label for="vm-edit-product-status"><?php _e('وضعیت محصول:', 'vendor-marketplace'); ?></label>
                            <select id="vm-edit-product-status" name="product_status">
                                <option value="publish"><?php _e('منتشر شده', 'vendor-marketplace'); ?></option>
                                <option value="draft"><?php _e('پیش‌نویس', 'vendor-marketplace'); ?></option>
                                <option value="pending"><?php _e('در انتظار بررسی', 'vendor-marketplace'); ?></option>
                            </select>
                        </div>

                        <div class="vm-form-row">
                            <label for="vm-edit-product-visibility"><?php _e('وضعیت نمایش:', 'vendor-marketplace'); ?></label>
                            <select id="vm-edit-product-visibility" name="product_visibility">
                                <option value="visible"><?php _e('قابل مشاهده', 'vendor-marketplace'); ?></option>
                                <option value="catalog"><?php _e('فقط در کاتالوگ', 'vendor-marketplace'); ?></option>
                                <option value="search"><?php _e('فقط در جستجو', 'vendor-marketplace'); ?></option>
                                <option value="hidden"><?php _e('مخفی', 'vendor-marketplace'); ?></option>
                            </select>
                        </div>

                        <div class="vm-form-row">
                            <label for="vm-edit-regular-price"><?php _e('قیمت عادی:', 'vendor-marketplace'); ?></label>
                            <input type="number" id="vm-edit-regular-price" name="regular_price" min="0" step="0.01" placeholder="0.00">
                        </div>

                        <div class="vm-form-row">
                            <label for="vm-edit-sale-price"><?php _e('قیمت فروش:', 'vendor-marketplace'); ?></label>
                            <input type="number" id="vm-edit-sale-price" name="sale_price" min="0" step="0.01" placeholder="0.00">
                        </div>

                        <div class="vm-form-row">
                            <label for="vm-edit-stock-status"><?php _e('وضعیت موجودی:', 'vendor-marketplace'); ?></label>
                            <select id="vm-edit-stock-status" name="stock_status">
                                <option value="instock"><?php _e('موجود', 'vendor-marketplace'); ?></option>
                                <option value="outofstock"><?php _e('ناموجود', 'vendor-marketplace'); ?></option>
                                <option value="onbackorder"><?php _e('در سفارش مجدد', 'vendor-marketplace'); ?></option>
                            </select>
                        </div>

                        <div class="vm-form-row">
                            <label for="vm-edit-manage-stock"><?php _e('مدیریت موجودی:', 'vendor-marketplace'); ?></label>
                            <input type="checkbox" id="vm-edit-manage-stock" name="manage_stock" value="1">
                            <span class="description"><?php _e('فعال کردن مدیریت موجودی', 'vendor-marketplace'); ?></span>
                        </div>

                        <div class="vm-form-row" id="vm-stock-quantity-row" style="display: none;">
                            <label for="vm-edit-stock-quantity"><?php _e('مقدار موجودی:', 'vendor-marketplace'); ?></label>
                            <input type="number" id="vm-edit-stock-quantity" name="stock_quantity" min="0" placeholder="0">
                        </div>
                    </form>
                </div>
                <div class="vm-modal-footer">
                    <button type="button" id="vm-save-quick-edit" class="button button-primary">
                        <?php _e('ذخیره تغییرات', 'vendor-marketplace'); ?>
                    </button>
                    <button type="button" id="vm-cancel-quick-edit" class="button">
                        <?php _e('انصراف', 'vendor-marketplace'); ?>
                    </button>
                </div>
            </div>
        </div>

        <style>
            .vm-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 9999;
                animation: vmFadeIn 0.3s ease;
            }

            .vm-modal.active {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .vm-modal-content {
                background: #fff;
                border-radius: 12px;
                padding: 0;
                max-width: 600px;
                max-height: 90%;
                overflow-y: auto;
                position: relative;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: vmSlideIn 0.3s ease;
                width: 90%;
                margin: 20px;
            }

            .vm-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 30px;
                border-bottom: 1px solid #e1e1e1;
                background: #f8f9fa;
                border-radius: 12px 12px 0 0;
            }

            .vm-modal-header h2 {
                margin: 0;
                color: #1d2327;
                font-size: 1.5em;
                font-weight: 600;
            }

            .vm-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #646970;
                padding: 5px;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }

            .vm-modal-close:hover {
                background: #e1e1e1;
                color: #1d2327;
            }

            .vm-modal-body {
                padding: 30px;
                max-height: 400px;
                overflow-y: auto;
            }

            .vm-form-row {
                margin-bottom: 20px;
            }

            .vm-form-row label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: #1d2327;
            }

            .vm-form-row input[type="text"],
            .vm-form-row input[type="number"],
            .vm-form-row select {
                width: 100%;
                padding: 10px 12px;
                border: 2px solid #ddd;
                border-radius: 6px;
                font-size: 14px;
                transition: all 0.3s ease;
                background: #fff;
            }

            .vm-form-row input[type="text"]:focus,
            .vm-form-row input[type="number"]:focus,
            .vm-form-row select:focus {
                border-color: #007cba;
                box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
                outline: none;
            }

            .vm-form-row input[type="checkbox"] {
                width: auto;
                margin-right: 8px;
            }

            .vm-form-row .description {
                color: #646970;
                font-size: 12px;
                margin-top: 4px;
                font-style: italic;
            }

            .vm-modal-footer {
                padding: 20px 30px;
                border-top: 1px solid #e1e1e1;
                background: #f8f9fa;
                border-radius: 0 0 12px 12px;
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }

            .vm-modal-footer .button {
                padding: 10px 20px;
                border-radius: 6px;
                font-weight: 500;
                transition: all 0.3s ease;
            }

            .vm-modal-footer .button-primary {
                background: #007cba;
                color: #fff;
                border: none;
            }

            .vm-modal-footer .button-primary:hover {
                background: #005a87;
                transform: translateY(-1px);
            }

            @keyframes vmFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes vmSlideIn {
                from {
                    opacity: 0;
                    transform: scale(0.9) translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: scale(1) translateY(0);
                }
            }

            @media (max-width: 768px) {
                .vm-modal-content {
                    width: 95%;
                    margin: 10px;
                    max-height: 95%;
                }

                .vm-modal-header,
                .vm-modal-body,
                .vm-modal-footer {
                    padding: 15px 20px;
                }

                .vm-modal-body {
                    max-height: 300px;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Only initialize if modal exists and we have quick edit buttons
                if (!$('#vm-quick-edit-modal').length || !$('.vm-quick-edit-btn').length) {
                    return;
                }

                // Quick edit button click
                $('.vm-quick-edit-btn').on('click', function() {
                    var productId = $(this).data('product-id');
                    var productName = $(this).data('product-name');
                    var commission = $(this).data('commission');
                    var uniqueCode = $(this).data('unique-code');

                    // Populate form
                    $('#vm-edit-product-id').val(productId);
                    $('#vm-edit-product-name').val(productName);
                    $('#vm-edit-commission').val(commission);
                    $('#vm-edit-unique-code').val(uniqueCode);

                    // Load additional product data via AJAX
                    $.ajax({
                        url: vm_ajax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vendor_get_product_data',
                            product_id: productId,
                            nonce: vm_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                var data = response.data;
                                $('#vm-edit-product-status').val(data.status);
                                $('#vm-edit-product-visibility').val(data.visibility);
                                $('#vm-edit-regular-price').val(data.regular_price);
                                $('#vm-edit-sale-price').val(data.sale_price);
                                $('#vm-edit-stock-status').val(data.stock_status);

                                if (data.manage_stock === 'yes') {
                                    $('#vm-edit-manage-stock').prop('checked', true);
                                    $('#vm-stock-quantity-row').show();
                                    $('#vm-edit-stock-quantity').val(data.stock_quantity);
                                } else {
                                    $('#vm-edit-manage-stock').prop('checked', false);
                                    $('#vm-stock-quantity-row').hide();
                                }
                            }
                        }
                    });

                    $('#vm-quick-edit-modal').addClass('active');
                });

                // Close modal
                $('#vm-close-quick-edit-modal, #vm-cancel-quick-edit').on('click', function() {
                    $('#vm-quick-edit-modal').removeClass('active');
                });

                // Close modal when clicking outside
                $('#vm-quick-edit-modal').on('click', function(e) {
                    if (e.target === this) {
                        $(this).removeClass('active');
                    }
                });

                // Toggle stock quantity field
                $('#vm-edit-manage-stock').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#vm-stock-quantity-row').show();
                    } else {
                        $('#vm-stock-quantity-row').hide();
                    }
                });

                // Save quick edit
                $('#vm-save-quick-edit').on('click', function() {
                    var formData = $('#vm-quick-edit-form').serialize();

                    $.ajax({
                        url: vm_ajax.ajaxurl,
                        type: 'POST',
                        data: formData,
                        success: function(response) {
                            console.log('AJAX Response:', response);
                            if (response.success) {
                                $('#vm-quick-edit-modal').removeClass('active');
                                location.reload(); // Refresh to show changes
                            } else {
                                alert('Error: ' + (response.data || '<?php _e('خطا در ذخیره تغییرات', 'vendor-marketplace'); ?>'));
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX Error:', xhr, status, error);
                            alert('Server Error: ' + error + ' (Status: ' + xhr.status + ')');
                        }
                    });
                });
            });
        </script>
        <?php
    }

    public function handle_quick_edit_product() {
        error_log('Vendor Marketplace: handle_quick_edit_product called');
        try {
            // Verify nonce
            if (!isset($_POST['vm_quick_edit_nonce']) || !wp_verify_nonce($_POST['vm_quick_edit_nonce'], 'vm_quick_edit_product')) {
                wp_send_json_error(__('امنیت نامعتبر', 'vendor-marketplace'));
                return;
            }

            // Check permissions
            if (!current_user_can('edit_products')) {
                wp_send_json_error(__('دسترسی غیرمجاز', 'vendor-marketplace'));
                return;
            }

            $product_id = intval($_POST['product_id']);
            if (!$product_id) {
                wp_send_json_error(__('شناسه محصول نامعتبر', 'vendor-marketplace'));
                return;
            }

            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(__('محصول یافت نشد', 'vendor-marketplace'));
                return;
            }

        // Update commission and unique code
        if (isset($_POST['commission_percentage'])) {
            $commission = sanitize_text_field($_POST['commission_percentage']);
            $commission = floatval($commission);
            if ($commission >= 0 && $commission <= 100) {
                update_post_meta($product_id, '_vendor_commission_percentage', $commission);
            }
        }

        if (isset($_POST['unique_product_code'])) {
            $unique_code = sanitize_text_field($_POST['unique_product_code']);
            update_post_meta($product_id, '_vendor_unique_product_code', $unique_code);
        }

        // Update product status
        if (isset($_POST['product_status'])) {
            $status = sanitize_text_field($_POST['product_status']);
            if (in_array($status, array('publish', 'draft', 'pending'))) {
                wp_update_post(array(
                    'ID' => $product_id,
                    'post_status' => $status
                ));
            }
        }

        // Update visibility
        if (isset($_POST['product_visibility'])) {
            $visibility = sanitize_text_field($_POST['product_visibility']);
            update_post_meta($product_id, '_visibility', $visibility);
        }

        // Update prices
        if (isset($_POST['regular_price'])) {
            $regular_price = floatval($_POST['regular_price']);
            update_post_meta($product_id, '_regular_price', $regular_price);
        }

        if (isset($_POST['sale_price'])) {
            $sale_price = floatval($_POST['sale_price']);
            update_post_meta($product_id, '_sale_price', $sale_price);
        }

        // Update stock
        if (isset($_POST['stock_status'])) {
            $stock_status = sanitize_text_field($_POST['stock_status']);
            update_post_meta($product_id, '_stock_status', $stock_status);
        }

        if (isset($_POST['manage_stock'])) {
            update_post_meta($product_id, '_manage_stock', 'yes');
            if (isset($_POST['stock_quantity'])) {
                $stock_quantity = intval($_POST['stock_quantity']);
                update_post_meta($product_id, '_stock', $stock_quantity);
            }
        } else {
            update_post_meta($product_id, '_manage_stock', 'no');
        }

        // Clear product cache
        wc_delete_product_transients($product_id);

        wp_send_json_success(__('تغییرات ذخیره شد', 'vendor-marketplace'));
        } catch (Exception $e) {
            wp_send_json_error(__('خطای سیستمی: ', 'vendor-marketplace') . $e->getMessage());
        }
    }

    public function enqueue_scripts($hook) {
        // Load on all admin pages for modal functionality
        wp_localize_script('jquery', 'vm_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vm_ajax_nonce')
        ));
    }

    public function handle_get_product_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vm_ajax_nonce')) {
            wp_die(__('امنیت نامعتبر', 'vendor-marketplace'));
        }

        // Check permissions
        if (!current_user_can('edit_products')) {
            wp_die(__('دسترسی غیرمجاز', 'vendor-marketplace'));
        }

        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(__('محصول یافت نشد', 'vendor-marketplace'));
        }

        $data = array(
            'status' => $product->get_status(),
            'visibility' => get_post_meta($product_id, '_visibility', true) ?: 'visible',
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'stock_status' => $product->get_stock_status(),
            'manage_stock' => $product->get_manage_stock() ? 'yes' : 'no',
            'stock_quantity' => $product->get_stock_quantity()
        );

        wp_send_json_success($data);
    }
}