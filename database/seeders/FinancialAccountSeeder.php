<?php

namespace Database\Seeders;

use App\Models\FinancialAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FinancialAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $financialAccount = [
            [
                'name' => 'BCA',
                'balance' => 1000000,
            ],
            [
                'name' => 'BNI',
                'balance' => 2000000
            ],
        ];

        foreach ($financialAccount as $financialAcc) {
            if (FinancialAccount::where('name', $financialAcc['name'])->doesntExist()) {
                FinancialAccount::create($financialAcc);
            }
        }
    }
}
