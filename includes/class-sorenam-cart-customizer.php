<?php
/**
 * Sorenam Cart Customizer - نمایش مستقیم فیلدهای سفارشی
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sorenam_Cart_Customizer {

    public function __construct() {
        // روش اصلی: JavaScript Inline (تضمینی)
        add_action('wp_footer', array($this, 'inject_custom_fields_js'));
    }

    /**
     * تزریق فیلدها به DOM با JavaScript
     */
    public function inject_custom_fields_js() {
        if (!is_cart() || !WC()->cart) {
            return;
        }

        // جمع‌آوری داده‌های مورد نیاز
        $items_data = array();
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'] ?? $cart_item['data']->get_id();
            $installments = get_post_meta($product_id, '_installments_number', true);
            $package_qty = get_post_meta($product_id, '_package_quantity', true);
            
            // فقط آیتم‌هایی که فیلد دارند رو اضافه کن
            if ($installments || $package_qty) {
                $items_data[] = array(
                    'product_id' => $product_id,
                    'installments' => $installments,
                    'package' => $package_qty
                );
            }
        }

        // اگر داده‌ای نداریم، کاری نکن
        if (empty($items_data)) {
            return;
        }

        ?>
        <script>
        (function($) {
            $(document).ready(function() {
                const sorenamData = <?php echo json_encode($items_data); ?>;
                
                function renderFields() {
                    // پیدا کردن تمام ردیف‌های سبد خرید
                    $('.wc-block-cart-items__row').each(function() {
                        const $row = $(this);
                        const $totalCell = $row.find('.wc-block-cart-item__total');
                        
                        // جلوگیری از اضافه شدن چندباره
                        if ($totalCell.find('.sorenam-fields-wrapper').length > 0) {
                            return;
                        }

                        // یافتن product ID از لینک محصول
                        const productLink = $row.find('.wc-block-components-product-name').attr('href');
                        if (!productLink) return;

                        let currentProductId = null;
                        
                        // جستجوی ID در داده‌ها
                        for (let item of sorenamData) {
                            // بررسی اینکه آیا URL حاوی ID هست یا نه
                            if (productLink.includes('product/' + item.product_id) || 
                                productLink.includes('?p=' + item.product_id) ||
                                productLink.includes('&p=' + item.product_id)) {
                                currentProductId = item.product_id;
                                break;
                            }
                        }

                        if (!currentProductId) return;

                        // یافتن داده مربوط به این محصول
                        const productData = sorenamData.find(item => item.product_id == currentProductId);
                        if (!productData) return;

                        // ساخت HTML
                        let html = '<div class="sorenam-fields-wrapper">';
                        
                        if (productData.installments) {
                            html += '<div class="sorenam-field">📅 اقساط: <strong>' + productData.installments + '</strong></div>';
                        }
                        
                        if (productData.package) {
                            html += '<div class="sorenam-field">📦 بسته: <strong>' + productData.package + '</strong></div>';
                        }
                        
                        html += '</div>';

                        // اضافه کردن به سلول
                        $totalCell.append(html);
                    });
                }

                // اجرای اولیه با تأخیر
                setTimeout(renderFields, 500);

                // شنود تغییرات سبد
                $(document).on('wc-blocks_cart_updated', function() {
                    setTimeout(renderFields, 300);
                });
            });
        })(jQuery);
        </script>
        
        <style>
        .sorenam-fields-wrapper {
            margin-top: 6px;
            font-size: 0.8em;
            color: var(--wp--preset--color--secondary, #666);
            line-height: 1.4;
        }
        .sorenam-field {
            display: block;
            margin: 3px 0;
        }
        .sorenam-field strong {
            color: var(--wp--preset--color--foreground, #333);
        }
        </style>
        <?php
    }
}