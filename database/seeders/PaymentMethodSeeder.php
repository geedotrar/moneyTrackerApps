<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payemtnMethods = [
            [
                'name' => 'BCA',
            ],
            [
                'name' => 'BNI',
            ],
        ];

        foreach ($payemtnMethods as $paymentMethod) {
            if (PaymentMethod::where('name', $paymentMethod['name'])->doesntExist()) {
                PaymentMethod::create($paymentMethod);
            }
        }
    }
}
