<?php
declare(strict_types=1);

namespace Sorenam\Delivery;


/**
 * ماژول اصلی برای مدیریت عملکردهای مرتبط با تحویل سفارش.
 */
final class DeliveryModule
{
    public function __construct()
    {
        // ثبت هوک‌های مربوط به محاسبه هزینه ارسال
        (new DeliveryAdminPage())->register();
        (new DeliveryRestApi())->register();
        (new DeliveryShortcode())->register();
        // (new DeliveryAjax())->register();
        
    }
}