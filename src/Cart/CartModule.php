<?php
declare(strict_types=1);

namespace Sorenam\Cart;

final class CartModule
{
    public function __construct()
    {
        // ثبت هوک‌ها
        
        (new CartFeeHandler())->register();
        (new CartShortcode())->register();
        (new CartAjax())->register();
        (new BlockEditor())->register();

    }

    
}