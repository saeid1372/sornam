<?php
declare(strict_types=1);

namespace Sorenam\Cart;

/**
 * تمام endpoint‌های AJAX مربوط به Cart
 * HPOS-Ready | PHP 8.1
 */
final class CartAjax
{
    public function register(): void
    {
        add_action('wp_ajax_update_sorenam_peyment_options_fee', [$this, 'handleFeeUpdate']);
        add_action('wp_ajax_nopriv_update_sorenam_peyment_options_fee', [$this, 'handleFeeUpdate']);

        add_action('wp_ajax_sorenam_upload_cheque_image', [$this, 'handleChequeUpload']);
        add_action('wp_ajax_nopriv_sorenam_upload_cheque_image', [$this, 'handleChequeUpload']);
    }

    /* ---------------------------------------------------------- */
    /*  به‌روزرسانی وضعیت پرداخت در سشن                          */
    /* ---------------------------------------------------------- */
    public function handleFeeUpdate(): void
    {
        check_ajax_referer('sorenam_peyment_options_fee_nonce', 'nonce');

        $status = sanitize_text_field($_POST['fee_status'] ?? 'no');
        if (WC()->session) {
            WC()->session->set('add_sorenam_peyment_options_fee', $status);
        }
        wp_send_json_success(['status' => $status]);
    }

    /* ---------------------------------------------------------- */
    /*  آپلود تصویر چک                                            */
    /* ---------------------------------------------------------- */
    public function handleChequeUpload(): void
    {
        check_ajax_referer('sorenam_peyment_options_fee_nonce', 'nonce');

        if (!isset($_FILES['sorenam_cheque_image']) || $_FILES['sorenam_cheque_image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('خطا در آپلود فایل.');
        }

        $file = $_FILES['sorenam_cheque_image'];
        if ($file['size'] > 1024 * 1024) {
            wp_send_json_error('حجم فایل نباید بیشتر از ۱ مگابایت باشد.');
        }

        $mimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $mimes, true)) {
            wp_send_json_error('فقط تصاویر JPG، PNG و GIF مجاز هستند.');
        }

        $user   = wp_get_current_user();
        $login  = $user->exists() ? $user->user_login : 'guest_' . session_id();
        $upload = wp_upload_dir();
        $dir    = $upload['basedir'] . '/sm-cheques/' . $login;

        wp_mkdir_p($dir);
        $filename = wp_unique_filename($dir, sanitize_file_name($file['name']));
        $target   = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            wp_send_json_error('خطا در ذخیرهٔ فایل.');
        }

        $url = $upload['baseurl'] . '/sm-cheques/' . $login . '/' . $filename;
        if (WC()->session) {
            WC()->session->set('sorenam_cheque_image_url', $url);
        }

        wp_send_json_success(['file_url' => $url]);
    }
}