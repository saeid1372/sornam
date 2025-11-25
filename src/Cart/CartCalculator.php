<?php
declare(strict_types=1);

namespace Sorenam\Cart;

use WC_Cart;

/**
 * محاسبهٔ تخفیف نقدی بر اساس میانگین دورهٔ بازپرداخت
 * HPOS-Ready | PHP 8.1
 */
final class CartCalculator
{
    /* ---------------------------------------------------------- */
    /*  محاسبهٔ میانگین روزهای بازپرداخت برای کل سبد                */
    /* ---------------------------------------------------------- */
    public function calcRepaymentPeriod(WC_Cart $cart): float
    {
        if ($cart->is_empty()) return 0.0;

        $totalPrice   = 0.0;
        $weightedSum  = 0.0;

        foreach ($cart->get_cart() as $item) {
            $product = $item['data'];
            if (!$product instanceof \WC_Product) continue;

            $lineTotal = (float) ($item['line_total'] + $item['line_tax']);
            $days      = (int) $product->get_meta('_sm_installments');

            $totalPrice  += $lineTotal;
            $weightedSum += $lineTotal * $days;
        }

        return $totalPrice > 0 ? ($weightedSum / $totalPrice) * 30 : 0.0;
    }

    /* ---------------------------------------------------------- */
    /*  محاسبهٔ مبلغ تخفیف (منفی)                                   */
    /* ---------------------------------------------------------- */
    public function getCashDiscountAmount(float $avgDays, float $cartTotal): float
    {
        if ($avgDays <= 20) return 0.0;

        $discountPercent = $avgDays * 0.1;
        $amount          = -round($cartTotal * $discountPercent / 100, wc_get_price_decimals());

        return $amount;
    }
}