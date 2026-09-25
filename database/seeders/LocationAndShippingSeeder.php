<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Courier;
use App\Models\District;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class LocationAndShippingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Provinces & Districts & Major Cities
        $provincesData = [
            'Western' => [
                'code' => 'WP',
                'districts' => [
                    'Colombo' => [
                        ['name' => 'Colombo 01 (Fort)', 'postal_code' => '00100'],
                        ['name' => 'Colombo 02 (Slave Island)', 'postal_code' => '00200'],
                        ['name' => 'Colombo 03 (Kollupitiya)', 'postal_code' => '00300'],
                        ['name' => 'Colombo 04 (Bambalapitiya)', 'postal_code' => '00400'],
                        ['name' => 'Colombo 05 (Havelock Town)', 'postal_code' => '00500'],
                        ['name' => 'Colombo 06 (Wellawatte)', 'postal_code' => '00600'],
                        ['name' => 'Colombo 07 (Cinnamon Gardens)', 'postal_code' => '00700'],
                        ['name' => 'Colombo 08 (Borella)', 'postal_code' => '00800'],
                        ['name' => 'Colombo 09 (Dematagoda)', 'postal_code' => '00900'],
                        ['name' => 'Colombo 10 (Maradana)', 'postal_code' => '01000'],
                        ['name' => 'Colombo 11 (Pettah)', 'postal_code' => '01100'],
                        ['name' => 'Colombo 12 (Hultsdorf)', 'postal_code' => '01200'],
                        ['name' => 'Colombo 13 (Kotahena)', 'postal_code' => '01300'],
                        ['name' => 'Colombo 14 (Grandpass)', 'postal_code' => '01400'],
                        ['name' => 'Colombo 15 (Mutwal)', 'postal_code' => '01500'],
                        ['name' => 'Dehiwala', 'postal_code' => '10350'],
                        ['name' => 'Mount Lavinia', 'postal_code' => '10370'],
                        ['name' => 'Moratuwa', 'postal_code' => '10400'],
                        ['name' => 'Sri Jayawardenepura Kotte', 'postal_code' => '10100'],
                        ['name' => 'Nugegoda', 'postal_code' => '10250'],
                        ['name' => 'Maharagama', 'postal_code' => '10280'],
                        ['name' => 'Kottawa', 'postal_code' => '10230'],
                        ['name' => 'Homagama', 'postal_code' => '10200'],
                        ['name' => 'Battaramulla', 'postal_code' => '10120'],
                        ['name' => 'Malabe', 'postal_code' => '10115'],
                        ['name' => 'Rajagiriya', 'postal_code' => '10107'],
                        ['name' => 'Pannipitiya', 'postal_code' => '10230'],
                        ['name' => 'Athurugiriya', 'postal_code' => '10150'],
                        ['name' => 'Kesbewa', 'postal_code' => '10300'],
                        ['name' => 'Piliyandala', 'postal_code' => '10300'],
                    ],
                    'Gampaha' => [
                        ['name' => 'Gampaha', 'postal_code' => '11000'],
                        ['name' => 'Negombo', 'postal_code' => '11500'],
                        ['name' => 'Kelaniya', 'postal_code' => '11600'],
                        ['name' => 'Kiribathgoda', 'postal_code' => '11600'],
                        ['name' => 'Kadawatha', 'postal_code' => '11850'],
                        ['name' => 'Ja-Ela', 'postal_code' => '11350'],
                        ['name' => 'Wattala', 'postal_code' => '11300'],
                        ['name' => 'Kandana', 'postal_code' => '11320'],
                        ['name' => 'Minuwangoda', 'postal_code' => '11550'],
                        ['name' => 'Mirigama', 'postal_code' => '11200'],
                        ['name' => 'Nittambuwa', 'postal_code' => '11880'],
                        ['name' => 'Ragama', 'postal_code' => '11010'],
                        ['name' => 'Seeduwa', 'postal_code' => '11410'],
                    ],
                    'Kalutara' => [
                        ['name' => 'Kalutara', 'postal_code' => '12000'],
                        ['name' => 'Panadura', 'postal_code' => '12500'],
                        ['name' => 'Horana', 'postal_code' => '12400'],
                        ['name' => 'Beruwala', 'postal_code' => '12070'],
                        ['name' => 'Aluthgama', 'postal_code' => '12080'],
                        ['name' => 'Matugama', 'postal_code' => '12100'],
                        ['name' => 'Wadduwa', 'postal_code' => '12560'],
                        ['name' => 'Bandaragama', 'postal_code' => '12530'],
                    ],
                ],
            ],
            'Central' => [
                'code' => 'CP',
                'districts' => [
                    'Kandy' => [
                        ['name' => 'Kandy City', 'postal_code' => '20000'],
                        ['name' => 'Peradeniya', 'postal_code' => '20400'],
                        ['name' => 'Katugastota', 'postal_code' => '20800'],
                        ['name' => 'Gampola', 'postal_code' => '20500'],
                        ['name' => 'Kundasale', 'postal_code' => '20168'],
                        ['name' => 'Akurana', 'postal_code' => '20850'],
                        ['name' => 'Digana', 'postal_code' => '20180'],
                    ],
                    'Matale' => [
                        ['name' => 'Matale', 'postal_code' => '21000'],
                        ['name' => 'Dambulla', 'postal_code' => '21100'],
                        ['name' => 'Galewela', 'postal_code' => '21200'],
                        ['name' => 'Sigiriya', 'postal_code' => '21120'],
                    ],
                    'Nuwara Eliya' => [
                        ['name' => 'Nuwara Eliya', 'postal_code' => '22200'],
                        ['name' => 'Hatton', 'postal_code' => '22000'],
                        ['name' => 'Talawakelle', 'postal_code' => '22100'],
                        ['name' => 'Ginigathena', 'postal_code' => '22010'],
                    ],
                ],
            ],
            'Southern' => [
                'code' => 'SP',
                'districts' => [
                    'Galle' => [
                        ['name' => 'Galle City', 'postal_code' => '80000'],
                        ['name' => 'Karapitiya', 'postal_code' => '80000'],
                        ['name' => 'Hikkaduwa', 'postal_code' => '80240'],
                        ['name' => 'Ambalangoda', 'postal_code' => '80300'],
                        ['name' => 'Elpitiya', 'postal_code' => '80400'],
                        ['name' => 'Baddegama', 'postal_code' => '80100'],
                    ],
                    'Matara' => [
                        ['name' => 'Matara City', 'postal_code' => '81000'],
                        ['name' => 'Weligama', 'postal_code' => '81700'],
                        ['name' => 'Akuressa', 'postal_code' => '81400'],
                        ['name' => 'Dickwella', 'postal_code' => '81170'],
                    ],
                    'Hambantota' => [
                        ['name' => 'Hambantota', 'postal_code' => '82000'],
                        ['name' => 'Tangalle', 'postal_code' => '82200'],
                        ['name' => 'Beliatta', 'postal_code' => '82400'],
                        ['name' => 'Tissamaharama', 'postal_code' => '82600'],
                    ],
                ],
            ],
            'North Western' => [
                'code' => 'NWP',
                'districts' => [
                    'Kurunegala' => [
                        ['name' => 'Kurunegala', 'postal_code' => '60000'],
                        ['name' => 'Kuliyapitiya', 'postal_code' => '60200'],
                        ['name' => 'Narammala', 'postal_code' => '60100'],
                        ['name' => 'Wariyapola', 'postal_code' => '60400'],
                        ['name' => 'Pannala', 'postal_code' => '60160'],
                    ],
                    'Puttalam' => [
                        ['name' => 'Puttalam', 'postal_code' => '61300'],
                        ['name' => 'Chilaw', 'postal_code' => '61000'],
                        ['name' => 'Wennappuwa', 'postal_code' => '61170'],
                        ['name' => 'Marawila', 'postal_code' => '61100'],
                        ['name' => 'Anamaduwa', 'postal_code' => '61500'],
                    ],
                ],
            ],
            'Sabaragamuwa' => [
                'code' => 'SGP',
                'districts' => [
                    'Ratnapura' => [
                        ['name' => 'Ratnapura', 'postal_code' => '70000'],
                        ['name' => 'Embilipitiya', 'postal_code' => '70200'],
                        ['name' => 'Balangoda', 'postal_code' => '70100'],
                        ['name' => 'Pelmadulla', 'postal_code' => '70070'],
                    ],
                    'Kegalle' => [
                        ['name' => 'Kegalle', 'postal_code' => '71000'],
                        ['name' => 'Mawanella', 'postal_code' => '71500'],
                        ['name' => 'Warakapola', 'postal_code' => '71600'],
                        ['name' => 'Rambukkana', 'postal_code' => '71100'],
                    ],
                ],
            ],
            'North Central' => [
                'code' => 'NCP',
                'districts' => [
                    'Anuradhapura' => [
                        ['name' => 'Anuradhapura City', 'postal_code' => '50000'],
                        ['name' => 'Kekirawa', 'postal_code' => '50100'],
                        ['name' => 'Medawachchiya', 'postal_code' => '50500'],
                        ['name' => 'Tambuttegama', 'postal_code' => '50140'],
                    ],
                    'Polonnaruwa' => [
                        ['name' => 'Polonnaruwa', 'postal_code' => '51000'],
                        ['name' => 'Kaduruwela', 'postal_code' => '51000'],
                        ['name' => 'Hingurakgoda', 'postal_code' => '51400'],
                    ],
                ],
            ],
            'Uva' => [
                'code' => 'UP',
                'districts' => [
                    'Badulla' => [
                        ['name' => 'Badulla', 'postal_code' => '90000'],
                        ['name' => 'Bandarawela', 'postal_code' => '90100'],
                        ['name' => 'Haputale', 'postal_code' => '90160'],
                        ['name' => 'Mahiyanganaya', 'postal_code' => '90700'],
                        ['name' => 'Welimada', 'postal_code' => '90200'],
                    ],
                    'Monaragala' => [
                        ['name' => 'Monaragala', 'postal_code' => '91000'],
                        ['name' => 'Wellawaya', 'postal_code' => '91200'],
                        ['name' => 'Bibile', 'postal_code' => '91500'],
                        ['name' => 'Buttala', 'postal_code' => '91100'],
                    ],
                ],
            ],
            'Eastern' => [
                'code' => 'EP',
                'districts' => [
                    'Trincomalee' => [
                        ['name' => 'Trincomalee City', 'postal_code' => '31000'],
                        ['name' => 'Kinniya', 'postal_code' => '31100'],
                        ['name' => 'Kantale', 'postal_code' => '31300'],
                    ],
                    'Batticaloa' => [
                        ['name' => 'Batticaloa City', 'postal_code' => '30000'],
                        ['name' => 'Eravur', 'postal_code' => '30300'],
                        ['name' => 'Kattankudy', 'postal_code' => '30130'],
                        ['name' => 'Valaichchenai', 'postal_code' => '30400'],
                    ],
                    'Ampara' => [
                        ['name' => 'Ampara', 'postal_code' => '32000'],
                        ['name' => 'Kalmunai', 'postal_code' => '32300'],
                        ['name' => 'Sammanthurai', 'postal_code' => '32200'],
                        ['name' => 'Akkaraipattu', 'postal_code' => '32400'],
                    ],
                ],
            ],
            'Northern' => [
                'code' => 'NP',
                'districts' => [
                    'Jaffna' => [
                        ['name' => 'Jaffna City', 'postal_code' => '40000'],
                        ['name' => 'Chavakachcheri', 'postal_code' => '40500'],
                        ['name' => 'Nallur', 'postal_code' => '40000'],
                        ['name' => 'Point Pedro', 'postal_code' => '40600'],
                    ],
                    'Kilinochchi' => [
                        ['name' => 'Kilinochchi', 'postal_code' => '44000'],
                    ],
                    'Mannar' => [
                        ['name' => 'Mannar', 'postal_code' => '41000'],
                    ],
                    'Vavuniya' => [
                        ['name' => 'Vavuniya', 'postal_code' => '43000'],
                    ],
                    'Mullaitivu' => [
                        ['name' => 'Mullaitivu', 'postal_code' => '42000'],
                    ],
                ],
            ],
        ];

        $pOrder = 0;
        foreach ($provincesData as $pName => $pDetails) {
            $province = Province::updateOrCreate(
                ['name' => $pName],
                ['code' => $pDetails['code'], 'sort_order' => $pOrder++]
            );

            $dOrder = 0;
            foreach ($pDetails['districts'] as $dName => $cities) {
                $district = District::updateOrCreate(
                    ['name' => $dName],
                    ['province_id' => $province->id, 'sort_order' => $dOrder++]
                );

                $cOrder = 0;
                foreach ($cities as $city) {
                    City::updateOrCreate(
                        ['district_id' => $district->id, 'name' => $city['name']],
                        ['postal_code' => $city['postal_code'], 'sort_order' => $cOrder++]
                    );
                }
            }
        }

        // 2. Shipping Zones & Methods
        $colomboZone = ShippingZone::updateOrCreate(
            ['name' => 'Colombo Metro & Suburbs'],
            [
                'base_price' => 500,
                'free_shipping_min' => 45000,
                'cod_available' => true,
                'estimated_days' => '1-2 Business Days',
                'status' => true,
            ]
        );

        $outstationZone = ShippingZone::updateOrCreate(
            ['name' => 'Islandwide Outstation'],
            [
                'base_price' => 650,
                'free_shipping_min' => 60000,
                'cod_available' => true,
                'estimated_days' => '2-3 Business Days',
                'status' => true,
            ]
        );

        // Shipping Methods
        ShippingMethod::updateOrCreate(
            ['code' => 'standard_colombo'],
            [
                'shipping_zone_id' => $colomboZone->id,
                'name' => 'Standard Delivery (Colombo & Suburbs)',
                'description' => 'Safe shockproof packaging with islandwide courier network.',
                'price' => 500,
                'estimated_days' => '1-2 Business Days',
                'cod_supported' => true,
                'status' => true,
            ]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'express_colombo'],
            [
                'shipping_zone_id' => $colomboZone->id,
                'name' => 'Express Same-Day Delivery (Colombo Only)',
                'description' => 'Priority dispatch within 6 hours across Colombo city limits.',
                'price' => 950,
                'estimated_days' => 'Same Day / Next Morning',
                'cod_supported' => true,
                'status' => true,
            ]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'standard_islandwide'],
            [
                'shipping_zone_id' => $outstationZone->id,
                'name' => 'Islandwide Standard Delivery (Outstation)',
                'description' => 'Fast delivery to any city across Sri Lanka with real-time tracking.',
                'price' => 650,
                'estimated_days' => '2-3 Business Days',
                'cod_supported' => true,
                'status' => true,
            ]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'store_pickup'],
            [
                'shipping_zone_id' => null,
                'name' => 'Store Pickup (Colombo Head Office)',
                'description' => 'Collect directly from our Colombo technician center with free testing.',
                'price' => 0,
                'estimated_days' => 'Ready within 2 Hours',
                'cod_supported' => true,
                'status' => true,
            ]
        );

        // 3. Couriers
        $couriers = [
            [
                'name' => 'Domex Courier',
                'contact_person' => 'Domex Customer Care',
                'phone' => '011 7 700 700',
                'email' => 'support@domex.lk',
                'tracking_url' => 'https://www.domex.lk/tracking?ref={TRACKING}',
            ],
            [
                'name' => 'Certis Lanka Courier',
                'contact_person' => 'Dispatch Desk',
                'phone' => '011 2 557 777',
                'email' => 'courier@certislanka.com',
                'tracking_url' => 'https://certislanka.com/track/{TRACKING}',
            ],
            [
                'name' => 'Pronto Lanka',
                'contact_person' => 'Logistics Support',
                'phone' => '011 2 505 505',
                'email' => 'info@prontolanka.com',
                'tracking_url' => 'https://pronto.lk/track?no={TRACKING}',
            ],
            [
                'name' => 'Koombiyo Delivery',
                'contact_person' => 'E-Commerce Dispatch',
                'phone' => '011 7 888 888',
                'email' => 'support@koombiyodelivery.lk',
                'tracking_url' => 'https://koombiyodelivery.lk/tracking?waybill={TRACKING}',
            ],
        ];

        foreach ($couriers as $c) {
            Courier::updateOrCreate(['name' => $c['name']], array_merge($c, ['status' => true]));
        }
    }
}
