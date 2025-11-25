<?php
declare(strict_types=1);

namespace Sorenam\Cart;

/**
 * فعال‌سازی شورت‌کد در بلوک‌های سبد خرید و تسویه حساب ووکامرس.
 */
final class BlockEditor
{
    public function register(): void
    {
        // هوک برای افزودن اسکریپت به ویرایشگر بلوک
        add_action('admin_enqueue_scripts', [$this, 'addInlineScript']);
    }

    /**
     * افزودن اسکریپت جاوااسکریپت به صورت inline برای فعال‌سازی بلوک شرت‌کد.
     *
     * @return void
     */
    public function addInlineScript(): void
    {
        $screen = get_current_screen();
        // فقط در ویرایشگر بلوک اجرا شود
        if (!$screen || !$screen->is_block_editor) {
            return;
        }

        // کد جاوااسکریپت اصلاح‌شده برای ثبت فیلترها
        // **تغییر کلیدی: امضای توابع به حالت اصلی و صحیح بازگردانده شد**
        $inline_script_code = <<<'JS'
            wp.domReady( function() {
                // --- برای بلوک تسویه حساب (Checkout Block) ---
                if ( window.wc && window.wc.blocksCheckout && window.wc.blocksCheckout.registerCheckoutFilters ) {
                    const { registerCheckoutFilters } = window.wc.blocksCheckout;
                    registerCheckoutFilters( "sm_allow_shortcode_in_checkout", {
                        additionalCartCheckoutInnerBlockTypes: ( value, extensions, args ) => {
                            return [ ...value, "core/shortcode" ];
                        }
                    });
                }

                // --- برای بلوک سبد خرید (Cart Block) ---
                if ( window.wc && window.wc.blocksCart && window.wc.blocksCart.registerCartFilters ) {
                    const { registerCartFilters } = window.wc.blocksCart;
                    registerCartFilters( "sm_allow_shortcode_in_cart", {
                        additionalCartInnerBlockTypes: ( value, extensions, args ) => {
                            return [ ...value, "core/shortcode" ];
                        }
                    });
                }
            });
JS;

        // افزودن اسکریپت به هندل‌های مربوط به بلوک‌های کارت و چک‌اوت
        wp_add_inline_script('wc-blocks-checkout', $inline_script_code);
        wp_add_inline_script('wc-blocks-cart', $inline_script_code);
    }
}