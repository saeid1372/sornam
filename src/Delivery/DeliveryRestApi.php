<?php
declare(strict_types=1);

namespace Sorenam\Delivery;

/**
 * مدیریت کامل Endpoints مربوط به REST API.
 */
final class DeliveryRestApi
{
    private const SCHEDULE_OPTION = 'sm_fsp_kerman_schedule';

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * ثبت روت‌های API.
     */
    public function registerRoutes(): void
    {
        register_rest_route('sm-fsp/v1', '/schedule', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getSchedule'],
                'permission_callback' => [$this, 'checkPermissions'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'saveSchedule'],
                'permission_callback' => [$this, 'checkPermissions'],
            ],
        ]);
    }

    /**
     * بررسی دسترسی کاربر.
     */
    public function checkPermissions(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * دریافت اطلاعات زمان‌بندی از دیتابیس.
     */
    public function getSchedule(): \WP_REST_Response
    {
        $data = get_option(self::SCHEDULE_OPTION, []);
        return rest_ensure_response($data);
    }

    /**
     * ذخیره اطلاعات زمان‌بندی در دیتابیس.
     */
    public function saveSchedule(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            return new \WP_Error('invalid_data', 'داده نامعتبر است', ['status' => 400]);
        }

        $clean = [];
        foreach ($params as $city => $day) {
            $clean[sanitize_text_field($city)] = sanitize_text_field($day);
        }

        update_option(self::SCHEDULE_OPTION, $clean);
        return rest_ensure_response(['success' => true]);
    }
}