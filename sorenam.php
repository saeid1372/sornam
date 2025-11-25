<?php
/**
 * Plugin Name: Sorenam
 * Plugin URI:  https://github.com/saeid1372/sornam
 * Description: افزونه سفارشی برای مدیریت زمان‌بندی ارسال، هزینه‌ها و نمایش محصولات
 * Version:     2.0.0
 * Author:      Saeid
 * License:     GPL v2 or later
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 * WC tested up to: 10.3
 * HPOS compatible: yes
 */

declare(strict_types=1);

namespace Sorenam;

defined('ABSPATH') || exit;
// ثابت‌های سراسری
defined('SM_VERSION')    || define('SM_VERSION', '2.0.0');
defined('SM_PLUGIN_URL') || define('SM_PLUGIN_URL', plugin_dir_url(__FILE__));
defined('SM_PLUGIN_DIR') || define('SM_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once __DIR__ . '/vendor/autoload.php';



add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

\Sorenam\SM_Bootstrap::getInstance();