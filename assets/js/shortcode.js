/**
 * sorenam-shortcode.js
 *
 * جاوااسکریپت مربوط به شورت‌کد انتخاب نوع پرداخت در سبد خرید و تسویه حساب.
 * این اسکریپت مدیریت دکمه‌های رادیویی، نمایش بخش چک، و آپلود تصویر چک را بر عهده دارد.
 */

document.addEventListener('DOMContentLoaded', function() {

    /**
     * تابعی برای اتصال رویدادها به عناصر شورت‌کد
     */
    function attachSorenamPeymentOptionsListeners() {
        // انتخابگر برای هدف‌گیری کانتینر شورت‌کد
        const shortcodeContainer = document.querySelector('.sorenam-peyment-options-shortcode-container');
        
        // اگر کانتینر شورت‌کد پیدا نشد، کاری نکن
        if (!shortcodeContainer) {
            return;
        }

        // پیدا کردن عناصر مورد نیاز در DOM
        const radioButtons = shortcodeContainer.querySelectorAll('input[type="radio"]');
        const chequeSection = document.getElementById('sorenam-cheque-section');
        const uploadBtn = document.getElementById('sorenam_upload_cheque_btn');
        const statusSpan = document.getElementById('sorenam_upload_status');
        const fileInput = document.getElementById('sorenam_cheque_image');
        
        // اگر دکمه‌های رادیویی پیدا نشدند، کاری نکن
        if (radioButtons.length === 0) {
            return;
        }

        console.log('Sorenam Script: Shortcode container found. Attaching listeners.');

        /**
         * تابعی برای نمایش یا مخفی کردن بخش آپلود چک
         */
        function toggleChequeSection() {
            if (!chequeSection) return;
            const isCreditSelected = document.querySelector('input[name="sorenam_button_option"]:checked').value === 'no';
            chequeSection.style.display = isCreditSelected ? 'block' : 'none';
        }

        // فراخوانی اولیه برای تنظیم وضعیت اولیه نمایش بخش چک
        toggleChequeSection();

        // اضافه کردن رویداد change به هر دو دکمه رادیویی
        radioButtons.forEach(button => {
            button.addEventListener('change', function() {
                const newStatus = this.value; // 'yes' یا 'no'

                // ارسال درخواست AJAX برای به‌روزرسانی سشن پرداخت
                fetch(sorenam_peyment_options_params.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: new URLSearchParams({
                        action: 'update_sorenam_peyment_options_fee',
                        fee_status: newStatus,
                        nonce: sorenam_peyment_options_params.nonce,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // بررسی اینکه آیا از WooCommerce Blocks استفاده می‌شود یا خیر
                        if (window.wc && window.wc.blocks && window.wc.blocks.CartStore) {
                             window.wc.blocks.CartStore.invalidateResolutionForStore();
                        } else {
                             // در غیر این صورت، کل صفحه را رفرش کن تا تغییرات نمایش داده شود
                             window.location.reload();
                        }
                    } else {
                        alert('خطایی در به‌روزرسانی رخ داد. لطفاً مجددا تلاش کنید.');
                    }
                })
                .catch(error => {
                    console.error('Sorenam Script: AJAX request failed:', error);
                    alert('خطایی در ارتباط با سرور رخ داد. لطفاً مجددا تلاش کنید.');
                })
                .finally(() => {
                    // در هر صورت، وضعیت نمایش بخش چک را به‌روز کن
                    toggleChequeSection();
                });
            });
        });

        // مدیریت دکمه آپلود چک
        if (uploadBtn) {
            uploadBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    alert('لطفاً ابتدا یک تصویر انتخاب کنید.');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'sorenam_upload_cheque_image');
                formData.append('nonce', sorenam_peyment_options_params.nonce);
                formData.append('sorenam_cheque_image', fileInput.files[0]);

                // نمایش وضعیت بارگذاری و غیرفعال کردن دکمه
                statusSpan.textContent = 'در حال آپلود...';
                statusSpan.style.color = 'black';
                uploadBtn.disabled = true;

                fetch(sorenam_peyment_options_params.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusSpan.textContent = data.data.message;
                        statusSpan.style.color = 'green';
                        // رفرش صفحه برای نمایش تصویر آپلود شده و مخفی کردن فرم
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500); // کمی تأخیر برای دیدن پیام موفقیت
                    } else {
                        statusSpan.textContent = 'خطا: ' + data.data;
                        statusSpan.style.color = 'red';
                    }
                })
                .catch(error => {
                    console.error('Upload failed:', error);
                    statusSpan.textContent = 'خطا در ارتباط با سرور.';
                    statusSpan.style.color = 'red';
                })
                .finally(() => {
                    // فعال کردن مجدد دکمه در هر صورت
                    uploadBtn.disabled = false;
                });
            });
        }
    }

    // استفاده از Interval برای صبر کردن تا زمانی که شورت‌کد (ممکن است با AJAX) رندر شود
    let attempts = 0;
    const maxAttempts = 50; // حداکثر تلاش برای پیدا کردن شورت‌کد (5 ثانیه)

    const checkForShortcode = setInterval(function() {
        const shortcodeContainer = document.querySelector('.sorenam-peyment-options-shortcode-container');
        attempts++;

        if (shortcodeContainer) {
            clearInterval(checkForShortcode);
            console.log('Sorenam Script: Shortcode found after ' + attempts + ' attempts.');
            attachSorenamPeymentOptionsListeners();
        } else if (attempts >= maxAttempts) {
            clearInterval(checkForShortcode);
            console.log('Sorenam Script: ERROR - Shortcode not found after ' + maxAttempts + ' attempts.');
        }
    }, 100); // هر ۱۰۰ میلی‌ثانیه یک بار چک کن
});