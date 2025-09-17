jQuery(document).ready(function($) {
    // Info icon click for vendor details
    $(document).on('click', '.vm-info-icon', function() {
        var productId = $(this).data('product-id');

        $.ajax({
            url: products_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_product_vendors',
                nonce: products_ajax.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    showVendorModal(response.data);
                } else {
                    alert(response.data || 'خطا در دریافت اطلاعات');
                }
            },
            error: function() {
                alert('خطا در ارتباط با سرور');
            }
        });
    });

    function showVendorModal(vendors) {
        var modalHtml = '<div id="vendor-info-modal" class="vm-modal" style="display: block;">';
        modalHtml += '<div class="vm-modal-content" style="max-width: 600px;">';
        modalHtml += '<div class="vm-modal-header">';
        modalHtml += '<h3>جزئیات فروشندگان</h3>';
        modalHtml += '<span class="vm-modal-close" id="close-vendor-modal">&times;</span>';
        modalHtml += '</div>';
        modalHtml += '<div class="vm-modal-body">';

        if (vendors.length > 0) {
            modalHtml += '<table class="wp-list-table widefat fixed striped" style="margin: 0;">';
            modalHtml += '<thead><tr><th>فروشنده</th><th>قیمت</th><th>انبار خود</th><th>انبار مرکزی</th><th>مجموع</th></tr></thead>';
            modalHtml += '<tbody>';

            vendors.forEach(function(vendor) {
                modalHtml += '<tr>';
                modalHtml += '<td>' + vendor.supplier_name + '</td>';
                modalHtml += '<td>' + vendor.price + '</td>';
                modalHtml += '<td>' + vendor.quantity_self + '</td>';
                modalHtml += '<td>' + vendor.quantity_central + '</td>';
                modalHtml += '<td>' + (parseInt(vendor.quantity_self) + parseInt(vendor.quantity_central)) + '</td>';
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
        $('#close-vendor-modal, #vendor-info-modal').on('click', function(e) {
            if (e.target === this) {
                $('#vendor-info-modal').remove();
            }
        });
    }
});