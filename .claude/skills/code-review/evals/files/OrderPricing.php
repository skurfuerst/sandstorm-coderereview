<?php

namespace App\Service;

class OrderPricing
{
    public function getSubtotal(array $order): float
    {
        return $order['subtotal'];
    }

    public function getCurrency(array $order): string
    {
        return $order['currency'];
    }

    public function calculate(array $order, ?array $discount, ?array $coupon, ?string $taxRegion): float
    {
        $total = $order['subtotal'];

        if ($discount !== null) {
            if ($discount['type'] === 'percent') {
                $total = $total - ($total * $discount['value'] / 100);
            } else {
                $total = $total - $discount['value'];
            }
        }

        if ($coupon !== null) {
            $total = $total - $coupon['amount'];
        }

        if ($taxRegion !== null) {
            $rate = $taxRegion === 'EU' ? 0.19 : ($taxRegion === 'US' ? 0.07 : 0.0);
            $total = $total + ($total * $rate);
        }

        return $total < 0 ? 0 : $total;
    }
}
