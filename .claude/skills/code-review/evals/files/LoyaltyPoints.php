<?php

namespace App\Model;

// A customer.
class Customer
{
    public string $tier = 'bronze';
    public int $points = 0;
    public bool $active = true;

    public function getTier(): string { return $this->tier; }
    public function setTier(string $t): void { $this->tier = $t; }
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $p): void { $this->points = $p; }
    public function isActive(): bool { return $this->active; }
}

class LoyaltyService
{
    public function award(Customer $c, int $orderAmount): void
    {
        if (!$c->isActive()) {
            return;
        }
        $rate = 1;
        if ($c->getTier() === 'silver') {
            $rate = 2;
        } elseif ($c->getTier() === 'gold') {
            $rate = 3;
        }
        $earned = $orderAmount * $rate;
        $c->setPoints($c->getPoints() + $earned);
        if ($c->getPoints() > 1000 && $c->getTier() === 'bronze') {
            $c->setTier('silver');
        }
    }
}
