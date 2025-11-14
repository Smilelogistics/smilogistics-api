<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Branch;
use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(LaratrustSeeder::class);
       $this->call(FeaturesSeeder::class);
       $this->call(PlansSeeder::class);
    //    $user = User::factory()->create([
    //         'fname' => 'Dev',
    //         'email' => 'codedkolobanny@gmail.com',
    //         'user_type' => 'superadministrator',
    //          'password' => Hash::make('123456789'),
    //     ]);
      DB::transaction(function () {

            // Create Super Admin
            $user = User::firstOrCreate(
                ['email' => 'superadmin@smileslogistics.com'],
                [
                    'fname' => 'superadmin',
                    'user_type' => 'superadministrator',
                    'password' => Hash::make('123456789'),
                ]
            );

            // Create Branch Administrator
            $user_branch = User::firstOrCreate(
                ['email' => 'codedkolobanny@gmail.com'],
                [
                    'fname' => 'Dev',
                    'user_type' => 'businessadministrator',
                    'password' => Hash::make('123456789'),
                ]
            );

            if ($user) {
                SuperAdmin::firstOrCreate([
                    'user_id' => $user->id,
                ]);

                $user->addRole($user->user_type);
            }

            if ($user_branch) {
                Branch::firstOrCreate([
                    'user_id' => $user_branch->id,
                ], [
                    'branch_code' => 'SML-' . $user_branch->id,
                    'phone' => '08000000000',
                    'address' => 'No address provided',
                    'about_us' => null,
                ]);

                $user_branch->addRole($user_branch->user_type);
            }
        });
    }
}
