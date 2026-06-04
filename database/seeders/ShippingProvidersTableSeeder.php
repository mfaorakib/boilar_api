<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingProvidersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('shipping_provider_translations')->delete();
        DB::table('shipping_providers')->delete();

        $providers = [
            [
                'code'            => 'standard',
                'is_featured'     => 1,
                'has_api'         => 0,
                'package_option'  => json_encode(['document', 'parcel']),
                'delivery_option' => json_encode(['regular', 'express']),
                'name'            => 'Standard Delivery',
                'description'     => 'Regular home delivery within 3–5 business days.',
            ],
            [
                'code'            => 'express',
                'is_featured'     => 1,
                'has_api'         => 0,
                'package_option'  => json_encode(['parcel']),
                'delivery_option' => json_encode(['express']),
                'name'            => 'Express Delivery',
                'description'     => 'Urgent delivery within 24 hours.',
            ],
            [
                'code'            => 'pathao',
                'is_featured'     => 0,
                'has_api'         => 1,
                'package_option'  => json_encode(['document', 'parcel', 'large-parcel']),
                'delivery_option' => json_encode(['regular', 'on-demand']),
                'name'            => 'Pathao Courier',
                'description'     => 'Pathao API-integrated delivery service.',
            ],
        ];

        foreach ($providers as $i => $provider) {
            $id = DB::table('shipping_providers')->insertGetId([
                'status'          => 'active',
                'is_featured'     => $provider['is_featured'],
                'has_api'         => $provider['has_api'],
                'code'            => $provider['code'],
                'package_option'  => $provider['package_option'],
                'delivery_option' => $provider['delivery_option'],
                'lft'             => $i * 2 + 1,
                'rgt'             => $i * 2 + 2,
                'depth'           => 0,
                'parent_id'       => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('shipping_provider_translations')->insert([
                'shipping_provider_id' => $id,
                'locale'               => 'en',
                'name'                 => $provider['name'],
                'description'          => $provider['description'],
            ]);
        }
    }
}
