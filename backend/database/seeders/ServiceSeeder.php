<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        $services = [
            [
                'name' => 'Bestseller Package',
                'slug' => Str::slug('Bestseller Package'),
                'description' => 'Full Service (Branded) - Wash, Dry & Fold with 2 sachets Ariel/Breeze, 2 sachets Downy, and 2 sachets Zonrox Colorsafe',
                'price_per_kg' => 200.00,
                'price_per_load' => 200.00,
                'pricing_type' => 'per_load',
                'min_weight' => 0.1,
                'max_weight' => 8.0,
                'turnaround_time' => 24,
                'service_type' => 'full_service',
                'icon_path' => 'services/bestseller.png',
                'is_active' => true,
            ],
            [
                'name' => 'Regular Package',
                'slug' => Str::slug('Regular Package'),
                'description' => 'Wash, Dry & Fold - up to 8kgs per load for regular clothes, free laundry detergent & free branded Del fabric conditioner',
                'price_per_kg' => 200.00,
                'price_per_load' => 200.00,
                'pricing_type' => 'per_load',
                'min_weight' => 0.1,
                'max_weight' => 8.0,
                'turnaround_time' => 24,
                'service_type' => 'full_service',
                'icon_path' => 'services/regular.png',
                'is_active' => true,
            ],
            [
                'name' => 'Premium Package',
                'slug' => Str::slug('Premium Package'),
                'description' => 'Wash, Dry & Fold - up to 8kgs per load, 2 sachets Ariel/Breeze, 2 sachets Del fabric conditioner, 60ml Zonrox colorsafe',
                'price_per_kg' => 220.00,
                'price_per_load' => 220.00,
                'pricing_type' => 'per_load',
                'min_weight' => 0.1,
                'max_weight' => 8.0,
                'turnaround_time' => 24,
                'service_type' => 'full_service',
                'icon_path' => 'services/premium.png',
                'is_active' => true,
            ],
            [
                'name' => 'Small Comforter/Blanket',
                'slug' => Str::slug('Small Comforter/Blanket'),
                'description' => 'Small comforter or heavy/thick blanket - per piece service including detergent & fabcon',
                'price_per_kg' => 0.00,
                'price_per_load' => 150.00,
                'pricing_type' => 'per_load',
                'min_weight' => null,
                'max_weight' => null,
                'turnaround_time' => 48,
                'service_type' => 'special_item',
                'icon_path' => 'services/comforter.png',
                'is_active' => true,
            ],
            [
                'name' => 'Medium Comforter/Blanket',
                'slug' => Str::slug('Medium Comforter/Blanket'),
                'description' => 'Medium comforter or heavy/thick blanket - per piece service including detergent & fabcon',
                'price_per_kg' => 0.00,
                'price_per_load' => 180.00,
                'pricing_type' => 'per_load',
                'min_weight' => null,
                'max_weight' => null,
                'turnaround_time' => 48,
                'service_type' => 'special_item',
                'icon_path' => 'services/comforter.png',
                'is_active' => true,
            ],
            [
                'name' => 'Large Comforter/Blanket',
                'slug' => Str::slug('Large Comforter/Blanket'),
                'description' => 'Large comforter or heavy/thick blanket - per piece service including detergent & fabcon',
                'price_per_kg' => 0.00,
                'price_per_load' => 200.00,
                'pricing_type' => 'per_load',
                'min_weight' => null,
                'max_weight' => null,
                'turnaround_time' => 48,
                'service_type' => 'special_item',
                'icon_path' => 'services/comforter.png',
                'is_active' => true,
            ],
            [
                'name' => 'Self-Service Wash Only',
                'slug' => Str::slug('Self-Service Wash Only'),
                'description' => 'Wash only - per load (customer operates the machine)',
                'price_per_kg' => 70.00,
                'price_per_load' => 70.00,
                'pricing_type' => 'per_load',
                'min_weight' => null,
                'max_weight' => null,
                'turnaround_time' => 2,
                'service_type' => 'self_service',
                'icon_path' => 'services/wash.png',
                'is_active' => true,
            ],
            [
                'name' => 'Self-Service Dry Only',
                'slug' => Str::slug('Self-Service Dry Only'),
                'description' => 'Dry only - per load (customer operates the machine)',
                'price_per_kg' => 70.00,
                'price_per_load' => 70.00,
                'pricing_type' => 'per_load',
                'min_weight' => null,
                'max_weight' => null,
                'turnaround_time' => 2,
                'service_type' => 'self_service',
                'icon_path' => 'services/dry.png',
                'is_active' => true,
            ],
            [
                'name' => 'Self-Service Fold Only',
                'slug' => Str::slug('Self-Service Fold Only'),
                'description' => 'Fold only - per load (customer folds their own clothes)',
                'price_per_kg' => 30.00,
                'price_per_load' => 30.00,
                'pricing_type' => 'per_load',
                'min_weight' => null,
                'max_weight' => null,
                'turnaround_time' => 1,
                'service_type' => 'self_service',
                'icon_path' => 'services/fold.png',
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['slug' => $service['slug']],
                $service
            );
        }





        $this->command->info('Services and add-ons created successfully!');
    }
}
