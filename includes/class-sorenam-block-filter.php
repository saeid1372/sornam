<?php
/**
 * Class Sorenam_Block_Filter
 *
 * برای فعال‌سازی شورت‌کد در بلوک‌های سبد خرید و تسویه حساب ووکامرس.
 */
class Sorenam_Block_Filter {

    /**
     * ثبت هوک‌های مورد نیاز.
     *
     * @return void
     */
    public function init_hooks() {
        // Hook into the admin scripts to add our inline script.
        add_action('admin_enqueue_scripts', [$this, 'add_inline_script']);
    }

    /**
     * افزودن اسکریپت جاوااسکریپت به صورت inline به صفحات ویرایشگر بلوک.
     *
     * @return void
     */
    public function add_inline_script() {
        $screen = get_current_screen();
        if (!$screen || !$screen->is_block_editor) {
            return;
        }

        // The JavaScript code to register the filters.
        $inline_script_code = <<<'JS'
            wp.domReady( function() {
                // --- For the Checkout block ---
                if ( window.wc && window.wc.blocksCheckout && window.wc.blocksCheckout.registerCheckoutFilters ) {
                    const { registerCheckoutFilters } = window.wc.blocksCheckout;
                    registerCheckoutFilters( "sorenam_allow_shortcode_in_checkout", {
                        additionalCartCheckoutInnerBlockTypes: ( value, extensions, args ) => {
                            return [ ...value, "core/shortcode" ];
                        }
                    });
                }

                // --- For the Cart block ---
                if ( window.wc && window.wc.blocksCart && window.wc.blocksCart.registerCartFilters ) {
                    const { registerCartFilters } = window.wc.blocksCart;
                    registerCartFilters( "sorenam_allow_shortcode_in_cart", {
                        additionalCartInnerBlockTypes: ( value, extensions, args ) => {
                            return [ ...value, "core/shortcode" ];
                        }
                    });
                }
            });
JS;

        // Add the inline script to both cart and checkout block editor assets.
        wp_add_inline_script('wc-blocks-checkout', $inline_script_code);
        wp_add_inline_script('wc-blocks-cart', $inline_script_code);
    }
}