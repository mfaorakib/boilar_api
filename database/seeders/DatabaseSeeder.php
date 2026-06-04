<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingsTableSeeder::class);
        $this->command->info('Settings table seeded!');

        $this->call(UsersTableSeeder::class);
        $this->command->info('Users table seeded!');

        $this->call(MenusTableSeeder::class);
        $this->command->info('Menus table seeded!');

        $this->call(AreasTableSeeder::class);
        $this->command->info('Areas / Divisions / Districts seeded!');

        $this->call(OrderStatusesTableSeeder::class);
        $this->command->info('Order statuses seeded!');

        $this->call(PaymentStatusesTableSeeder::class);
        $this->command->info('Payment statuses seeded!');

        $this->call(PaymentMethodsTableSeeder::class);
        $this->command->info('Payment methods seeded!');

        $this->call(ShippingProvidersTableSeeder::class);
        $this->command->info('Shipping providers seeded!');
    }
}
