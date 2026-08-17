<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction\PaymentType;
use App\Models\Transaction\Order;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = Order::getPaymentTypeLabels();

        foreach ($types as $code => $name) {
            PaymentType::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'is_active' => true,
                ]
            );
        }
    }
}
