<?php
/**
 * User Management functionality for Wholesale Marketplace Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vendor_Marketplace_Admin_Users {

    public function user_management_page() {
        // Handle form submissions
        $this->handle_user_management_actions();

        // Get current page and search parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $role_filter = isset($_GET['role_filter']) ? sanitize_text_field($_GET['role_filter']) : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

        // Build user query arguments
        $args = array(
            'number' => 20,
            'offset' => ($current_page - 1) * 20,
            'orderby' => 'display_name',
            'order' => 'ASC',
        );

        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }

        if (!empty($role_filter)) {
            $args['role'] = $role_filter;
        }

        $users = get_users($args);
        $total_users = count(get_users(array_diff_key($args, array('number' => '', 'offset' => ''))));

        ?>
        <div class="wrap vendor-marketplace-admin">
            <h1><?php _e('مدیریت کاربران بازار عمده فروشی', 'vendor-marketplace'); ?></h1>

            <!-- Search and Filter Form -->
            <div class="vm-search-filters">
                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="vendor-marketplace-users">
                    <p class="search-box">
                        <label class="screen-reader-text" for="user-search-input"><?php _e('جستجوی کاربر:', 'vendor-marketplace'); ?></label>
                        <input type="search" id="user-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('جستجو بر اساس نام، ایمیل یا نام کاربری', 'vendor-marketplace'); ?>">
                        <input type="submit" id="search-submit" class="button" value="<?php _e('جستجو', 'vendor-marketplace'); ?>">
                    </p>

                    <div class="actions">
                        <select name="role_filter">
                            <option value=""><?php _e('همه نقش‌ها', 'vendor-marketplace'); ?></option>
                            <option value="supplier" <?php selected($role_filter, 'supplier'); ?>><?php _e('تامین‌کننده', 'vendor-marketplace'); ?></option>
                            <option value="wholesale_customer" <?php selected($role_filter, 'wholesale_customer'); ?>><?php _e('مشتری عمده', 'vendor-marketplace'); ?></option>
                            <option value="market_manager" <?php selected($role_filter, 'market_manager'); ?>><?php _e('مدیربازار', 'vendor-marketplace'); ?></option>
                        </select>

                        <select name="status_filter">
                            <option value=""><?php _e('همه وضعیت‌ها', 'vendor-marketplace'); ?></option>
                            <option value="active" <?php selected($status_filter, 'active'); ?>><?php _e('فعال', 'vendor-marketplace'); ?></option>
                            <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php _e('غیرفعال', 'vendor-marketplace'); ?></option>
                        </select>

                        <input type="submit" class="button" value="<?php _e('فیلتر', 'vendor-marketplace'); ?>">
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="vm-data-table">
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('نام کاربر', 'vendor-marketplace'); ?></th>
                            <th><?php _e('ایمیل', 'vendor-marketplace'); ?></th>
                            <th><?php _e('نقش', 'vendor-marketplace'); ?></th>
                            <th><?php _e('تغییر نقش', 'vendor-marketplace'); ?></th>
                            <th><?php _e('وضعیت', 'vendor-marketplace'); ?></th>
                            <th><?php _e('آدرس', 'vendor-marketplace'); ?></th>
                            <th><?php _e('مدارک', 'vendor-marketplace'); ?></th>
                            <th><?php _e('عملیات', 'vendor-marketplace'); ?></th>
                        </tr>
                    </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8"><?php _e('هیچ کاربری یافت نشد.', 'vendor-marketplace'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $user_status = get_user_meta($user->ID, 'vendor_user_status', true) ?: 'active';
                            $user_address = get_user_meta($user->ID, 'vendor_address', true);
                            $user_documents = get_user_meta($user->ID, 'vendor_documents', true);
                            $user_license = get_user_meta($user->ID, 'vendor_license', true);
                            ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?><br><small><?php echo esc_html($user->user_login); ?></small></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('change_user_role', 'change_role_nonce'); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                        <input type="hidden" name="action" value="change_role">
                                        <select name="new_role" style="width: 120px;">
                                            <option value="subscriber" <?php selected($user->roles[0], 'subscriber'); ?>><?php _e('کاربر عادی', 'vendor-marketplace'); ?></option>
                                            <option value="supplier" <?php selected($user->roles[0], 'supplier'); ?>><?php _e('تامین‌کننده', 'vendor-marketplace'); ?></option>
                                            <option value="wholesale_customer" <?php selected($user->roles[0], 'wholesale_customer'); ?>><?php _e('مشتری عمده', 'vendor-marketplace'); ?></option>
                                            <option value="market_manager" <?php selected($user->roles[0], 'market_manager'); ?>><?php _e('مدیربازار', 'vendor-marketplace'); ?></option>
                                        </select>
                                        <input type="submit" class="button button-small" value="<?php _e('تغییر', 'vendor-marketplace'); ?>">
                                    </form>
                                </td>
                                <td>
                                    <span class="vm-status-badge <?php echo $user_status; ?>">
                                        <?php echo $user_status === 'active' ? __('فعال', 'vendor-marketplace') : __('غیرفعال', 'vendor-marketplace'); ?>
                                    </span>
                                </td>
                                <td><?php echo $user_address ? esc_html($user_address) : __('---', 'vendor-marketplace'); ?></td>
                                <td>
                                    <?php if ($user_documents): ?>
                                        <span class="dashicons dashicons-yes" style="color: #00a32a; font-size: 18px;"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-no" style="color: #d63638; font-size: 18px;"></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="vm-action-buttons">
                                        <a href="?page=vendor-marketplace-users&action=edit&user_id=<?php echo $user->ID; ?>" class="button button-small">
                                            <?php _e('ویرایش', 'vendor-marketplace'); ?>
                                        </a>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('toggle_user_status', 'toggle_status_nonce'); ?>
                                            <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="submit" class="button button-small <?php echo $user_status === 'active' ? 'button-secondary' : 'button-primary'; ?>"
                                                   value="<?php echo $user_status === 'active' ? __('غیرفعال کردن', 'vendor-marketplace') : __('فعال کردن', 'vendor-marketplace'); ?>">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_users > 20): ?>
                <div class="vm-pagination">
                    <?php
                    $total_pages = ceil($total_users / 20);
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

            <!-- Edit User Modal/Form -->
            <?php $this->render_edit_user_form(); ?>

            <script>
                jQuery(document).ready(function($) {
                    // Show edit form when edit button is clicked
                    $('.edit-user-link').on('click', function(e) {
                        e.preventDefault();
                        var userId = $(this).data('user-id');
                        $('.edit-user-form').removeClass('active');
                        $('#edit-user-' + userId).addClass('active');
                        $('html, body').animate({
                            scrollTop: $('#edit-user-' + userId).offset().top - 50
                        }, 500);
                    });

                    // Add new document upload field
                    $('#add-document-upload').on('click', function() {
                        var $container = $('#document-upload-container');
                        var $newItem = $container.find('.document-upload-item:first').clone();
                        $newItem.find('input[type="text"]').val('');
                        $newItem.find('input[type="file"]').val('');
                        $container.append($newItem);
                    });

                    // Remove document upload field
                    $(document).on('click', '.remove-upload-item', function() {
                        var $container = $('#document-upload-container');
                        if ($container.find('.document-upload-item').length > 1) {
                            $(this).closest('.document-upload-item').remove();
                        } else {
                            alert('حداقل یک فیلد آپلود باید وجود داشته باشد.');
                        }
                    });

                    // Document preview functionality
                    $('.preview-doc-btn').on('click', function() {
                        var docUrl = $(this).data('doc-url');
                        var docName = $(this).data('doc-name');
                        var fileExt = docName.split('.').pop().toLowerCase();

                        $('#document-preview-content').html('<p>در حال بارگذاری...</p>');
                        $('#document-preview-modal').show();

                        if (fileExt === 'pdf') {
                            $('#document-preview-content').html('<iframe src="' + docUrl + '" style="width: 100%; height: 600px; border: none;"></iframe>');
                        } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                            $('#document-preview-content').html('<img src="' + docUrl + '" style="max-width: 100%; max-height: 600px;" />');
                        } else {
                            $('#document-preview-content').html('<p>این نوع فایل قابل پیش‌نمایش نیست. <a href="' + docUrl + '" target="_blank">برای مشاهده کلیک کنید</a></p>');
                        }
                    });

                    // Update document preview data attributes for new format
                    $('.preview-doc-btn').each(function() {
                        var $btn = $(this);
                        var docUrl = $btn.data('doc-url');
                        var docName = $btn.data('doc-name');

                        // If docName doesn't contain extension, try to get it from URL
                        if (docName && !docName.includes('.')) {
                            var urlParts = docUrl.split('/');
                            var filename = urlParts[urlParts.length - 1];
                            if (filename.includes('.')) {
                                $btn.data('doc-name', filename);
                            }
                        }
                    });

                    // Close preview modal
                    $('#close-preview-modal').on('click', function() {
                        $('#document-preview-modal').hide();
                    });

                    // Close modal when clicking outside
                    $('#document-preview-modal').on('click', function(e) {
                        if (e.target === this) {
                            $(this).hide();
                        }
                    });

                    // Document removal functionality
                    var documentsToRemove = [];

                    $('.remove-doc-btn').on('click', function() {
                        var docIndex = $(this).data('doc-index');
                        var $documentItem = $(this).closest('.document-item');

                        if (confirm('آیا مطمئن هستید که می‌خواهید این مدرک را حذف کنید؟')) {
                            documentsToRemove.push(docIndex);
                            $('#remove_documents').val(JSON.stringify(documentsToRemove));
                            $documentItem.fadeOut(300, function() {
                                $(this).remove();
                            });
                        }
                    });
                });
            </script>
        </div>
        <?php
    }

    private function handle_user_management_actions() {
        // Handle role change
        if (isset($_POST['action']) && $_POST['action'] === 'change_role' &&
            isset($_POST['change_role_nonce']) && wp_verify_nonce($_POST['change_role_nonce'], 'change_user_role')) {

            $user_id = intval($_POST['user_id']);
            $new_role = sanitize_text_field($_POST['new_role']);

            if (Vendor_Marketplace_Roles::change_user_role($user_id, $new_role)) {
                echo '<div class="notice notice-success"><p>' . __('نقش کاربر تغییر یافت.', 'vendor-marketplace') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('خطا در تغییر نقش.', 'vendor-marketplace') . '</p></div>';
            }
        }

        // Handle status toggle
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_status' &&
            isset($_POST['toggle_status_nonce']) && wp_verify_nonce($_POST['toggle_status_nonce'], 'toggle_user_status')) {

            $user_id = intval($_POST['user_id']);
            $current_status = get_user_meta($user_id, 'vendor_user_status', true) ?: 'active';
            $new_status = $current_status === 'active' ? 'inactive' : 'active';

            update_user_meta($user_id, 'vendor_user_status', $new_status);

            $message = $new_status === 'active' ?
                __('کاربر فعال شد.', 'vendor-marketplace') :
                __('کاربر غیرفعال شد.', 'vendor-marketplace');

            echo '<div class="notice notice-success"><p>' . $message . '</p></div>';
        }

        // Handle user profile update
        if (isset($_POST['action']) && $_POST['action'] === 'update_user_profile' &&
            isset($_POST['update_profile_nonce']) && wp_verify_nonce($_POST['update_profile_nonce'], 'update_user_profile')) {

            $user_id = intval($_POST['user_id']);

            // Update user meta fields
            $fields = array(
                'vendor_address' => 'address',
                'vendor_phone' => 'phone',
                'vendor_company' => 'company',
                'vendor_license' => 'license',
                'vendor_documents' => 'documents'
            );

            foreach ($fields as $meta_key => $post_key) {
                if (isset($_POST[$post_key])) {
                    update_user_meta($user_id, $meta_key, sanitize_text_field($_POST[$post_key]));
                }
            }

            // Handle document removal
            if (!empty($_POST['remove_documents'])) {
                $documents_to_remove = json_decode(stripslashes($_POST['remove_documents']), true);
                if (is_array($documents_to_remove)) {
                    $existing_docs = get_user_meta($user_id, 'vendor_documents', true) ?: array();
                    foreach ($documents_to_remove as $index) {
                        if (isset($existing_docs[$index])) {
                            unset($existing_docs[$index]);
                        }
                    }
                    // Re-index array
                    $existing_docs = array_values($existing_docs);
                    update_user_meta($user_id, 'vendor_documents', $existing_docs);
                }
            }

            // Handle file uploads for documents
            if (!empty($_FILES['documents']['name'])) {
                $this->handle_document_upload($user_id);
            }

            echo '<div class="notice notice-success"><p>' . __('پروفایل کاربر به‌روزرسانی شد.', 'vendor-marketplace') . '</p></div>';
        }
    }

    private function handle_document_upload($user_id) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $files = $_FILES['documents'];
        $document_names = isset($_POST['document_names']) ? $_POST['document_names'] : array();
        $uploaded_files = array();

        // Handle multiple files
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if (!empty($files['name'][$i])) {
                    $file = array(
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    );

                    $upload_overrides = array('test_form' => false);
                    $movefile = wp_handle_upload($file, $upload_overrides);

                    if ($movefile && !isset($movefile['error'])) {
                        $doc_name = isset($document_names[$i]) && !empty($document_names[$i]) ?
                            sanitize_text_field($document_names[$i]) :
                            basename($files['name'][$i]);

                        $uploaded_files[] = array(
                            'name' => $doc_name,
                            'url' => $movefile['url'],
                            'filename' => basename($files['name'][$i])
                        );
                    }
                }
            }
        } else {
            // Single file
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($files, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $doc_name = isset($document_names[0]) && !empty($document_names[0]) ?
                    sanitize_text_field($document_names[0]) :
                    basename($files['name']);

                $uploaded_files[] = array(
                    'name' => $doc_name,
                    'url' => $movefile['url'],
                    'filename' => basename($files['name'])
                );
            }
        }

        if (!empty($uploaded_files)) {
            $existing_docs = get_user_meta($user_id, 'vendor_documents', true) ?: array();
            $all_docs = array_merge($existing_docs, $uploaded_files);
            update_user_meta($user_id, 'vendor_documents', $all_docs);
        }
    }

    private function render_edit_user_form() {
        $edit_user_id = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        if (!$edit_user_id) return;

        $user = get_user_by('id', $edit_user_id);
        if (!$user) return;

        // Get user meta
        $address = get_user_meta($edit_user_id, 'vendor_address', true);
        $phone = get_user_meta($edit_user_id, 'vendor_phone', true);
        $company = get_user_meta($edit_user_id, 'vendor_company', true);
        $license = get_user_meta($edit_user_id, 'vendor_license', true);
        $documents = get_user_meta($edit_user_id, 'vendor_documents', true) ?: array();

        ?>
        <div id="edit-user-<?php echo $edit_user_id; ?>" class="vm-form-container edit-user-form active">
            <h2><?php printf(__('ویرایش پروفایل: %s', 'vendor-marketplace'), esc_html($user->display_name)); ?></h2>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('update_user_profile', 'update_profile_nonce'); ?>
                <input type="hidden" name="action" value="update_user_profile">
                <input type="hidden" name="user_id" value="<?php echo $edit_user_id; ?>">

                <table class="form-table">
                    <tr>
                        <th><label for="address"><?php _e('آدرس', 'vendor-marketplace'); ?></label></th>
                        <td><textarea name="address" id="address" rows="3" cols="50"><?php echo esc_textarea($address); ?></textarea></td>
                    </tr>

                    <tr>
                        <th><label for="phone"><?php _e('شماره تلفن', 'vendor-marketplace'); ?></label></th>
                        <td><input type="text" name="phone" id="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th><label for="company"><?php _e('نام شرکت', 'vendor-marketplace'); ?></label></th>
                        <td><input type="text" name="company" id="company" value="<?php echo esc_attr($company); ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th><label for="license"><?php _e('شماره مجوز', 'vendor-marketplace'); ?></label></th>
                        <td><input type="text" name="license" id="license" value="<?php echo esc_attr($license); ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th><label for="documents"><?php _e('مدارک', 'vendor-marketplace'); ?></label></th>
                        <td>
                            <div id="document-upload-container" class="vm-document-upload-container">
                                <div class="vm-document-upload-item">
                                    <input type="text" name="document_names[]" placeholder="<?php _e('نام مدرک', 'vendor-marketplace'); ?>" class="regular-text">
                                    <input type="file" name="documents[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <button type="button" class="button remove-upload-item"><?php _e('حذف', 'vendor-marketplace'); ?></button>
                                </div>
                            </div>
                            <button type="button" id="add-document-upload" class="vm-add-document-btn"><?php _e('افزودن مدرک دیگر', 'vendor-marketplace'); ?></button>
                            <p class="description"><?php _e('برای هر مدرک یک نام مشخص کنید (PDF, JPG, PNG, DOC, DOCX)', 'vendor-marketplace'); ?></p>

                            <?php if (!empty($documents)): ?>
                                <h4><?php _e('مدارک موجود:', 'vendor-marketplace'); ?></h4>
                                <div class="vm-documents-section">
                                    <?php foreach ($documents as $index => $doc): ?>
                                        <?php
                                        // Handle both old format (string URLs) and new format (arrays)
                                        $doc_name = is_array($doc) ? $doc['name'] : basename($doc);
                                        $doc_url = is_array($doc) ? $doc['url'] : $doc;
                                        $doc_filename = is_array($doc) && isset($doc['filename']) ? $doc['filename'] : basename($doc_url);
                                        ?>
                                        <div class="vm-document-item">
                                            <div class="document-title"><?php echo esc_html($doc_name); ?></div>
                                            <?php if ($doc_name !== $doc_filename): ?>
                                                <div class="document-filename"><?php echo esc_html($doc_filename); ?></div>
                                            <?php endif; ?>
                                            <div class="document-actions">
                                                <a href="<?php echo esc_url($doc_url); ?>" target="_blank" class="button button-small"><?php _e('دانلود', 'vendor-marketplace'); ?></a>
                                                <button type="button" class="button button-small preview-doc-btn" data-doc-url="<?php echo esc_url($doc_url); ?>" data-doc-name="<?php echo esc_attr($doc_filename); ?>"><?php _e('پیش‌نمایش', 'vendor-marketplace'); ?></button>
                                                <button type="button" class="button button-small button-link-delete remove-doc-btn" data-doc-index="<?php echo $index; ?>"><?php _e('حذف', 'vendor-marketplace'); ?></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Document Preview Modal -->
                                <div id="document-preview-modal" class="vm-modal">
                                    <div class="vm-modal-content">
                                        <div class="vm-modal-header">
                                            <button type="button" id="close-preview-modal" class="vm-modal-close">&times;</button>
                                        </div>
                                        <div id="document-preview-content">
                                            <!-- Document content will be loaded here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden inputs for document removal -->
                                <input type="hidden" name="remove_documents" id="remove_documents" value="">
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('به‌روزرسانی پروفایل', 'vendor-marketplace'); ?>">
                    <a href="?page=vendor-marketplace-users" class="button"><?php _e('انصراف', 'vendor-marketplace'); ?></a>
                </p>
            </form>
        </div>
        <?php
    }
}