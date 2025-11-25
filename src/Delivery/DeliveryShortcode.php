<?php
declare(strict_types=1);

namespace Sorenam\Delivery;

/**
 * مدیریت شرت‌کد و دارایی‌های بخش کاربری.
 */
final class DeliveryShortcode
{
    private const SCHEDULE_OPTION = 'sm_fsp_kerman_schedule';

    public function register(): void
    {
       
        add_action('wp_head', [$this, 'printInlineStyles'], 999);
        add_shortcode('sm_free_shipping_day_message', [$this, 'renderShortcode']);
    }

    

    /**
     * چاپ استایل‌های سفارشی در head.
     */
    public function printInlineStyles(): void
    {
        if (!is_cart() && !is_checkout()) {
            return;
        }
        ?>
        <style type="text/css" id="sm-fsp-inline-css">
            .sm-fsp-message {
                display: block !important;
                background-color: #d4edda !important;
                padding: 12px 15px !important;
                margin: 10px 0 !important;
                border-radius: 4px !important;
                border: 1px solid #c3e6cb !important;
                font-size: 34px !important;
                color: #155724 !important;
                font-weight: bold !important;
                text-align: center !important;
            }
            .sm-fsp-error {
                background-color: #f8d7da !important;
                border-color: #f5c6cb !important;
                color: #721c24 !important;
            }
        </style>
        <?php
    }

    /**
     * رندر کردن شرت‌کد نمایش پیام ارسال رایگان.
     */
    public function renderShortcode(): string
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $user_id = get_current_user_id();
        $billing_city = get_user_meta($user_id, 'billing_city', true);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[SM FSP] User ID: ' . $user_id . ' | Billing City: ' . $billing_city);
        }

        if (empty($billing_city)) {
            return '<div class="sm-fsp-message sm-fsp-error">لطفاً شهر خود را در پروفایل تکمیل کنید.</div>';
        }

        $schedule = get_option(self::SCHEDULE_OPTION, []);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[SM FSP] Schedule loaded: ' . print_r($schedule, true));
        }

        if (!isset($schedule[$billing_city]) || empty($schedule[$billing_city])) {
            return '<div class="sm-fsp-message sm-fsp-error">برای شهر «' . esc_html($billing_city) . '» روز ارسال رایگان تعریف نشده است.</div>';
        }

        $day = esc_html($schedule[$billing_city]);

        return sprintf(
            '<div class="sm-fsp-message sm-fsp-success">(%s) ارسال برای شما رایگان است</div>',
            $day
        );
    }
}