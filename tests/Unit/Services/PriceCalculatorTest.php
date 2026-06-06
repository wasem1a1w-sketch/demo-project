<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Services\PriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PriceCalculator();
    }

    public function test_calculates_subtotal_from_items(): void
    {
        $product = Product::factory()->create(['price' => 49.99]);
        $product2 = Product::factory()->create(['price' => 19.95]);

        $items = [
            ['product_id' => $product->id, 'quantity' => 2],
            ['product_id' => $product2->id, 'quantity' => 3],
        ];
        $products = Product::whereIn('id', [$product->id, $product2->id])->get()->keyBy('id');

        $subtotal = $this->calculator->calculateSubtotal($items, $products);

        $this->assertEqualsWithDelta(49.99 * 2 + 19.95 * 3, $subtotal, 0.001);
    }

    public function test_calculates_tax_with_and_without_discount(): void
    {
        $tax = $this->calculator->calculateTax(100.00, 0, 10);
        $this->assertEquals(10.00, $tax);

        $taxWithDiscount = $this->calculator->calculateTax(100.00, 20.00, 10);
        $this->assertEquals(8.00, $taxWithDiscount);

        $taxZeroSubtotal = $this->calculator->calculateTax(0, 0, 10);
        $this->assertEquals(0, $taxZeroSubtotal);
    }

    public function test_calculates_shipping_below_free_threshold(): void
    {
        $shipping = $this->calculator->calculateShipping(50.00, 0, 100.00, 15.00);
        $this->assertEquals(15.00, $shipping);

        $shippingWithDiscount = $this->calculator->calculateShipping(110.00, 20.00, 100.00, 15.00);
        $this->assertEquals(15.00, $shippingWithDiscount);
    }

    public function test_free_shipping_above_threshold(): void
    {
        $shipping = $this->calculator->calculateShipping(100.00, 0, 100.00, 15.00);
        $this->assertEquals(0, $shipping);

        $shippingAbove = $this->calculator->calculateShipping(150.00, 0, 100.00, 15.00);
        $this->assertEquals(0, $shippingAbove);
    }

    public function test_calculates_total_correctly(): void
    {
        $total = $this->calculator->calculateTotal(100.00, 10.00, 9.00, 5.00);
        $this->assertEquals(104.00, $total);

        $totalNoDiscount = $this->calculator->calculateTotal(100.00, 0, 10.00, 15.00);
        $this->assertEquals(125.00, $totalNoDiscount);
    }
}
