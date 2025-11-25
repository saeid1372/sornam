<?php
declare(strict_types=1);

namespace Sorenam\Product;

final class ProductModule
{
    public function __construct()
    {
        // ثبت هوک‌ها
        (new ProductFields())->register();
        (new ProductColumns())->register();
        (new QuickEditHandler())->register();
        (new ProductFrontendDisplay())->register();
        
    }

    
}