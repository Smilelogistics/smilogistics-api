<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
    //     $this->call(LaratrustSeeder::class);
    //    $this->call(FeaturesSeeder::class);
    //    $this->call(PlansSeeder::class);
       $user = User::factory()->create([
            'fname' => 'Dev',
            'email' => 'codedkolobanny@gmail.com',
            'user_type' => 'superadministrator',
             'password' => Hash::make('123456789'),
        ]);
        if($user)
        {
            SuperAdmin::create([
                     'user_id' => $user->id
                 ]);

                 $user->addRole($user->user_type);
        }
    }
}
