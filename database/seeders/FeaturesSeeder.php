<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $features = [
        ['name' => 'Manage Shipment', 'slug' => 'manage-shipment', 'description' => 'manage shipment'],
        ['name' => 'Manage Customers', 'slug' => 'manage-customers', 'description' => 'Manage Customers'],
        ['name' => 'Manage Drivers', 'slug' => 'manage-drivers', 'description' => 'Manage Drivers'],

        ['name' => 'Manage Trucks', 'slug' => 'manage-trucks', 'description' => 'Manage Trucks'],
        ['name' => 'Manage Users', 'slug' => 'manage-users', 'description' => 'Manage Users'],

         ['name' => 'Track Shipment', 'slug' => 'track-shipment', 'description' => 'Track Shipment'], ['name' => 'Change Copyright', 'slug' => 'change-copyright', 'description' => 'Change Copyright'],
          ['name' => 'Invoice Logo', 'slug' => 'invoice-logo', 'description' => 'Invoice Logo'],
        ['name' => 'App Logo', 'slug' => 'app-logo', 'description' => 'App Logo'], 
        ['name' => 'Payment Account Setup', 'slug' => 'payment-account-setup', 'description' => 'Payment Account Setup'],
        ['name' => 'Payment Gateway Setup', 'slug' => 'payment-gateway-setup', 'description' => 'Payment Gateway Setup'], 
        ['name' => 'Private Email Setup', 'slug' => 'private-email-setup', 'description' => 'Private Email Setup'],
        ['name' => 'Manage Bike', 'slug' => 'manage-bike', 'description' => 'Manage Bike'],
        ['name' => 'Manage Bike Delivery', 'slug' => 'manage-bike-delivery', 'description' => 'Manage Bike Delivery'],
        ['name' => 'Manage Settlement', 'slug' => 'manage-settlement', 'description' => 'Manage Settlement'],
        ['name' => 'Manage Carriers', 'slug' => 'manage-carriers', 'description' => 'Manage Carriers'],
        ['name' => 'Bike Delivery Base Fee', 'slug' => 'bike-delivery-base-fee', 'description' => 'Bike Delivery Base Fee'],
        ['name' => 'Handling Fee', 'slug' => 'handling-fee', 'description' => 'Handling Fee'],
    ];

    foreach ($features as $feature) {
        Feature::create($feature);
    }

   
    }
}
