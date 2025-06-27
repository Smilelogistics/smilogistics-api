<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Now seed Plans
        $plans = [
            [
                'name' => 'Basic',
                'level' => 1,
                'slug' => 'basic',
                'price' => 49.99,
                'interval' => 'monthly',
                'description' => 'Basic plan with limited features',
                'features' => [1, 2] // Using IDs of Basic Dashboard and Limited Reports
            ],
            [
                'name' => 'Standard',
                'level' => 2,
                'slug' => 'standard',
                'price' => 99.99,
                'interval' => 'monthly',
                'description' => 'Standard plan with limited features',
                'features' => [3, 4] // Standard Analytics and Advanced Reports
            ],
            [
                'name' => 'Premium',
                'level' => 3,
                'slug' => 'premium',
                'price' => 119.99,
                'interval' => 'monthly',
                'description' => 'Premium plan with all features',
                'features' => [5, 6, 7, 8] // All premium features
            ]
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);
            
            $plan = Plan::create($planData);
            $plan->features()->attach($features);
        }
    }
}
