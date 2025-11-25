jQuery(function ($) {
    $(document).on('click', '.editinline', function () {
        const postId = $(this).closest('tr').attr('id').replace('post-', '');
        const $hidden = $('#inline_' + postId);

        setTimeout(function () {
            const installments = $hidden.find('._sm_installments').text();
            const packageQty   = $hidden.find('._sm_package_qty').text();

            // فقط اولین input visible
            $('.inline-edit-row input[name="_sm_installments"]:visible').first().val(installments);
            $('.inline-edit-row input[name="_sm_package_qty"]:visible').first().val(packageQty);
        }, 50);
    });
});