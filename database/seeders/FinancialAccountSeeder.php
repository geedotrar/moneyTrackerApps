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
            ],
            [
                'name' => 'BNI',
            ],
        ];

        foreach ($financialAccount as $financialAcc) {
            if (FinancialAccount::where('name', $financialAcc['name'])->doesntExist()) {
                FinancialAccount::create($financialAcc);
            }
        }
    }
}
