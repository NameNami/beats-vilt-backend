<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Reward;

class RewardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $rewards = [
            [
                'name'        => 'Free Printing (10 pages)',
                'cost_points' => 50,
                'stock'       => 100,
                'is_active'   => true,
            ],
            [
                'name'        => 'Cafeteria Voucher (RM5)',
                'cost_points' => 100,
                'stock'       => 50,
                'is_active'   => true,
            ],
            [
                'name'        => 'Bookstore Voucher (RM10)',
                'cost_points' => 200,
                'stock'       => 30,
                'is_active'   => true,
            ],
            [
                'name'        => 'Early Exam Registration',
                'cost_points' => 500,
                'stock'       => 20,
                'is_active'   => true,
            ],
            [
                'name'        => 'BEATS Exclusive Hoodie',
                'cost_points' => 1000,
                'stock'       => 10,
                'is_active'   => true,
            ],
        ];

        foreach ($rewards as $reward) {
            Reward::create($reward);
        }
    }
}
