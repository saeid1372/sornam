<?php
/**
 * Class Sorenam_Admin_Product_Handler
 *
 * مدیریت فیلدهای سفارشی محصول (تعداد اقساط و تعداد در بسته) در پیشخوان وردپرس.
 */
class Sorenam_Admin_Product_Handler {

    /**
     * آرایه‌ای برای تعریف فیلدهای سفارشی
     * @var array
     */
    private $custom_fields = [
        '_installments_number' => [
            'label' => 'تعداد اقساط ',
            'column_label' => 'تعداد اقساط',
            'placeholder' => '0',
            'description' => 'اگر خالی بماند یا صفر باشد، محصول نقدی محسوب می‌شود.',
            'quick_edit_label' => 'مدت بازپرداخت',
        ],
        '_package_quantity' => [
            'label' => 'تعداد در بسته',
            'column_label' => 'تعداد در بسته',
            'placeholder' => '1',
            'description' => 'تعداد کالا در هر بسته.',
            'quick_edit_label' => 'تعداد در بسته',
        ],
    ];

    /**
     * ثبت هوک‌های مورد نیاز.
     *
     * @return void
     */
    public function init_hooks() {
        // Add custom fields to the general product data tab
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_custom_fields']);
        // Save custom field data
        add_action('woocommerce_admin_process_product_object', [$this, 'save_custom_fields']);
        // Add custom columns to the products list
        add_filter('manage_edit-product_columns', [$this, 'add_custom_columns'], 20);
        // Display data in the custom columns
        add_action('manage_product_posts_custom_column', [$this, 'display_custom_columns_data'], 10, 2);
        // Add custom fields to quick edit
        add_action('quick_edit_custom_box', [$this, 'add_fields_to_quick_edit'], 10, 2);
        // Save quick edit data
        add_action('save_post_product', [$this, 'save_quick_edit_data_robust'], 10, 2);
        // Add custom CSS for admin columns
        add_action('admin_head', [$this, 'add_admin_css']);
        // Enqueue scripts for admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * بارگذاری اسکریپت مورد نیاز برای بخش مدیریت (ویرایش سریع).
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_scripts($hook) {
        $screen = get_current_screen();
        if ($screen && 'edit-product' === $screen->id) {
            wp_enqueue_script(
                'sorenam-admin-product-handler',
                SORENAM_PLUGIN_URL . 'assets/js/admin-product-handler.js',
                ['jquery'],
                SORENAM_VERSION,
                true
            );
            // ارسال نام فیلدها به جاوااسکریپت
            wp_localize_script('sorenam-admin-product-handler', 'sorenam_admin_params', [
                'fields' => array_keys($this->custom_fields),
            ]);
        }
    }

    /**
     * فیلدهای سفارشی را به گزینه‌های عمومی محصول اضافه می‌کند.
     */
    public function add_custom_fields(): void {
        foreach ($this->custom_fields as $meta_key => $field_data) {
            $product_id = get_the_ID();
            $value = $product_id ? (int) get_post_meta($product_id, $meta_key, true) : 0;

            woocommerce_wp_text_input(
                [
                    'id' => $meta_key,
                    'label' => $field_data['label'],
                    'placeholder' => $field_data['placeholder'],
                    'desc_tip' => true,
                    'description' => $field_data['description'],
                    'type' => 'number',
                    'custom_attributes' => ['min' => 0, 'step' => 1],
                    'value' => max(0, $value),
                ]
            );
        }
    }

    /**
     * ذخیره مقادیر فیلدهای سفارشی
     *
     * @param WC_Product $product The product object.
     */
    public function save_custom_fields(\WC_Product $product): void {
        foreach ($this->custom_fields as $meta_key => $field_data) {
            $value = filter_input(INPUT_POST, $meta_key, FILTER_SANITIZE_NUMBER_INT);
            $product->update_meta_data($meta_key, max(0, (int) $value));
        }
    }

    /**
     * افزودن ستون‌های سفارشی به جدول محصولات در پیشخوان
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_custom_columns(array $columns): array {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ('price' === $key) {
                foreach ($this->custom_fields as $meta_key => $field_data) {
                    $new_columns[sanitize_key($meta_key)] = $field_data['column_label'];
                }
            }
        }
        return $new_columns;
    }

        /**
     * نمایش مقادیر در ستون‌های سفارشی در جدول محصولات
     *
     * @param string $column نام کلید ستون (که با sanitize_key ساخته شده).
     * @param int    $post_id Product ID.
     */
    public function display_custom_columns_data(string $column, int $post_id): void {
        // پیدا کردن کلید اصلی متا از روی کلید ستون
        $original_meta_key = '';
        foreach ($this->custom_fields as $meta_key => $field_data) {
            if (sanitize_key($meta_key) === $column) {
                $original_meta_key = $meta_key;
                break;
            }
        }

        // اگر کلید اصلی پیدا نشد، کاری نکن
        if (empty($original_meta_key)) {
            return;
        }

        $value = (int) get_post_meta($post_id, $original_meta_key, true);
        if ($value > 0) {
            echo esc_html($value );
        } else {
            echo '<span style="color: #777;">' . ($original_meta_key === '_installments_number' ? 'نقدی' : '-') . '</span>';
        }
    }

    /**
     * افزودن فیلدهای سفارشی به ویرایش سریع.
     *
     * @param string $column_name Column name.
     * @param string $post_type   Post type.
     */
    public function add_fields_to_quick_edit(string $column_name, string $post_type): void {
        if (!array_key_exists($column_name, $this->custom_fields) || 'product' !== $post_type) {
            return;
        }
        $field_data = $this->custom_fields[$column_name];
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php echo esc_html($field_data['quick_edit_label']); ?></span>
                    <span class="input-text-wrap">
                        <input type="number" name="<?php echo esc_attr($column_name); ?>" class="text number" value="" min="0" step="1" placeholder="<?php echo esc_attr($field_data['placeholder']); ?>">
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

           /**
     * ذخیره مقادیر فیلدهای سفارشی در ویرایش سریع (نسخه بسیار مطمئن).
     *
     * @param int     $post_id شناسه پست.
     * @param WP_Post $post    شیء پست.
     * @return void
     */
    public function save_quick_edit_data_robust(int $post_id, \WP_Post $post) {
        // چک ۱: آیا این یک درخواست AJAX ویرایش سریع است؟
        if (!defined('DOING_AJAX') || !DOING_AJAX || !isset($_POST['action']) || $_POST['action'] !== 'inline-save') {
            return;
        }

        // چک ۲: آیا پست از نوع محصول است؟
        if ('product' !== $post->post_type) {
            return;
        }

        // چک ۳: آیا کاربر مجوز ویرایش را دارد؟
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // دریافت شیء محصول
        $product = wc_get_product($post_id);
        if (!$product) {
            return;
        }

        // حلقه روی تمام فیلدهای سفارشی تعریف شده
        foreach ($this->custom_fields as $meta_key => $field_data) {
            // مقدار فیلد را از $_POST بخوان و پاک‌سازی کن
            $value = filter_input(INPUT_POST, $meta_key, FILTER_SANITIZE_NUMBER_INT);
            
            // اگر مقداری ارسال شده بود، آن را ذخیره کن
            if ($value !== null && $value !== '') {
                $product->update_meta_data($meta_key, max(0, (int) $value));
            }
        }

        // مرحله کلیدی: شیء محصول را به صورت دستی ذخیره کن
        $product->save();
    }

    /**
     * افزودن استایل به ستون‌های ادمین.
     */
    public function add_admin_css(): void {
        $screen = get_current_screen();
        if ($screen && 'edit-product' === $screen->id) {
            ?>
            <style>
                <?php foreach (array_keys($this->custom_fields) as $meta_key): ?>
                .widefat th#<?php echo esc_attr(sanitize_key($meta_key)); ?>,
                .widefat td.column-<?php echo esc_attr(sanitize_key($meta_key)); ?> {
                    width: 100px;
                    text-align: center;
                }
                <?php endforeach; ?>
            </style>
            <?php
        }
    }
}