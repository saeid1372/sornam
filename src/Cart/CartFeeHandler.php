<?php
declare(strict_types=1);

namespace Sorenam\Cart;

use WC_Order;

/**
 * افزودن Fee در چک‌اوت و ذخیرهٔ تصویر چک در متای سفارش
 * HPOS-Ready | PHP 8.1
 */
final class CartFeeHandler
{
    public function register(): void
    {
        add_action('woocommerce_cart_calculate_fees', [$this, 'addConditionalFee']);
        add_action('woocommerce_checkout_create_order', [$this, 'saveChequeImageToOrderMeta'], 10, 2);
    }

    public function addConditionalFee(\WC_Cart $cart): void
    {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (!WC()->session) return;

        $flag = WC()->session->get('add_sorenam_peyment_options_fee');
        if ($flag !== 'yes') return;

        $calc   = new CartCalculator();
        $days   = $calc->calcRepaymentPeriod($cart);
        $total  = (float)$cart->get_cart_contents_total();
        $fee    = $calc->getCashDiscountAmount($days, $total);

        if ($fee !== 0.0) {
            $cart->add_fee(__('تخفیف پرداخت نقدی', 'sorenam'), $fee, false, '');
        }
    }

    public function saveChequeImageToOrderMeta(\WC_Order $order, array $data): void
    {
        if (!WC()->session) return;

        $url = WC()->session->get('sorenam_cheque_image_url');
        if ($url) {
            $order->add_meta_data('_sm_cheque_image_url', $url, true);
            WC()->session->__unset('sorenam_cheque_image_url');
        }
    }
}