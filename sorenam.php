<?php
/**
 * Plugin Name:       Sorenam
 * Plugin URI:        https://example.com/
 * Description:       برای سورنا موتور - افزونه پرداخت نقدی با تخفیف و اعتباری بدون تخفیف
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            saeid
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sorenam-peyment
 * WC requires at least: 8.0
 * WC tested up to: 10.3.4
 * Requires Plugins: woocommerce
 * WooCommerce HPOS: yes
 */


if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌های پلاگین
define('SORENAM_VERSION', '1.0.0');
define('SORENAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SORENAM_PLUGIN_URL', plugin_dir_url(__FILE__));

// بارگذاری ماژول برنامه ارسال رایگان
require_once SORENAM_PLUGIN_DIR . 'includes/sorenam-delivery-schedule.php';

/**
 * تابع فعال‌سازی پلاگین برای بررسی وابستگی‌ها
 */
function sorenam_activate() {
    // بررسی نصب بودن ووکامرس
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('این پلاگین به ووکامرس نیاز دارد. لطفاً ابتدا ووکامرس را نصب و فعال کنید.', 'sorenam-peyment'));
    }

    // بررسی نسخه PHP
    if (version_compare(PHP_VERSION, '8.1', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('این پلاگین به PHP نسخه 8.1 یا بالاتر نیاز دارد.', 'sorenam-peyment'));
    }
}
register_activation_hook(__FILE__, 'sorenam_activate');

/**
 * بارگذاری تمام کلاس‌های پلاگین از پوشه includes
 */
function sorenam_autoload_classes() {
    $class_files = glob(SORENAM_PLUGIN_DIR . 'includes/class-*.php');
    if ($class_files) {
        foreach ($class_files as $file) {
            require_once $file;
        }
    }
}
sorenam_autoload_classes();

/**
 * تابع اصلی برای راه‌اندازی کلاس‌های پلاگین
 */
function sorenam_plugin_initializer() {
    // اطمینان از اینکه ووکامرس فعال است
    if (!class_exists('WooCommerce')) {
        return;
    }

    // نمونه‌سازی و راه‌اندازی کلاس‌ها
    if (class_exists('Sorenam_Block_Filter')) {
        $block_filter = new Sorenam_Block_Filter();
        $block_filter->init_hooks();
    }

    if (class_exists('Sorenam_Admin_Product_Handler')) {
        $admin_handler = new Sorenam_Admin_Product_Handler();
        $admin_handler->init_hooks();
    }

    
      if (class_exists('Sorenam_Shortcode')) {
        $shortcode_handler = new Sorenam_Shortcode();
        $shortcode_handler->init_hooks();
    }

    if (class_exists('Sorenam_AJAX')) {
        $ajax_handler = new Sorenam_AJAX();
        $ajax_handler->init_hooks();
    }

    if (class_exists('Sorenam_Cart_Fee_Handler')) {
        $cart_fee_handler = new Sorenam_Cart_Fee_Handler();
        $cart_fee_handler->init_hooks();
    }

	// فراخوانی کلاس جدید برای نمایش در صفحه محصول
    if (class_exists('Sorenam_Frontend_Product_Display')) {
        $frontend_display = new Sorenam_Frontend_Product_Display();
        $frontend_display->init_hooks();
    }

    // نمونه‌سازی و راه‌اندازی کلاس جدید برای برنامه ارسال رایگان
    if (class_exists('Sorenam_Delivery_AJAX')) {
        $delivery_ajax = new Sorenam_Delivery_AJAX();
        $delivery_ajax->init_hooks();
    }

    // نمونه‌سازی و راه‌اندازی کلاس جدید برای نمایش تعداد اقساط هر محصول در صفحه checkout
    if (class_exists('Sorenam_Checkout_Display')) {
        $checkout_display = new Sorenam_Checkout_Display();
        $checkout_display->init_hooks();        
    }


	// افزودن عبارت تستی به بلوک کارت
	if (class_exists('Sorenam_Cart_Customizer')) {
	    new Sorenam_Cart_Customizer(); // فقط instantiate - هوک‌ها خودکار اجرا می‌شوند
	}

}
// هوک برای اجرای راه‌انداز پس از بارگذاری تمام پلاگین‌ها
add_action('plugins_loaded', 'sorenam_plugin_initializer');