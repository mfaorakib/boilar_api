<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       DB::table('settings')->truncate();

        DB::table('settings')->insert([
            [
                'key' => 'site_name',
                'category' => 'General',
                'type' => 'text',
                'label' => 'Site Name',
                'value' => 'Spark Admin Panel',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_text',
                'category' => 'General',
                'type' => 'text',
                'label' => 'Footer Text',
                'value' => 'Spark Admin Panel',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'phone',
                'category' => 'Website',
                'type' => 'text',
                'label' => 'Phone Number',
                'value' => '+880 1744716657',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'email',
                'category' => 'Website',
                'type' => 'text',
                'label' => 'Email Address',
                'value' => 'info@sparkbringit.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'favicon',
                'category' => 'Website',
                'type' => 'image',
                'label' => 'Favicon Image',
                'value' => 'null',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'logo',
                'category' => 'Website',
                'type' => 'image',
                'label' => 'Logo Image',
                'value' => 'null',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'small_logo',
                'category' => 'Website',
                'type' => 'image',
                'label' => 'Small Logo',
                'value' => 'null',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'password_edit_enabled',
                'category' => 'User',
                'type' => 'boolean',
                'label' => 'Password edit enabled?',
                'value' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
