<?php
declare(strict_types=1);

namespace Sorenam\Delivery;

/**
 * مدیریت کامل بخش ادمین ماژول تحویل.
 */
final class DeliveryAdminPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * افزودن منو در بخش ووکامرس.
     */
    public function addAdminMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            'برنامه ارسال رایگان',
            'برنامه ارسال رایگان',
            'manage_woocommerce',
            'sm-free-shipping-program',
            [$this, 'renderAdminPage'],
            50
        );
    }

    /**
     * رندر کردن صفحه HTML ادمین.
     */
    public function renderAdminPage(): void
    {
        ?>
        <div class="wrap">
            <h1>برنامه ارسال رایگان شهرستان‌های کرمان</h1>
            <div id="sm-fsp-admin-app"></div>
        </div>
        <?php
    }

    /**
     * بارگذاری JS و CSS صفحه ادمین.
     */
    public function enqueueAdminAssets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_sm-free-shipping-program') {
            return;
        }

        $script_path = SM_PLUGIN_DIR . 'assets/js/admin-delivery-schedule.js';
        $script_url  = SM_PLUGIN_URL . 'assets/js/admin-delivery-schedule.js';

        wp_enqueue_script(
            'sm-fsp-admin',
            $script_url,
            ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-data', 'jquery'],
            SM_VERSION,
            true
        );

        wp_enqueue_style('wp-components');

        wp_localize_script('sm-fsp-admin', 'smFspData', [
            'nonce'    => wp_create_nonce('wp_rest'),
            'rest_url' => esc_url_raw(rest_url('sm-fsp/v1/schedule')),
        ]);
    }
}