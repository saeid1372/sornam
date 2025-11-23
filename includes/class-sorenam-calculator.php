<?php
/**
 * Class Sorenam_Calculator
 *
 * هسته محاسباتی پلاگین برای تخفیف نقدی.
 * این کلاس هیچ وابستگی به سایر بخش‌های پلاگین ندارد و فقط وظیفه محاسبه را بر عهده دارد.
 */
class Sorenam_Calculator {

    /**
     * میانگین موزون دوره بازپرداخت را بر حسب روز برای یک سبد خرید محاسبه می‌کند.
     *
     * @param \WC_Cart $cart شیء سبد خرید برای محاسبه.
     * @return float میانگین تعداد روزها.
     */
    public function calc_repayment_period(\WC_Cart $cart): float {
        if ($cart->is_empty()) {
            return 0.0;
        }

        $total_price = 0.0;
        $weighted_sum = 0.0;

        foreach ($cart->get_cart() as $cart_item) {
            $line_total = (float) $cart_item['line_total'] + (float) $cart_item['line_tax'];
            // فرض بر این است که _installments_number تعداد روزها را ذخیره می‌کند.
            $installments_in_days = (int) $cart_item['data']->get_meta('_installments_number', true);

            // برای دیباگ کردن می‌توانید از خط زیر استفاده کنید
            // error_log('[Sorenam Debug] installments_in_days for product ' . $cart_item['data']->get_id() . ': ' . $installments_in_days);

            $total_price += $line_total;
            $weighted_sum += $line_total * $installments_in_days;
        }

        // ضریب 30 برای تبدیل ماه به روز (در صورت نیاز به منطق ماهانه)
        // در کد اصلی شما این ضریب وجود داشت. اگر منطق شما مستقیماً بر اساس روز است، آن را حذف کنید.
        $average_days = $total_price > 0 ? ($weighted_sum / $total_price)*30 : 0.0;
//           error_log('[Sorenam Debug] Final average_days: ' . $average_days);

        return $average_days;
    }

    /**
     * مبلغ تخفیف نقدی را بر اساس دوره بازپرداخت محاسبه می‌کند.
     * تخفیف 0.1% به ازای هر روز بالاتر از آستانه 20 روز اعمال می‌شود.
     *
     * @param float $average_days میانگین دوره بازپرداخت به روز.
     * @param float $cart_total مجموع کل سبد خرید قبل از تخفیف.
     * @return float مبلغ تخفیف (مقدار منفی).
     */
    public function get_cash_discount_amount(float $average_days, float $cart_total): float {
        if ($average_days < 1) {
            return 0.0;
        }

        // اگر دوره بازپرداخت ۲۰ روز یا کمتر بود، تخفیفی اعمال نمی‌شود.
        if ($average_days <= 20) {
            return 0.0;
        }
		
		 // تعیین تعداد روزها برای محاسبه تخفیف بر اساس شرط جدید
        $days_for_calculation = $average_days; // مقدار پیش‌فرض

        if ($average_days > 20 && $average_days <= 30) {
            // اگر میانگین روزها بین ۲۰ تا ۳۰ بود، آن را برابر ۳۰ در نظر بگیر
            $days_for_calculation = 30;
        }
		
        // به ازای هر روز از دوره بازپرداخت، ۰.۱ درصد تخفیف اعمال می‌شود.
        $discount_percent = $average_days * 0.1;

        $discount_amount = $cart_total * $discount_percent / 100;
         //error_log('[Sorenam Debug] Calculated discount_amount: ' . $discount_amount);

        return -round($discount_amount, wc_get_price_decimals());
    }
}