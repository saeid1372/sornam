<?php
/**
 * Class Sorenam_Shortcode
 *
 * مدیریت شورت‌کد نمایش دکمه‌های انتخاب نوع پرداخت و بارگذاری منابع مرتبط.
 */
class Sorenam_Shortcode {

    /**
     * ثبت هوک‌های مورد نیاز.
     *
     * @return void
     */
    public function init_hooks() {
        // ثبت شورت‌کد
        add_shortcode('sorenam_peyment_options', [$this, 'render_shortcode']);
        // بارگذاری اسکریپت و استایل
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * محتوای HTML شورت‌کد را رندر می‌کند.
     *
     * @return string خروجی HTML شورت‌کد.
     */
       public function render_shortcode() {
        if (!function_exists('WC') || !WC() || !WC()->session || !WC()->cart) {
            return '';
        }

        $cart = WC()->cart;
        $discount_amount = 0;
        $average_period_in_months = 0;

        if (!$cart->is_empty() && class_exists('Sorenam_Calculator')) {
            $calculator = new Sorenam_Calculator();
            $average_days = $calculator->calc_repayment_period($cart);
            $cart_total = $cart->get_cart_contents_total();
            $discount_amount = $calculator->get_cash_discount_amount($average_days, $cart_total);
            if ($average_days > 0) {
                $average_period_in_months = round($average_days / 30);
            }
        }

        $formatted_discount = wc_price(abs($discount_amount));
        $is_checked = (WC()->session->get('add_sorenam_peyment_options_fee') === 'yes');
        
         // محاسبه تاریخ سررسید چک به صورت کاملاً سازگار با پارسی دیت
        $due_timestamp = strtotime("+$average_period_in_months months");

        // ابتدا بررسی می‌کنیم که افزونه پارسی دیت فعال و تابعش موجود است
        if (function_exists('parsidate')) {
            // اگر فعال بود، تاریخ را مستقیماً با فرمت شمسی چاپ کن
            $cheque_due_date = parsidate('Y/m/d', $due_timestamp);
        } else {
            // اگر فعال نبود، از فرمت میلادی به عنوان پشتیبان استفاده کن
            $cheque_due_date = date('Y/m/d', $due_timestamp);
        }
        
        // دریافت URL تصویر چک از سشن (در صورت وجود)
        $cheque_image_url = WC()->session->get('sorenam_cheque_image_url', '');

        ob_start();
        ?>
        <div class="sorenam-peyment-options-shortcode-container">
            <div>
                <input type="radio" id="sorenam-button-option-1" name="sorenam_button_option" value="yes" <?php checked($is_checked); ?>>
                <label for="sorenam-button-option-1"><?php printf(esc_html__('نقدی با تخفیف %s', 'sorenam-peyment'), $formatted_discount); ?></label>
            </div>
            <div>
                <input type="radio" id="sorenam-button-option-2" name="sorenam_button_option" value="no" <?php checked(!$is_checked); ?>>
                <label for="sorenam-button-option-2"><?php printf(esc_html__('پرداخت اعتباری %d ماهه بدون تخفیف', 'sorenam-peyment'), $average_period_in_months); ?></label>
            </div>
            
            <div id="sorenam-cheque-section" style="margin-top: 1em; display: none;">
                <p><?php printf(esc_html__('چک خود را به تاریخ %s ثبت نموده و تصویر آن را ارسال نمایید.', 'sorenam-peyment'), '<strong>' . $cheque_due_date . '</strong>'); ?></p>
                
                <?php if ($cheque_image_url): ?>
                    <p><?php esc_html_e('تصویر چک با موفقیت آپلود شد:', 'sorenam-peyment'); ?></p>
                    <a href="<?php echo esc_url($cheque_image_url); ?>" target="_blank">
                        <img src="<?php echo esc_url($cheque_image_url); ?>" alt="Cheque Image" style="max-width: 150px; height: auto; border: 1px solid #ccc; padding: 5px;">
                    </a>
                <?php else: ?>
                    <label for="sorenam_cheque_image"><?php esc_html_e('تصویر چک تایید شده خود را با حجم کمتر از 1MB ارسال نمایید:', 'sorenam-peyment'); ?></label>
                    <input type="file" id="sorenam_cheque_image" name="sorenam_cheque_image" accept="image/*" style="width: 100%; margin-top: 0.5em;">
                    <button id="sorenam_upload_cheque_btn" class="button" style="margin-top: 0.5em;"><?php esc_html_e('آپلود تصویر چک', 'sorenam-peyment'); ?></button>
                    <span id="sorenam_upload_status" style="margin-left: 10px;"></span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * اسکریپت و استایل‌های مورد نیاز شورت‌کد را بارگذاری می‌کند.
     *
     * @return void
     */
    public function enqueue_assets() {
        // فقط در صفحات سبد خرید و تسویه حساب بارگذاری شود
        if (!is_cart() && !is_checkout()) {
            return;
        }

        // بارگذاری فایل جاوااسکریپت
        wp_enqueue_script(
            'sorenam-shortcode-script',
            SORENAM_PLUGIN_URL . 'assets/js/shortcode.js',
            array(),
            SORENAM_VERSION,
            true
        );

        // ارسال متغیرهای لازم به فایل جاوااسکریپت
        wp_localize_script(
            'sorenam-shortcode-script',
            'sorenam_peyment_options_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('sorenam_peyment_options_fee_nonce'),
            )
        );

        // بارگذاری فایل استایل
        wp_enqueue_style(
            'sorenam-shortcode-style',
            SORENAM_PLUGIN_URL . 'assets/css/sorenam-style.css',
            array(),
            SORENAM_VERSION
        );
    }
}