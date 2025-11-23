jQuery(document).ready(function($) {
	// div نمایش روز ارسال رایگان
	var displayDiv = $('<div id="wc-fsp-delivery-day" style="margin:15px 0; font-weight:bold;"></div>');
	$('.woocommerce-cart-form').before(displayDiv);

	// AJAX برای دریافت روز ارسال رایگان
	$.ajax({
		url: wcFspCart.ajax_url,
		type: 'POST',
		data: { action: 'wc_fsp_get_delivery_day' },
		success: function(response) {
			if (response.success) {
				displayDiv.text('روز ارسال رایگان برای شهر شما (' + response.data.city + '): ' + response.data.day);
			} else {
				displayDiv.text(response.data || 'اطلاعات ارسال رایگان موجود نیست.');
			}
		},
		error: function() {
			displayDiv.text('خطا در بارگذاری اطلاعات ارسال رایگان.');
		}
	});
});
