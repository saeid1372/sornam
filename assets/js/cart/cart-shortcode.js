/* assets/js/cart-shortcode.js */
(() => {
    'use strict';

    /**
     * اتصال رویدادها به شورت‌کد sm_payment_options
     */
    function attachListeners() {
        const container = document.querySelector('.sm-payment-options');
        if (!container) return;

        const radios   = container.querySelectorAll('input[type="radio"]');
        const uploadBtn = container.querySelector('.sm-upload-cheque');
        const fileInput = container.querySelector('input[type="file"]');
        const status    = container.querySelector('.sm-upload-status');

        /* نمایش/مخفی کردن بخش آپلود */
        function toggleSection() {
            const section = container.querySelector('.sm-cheque-section');
            if (!section) return;
            const isCredit = container.querySelector('input[name="sm_payment_option"]:checked')?.value === 'no';
            section.style.display = isCredit ? 'block' : 'none';
        }

        /* ارسال وضعیت به سرور */
        function sendStatus(value) {
            fetch(sm_cart_params.ajax_url, {
                method : 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body   : new URLSearchParams({
                    action : 'update_sorenam_peyment_options_fee',
                    nonce  : sm_cart_params.nonce,
                    fee_status: value
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    /* WooCommerce Blocks */
                    if (window.wc?.blocks?.CartStore) {
                        window.wc.blocks.CartStore.invalidateResolutionForStore();
                    } else {
                        location.reload(); // fallback
                    }
                } else {
                    alert(res.data || 'خطا در به‌روز‌رسانی.');
                }
            })
            .catch(err => console.error('SM Cart:', err));
        }

        /* آپلود تصویر چک */
        function uploadCheque() {
            if (!fileInput?.files?.length) return;
            const data = new FormData();
            data.append('action', 'sorenam_upload_cheque_image');
            data.append('nonce', sm_cart_params.nonce);
            data.append('sorenam_cheque_image', fileInput.files[0]);

            if (uploadBtn) uploadBtn.disabled = true;
            if (status) {
                status.textContent = 'در حال آپلود...';
                status.style.color = 'black';
            }

            fetch(sm_cart_params.ajax_url, { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        if (status) {
                            status.textContent = res.data.message;
                            status.style.color = 'green';
                        }
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        throw new Error(res.data || 'خطا در آپلود');
                    }
                })
                .catch(err => {
                    console.error('SM Cart Upload:', err);
                    if (status) {
                        status.textContent = err.message;
                        status.style.color = 'red';
                    }
                })
                .finally(() => {
                    if (uploadBtn) uploadBtn.disabled = false;
                });
        }

        /* رویداد رادیو دکمه‌ها */
        radios.forEach(radio =>
            radio.addEventListener('change', () => {
                sendStatus(radio.value);
                toggleSection();
            })
        );

        /* رویداد دکمهٔ آپلود */
        if (uploadBtn)
            uploadBtn.addEventListener('click', e => {
                e.preventDefault();
                uploadCheque();
            });

        /* تنظیم وضعیت اولیه */
        toggleSection();
    }

    /* صبر می‌کنیم تا شورت‌کد در DOM بیاید (ممکن است با AJAX رندر شود) */
    let tries = 0;
    const maxTries = 50;
    const interval = setInterval(() => {
        if (document.querySelector('.sm-payment-options')) {
            clearInterval(interval);
            attachListeners();
        } else if (++tries >= maxTries) {
            clearInterval(interval);
            console.warn('SM Cart: shortcode container not found.');
        }
    }, 100);
})();