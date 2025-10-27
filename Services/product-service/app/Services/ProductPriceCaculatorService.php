<?php

namespace App\Services;

class ProductPriceCaculatorService
{
    /**
     * calcualate product total price
     *
     * @param integer $unitePrice
     * @param integer $quantity
     * @return integer
     */
    public function calculateTotal(int $unitePrice, int $quantity): int
    {
        return $unitePrice * $quantity;
    }
}
