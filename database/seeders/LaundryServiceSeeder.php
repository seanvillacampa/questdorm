<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Seeder;

class LaundryServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'code'            => 'full_service',
                'name'            => 'Full Service (Wash, Dry & Fold)',
                'weight_limit_kg' => 8.00,
                'prices' => [
                    'tenant'      => 140.00,
                    'student'     => 150.00,
                    'non_student' => 165.00,
                ],
            ],
            [
                'code'            => 'wash_only',
                'name'            => 'Wash Only',
                'weight_limit_kg' => 8.00,
                'prices' => [
                    'tenant'      => 80.00,
                    'student'     => 80.00,
                    'non_student' => 80.00,
                ],
            ],
            [
                'code'            => 'bedsheet_only',
                'name'            => 'Bedsheet / Blanket',
                'weight_limit_kg' => 5.00,
                'prices' => [
                    'tenant'      => 160.00,
                    'student'     => 160.00,
                    'non_student' => 160.00,
                ],
            ],
        ];

        foreach ($services as $data) {
            $prices = $data['prices'];
            unset($data['prices']);

            $service = Service::updateOrCreate(['code' => $data['code']], $data);

            foreach ($prices as $type => $price) {
                ServicePrice::updateOrCreate(
                    ['service_id' => $service->id, 'customer_type' => $type],
                    ['price' => $price]
                );
            }
        }
    }
}
