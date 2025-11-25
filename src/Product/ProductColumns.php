<?php
declare(strict_types=1);

namespace Sorenam\Product;

/**
 * ستون‌های «تعداد اقساط» و «تعداد در بسته» در جدول محصولات
 * HPOS-Ready | PHP 8.1
 */
final class ProductColumns
{
    private const INSTALLMENTS = '_sm_installments';
    private const PACKAGE_QTY  = '_sm_package_qty';

    public function register(): void
    {
        add_filter('manage_edit-product_columns', [$this, 'addColumns'], 20);
        add_action('manage_product_posts_custom_column', [$this, 'renderColumn'], 10, 2);
        add_action('admin_head-edit.php', [$this, 'columnWidth']);
    }

    /* ---------------------------------------------------------- */
    /*  افزودن ستون‌ها (بدون عرض اضافی)                          */
    /* ---------------------------------------------------------- */
    public function addColumns(array $columns): array
    {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'price') {
                $new[self::INSTALLMENTS] = 'تعداد اقساط';
                $new[self::PACKAGE_QTY]  = 'تعداد در بسته';
            }
        }
        return $new;
    }

    /* ---------------------------------------------------------- */
    /*  نمایش مقادیر                                             */
    /* ---------------------------------------------------------- */
    public function renderColumn(string $column, int $postId): void
    {
        $product = wc_get_product($postId);
        if (!$product instanceof \WC_Product) return;

        $value = match ($column) {
            self::INSTALLMENTS => (int) $product->get_meta(self::INSTALLMENTS),
            self::PACKAGE_QTY  => (int) $product->get_meta(self::PACKAGE_QTY),
            default            => null,
        };

        if ($value === null) return;

        echo $value > 0
            ? '<span class="post-state">' . esc_html((string) $value) . '</span>'
            : '<span class="post-state inactive" >' .
              ($column === self::INSTALLMENTS ? 'نقدی' : '-') .
              '</span>';
    }

    /* ---------------------------------------------------------- */
    /*  عرض 11٪ برای هر دو ستون – همان کلاس core                   */
    /* ---------------------------------------------------------- */
    public function columnWidth(): void
    {
        echo '<style>
            .column-' . self::INSTALLMENTS . ',
            .column-' . self::PACKAGE_QTY . ' {
                width: 11% !important;
            }
        </style>';
    }
}