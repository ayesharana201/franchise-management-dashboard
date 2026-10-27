<?php

namespace Database\Seeders;

use App\Models\ComplianceCheck;
use App\Models\ComplianceItem;
use App\Models\Document;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\Message;
use App\Models\SalesRecord;
use App\Models\SupplyOrder;
use App\Models\User;
use App\Services\RoyaltyCalculator;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42); // deterministic demo data

        $franchisor = User::create([
            'name' => 'Franchisor Admin', 'email' => 'admin@franchise.test',
            'password' => 'password', 'role' => User::ROLE_FRANCHISOR,
        ]);

        $regions = ['North', 'South', 'East'];
        $regional = [];
        foreach ($regions as $region) {
            $regional[$region] = User::create([
                'name' => "$region Regional Manager", 'email' => strtolower($region) . '.manager@franchise.test',
                'password' => 'password', 'role' => User::ROLE_REGIONAL, 'region' => $region,
            ]);
        }

        $cities = [
            'North' => ['Lahore', 'Islamabad', 'Peshawar'],
            'South' => ['Karachi', 'Hyderabad', 'Sukkur'],
            'East' => ['Faisalabad', 'Multan', 'Sialkot'],
        ];

        $locations = [];
        $n = 1;
        foreach ($cities as $region => $list) {
            foreach ($list as $city) {
                $loc = Location::create([
                    'name' => "Franchise $city", 'code' => sprintf('FR-%03d', $n++), 'region' => $region,
                    'city' => $city, 'address' => "Main Boulevard, $city",
                    'manager_name' => "$city Manager", 'status' => 'active',
                    'opened_on' => now()->subMonths(mt_rand(8, 48))->toDateString(),
                    'royalty_rate' => 6, 'marketing_fee_rate' => 2, 'tech_fee' => 150,
                ]);
                User::create([
                    'name' => "$city Franchisee", 'email' => strtolower($city) . '@franchise.test',
                    'password' => 'password', 'role' => User::ROLE_FRANCHISEE, 'location_id' => $loc->id,
                ]);
                $locations[] = $loc;
            }
        }

        // Six months of sales + royalty statements
        $calc = app(RoyaltyCalculator::class);
        foreach ($locations as $i => $loc) {
            $base = 40000 + $i * 6500;
            for ($m = 5; $m >= 0; $m--) {
                $gross = $base * (1 + (mt_rand(-8, 12) / 100));
                $record = SalesRecord::create([
                    'location_id' => $loc->id,
                    'period' => now()->subMonths($m)->startOfMonth()->toDateString(),
                    'gross_sales' => round($gross, 2),
                    'cost_of_goods' => round($gross * 0.32, 2),
                    'labor_cost' => round($gross * 0.28, 2),
                    'other_expenses' => round($gross * 0.10, 2),
                    'transactions' => (int) ($gross / 18),
                    'customer_rating' => round(mt_rand(380, 495) / 100, 2),
                ]);
                $statement = $calc->generateStatement($record);
                if ($m >= 2) {
                    $statement->update(['status' => 'paid', 'paid_at' => $statement->due_date]);
                } elseif ($m === 1 && $i % 4 === 0) {
                    $statement->update(['status' => 'overdue']);
                }
            }
        }

        // Compliance checklist
        $items = collect([
            ['Food safety inspection', 'safety', 'monthly'],
            ['Brand signage & uniform standards', 'brand', 'quarterly'],
            ['Cash handling audit', 'financial', 'monthly'],
            ['Fire safety equipment check', 'safety', 'quarterly'],
            ['Customer service mystery shop', 'operations', 'quarterly'],
        ])->map(fn ($r) => ComplianceItem::create(['title' => $r[0], 'category' => $r[1], 'frequency' => $r[2]]));

        foreach ($locations as $loc) {
            foreach ($items as $item) {
                $roll = mt_rand(1, 100);
                $status = $roll <= 70 ? 'passed' : ($roll <= 82 ? 'failed' : 'pending');
                ComplianceCheck::create([
                    'location_id' => $loc->id, 'compliance_item_id' => $item->id, 'status' => $status,
                    'due_date' => now()->addDays(mt_rand(-10, 20))->toDateString(),
                    'checked_on' => $status === 'pending' ? null : now()->subDays(mt_rand(1, 20))->toDateString(),
                    'checked_by' => $status === 'pending' ? null : $regional[$loc->region]->id,
                ]);
            }
        }

        // Inventory
        $catalog = [
            ['PKG-001', 'Takeaway boxes (case of 200)', 'Packaging', 'case', 42.00, 120],
            ['PKG-002', 'Branded cups (case of 500)', 'Packaging', 'case', 58.50, 80],
            ['ING-101', 'Signature sauce (5L)', 'Ingredients', 'bottle', 24.00, 60],
            ['ING-102', 'Flour (25kg)', 'Ingredients', 'bag', 31.25, 8],
            ['UNI-201', 'Staff uniform set', 'Uniforms', 'set', 19.99, 200],
            ['MKT-301', 'Promo poster pack', 'Marketing', 'pack', 12.00, 5],
        ];
        foreach ($catalog as [$sku, $name, $cat, $unit, $price, $stock]) {
            InventoryItem::create(compact('sku', 'name', 'unit', 'stock') + ['category' => $cat, 'unit_price' => $price, 'reorder_level' => 10]);
        }

        // One sample order
        $first = $locations[0];
        $order = SupplyOrder::create([
            'location_id' => $first->id, 'user_id' => User::where('location_id', $first->id)->value('id'),
            'status' => 'submitted', 'total' => 0,
        ]);
        $total = 0;
        foreach (InventoryItem::limit(2)->get() as $item) {
            $order->items()->create(['inventory_item_id' => $item->id, 'quantity' => 3, 'unit_price' => $item->unit_price]);
            $total += 3 * $item->unit_price;
        }
        $order->update(['total' => $total]);

        // Messages & documents
        Message::create([
            'sender_id' => $franchisor->id, 'location_id' => null, 'is_announcement' => true,
            'subject' => 'Q4 promotion kicks off Monday',
            'body' => 'Please make sure promo posters are up before opening on Monday. Order extra packs from the supply catalogue if needed.',
        ]);
        foreach (array_slice($locations, 0, 3) as $loc) {
            Document::create([
                'location_id' => $loc->id, 'uploaded_by' => $franchisor->id, 'title' => "{$loc->name} franchise agreement",
                'type' => 'contract', 'path' => 'documents/sample.pdf', 'original_name' => 'franchise-agreement.pdf',
                'expires_on' => now()->addMonths(mt_rand(1, 24))->toDateString(),
            ]);
        }
    }
}
