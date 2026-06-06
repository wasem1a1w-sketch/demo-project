<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PriceCalculator
{
    public function calculateSubtotal(array $items, Collection $products): float
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            if (!$product) continue;
            $subtotal += (float) $product->price * (int) $item['quantity'];
        }

        return $subtotal;
    }

    public function calculateTax(float $subtotal, float $discount, float $taxRate): float
    {
        return max(0, ($subtotal - $discount) * $taxRate / 100);
    }

    public function calculateShipping(float $subtotal, float $discount, float $freeShippingThreshold, float $shippingRate): float
    {
        return ($subtotal - $discount) >= $freeShippingThreshold ? 0 : $shippingRate;
    }

    public function calculateTotal(float $subtotal, float $discount, float $tax, float $shipping): float
    {
        return $subtotal - $discount + $tax + $shipping;
    }

    public function calculate(array $items, Collection $products, float $discount, float $taxRate, float $freeShippingThreshold, float $shippingRate): array
    {
        $subtotal = $this->calculateSubtotal($items, $products);
        $tax = $this->calculateTax($subtotal, $discount, $taxRate);
        $shipping = $this->calculateShipping($subtotal, $discount, $freeShippingThreshold, $shippingRate);
        $total = $this->calculateTotal($subtotal, $discount, $tax, $shipping);

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
        ];
    }
}
