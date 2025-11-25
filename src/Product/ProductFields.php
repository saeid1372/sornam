<?php
declare(strict_types=1);

namespace Sorenam\Product;

use WC_Product;

/**
 * فیلدهای سفارشی صفحهٔ ویرایش محصول
 * HPOS-Ready | PHP 8.1
 */
final class ProductFields
{
    /** کلیدها و لیبل‌ها */
    private const INSTALLMENTS = '_sm_installments';
    private const PACKAGE_QTY  = '_sm_package_qty';

    private array $fields = [
        self::INSTALLMENTS => [
            'label'       => 'تعداد اقساط',
            'placeholder' => '0',
            'desc'        => 'اگر صفر باشد، محصول نقدی محسوب می‌شود.',
        ],
        self::PACKAGE_QTY  => [
            'label'       => 'تعداد در بسته',
            'placeholder' => '1',
            'desc'        => 'تعداد کالا در هر بسته.',
        ],
    ];

    public function register(): void
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'render']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save']);
    }

    public function render(): void
    {
        $product = wc_get_product(get_the_ID());
        foreach ($this->fields as $key => $data) {
            $value = (int) $product->get_meta($key);
            woocommerce_wp_text_input([
                'id'                => $key,
                'label'             => $data['label'],
                'placeholder'       => $data['placeholder'],
                'desc_tip'          => true,
                'description'       => $data['desc'],
                'type'              => 'number',
                'custom_attributes' => ['min' => 0, 'step' => 1],
                'value'             => max(0, $value),
            ]);
        }
    }

    public function save(WC_Product $product): void
    {
        foreach ($this->fields as $key => $data) {
            $value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_NUMBER_INT);
            $product->update_meta_data($key, max(0, (int) $value));
        }
    }
}