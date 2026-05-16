<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('shipping_rate', 15);
        Setting::set('free_shipping_threshold', 100);
        Setting::set('tax_rate', 10);
    }
}
