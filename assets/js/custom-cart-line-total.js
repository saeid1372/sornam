(function($) {
    'use strict';

    window.sorenamRefreshCartDisplay = renderCustomFields;

    function renderCustomFields() {
        console.log('=== Sorenam Debug: renderCustomFields() ===');
        
        const phpData = window.sorenamCartData?.items || {};
        console.log('PHP Data:', phpData);

        if (!Object.keys(phpData).length) return;

        // Map بر اساس Product ID
        const itemsByProductId = {};
        for (let key in phpData) {
            const item = phpData[key];
            itemsByProductId[item.product_id] = item;
        }

        const rows = document.querySelectorAll('.wc-block-cart-items__row');
        console.log('Found rows:', rows.length);

        rows.forEach((row, index) => {
            const totalCell = row.querySelector('.wc-block-cart-item__total');
            if (!totalCell || totalCell.querySelector('.sorenam-fields-wrapper')) return;

            // ===== روش نهایی: یافتن Product ID از hidden input =====
            let productId = null;
            
            // بررسی تمام input های داخل ردیف
            const inputs = row.querySelectorAll('input');
            inputs.forEach(input => {
                if (input.name && input.name.includes('cart[')) {
                    // نام معمولاً: cart[cdcb2f5c7b071143529ef7f2705dfbc4][qty]
                    const match = input.name.match(/cart\[([^\]]+)\]/);
                    if (match) {
                        // حالا باید این کلید رو به ID تبدیل کنیم
                        // روش: از طریق مقایسه با داده‌های PHP
                        const cartKey = match[1];
                        if (phpData[cartKey]) {
                            productId = phpData[cartKey].product_id;
                        }
                    }
                }
            });

            // اگر از input پیدا نشد، از لینک محصول پیدا کن
            if (!productId) {
                const productNameLink = row.querySelector('.wc-block-components-product-name');
                if (productNameLink) {
                    const href = productNameLink.href;
                    // بررسی تمام ID های موجود در داده‌ها
                    for (let id in itemsByProductId) {
                        if (href.includes('product_id=' + id) || href.includes('p=' + id)) {
                            productId = id;
                            break;
                        }
                    }
                }
            }

            // بکاپ: از طریق یافتن ID در URL لینک محصول
            if (!productId && productNameLink) {
                const urlParts = productNameLink.href.split('/');
                // ID معمولاً آخرین بخش یا بخش قبل از آخرین
                for (let i = urlParts.length - 1; i >= 0; i--) {
                    const part = urlParts[i];
                    if (!isNaN(part) && part > 0) {
                        productId = part;
                        break;
                    }
                }
            }

            console.log(`Row ${index}: Product ID = ${productId}`);

            if (!productId) {
                console.log(`Row ${index}: Still no product ID`);
                return;
            }

            const itemData = itemsByProductId[productId];
            if (!itemData) {
                console.log(`Row ${index}: No data for product ${productId}`);
                return;
            }

            const installments = itemData.installments || '';
            const packageQty = itemData.package || '';

            if (!installments && !packageQty) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'sorenam-fields-wrapper';

            if (installments) {
                const div = document.createElement('div');
                div.className = 'sorenam-field instalments';
                div.innerHTML = `📅 اقساط: <strong>${installments}</strong>`;
                wrapper.appendChild(div);
            }

            if (packageQty) {
                const div = document.createElement('div');
                div.className = 'sorenam-field package-qty';
                div.innerHTML = `📦 بسته: <strong>${packageQty}</strong>`;
                wrapper.appendChild(div);
            }

            totalCell.appendChild(wrapper);
            console.log(`✅ Row ${index}: Success`);
        });
    }

    // شروع
    function init() {
        $(document).ready(() => setTimeout(renderCustomFields, 800));
        $(document).on('wc-blocks_cart_updated', () => setTimeout(renderCustomFields, 500));
        $(window).on('load', () => setTimeout(renderCustomFields, 1000));
    }

    init();

})(jQuery);