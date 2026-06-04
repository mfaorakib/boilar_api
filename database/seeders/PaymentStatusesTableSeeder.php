<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentStatusesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('payment_status_translations')->delete();
        DB::table('payment_statuses')->delete();

        $statuses = [
            ['code' => 'pending',   'color' => '#FFC107', 'name' => 'Pending',   'description' => 'Payment is pending.'],
            ['code' => 'paid',      'color' => '#4CAF50', 'name' => 'Paid',      'description' => 'Payment completed.'],
            ['code' => 'failed',    'color' => '#F44336', 'name' => 'Failed',    'description' => 'Payment failed.'],
            ['code' => 'refunded',  'color' => '#FF9800', 'name' => 'Refunded',  'description' => 'Payment has been refunded.'],
            ['code' => 'cancelled', 'color' => '#9E9E9E', 'name' => 'Cancelled', 'description' => 'Payment was cancelled.'],
        ];

        foreach ($statuses as $i => $status) {
            $id = DB::table('payment_statuses')->insertGetId([
                'status'     => 'active',
                'code'       => $status['code'],
                'color'      => $status['color'],
                'lft'        => $i * 2 + 1,
                'rgt'        => $i * 2 + 2,
                'depth'      => 0,
                'parent_id'  => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('payment_status_translations')->insert([
                'payment_status_id' => $id,
                'locale'            => 'en',
                'name'              => $status['name'],
                'description'       => $status['description'],
            ]);
        }
    }
}
