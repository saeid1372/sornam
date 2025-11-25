<?php
declare(strict_types=1);

namespace Sorenam\Cart;

/**
 * شورت‌کد نمایش گزینه‌های پرداخت + Assets
 * HPOS-Ready | PHP 8.1
 */
final class CartShortcode
{
    public function register(): void
    {
        add_shortcode('sm_payment_options', [$this, 'renderShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function renderShortcode(array $atts = []): string
    {
        if (!is_cart() && !is_checkout()) return '';

        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) return '';

        $calc        = new CartCalculator();
        $avgDays     = $calc->calcRepaymentPeriod($cart);
        $cartTotal   = (float)$cart->get_cart_contents_total();
        $discount    = $calc->getCashDiscountAmount($avgDays, $cartTotal);
        $months      = $avgDays > 0 ? round($avgDays / 30) : 0;
        $dueDate     = $months > 0 ? date('Y/m/d', strtotime("+$months months")) : '';
        $isChecked   = WC()->session->get('add_sorenam_peyment_options_fee') === 'yes';
        $chequeUrl   = WC()->session->get('sorenam_cheque_image_url', '');

        ob_start();
        ?>
        <div class="sm-payment-options">
            <label>
                <input type="radio" name="sm_payment_option" value="yes" <?php checked($isChecked); ?>>
                <?= sprintf('نقدی با تخفیف %s', wc_price(abs($discount))) ?>
            </label>
            <label>
                <input type="radio" name="sm_payment_option" value="no" <?php checked(!$isChecked); ?>>
                <?= sprintf('پرداخت اعتباری %d ماهه بدون تخفیف', $months) ?>
            </label>

            <?php if ($months > 0): ?>
                <div class="sm-cheque-section" style="margin-top:1em;display:none;">
                    <p>چک خود را به تاریخ <strong><?= esc_html($dueDate) ?></strong> ثبت و تصویر آن را ارسال نمایید.</p>
                    <?php if ($chequeUrl): ?>
                        <p><img src="<?= esc_url($chequeUrl) ?>" style="max-width:150px;"></p>
                    <?php else: ?>
                        <input type="file" name="sm_cheque_image" accept="image/*">
                        <button type="button" class="button sm-upload-cheque"><?= esc_html('آپلود تصویر چک') ?></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueueAssets(): void
    {
        if (!is_cart() && !is_checkout()) return;

        wp_enqueue_script(
            'sm-cart-shortcode',
            SM_PLUGIN_URL . 'assets/js/cart/cart-shortcode.js',
            ['jquery'],
            SM_VERSION,
            true
        );

        wp_localize_script('sm-cart-shortcode', 'sm_cart_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sorenam_peyment_options_fee_nonce'),
        ]);

        wp_enqueue_style(
            'sm-cart-shortcode',
            SM_PLUGIN_URL . 'assets/css/cart-shortcode.css',
            [],
            SM_VERSION
        );
    }
}