<?php
declare(strict_types=1);

namespace Sorenam;

defined('ABSPATH') || exit;

final class SM_Bootstrap
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->registerModules();
    }

    private function registerModules(): void
    {
        /* هر خط = Aggregate Root یک ماژول */
        new Product\ProductModule();
        new Delivery\DeliveryModule();
        new Cart\CartModule();
        
    }
}