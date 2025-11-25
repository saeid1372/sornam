<?php
declare(strict_types=1);

namespace Sorenam\Product;

use WP_Post;
use WC_Product;

/**
 * Quick Edit رسمی وردپرس / ووکامرس
 * 1) نمایش فیلدها در فرم Quick Edit
 * 2) ساخت div.hidden حاوی مقادیر واقعی
 * 3) ذخیره/پاک‌سازی مقادیر پس از Submit
 * HPOS-Ready | PHP 8.1
 */
final class QuickEditHandler
{
    private const INSTALLMENTS = '_sm_installments';
    private const PACKAGE_QTY  = '_sm_package_qty';

    public function register(): void
    {
        add_action('quick_edit_custom_box', [$this, 'renderFields'], 10, 2);
        add_action('manage_product_posts_custom_column', [$this, 'inlineHiddenData'], 11, 2);
        add_action('save_post_product', [$this, 'saveQuickEdit'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /* ---------------------------------------------------------- */
    /*  نمایش فیلدها در Quick Edit                               */
    /* ---------------------------------------------------------- */
    public function renderFields(string $column, string $postType): void
    {
        if ($postType !== 'product') return;

        $label = match ($column) {
            self::INSTALLMENTS => 'مدت بازپرداخت',
            self::PACKAGE_QTY  => 'تعداد در بسته',
            default            => null,
        };
        if (!$label) return;
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php echo esc_html($label); ?></span>
                    <span class="input-text-wrap">
                        <input type="number"
                               name="<?php echo esc_attr($column); ?>"
                               class="<?php echo esc_attr($column); ?> text number"
                               min="0" step="1" placeholder="0">
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /* ---------------------------------------------------------- */
    /*  ساخت div.hidden رسمی وردپرس – فقط یک‌بار                  */
    /* ---------------------------------------------------------- */
    public function inlineHiddenData(string $column, int $postId): void
    {
        if ($column !== self::INSTALLMENTS) return; // یک‌بار کافی است
        $product = wc_get_product($postId);
        if (!$product instanceof WC_Product) return;

        echo '<div class="hidden" id="inline_' . esc_attr((string) $postId) . '">';
        echo '<div class="' . self::INSTALLMENTS . '">' . esc_html($product->get_meta(self::INSTALLMENTS)) . '</div>';
        echo '<div class="' . self::PACKAGE_QTY . '">' . esc_html($product->get_meta(self::PACKAGE_QTY)) . '</div>';
        echo '</div>';
    }

    /* ---------------------------------------------------------- */
    /*  ذخیره/پاک‌سازی مقادیر Quick Edit – HPOS-Ready              */
    /* ---------------------------------------------------------- */
    public function saveQuickEdit(int $postId, WP_Post $post): void
    {
        if (!defined('DOING_AJAX') || !DOING_AJAX ||
            ($_POST['action'] ?? '') !== 'inline-save' ||
            $post->post_type !== 'product' ||
            !current_user_can('edit_post', $postId)) {
            return;
        }

        $product = wc_get_product($postId);
        if (!$product instanceof WC_Product) return;

        foreach ([self::INSTALLMENTS, self::PACKAGE_QTY] as $key) {
            if (isset($_POST[$key])) {
                // پاک‌سازی عددی + ذخیره با متد رسمی WC
                $value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_NUMBER_INT);
                $product->update_meta_data($key, max(0, (int) $value));
            }
        }
        $product->save();
    }

    /* ---------------------------------------------------------- */
    /*  Assets – فقط صفحهٔ لیست محصولات                             */
    /* ---------------------------------------------------------- */
    public function enqueueAssets(string $hook): void
    {

        $screen = get_current_screen();
        if ($screen && $screen->id === 'edit-product') {
            wp_enqueue_script(
            'sm-product-quick-edit',
            SM_PLUGIN_URL . 'assets/js/product/quick-edit-handler.js',
            ['jquery'],
            SM_VERSION,
            true
        );
        }
    }
}