<?php
/**
 * Class Sorenam_Cart_Fee_Handler
 *
 * مسئولیت مدیریت و افزودن هزینه (تخفیف) به سبد خرید ووکامرس بر اساس انتخاب کاربر.
 * این کلاس به Sorenam_Calculator وابسته است.
 */
class Sorenam_Cart_Fee_Handler {

    /**
     * ثبت هوک‌های مورد نیاز.
     *
     * @return void
     */
    public function init_hooks() {
        // هوک برای افزودن Fee به صورت شرطی
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_conditional_fee']);
		// هوک جدید برای انتقال اطلاعات از سشن به متای سفارش
        add_action('woocommerce_checkout_create_order', [$this, 'save_cheque_image_to_order_meta'], 10, 2);
    }

		/**
		 * URL تصویر چک را از سشن به متادیتای سفارش منتقل می‌کند.
		 *
		 * @param \WC_Order $order شیء سفارش.
		 * @param array $data داده‌های ارسال شده از فرم تسویه حساب.
		 * @return void
		 */
		public function save_cheque_image_to_order_meta(\WC_Order $order, $data) {
			if (WC()->session) {
				$cheque_image_url = WC()->session->get('sorenam_cheque_image_url');
				if ($cheque_image_url) {
					$order->add_meta_data('_sorenam_cheque_image_url', $cheque_image_url, true);
					// پاک کردن URL از سشن پس از ذخیره در سفارش
					WC()->session->__unset('sorenam_cheque_image_url');
				}
			}
		}
	 
    /**
     * تخفیف را به سبد خرید اضافه می‌کند در صورتی که کاربر "پرداخت نقدی" را انتخاب کرده باشد.
     *
     * @param \WC_Cart $cart شیء سبد خرید ووکامرس.
     * @return void
     */
    public function add_conditional_fee(\WC_Cart $cart) {
        // جلوگیری از اجرا در بخش مدیریت (مگر در درخواست‌های AJAX)
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // اطمینان از اینکه سشن در دسترس است
        if (!WC()->session) {
            return;
        }

        // دریافت وضعیت از سشن که توسط کلاس Sorenam_AJAX ذخیره شده است
        $add_sorenam_peyment_options_fee = WC()->session->get('add_sorenam_peyment_options_fee');

        // اگر دکمه "پرداخت نقدی با تخفیف" انتخاب نشده بود، کاری نکن
        if ($add_sorenam_peyment_options_fee !== 'yes') {
            return;
        }

        // نمونه‌سازی از کلاس ماشین حساب برای محاسبه تخفیف
        $calculator = new Sorenam_Calculator();

        // ۱. محاسبه میانگین دوره بازپرداخت
        $average_days = $calculator->calc_repayment_period($cart);

        // ۲. محاسبه مبلغ تخفیف بر اساس دوره بازپرداخت و مجموع سبد خرید
        $cart_total = $cart->get_cart_contents_total(); // مجموع قیمت محصولات بدون هزینه ارسال
        $discount_amount = $calculator->get_cash_discount_amount($average_days, $cart_total);

        // ۳. اگر مبلغ تخفیف معتبر بود، آن را به سبد خرید اضافه کن
        if ($discount_amount !== 0.0) {
            // با تعیین یک کلاس مالیاتی صریح (حتی خالی)، از باگ احتمالی در هسته ووکامرس جلوگیری می‌کنیم.
            $cart->add_fee('تخفیف پرداخت نقدی', $discount_amount, false, '');
        }
    }
}