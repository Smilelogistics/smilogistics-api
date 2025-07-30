<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         Schema::disableForeignKeyConstraints();
    DB::table('plans')->truncate(); // Deletes all rows and resets auto-increment ID
    Schema::enableForeignKeyConstraints();
         // Now seed Plans
        $plans = [
            [
                'name' => 'Basic',
                'level' => 1,
                'slug' => 'basic',
                'price' => 50,
                'interval' => 'monthly',
                'description' => 'Basic plan with limited features',
                'features' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12] // Using IDs of Basic Dashboard and Limited Reports
            ],
            [
                'name' => 'Standard',
                'level' => 2,
                'slug' => 'standard',
                'price' => 100,
                'interval' => 'monthly',
                'description' => 'Standard plan with limited features',
                'features' => [13, 14, 15, 16] // Standard Analytics and Advanced Reports
            ],
            [
                'name' => 'Premium',
                'level' => 3,
                'slug' => 'premium',
                'price' => 120,
                'interval' => 'monthly',
                'description' => 'Premium plan with all features',
                'features' => [17, 18] // All premium features
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
