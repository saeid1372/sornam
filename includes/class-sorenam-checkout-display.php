<?php

/**
 * کلاس برای نمایش اطلاعات سفارشی در بلوک سبد خرید ووکامرس
 */
class WC_Custom_Cart_Block_Display {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        // 1. هوک برای افزودن داده‌های سفارشی به پاسخ API سبد خرید
        add_filter( 'woocommerce_store_api_cart_item', [ $this, 'add_custom_data_to_cart_item' ], 10, 2 );

        // 2. هوک برای افزودن اسکریپت جاوااسکریپت به صفحات مورد نیاز
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_custom_display_script' ] );
    }

    /**
     * داده‌های سفارشی (تعداد اقساط و جمع کل) را به آیتم سبد خرید در Store API اضافه می‌کند.
     *
     * @param array $cart_item_data آرایه داده‌ای که به API ارسال می‌شود.
     * @param array $cart_item آیتم سبد خرید اصلی ووکامرس.
     * @return array آرایه داده‌ی اصلاح شده.
     */
    public function add_custom_data_to_cart_item( $cart_item_data, $cart_item ) {
        // دریافت تعداد اقساط از متای محصول
        $installments_number = $cart_item['data']->get_meta( '_installments_number', true );

        // دریافت جمع کل ردیف (این مقدار معمولاً از قبل وجود دارد، اما ما آن را با فرمت‌بندی مجدد اضافه می‌کنیم)
        $line_total = $cart_item['line_total'];

        // اگر تعداد اقساط وجود داشت، آن را به داده‌ها اضافه کن
        if ( ! empty( $installments_number ) ) {
            $cart_item_data['custom_installments_number'] = (int) $installments_number;
        }

        // جمع کل را نیز با یک کلید سفارشی اضافه می‌کنیم تا در جاوااسکریپت به راحتی قابل استفاده باشد
        $cart_item_data['custom_line_total_formatted'] = wc_price( $line_total );

        return $cart_item_data;
    }

    /**
     * اسکریپت جاوااسکریپت مورد نیاز برای نمایش داده‌ها را به صفحه اضافه می‌کند.
     */
    public function enqueue_custom_display_script() {
        // فقط در صفحاتی که بلوک سبد خرید وجود دارد، اسکریپت را بارگذاری کن
        if ( has_block( 'woocommerce/cart' ) || is_cart() ) {
            $script = '
                document.addEventListener("DOMContentLoaded", function() {
                    const displayCustomData = () => {
                        // انتخاب تمام ردیف‌های آیتم در سبد خرید
                        const cartItems = document.querySelectorAll(".wc-block-cart-item");

                        cartItems.forEach(item => {
                            // پیدا کردن المان قیمت در هر ردیف
                            const priceElement = item.querySelector(".wc-block-cart-item__price");
                            if (!priceElement) return;

                            // خواندن داده‌های سفارشی از data-attributes که توسط بلوک رندر شده‌اند
                            const installments = item.dataset.customInstallmentsNumber;
                            const lineTotal = item.dataset.customLineTotalFormatted;

                            // اگر قبلاً محتوای سفارشی اضافه نشده باشد
                            if (priceElement.parentNode.querySelector(".custom-cart-item-meta")) {
                                return; // از افزودن مجدد جلوگیری کن
                            }

                            let customHtml = "";
                            const wrapper = document.createElement("div");
                            wrapper.style.fontSize = "0.9em";
                            wrapper.style.color = "#666";
                            wrapper.style.marginTop = "5px";
                            wrapper.className = "custom-cart-item-meta";

                            if (installments) {
                                customHtml += `<div style="margin-bottom: 3px;">تعداد اقساط: <strong>${installments}</strong></div>`;
                            }
                            
                            // نمایش جمع کل ردیف (این مورد ممکن است تکراری باشد، اما طبق درخواست شما اضافه شد)
                            if (lineTotal) {
                                customHtml += `<div>جمع کل این ردیف: <strong>${lineTotal}</strong></div>`;
                            }
                            
                            wrapper.innerHTML = customHtml;

                            // افزودن محتوای سفارشی بعد از المان قیمت
                            priceElement.parentNode.insertBefore(wrapper, priceElement.nextSibling);
                        });
                    };

                    // اجرای اولیه
                    displayCustomData();

                    // مشاهده تغییرات در بلوک سبد خرید برای زمانی که کاربر مثلاً تعداد محصول را تغییر می‌دهد
                    const cartBlock = document.querySelector(".wc-block-cart");
                    if (cartBlock) {
                        const observer = new MutationObserver(displayCustomData);
                        observer.observe(cartBlock, { childList: true, subtree: true });
                    }
                });
            ';

            // افزودن اسکریپت به صورت inline و وابسته به اسکریپت اصلی بلوک‌های ووکامرس
            wp_add_inline_script( 'wc-blocks-cart', $script );
        }
    }
}

// نمونه‌سازی از کلاس برای فعال شدن هوک‌ها
new WC_Custom_Cart_Block_Display();
