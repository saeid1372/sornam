<?php

// شامل کردن کلاس AJAX ارسال رایگان
require_once SORENAM_PLUGIN_DIR . 'includes/class-sorenam-delivery-ajax.php';


/**
 * افزودن منو در بخش ووکامرس
 */
add_action( 'admin_menu', function() {
	add_submenu_page(
		'woocommerce',
		'برنامه ارسال رایگان',
		'برنامه ارسال رایگان',
		'manage_woocommerce',
		'wc-free-shipping-program',
		'wc_fsp_render_admin_page',
		50
	);
});

/**
 * صفحه ادمین (ری‌اکتیو)
 */
function wc_fsp_render_admin_page() {
	?>
	<div class="wrap">
		<h1>برنامه ارسال رایگان شهرستان‌های کرمان</h1>
		<div id="wc-fsp-admin-app"></div>
	</div>
	<?php
}

/**
 * بارگذاری JS صفحه ادمین
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
	if ( $hook !== 'woocommerce_page_wc-free-shipping-program' ) return;

    $script_path = SORENAM_PLUGIN_DIR . 'assets/js/admin-delivery-schedule.js';
    $script_url  = SORENAM_PLUGIN_URL . 'assets/js/admin-delivery-schedule.js';

	wp_enqueue_script(
		'wc-fsp-admin',
        $script_url,
        array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-data', 'jquery' ),
        filemtime( $script_path ),
        true
	);

	wp_enqueue_style( 'wp-components' );

	wp_localize_script( 'wc-fsp-admin', 'wcFspData', array(
		'nonce' => wp_create_nonce( 'wp_rest' ),
		'rest_url' => esc_url_raw( rest_url( 'wc-fsp/v1/schedule' ) ),
	));
});

/**
 * بارگذاری JS صفحه Cart
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! is_cart() ) return;

	wp_enqueue_script(
		'wc-fsp-cart',
		SORENAM_PLUGIN_URL . 'assets/js/cart-delivery-schedule.js',
		array( 'jquery' ),
		filemtime( SORENAM_PLUGIN_DIR . 'assets/js/cart-delivery-schedule.js' ),
		true
	);

	wp_localize_script( 'wc-fsp-cart', 'wcFspCart', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	));
});

/**
 * ایجاد REST API برای داده‌های ادمین
 */
add_action( 'rest_api_init', function() {
	register_rest_route( 'wc-fsp/v1', '/schedule', array(
		array(
			'methods'  => 'GET',
			'callback' => 'wc_fsp_get_schedule',
			'permission_callback' => function() {
				return current_user_can( 'manage_woocommerce' );
			},
		),
		array(
			'methods'  => 'POST',
			'callback' => 'wc_fsp_save_schedule',
			'permission_callback' => function() {
				return current_user_can( 'manage_woocommerce' );
			},
		),
	));
});

function wc_fsp_get_schedule() {
	$data = get_option( 'wc_fsp_kerman_schedule', array() );
	return rest_ensure_response( $data );
}

function wc_fsp_save_schedule( WP_REST_Request $request ) {
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) return new WP_Error( 'invalid_data', 'داده نامعتبر است', array( 'status' => 400 ) );

	$clean = array();
	foreach ( $params as $city => $day ) {
		$clean[ sanitize_text_field( $city ) ] = sanitize_text_field( $day );
	}

	update_option( 'wc_fsp_kerman_schedule', $clean );
	return rest_ensure_response( array( 'success' => true ) );
}


// ============================================================
// ✅ کدهای جدید: شرت‌کد نمایش پیام ارسال رایگان
// ============================================================

/**
 * شرت‌کد نمایش پیام ارسال رایگان بر اساس billing_city کاربر
 * استفاده: [free_shipping_day_message]
 */
add_shortcode('free_shipping_day_message', 'sorenam_fsp_shipping_message_shortcode');

function sorenam_fsp_shipping_message_shortcode($atts, $content = null) {
    // فقط برای کاربران لاگین‌شده
    if (!is_user_logged_in()) {
        return '';
    }

    // گرفتن billing_city کاربر
    $user_id = get_current_user_id();
    $billing_city = get_user_meta($user_id, 'billing_city', true);

    // لاگ برای دیباگ (در صورت فعال بودن WP_DEBUG)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Sorenam FSP] User ID: ' . $user_id . ' | Billing City: ' . $billing_city);
    }

    // بررسی خالی بودن شهر
    if (empty($billing_city)) {
        return '<div class="sorenam-fsp-message sorenam-fsp-error">لطفاً شهر خود را در پروفایل تکمیل کنید.</div>';
    }

    // خواندن جدول زمان‌بندی از دیتابیس
    $schedule = get_option('wc_fsp_kerman_schedule', []);

    // لاگ برای دیباگ
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Sorenam FSP] Schedule loaded: ' . print_r($schedule, true));
    }

    // بررسی وجود شهر در جدول برنامه‌ریزی
    if (!isset($schedule[$billing_city]) || empty($schedule[$billing_city])) {
        return '<div class="sorenam-fsp-message sorenam-fsp-error">برای شهر «' . esc_html($billing_city) . '» روز ارسال رایگان تعریف نشده است.</div>';
    }

    $day = esc_html($schedule[$billing_city]);

    // خروجی نهایی
    return sprintf(
        '<div class="sorenam-fsp-message sorenam-fsp-success">(%s) ارسال برای شما رایگان است</div>',
        $day
    );
}

/**
 * ✅ اضافه کردن استایل‌های سفارشی (روش تضمین‌شده - چاپ مستقیم در head)
 */
add_action('wp_head', function() {
    // فقط در صفحات cart و checkout
    if (!is_cart() && !is_checkout()) {
        return;
    }

    // لاگ برای دیباگ - بعد از تست می‌توانید این خط را حذف کنید
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Sorenam FSP CSS] CSS is being printed directly in head');
    }

    // چاپ مستقیم استایل در head
    ?>
    <style type="text/css" id="sorenam-fsp-inline-css">
        .sorenam-fsp-message {
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
        .sorenam-fsp-error {
            background-color: #f8d7da !important;
            border-color: #f5c6cb !important;
            color: #721c24 !important;
        }
    </style>
    <?php
}, 999); // اولویت بالا برای اجرای آخر

// ============================================================
// پایان کدهای جدید
// ============================================================