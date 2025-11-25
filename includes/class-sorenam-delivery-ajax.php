<?php
// جلوگیری از دسترسی مستقیم به فایل
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AJAX دریافت روز ارسال بر اساس شهر کاربر
 */
class Sorenam_Delivery_AJAX {

    /**
     * ثبت هوک‌های AJAX
     */
    public function init_hooks() {

        add_action( 'wp_ajax_wc_fsp_get_delivery_day', [ $this, 'get_delivery_day' ] );
        add_action( 'wp_ajax_nopriv_wc_fsp_get_delivery_day', [ $this, 'get_delivery_day' ] );
    }

    /**
     * پردازش درخواست دریافت روز ارسال
     */
    public function get_delivery_day() {

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'کاربر وارد نشده است' );
        }

        $user_id = get_current_user_id();
        $billing_city = get_user_meta( $user_id, 'billing_city', true );

        if ( empty( $billing_city ) ) {
            wp_send_json_error( 'شهر وارد نشده است' );
        }

        $schedule = get_option( 'wc_fsp_kerman_schedule', [] );

        if ( isset( $schedule[$billing_city] ) && ! empty( $schedule[$billing_city] ) ) {

            wp_send_json_success([
                'city' => $billing_city,
                'day'  => $schedule[$billing_city],
            ]);

        } else {
            wp_send_json_error( 'روز ارسال برای این شهر تعریف نشده است' );
        }
    }
}
