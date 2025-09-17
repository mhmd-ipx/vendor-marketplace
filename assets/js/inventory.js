jQuery(document).ready(function($) {
    // Central inventory info icon
    $(document).on('click', '.vm-central-info-icon', function() {
        var productId = $(this).data('product-id');

        $.ajax({
            url: inventory_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_central_inventory_vendors',
                nonce: inventory_ajax.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    showCentralVendorsModal(response.data);
                } else {
                    alert(response.data || 'خطا در دریافت اطلاعات');
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    });

    // Add product button
    $('#add-product-btn').on('click', function() {
        var supplierId = $(this).data('supplier-id');
        $('#supplier-id').val(supplierId);
        $('#inventory-id').val('');
        $('#product-select').val('');
        $('#quantity-self').val('');
        $('#quantity-central').val('');
        $('#product-price').val('');
        $('#modal-title').text('افزودن محصول به انبار');
        $('#inventory-modal').show();
    });

    // Edit product button
    $(document).on('click', '.edit-inventory-btn', function() {
        var id = $(this).data('id');
        var productId = $(this).data('product-id');
        var productName = $(this).data('product-name');
        var quantitySelf = $(this).data('quantity-self');
        var quantityCentral = $(this).data('quantity-central');
        var price = $(this).data('price');

        $('#inventory-id').val(id);
        $('#product-select').val(productId);
        $('#quantity-self').val(quantitySelf);
        $('#quantity-central').val(quantityCentral);
        $('#product-price').val(price);
        $('#modal-title').text('ویرایش محصول');
        $('#inventory-modal').show();
    });

    // Delete product button
    $(document).on('click', '.delete-inventory-btn', function() {
        var id = $(this).data('id');
        var productName = $(this).data('product-name');

        if (confirm('آیا مطمئن هستید که می‌خواهید محصول "' + productName + '" را حذف کنید؟')) {
            $.ajax({
                url: inventory_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_inventory_item',
                    nonce: inventory_ajax.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert(response.data || 'خطا در حذف محصول');
                    }
                },
                error: function() {
                    alert('خطا در ارتباط با سرور');
                }
            });
        }
    });

    // Save button
    $('#save-btn').on('click', function() {
        var formData = {
            action: $('#inventory-id').val() ? 'edit_inventory_item' : 'add_inventory_item',
            nonce: inventory_ajax.nonce,
            id: $('#inventory-id').val(),
            supplier_id: $('#supplier-id').val(),
            product_id: $('#product-select').val(),
            quantity_self: $('#quantity-self').val(),
            quantity_central: $('#quantity-central').val(),
            price: $('#product-price').val()
        };

        // Validation
        if (!formData.product_id || !formData.quantity_self || !formData.quantity_central || !formData.price) {
            alert('لطفاً همه فیلدها را پر کنید.');
            return;
        }

        $.ajax({
            url: inventory_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#inventory-modal').hide();
                    location.reload();
                } else {
                    alert(response.data || 'خطا در ذخیره محصول');
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    });

    // Cancel button
    $('#cancel-btn, .vm-modal-close').on('click', function() {
        $('#inventory-modal').hide();
    });

    // Transactions button for vendors
    $(document).on('click', '.view-transactions-btn', function() {
        var productId = $(this).data('product-id');
        var userId = $(this).data('user-id');
        var productName = $(this).data('product-name');

        $('#transaction-product-name').text(productName);
        $('#transactions-modal').data('product-id', productId);
        $('#transactions-modal').data('user-id', userId);
        $('#transactions-modal').data('inventory-type', 'vendor');

        loadTransactions();
        $('#transactions-modal').show();
    });

    // Transactions button for central inventory
    $(document).on('click', '.view-central-transactions-btn', function() {
        var productId = $(this).data('product-id');
        var productName = $(this).data('product-name');

        $('#transaction-product-name').text(productName + ' - انبار مرکزی');
        $('#transactions-modal').data('product-id', productId);
        $('#transactions-modal').data('user-id', 0);
        $('#transactions-modal').data('inventory-type', 'central');

        loadTransactions();
        $('#transactions-modal').show();
    });

    // Filter transactions
    $('#filter-transactions').on('click', function() {
        loadTransactions();
    });

    // Clear filters
    $('#clear-filters').on('click', function() {
        $('#date-from').val('');
        $('#date-to').val('');
        loadTransactions();
    });

    function loadTransactions() {
        var productId = $('#transactions-modal').data('product-id');
        var userId = $('#transactions-modal').data('user-id');
        var inventoryType = $('#transactions-modal').data('inventory-type');
        var dateFrom = $('#date-from').val();
        var dateTo = $('#date-to').val();

        $.ajax({
            url: inventory_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_inventory_transactions',
                nonce: inventory_ajax.nonce,
                product_id: productId,
                user_id: userId,
                inventory_type: inventoryType,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                if (response.success) {
                    $('#transactions-content').html(response.data.html);
                } else {
                    $('#transactions-content').html('<p>خطا در دریافت تراکنش‌ها</p>');
                }
            },
            error: function() {
                $('#transactions-content').html('<p>خطا در ارتباط با سرور</p>');
            }
        });
    }

    // Close transactions modal
    $('#close-transactions-modal, #transactions-modal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('vm-modal-close')) {
            $('#transactions-modal').hide();
        }
    });

    // Close modal when clicking outside
    $('#inventory-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    function showCentralVendorsModal(vendors) {
        var modalHtml = '<div id="central-vendor-modal" class="vm-modal" style="display: block;">';
        modalHtml += '<div class="vm-modal-content" style="max-width: 600px;">';
        modalHtml += '<div class="vm-modal-header">';
        modalHtml += '<h3>فروشندگان انبار مرکزی</h3>';
        modalHtml += '<span class="vm-modal-close" id="close-central-modal">&times;</span>';
        modalHtml += '</div>';
        modalHtml += '<div class="vm-modal-body">';

        if (vendors.length > 0) {
            modalHtml += '<table class="wp-list-table widefat fixed striped" style="margin: 0;">';
            modalHtml += '<thead><tr><th>فروشنده</th><th>قیمت</th><th>تعداد در انبار مرکزی</th></tr></thead>';
            modalHtml += '<tbody>';

            vendors.forEach(function(vendor) {
                modalHtml += '<tr>';
                modalHtml += '<td>' + vendor.supplier_name + '</td>';
                modalHtml += '<td>' + vendor.price + '</td>';
                modalHtml += '<td>' + vendor.quantity_central + '</td>';
                modalHtml += '</tr>';
            });

            modalHtml += '</tbody></table>';
        } else {
            modalHtml += '<p>هیچ فروشنده‌ای یافت نشد.</p>';
        }

        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';

        $('body').append(modalHtml);

        // Close modal
        $('#close-central-modal, #central-vendor-modal').on('click', function(e) {
            if (e.target === this || $(e.target).hasClass('vm-modal-close')) {
                $('#central-vendor-modal').remove();
            }
        });
    }
});