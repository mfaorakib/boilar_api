<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderStatusesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('order_status_translations')->delete();
        DB::table('order_statuses')->delete();

        $statuses = [
            ['code' => 'pending',    'color' => '#FFC107', 'name' => 'Pending',    'description' => 'Order is awaiting processing.'],
            ['code' => 'processing', 'color' => '#2196F3', 'name' => 'Processing', 'description' => 'Order is being processed.'],
            ['code' => 'shipped',    'color' => '#9C27B0', 'name' => 'Shipped',    'description' => 'Order has been shipped.'],
            ['code' => 'delivered',  'color' => '#4CAF50', 'name' => 'Delivered',  'description' => 'Order has been delivered.'],
            ['code' => 'cancelled',  'color' => '#F44336', 'name' => 'Cancelled',  'description' => 'Order has been cancelled.'],
            ['code' => 'returned',   'color' => '#FF5722', 'name' => 'Returned',   'description' => 'Order has been returned.'],
        ];

        foreach ($statuses as $i => $status) {
            $id = DB::table('order_statuses')->insertGetId([
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

            DB::table('order_status_translations')->insert([
                'order_status_id' => $id,
                'locale'          => 'en',
                'name'            => $status['name'],
                'description'     => $status['description'],
            ]);
        }
    }
}
