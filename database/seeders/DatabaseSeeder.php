<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\RunState;
use App\Models\AddOn;
use App\Models\BoxTemplate;
use App\Models\DispatchZone;
use App\Models\Hub;
use App\Models\Item;
use App\Models\PackUnit;
use App\Models\Rider;
use App\Models\Run;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Local demo data. Illustrative names and prices only (concept docs: not final).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo data must not be seeded in production. Run PolicySeeder only, then php artisan josveggies:create-admin.');
        }

        $this->call(PolicySeeder::class);

        $hub = Hub::query()->create([
            'name' => 'JosVeggies Hub, Wuse 2',
            'address' => '12 Example Crescent, Wuse 2, Abuja (placeholder — location to be confirmed)',
            'directions' => 'Look for the green JosVeggies banner.',
            'opening_note' => 'Lit and staffed through the night during pickup windows.',
        ]);

        foreach ([['Central Abuja', 'Wuse, Maitama, Garki, Asokoro', 150000], ['Inner Abuja', 'Jabi, Utako, Gwarinpa, Wuye', 250000], ['Outer Abuja', 'Lugbe, Kubwa, Lokogoma, Karu', 400000]] as $i => [$name, $areas, $fee]) {
            DispatchZone::query()->create(['name' => $name, 'areas' => $areas, 'fee_kobo' => $fee, 'sort_order' => $i]);
        }

        $mudu = PackUnit::query()->create(['traditional_name' => '1 Mudu', 'container' => '10 × 10 cm box', 'min_weight_g' => 1200, 'max_weight_g' => 1400]);
        $bunch = PackUnit::query()->create(['traditional_name' => 'Bunch', 'container' => 'Banded bunch', 'min_weight_g' => 250, 'max_weight_g' => 350]);
        $bucket = PackUnit::query()->create(['traditional_name' => 'Small paint bucket', 'container' => '4 L bucket', 'min_weight_g' => 2500, 'max_weight_g' => 3000]);
        $head = PackUnit::query()->create(['traditional_name' => 'Head', 'container' => 'Single head, wrapped', 'min_weight_g' => 600, 'max_weight_g' => 900]);

        $items = [];
        foreach ([
            ['Tomatoes', 'fruiting', $bucket, 450000], ['Tatashe (red bell pepper)', 'fruiting', $mudu, 300000],
            ['Ata rodo (scotch bonnet)', 'fruiting', $mudu, 250000], ['Onions', 'bulb', $mudu, 200000],
            ['Ugu (pumpkin leaf)', 'leafy', $bunch, 80000], ['Scent leaf', 'herb', $bunch, 60000],
            ['Irish potatoes', 'root', $bucket, 350000], ['Carrots', 'root', $mudu, 220000],
            ['Cabbage', 'leafy', $head, 150000], ['Lettuce', 'salad', $head, 180000],
            ['Cucumber', 'salad', $mudu, 160000], ['Green pepper', 'salad', $mudu, 200000],
            ['Spinach (efo)', 'leafy', $bunch, 90000],
        ] as [$name, $category, $unit, $price]) {
            $items[$name] = Item::query()->create(['name' => $name, 'category' => $category, 'pack_unit_id' => $unit->id, 'reference_price_kobo' => $price]);
        }

        $boxes = [
            ['Soup Pot Box', 'Everything for a proper pot of stew and soup', 'tomato', 1650000, ['Tomatoes' => 1, 'Tatashe (red bell pepper)' => 1, 'Ata rodo (scotch bonnet)' => 1, 'Onions' => 1, 'Ugu (pumpkin leaf)' => 2, 'Scent leaf' => 1]],
            ['Family Weekly Box', 'A fuller mix for a household of 4–6', 'leaf', 2950000, ['Tomatoes' => 2, 'Tatashe (red bell pepper)' => 1, 'Onions' => 2, 'Irish potatoes' => 1, 'Carrots' => 1, 'Cabbage' => 1, 'Ugu (pumpkin leaf)' => 2, 'Spinach (efo)' => 1]],
            ['Salad Box', 'Crisp, fresh and ready for the bowl', 'salad', 1200000, ['Lettuce' => 1, 'Cucumber' => 1, 'Carrots' => 1, 'Cabbage' => 1, 'Green pepper' => 1]],
        ];

        $boxModels = [];
        foreach ($boxes as $i => [$name, $tagline, $accent, $price, $contents]) {
            $box = BoxTemplate::query()->create([
                'name' => $name, 'slug' => Str::slug($name), 'tagline' => $tagline,
                'description' => $tagline.'.', 'price_kobo' => $price, 'accent' => $accent, 'sort_order' => $i,
            ]);
            $box->items()->attach(collect($contents)->mapWithKeys(fn ($qty, $item) => [$items[$item]->id => ['quantity' => $qty]])->all());
            $boxModels[] = $box;
        }

        $addOns = [
            AddOn::query()->create(['item_id' => $items['Tomatoes']->id, 'name' => 'Extra tomatoes', 'quantity' => 1, 'price_kobo' => 550000, 'sort_order' => 0]),
            AddOn::query()->create(['item_id' => $items['Ata rodo (scotch bonnet)']->id, 'name' => 'Extra pepper', 'quantity' => 1, 'price_kobo' => 300000, 'sort_order' => 1]),
            AddOn::query()->create(['item_id' => $items['Onions']->id, 'name' => 'Extra onions', 'quantity' => 1, 'price_kobo' => 250000, 'sort_order' => 2]),
        ];

        // Staff accounts for local testing. Each must set up 2FA on first console sign-in.
        foreach ([['Founder Admin', 'admin@josveggies.test', Role::Admin], ['Jos Ops', 'jos@josveggies.test', Role::JosOps], ['Abuja Ops', 'abuja@josveggies.test', Role::AbujaOps]] as [$name, $email, $role]) {
            $user = new User(['name' => $name, 'email' => $email, 'password' => Hash::make('password1234')]);
            $user->role = $role;
            $user->email_verified_at = now();
            $user->save();
        }

        $customer = new User(['name' => 'Ada Customer', 'email' => 'customer@josveggies.test', 'phone' => '2348031234567', 'password' => Hash::make('password1234')]);
        $customer->email_verified_at = now();
        $customer->save();

        Rider::query()->create(['name' => 'Musa (Rider)', 'phone' => '2349011112222', 'company' => 'Swift Riders']);
        Rider::query()->create(['name' => 'Chinedu (Rider)', 'phone' => '2349033334444', 'company' => 'Swift Riders']);

        // An open Run (orders closing in 2 days) and the next scheduled Run.
        $open = new Run([
            'code' => 'RUN-'.now()->format('Y').'-001', 'hub_id' => $hub->id, 'slot_capacity' => 30, 'break_even_boxes' => 18,
            'opens_at' => now()->subHours(6), 'closes_at' => now()->addDays(2)->setTime(20, 0),
            'market_day' => now()->addDays(5)->startOfDay(), 'delivery_starts_at' => now()->addDays(5)->setTime(18, 0),
            'internal_notes' => 'Farmers: Mama Ngozi (tomatoes), Bitrus (leafy greens). Cargo: premium saloon, 6:30am park check-in.',
        ]);
        $open->state = RunState::Open;
        $open->save();
        $open->boxTemplates()->attach(collect($boxModels)->pluck('id'));
        $open->addOns()->attach(collect($addOns)->pluck('id'));

        $next = new Run([
            'code' => 'RUN-'.now()->format('Y').'-002', 'hub_id' => $hub->id, 'slot_capacity' => 30, 'break_even_boxes' => 18,
            'opens_at' => now()->addDays(6)->setTime(8, 0), 'closes_at' => now()->addDays(8)->setTime(20, 0),
            'market_day' => now()->addDays(11)->startOfDay(), 'delivery_starts_at' => now()->addDays(12)->setTime(9, 0),
        ]);
        $next->state = RunState::Scheduled;
        $next->save();
        $next->boxTemplates()->attach(collect($boxModels)->pluck('id'));
        $next->addOns()->attach(collect($addOns)->pluck('id'));
    }
}
