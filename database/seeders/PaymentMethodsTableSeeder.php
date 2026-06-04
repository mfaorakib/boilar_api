<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('payment_method_translations')->delete();
        DB::table('payment_methods')->delete();

        $methods = [
            ['code' => 'cash_on_delivery', 'name' => 'Cash on Delivery', 'description' => 'Pay with cash upon delivery.', 'is_featured' => 1],
            ['code' => 'bkash',            'name' => 'bKash',            'description' => 'Pay via bKash mobile banking.', 'is_featured' => 1],
            ['code' => 'nagad',            'name' => 'Nagad',            'description' => 'Pay via Nagad mobile banking.', 'is_featured' => 0],
            ['code' => 'card',             'name' => 'Credit / Debit Card', 'description' => 'Pay with Visa, Mastercard, or AMEX.', 'is_featured' => 0],
        ];

        foreach ($methods as $i => $method) {
            $id = DB::table('payment_methods')->insertGetId([
                'status'      => 'active',
                'is_featured' => $method['is_featured'],
                'code'        => $method['code'],
                'icon'        => null,
                'image'       => null,
                'lft'         => $i * 2 + 1,
                'rgt'         => $i * 2 + 2,
                'depth'       => 0,
                'parent_id'   => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('payment_method_translations')->insert([
                'payment_method_id' => $id,
                'locale'            => 'en',
                'name'              => $method['name'],
                'description'       => $method['description'],
            ]);
        }
    }
}
