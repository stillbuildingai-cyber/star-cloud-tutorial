<?php

namespace Tests\Unit;

use App\Models\Transaction\Order;
use PHPUnit\Framework\TestCase;

class OrderPickupRecipientMaskTest extends TestCase
{
    public function test_it_masks_pickup_recipient_names(): void
    {
        $this->assertSame('鄭O', Order::maskPickupRecipientName('鄭英'));
        $this->assertSame('鄭O英', Order::maskPickupRecipientName('鄭小英'));
        $this->assertSame('鄭OO英', Order::maskPickupRecipientName('鄭曉小英'));
    }

    public function test_it_keeps_pickup_recipient_suffix(): void
    {
        $this->assertSame('黃O美 (2593)', Order::maskPickupRecipientName('黃麗美 (2593)'));
        $this->assertSame("黃O美\n(2593)", Order::maskPickupRecipientName("黃麗美\n(2593)"));
    }
}
