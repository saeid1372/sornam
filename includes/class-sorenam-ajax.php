<?php
/**
 * Class Sorenam_AJAX
 *
 * مدیریت درخواست‌های AJAX مربوط به به‌روزرسانی وضعیت پرداخت در سشن.
 */
class Sorenam_AJAX {

    /**
     * ثبت هوک‌های مورد نیاز برای AJAX.
     *
     * @return void
     */
    public function init_hooks() {
        // ثبت اکشن برای کاربران لاگین کرده
        add_action('wp_ajax_update_sorenam_peyment_options_fee', [$this, 'handle_fee_update']);
        // ثبت اکشن برای کاربران مهمان
        add_action('wp_ajax_nopriv_update_sorenam_peyment_options_fee', [$this, 'handle_fee_update']);
		
		// اکشن جدید برای آپلود تصویر چک
    add_action('wp_ajax_sorenam_upload_cheque_image', [$this, 'handle_cheque_upload']);
    add_action('wp_ajax_nopriv_sorenam_upload_cheque_image', [$this, 'handle_cheque_upload']);

    }

	
	/**
 * درخواست AJAX برای آپلود تصویر چک را پردازش می‌کند.
 *
 * @return void
 */
public function handle_cheque_upload() {
    // ۱. بررسی امنیتی nonce
    if (!check_ajax_referer('sorenam_peyment_options_fee_nonce', 'nonce', false)) {
        wp_send_json_error('امنیت nonce معتبر نیست.');
        wp_die();
    }

    // ۲. بررسی وجود فایل
    if (!isset($_FILES['sorenam_cheque_image']) || $_FILES['sorenam_cheque_image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('خطا در آپلود فایل. لطفاً مجددا تلاش کنید.');
        wp_die();
    }

    $file = $_FILES['sorenam_cheque_image'];

    // ۳. اعتبارسنجی حجم فایل (کمتر از 1 مگابایت)
    if ($file['size'] > 1024 * 1024) {
        wp_send_json_error('حجم فایل نباید بیشتر از 1 مگابایت باشد.');
        wp_die();
    }

    // ۴. اعتبارسنجی نوع فایل (فقط تصاویر)
    $mimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $mimes)) {
        wp_send_json_error('فقط فرمت‌های JPG, PNG و GIF مجاز هستند.');
        wp_die();
    }

    // ۵. آماده‌سازی نام و مسیر ذخیره‌سازی
    $current_user = wp_get_current_user();
    $username = sanitize_user($current_user->user_login);
    if (empty($username)) { // برای کاربران مهمان
        $username = 'guest_' . session_id();
    }

    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/sorenam-cheques/' . $username;

    // ایجاد پوشه کاربر در صورت عدم وجود
    if (!wp_mkdir_p($target_dir)) {
        wp_send_json_error('خطا در ایجاد پوشه ذخیره‌سازی.');
        wp_die();
    }

    // ایجاد یک نام منحصر به فرد برای فایل
    $filename = $file['name'];
    $filename = sanitize_file_name($filename);
    $target_path = $target_dir . '/' . wp_unique_filename($target_dir, $filename);

    // ۶. جابجایی فایل از مسیر موقت به مسیر نهایی
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        wp_send_json_error('خطا در ذخیره فایل.');
        wp_die();
    }

    // ۷. ساخت URL فایل برای ذخیره در سشن
    $file_url = $upload_dir['baseurl'] . '/sorenam-cheques/' . $username . '/' . basename($target_path);

    // ۸. ذخیره URL فایل در سشن ووکامرس
    WC()->session->set('sorenam_cheque_image_url', $file_url);

    // ۹. ارسال پاسخ موفقیت‌آمیز به همراه URL فایل
    wp_send_json_success(['message' => 'تصویر با موفقیت آپلود شد.', 'file_url' => $file_url]);
    wp_die();
}
	
    /**
     * درخواست AJAX برای به‌روزرسانی سشن را پردازش می‌کند.
     *
     * @return void
     */
    public function handle_fee_update() {
        // ۱. بررسی امنیتی nonce
        if (!check_ajax_referer('sorenam_peyment_options_fee_nonce', 'nonce', false)) {
            wp_send_json_error('امنیت nonce معتبر نیست.');
            wp_die();
        }

        // ۲. دریافت و پاک‌سازی وضعیت ارسالی از جاوااسکریپت
        $fee_status = isset($_POST['fee_status']) ? sanitize_text_field($_POST['fee_status']) : 'no';

        // ۳. به‌روزرسانی سشن ووکامرس
        // اطمینان از اینکه WC()->session موجود است
        if (WC()->session) {
            WC()->session->set('add_sorenam_peyment_options_fee', $fee_status);
        }

        // ۴. ارسال پاسخ موفقیت‌آمیز
        wp_send_json_success(['status' => $fee_status]);

        // ۵. خاتمه اجرا
        wp_die();
    }
}