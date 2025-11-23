/**
 * sorenam-admin-product-handler.js
 *
 * جاوااسکریپت مربوط به بخش مدیریت محصولات در پیشخوان وردپرس.
 * این اسکریپت فیلد "تعداد اقساط" را در حالت ویرایش سریع (Quick Edit) با مقدار فعلی پر می‌کند.
 */

jQuery(document).ready(function($) {
    $('body').on('click', '.editinline', function() {
        const postId = $(this).closest('tr').attr('id').replace("post-", "");
        
		        console.log('[Sorenam JS Debug] Quick Edit clicked for Post ID:', postId);

		
        // حلقه برای پردازش تمام فیلدهای تعریف شده در PHP
        if (typeof sorenam_admin_params !== 'undefined' && sorenam_admin_params.fields) {
            sorenam_admin_params.fields.forEach(function(metaKey) {
                const columnClass = '.column-' + metaKey.replace('_', '-');
                const cellValue = $('#post-' + postId + ' ' + columnClass).text().trim();
                const inputField = $('input[name="' + metaKey + '"]');

				
				console.log('[Sorenam JS Debug] Processing Meta Key:', metaKey);
                console.log('[Sorenam JS Debug] Looking for value in selector:', cellSelector);
                console.log('[Sorenam JS Debug] Found cell value:', cellValue);
                console.log('[Sorenam JS Debug] Found input field:', inputField.length > 0 ? 'Yes' : 'No');
				
                if (inputField.length) {
                    if (cellValue.includes('نقدی') || cellValue === '-') {
						 console.log('[Sorenam JS Debug] Value is cash or empty, setting input to ""');
                        inputField.val('');
                    } else {
                        // استخراج عدد از رشته (مثلاً "30 روز" یا "5")
                        const number = cellValue.replace(/[^0-9]/g, '');
						                        console.log('[Sorenam JS Debug] Extracted number:', number);

                        inputField.val(number);
                    }
                }else {
                    console.error('[Sorenam JS Debug] Input field not found for meta key:', metaKey);
                }
                console.log('-----------------------------');
            });
        }
    });
});