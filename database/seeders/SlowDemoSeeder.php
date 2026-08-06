<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $target = (int) (env('SLOW_DEMO_ROWS') ?: 500_000);

        $current = DB::table('user_activity_logs')
            ->whereRaw('JSON_EXTRACT(data, "$.demo") = true')
            ->count();

        if ($current >= $target) {
            $this->command->warn("Already seeded {$current} rows. Skipping.");

            return;
        }

        $this->command->info("Toping up from {$current} to {$target} rows.");

        $users = DB::table('users')->pluck('id')->toArray();
        $toInsert = $target - $current;
        $chunk = 1_000;
        $progress = $this->command->getOutput()->createProgressBar($toInsert / $chunk);
        $progress->start();

        $products = [
            'Wireless Bluetooth Earbuds', 'Stainless Steel Water Bottle', 'Mechanical Gaming Keyboard',
            '4K Ultra HD Monitor', 'Ergonomic Office Chair', 'USB-C Fast Charging Cable',
            'Noise Cancelling Headphones', 'Smart Home Thermostat', 'Portable Power Bank 20000mAh',
            'Organic Cotton T-Shirt', 'Leather Wallet RFID Blocking', 'Electric Standing Desk',
            'Dash Cam with Night Vision', 'Air Fryer 5.5 Quart', 'Yoga Mat Non-Slip',
            'Espresso Machine Barista', 'Robot Vacuum Cleaner', 'Hiking Backpack 40L',
            'Instant Read Meat Thermometer', 'Adjustable Dumbbell Set', 'LED Ring Light 18 inch',
            'Cordless Drill 20V', 'Ceramic Non-Stick Cookware Set', 'Gaming Mouse with RGB',
            'Memory Foam Pillow', 'Smart Watch Fitness Tracker', 'Cast Iron Skillet 12 inch',
            'External SSD 1TB', 'Blue Light Blocking Glasses', 'Indoor Plant Grow Light',
        ];

        $categories = [
            'Electronics', 'Home & Kitchen', 'Sports & Outdoors', 'Fashion', 'Office Supplies',
            'Beauty & Personal Care', 'Toys & Games', 'Automotive', 'Grocery', 'Pet Supplies',
        ];

        $terms = [
            'headphones', 'wireless charger', 'running shoes', 'coffee maker', 'desk organizer',
            'bluetooth speaker', 'protein powder', 'skincare set', 'backpack', 'monitor stand',
            'yoga blocks', 'water filter', 'smart plugs', 'camping tent', 'dash cam',
            'mechanical keyboard', 'air purifier', 'coffee beans', 'resistance bands', 'photo printer',
        ];

        $types = [
            'page_view', 'product_viewed', 'search_performed', 'cart_updated', 'checkout_started',
            'order_placed', 'coupon_applied', 'wishlist_added', 'profile_updated', 'login_successful',
        ];

        $bots = [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)',
        ];

        $clients = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        ];

        $orderNumbers = [];
        for ($i = 0; $i < 200; $i++) {
            $orderNumbers[] = strtoupper(substr(md5((string) $i), 0, 6)).'-'.random_int(100000, 999999);
        }

        $now = time();

        for ($i = 0; $i < $toInsert; $i += $chunk) {
            $rows = [];

            for ($j = 0; $j < $chunk; $j++) {
                $product = $products[array_rand($products)];
                $category = $categories[array_rand($categories)];
                $term = $terms[array_rand($terms)];
                $orderNo = $orderNumbers[array_rand($orderNumbers)];
                $type = $types[array_rand($types)];

                $description = match (random_int(0, 9)) {
                    0 => "Viewed product: {$product}",
                    1 => "Searched for \"{$term}\"",
                    2 => "Added SKU-".random_int(1000, 9999)." to cart",
                    3 => "Placed order #{$orderNo}",
                    4 => "Checked out with ".random_int(1, 9)." items",
                    5 => "Applied coupon SAVE".random_int(5, 30),
                    6 => "Viewed category \"{$category}\"",
                    7 => "Filtered products by price range \$".random_int(10, 90)." - \$".random_int(100, 900),
                    8 => "Reviewed product: {$product}",
                    default => "Completed checkout for order #{$orderNo}",
                };

                $isBot = random_int(0, 100) < 15;
                $daysAgo = random_int(0, 180);

                $rows[] = [
                    'user_id' => $users ? $users[array_rand($users)] : null,
                    'type' => $type,
                    'description' => $description,
                    'data' => json_encode(['demo' => true]),
                    'ip_address' => random_int(11, 223).'.'.random_int(0, 255).'.'.random_int(0, 255).'.'.random_int(1, 254),
                    'user_agent' => $isBot ? $bots[array_rand($bots)] : $clients[array_rand($clients)],
                    'created_at' => date('Y-m-d H:i:s', $now - ($daysAgo * 86400) - random_int(0, 86399)),
                ];
            }

            DB::table('user_activity_logs')->insert($rows);
            $progress->advance();
        }

        $progress->finish();
        $this->command->newLine(2);
        $this->command->info("Seeded {$toInsert} rows (total {$target}) into user_activity_logs.");
    }
}
