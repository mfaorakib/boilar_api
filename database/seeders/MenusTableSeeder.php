<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('menus')->delete();
        $menus = [
            [
                'title' => 'Product',
                'icon' => 'mdi-store-24-hour',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Product',
                        'icon' => 'mdi-basket',
                        'link' => '/product/products',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Product Trashed',
                        'icon' => 'mdi-trash-can',
                        'link' => '/product/products-trash',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Category',
                        'icon' => 'mdi-shopping',
                        'link' => '/product/categories',
                        'status' => 'active',
                    ],
                ]
            ],
            [
                'title' => 'Order',
                'icon' => 'mdi-order-numeric-ascending',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Orders',
                        'icon' => 'mdi-order-numeric-descending',
                        'link' => '/order/orders',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Order Status',
                        'icon' => 'mdi-label',
                        'link' => '/order/statuses',
                        'status' => 'active',
                    ],
                ]
            ],
            [
                'title' => 'Marketing',
                'icon' => 'mdi-bullhorn',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Coupons',
                        'icon' => 'mdi-cards',
                        'link' => '/marketing/coupons',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Branches',
                        'icon' => 'mdi-source-branch',
                        'link' => '/marketing/branches',
                        'status' => 'active',
                    ]
                ]
            ],
            [
                'title' => 'Common',
                'icon' => 'mdi-forum',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Filters',
                        'icon' => 'mdi-forum',
                        'link' => '/common/filters',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Tags',
                        'icon' => 'mdi-label',
                        'link' => '/common/tags',
                        'status' => 'active',
                    ]
                ]
            ],
            [
                'title' => 'Shipping',
                'icon' => 'mdi-truck',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Provider',
                        'icon' => 'mdi-truck',
                        'link' => '/shipping/providers',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Division',
                        'icon' => 'mdi-map-marker',
                        'link' => '/shipping/divisions',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'District',
                        'icon' => 'mdi-map-marker',
                        'link' => '/shipping/districts',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Area/ Thana',
                        'icon' => 'mdi-map-marker',
                        'link' => '/shipping/areas',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Shipping Cost',
                        'icon' => 'mdi-currency-bdt',
                        'link' => '/shipping/costs',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Shipping Cost Tree View',
                        'icon' => 'mdi-currency-bdt',
                        'link' => '/shipping/cost-trees',
                        'status' => 'active',
                    ],
                ]
            ],
            [
                'title' => 'Payment',
                'icon' => 'mdi-credit-card-outline',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Payment Status',
                        'icon' => 'mdi-label',
                        'link' => '/payment/statuses',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Payment Method',
                        'icon' => 'mdi-credit-card-outline',
                        'link' => '/payment/payment-methods',
                        'status' => 'active',
                    ]
                ]
            ],
            [
                'title' => 'Home',
                'icon' => 'mdi-home',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Main Sliders',
                        'icon' => 'mdi-folder-multiple-image',
                        'link' => '/home/sliders',
                        'status' => 'active',
                    ]
                ]
            ],
            [
                'title' => 'Users',
                'icon' => 'mdi-account-multiple',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Customers',
                        'icon' => 'mdi-account-multiple',
                        'link' => '/customers',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Users',
                        'icon' => 'mdi-account-multiple',
                        'link' => '/users',
                        'status' => 'active',
                    ]
                ]
            ],
            [
                'title' => 'Reports',
                'icon' => 'mdi-poll-box',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Sales',
                        'icon' => 'mdi-cards',
                        'link' => '/reports/sales',
                        'status' => 'active',
                    ]
                ]
            ],
            [
                'title' => 'Roles',
                'icon' => 'mdi-lock',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Roles',
                        'icon' => 'mdi-lock',
                        'link' => '/roles',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Role wise Permissions',
                        'icon' => 'mdi-lock',
                        'link' => '/role-permissions',
                        'status' => 'active',
                    ]
                ]
            ],
            [
                'title' => 'Media Library',
                'icon' => 'mdi-folder',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Assets',
                        'icon' => 'mdi-file',
                        'link' => '/media/assets',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Category',
                        'icon' => 'mdi-folder',
                        'link' => '/media/categories',
                        'status' => 'active',
                    ],
                ]
            ],
            [
                'title' => 'Administrations',
                'icon' => 'mdi-lock',
                'link' => null,
                'status' => 'active',
                'children' => [
                    [
                        'title' => 'Menus',
                        'icon' => 'mdi-menu',
                        'link' => '/administrations/menus',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Settings',
                        'icon' => 'mdi-cogs',
                        'link' => '/administrations/settings',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Activities Log',
                        'icon' => 'mdi-history',
                        'link' => '/administrations/activity-log',
                        'status' => 'active',
                    ],
                    [
                        'title' => 'Cache Management',
                        'icon' => 'mdi-folder',
                        'link' => '/administrations/cache-management',
                        'status' => 'active',
                    ],
                ]
            ],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }

        $items = Menu::all();
        foreach ($items as $item) {
            $item->roles()->attach([1, 2]);
        }
    }
}
