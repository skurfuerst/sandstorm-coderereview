<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class LoyaltyServiceTest extends TestCase
{
    public function testAwardCallsSetPoints(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('isActive')->willReturn(true);
        $customer->method('getTier')->willReturn('bronze');
        $customer->method('getPoints')->willReturn(0);

        // assert the service pokes the setter with the internally-computed value
        $customer->expects($this->once())->method('setPoints')->with(50);

        $service = new LoyaltyService();
        $service->award($customer, 50);
    }

    public function testRateIsTwoForSilver(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('isActive')->willReturn(true);
        $customer->method('getTier')->willReturn('silver');
        $customer->method('getPoints')->willReturn(0);
        $customer->expects($this->once())->method('setPoints')->with(100);

        $service = new LoyaltyService();
        $service->award($customer, 50);
    }
}
