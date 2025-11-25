<?php
/**
 * Class Sorenam_Frontend_Product_Display
 *
 * نمایش فیلدهای سفارشی محصول از طریق شورت‌کد.
 */
class Sorenam_Frontend_Product_Display {

    /**
     * ثبت هوک‌های مورد نیاز.
     *
     * @return void
     */
    public function init_hooks() {
        // ثبت شورت‌کد برای نمایش اطلاعات محصول
        add_shortcode('sorenam_product_info', [$this, 'render_product_info_shortcode']);
    }

    /**
     * محتوای HTML شورت‌کد اطلاعات محصول را رندر می‌کند.
     *
     * @param array $atts ویژگی‌های شورت‌کد (در حال حاضر استفاده نمی‌شود).
     * @return string خروجی HTML شورت‌کد.
     */
    public function render_product_info_shortcode($atts) {
        global $product;

        // اگر شورت‌کد خارج از حلقه محصول استفاده شود، هیچ چیزی نمایش نده
        if (!$product) {
            return '';
        }

        $installments_number = (int) $product->get_meta('_installments_number', true);
        $package_quantity = (int) $product->get_meta('_package_quantity', true);

        $has_installment = $installments_number > 0;
        $has_package = $package_quantity > 0;

        // اگر هیچ‌کدام مقدار نداشتند، یک رشته خالی برگردان
        if (!$has_installment && !$has_package) {
            return '';
        }

        // شروع به تولید بافر خروجی
        ob_start();
        ?>
        <div class="sorenam-product-meta" style="margin-bottom: 1em; font-size: 0.9em; color: #555;">
            <?php if ($has_installment): ?>
                <p><strong><?php esc_html_e('بازپرداخت', 'sorenam-peyment'); ?></strong> <?php echo esc_html($installments_number . 'ماهه'); ?></p>
            <?php endif; ?>

            <?php if ($has_package): ?>
                <p><?php echo esc_html($package_quantity . 'عدد در بسته'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        // دریافت محتوای بافر و پاک‌سازی آن
        return ob_get_clean();
    }
}