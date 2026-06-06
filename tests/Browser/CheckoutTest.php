<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CheckoutTest extends DuskTestCase
{
    public function test_homepage_and_checkout_pages_load(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertTitle('Laravel');

            $browser->visit('/checkout')
                ->assertTitle('Laravel');

            $browser->visit(route('checkout.success'))
                ->assertTitle('Laravel');

            $browser->visit('/shop')
                ->assertTitle('Laravel');
        });
    }
}
