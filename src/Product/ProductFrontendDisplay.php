<?php
declare(strict_types=1);

namespace Sorenam\Product;

/**
 * نمایش فیلدهای سفارشی محصول در frontend از طریق شورت‌کد
 * HPOS-Ready | PHP 8.1
 */
final class ProductFrontendDisplay
{
    public function register(): void
    {
        add_shortcode('sm_product_info', [$this, 'renderShortcode']);
    }

    public function renderShortcode(array $atts = []): string
    {
        $product = wc_get_product(get_the_ID());
        if (!$product instanceof \WC_Product) return '';

        $inst = (int) $product->get_meta('_sm_installments');
        $pkg  = (int) $product->get_meta('_sm_package_qty');

        if ($inst === 0 && $pkg === 0) return '';

        ob_start();
        ?>
        <div class="sm-product-meta">
            <?php if ($inst > 0): ?>
                <p><strong><?php esc_html_e('بازپرداخت', 'sorenam'); ?></strong>
                    <?php echo esc_html($inst . ' ماهه'); ?></p>
            <?php endif; ?>

            <?php if ($pkg > 0): ?>
                <p><?php echo esc_html($pkg . ' عدد در بسته'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}