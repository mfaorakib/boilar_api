<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PermissionsTableSeeder::class);
        $this->call(RolesTableSeeder::class);

       DB::table('users')->delete();

        $super_admin1 = User::create([
            'name' => 'Spark Bring It',
            'email' => 'admin@sparkbringit.com',
            'mobile' => null,
            'password' => 'Start12345',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $super_admin2 = User::create([
            'name' => 'Masum Billah',
            'email' => 'masum@xyz.com',
            'mobile' => '+8801922483273',
            'password' => 'Start12345',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $super_admin1->assignRole('super-admin');
        $super_admin2->assignRole('super-admin');
    }
}
