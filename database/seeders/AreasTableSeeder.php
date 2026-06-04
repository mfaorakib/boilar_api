<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AreasTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // seed division
        DB::unprepared(File::get(public_path('assets/divisions.sql')));
        $this->command->info('Division table seeded!');
        // seed district
        DB::unprepared(File::get(public_path('assets/districts.sql')));
        $this->command->info('District table seeded!');
        // seed area
        DB::unprepared(File::get(public_path('assets/areas.sql')));
        $this->command->info('Area table seeded!');
    }
}
