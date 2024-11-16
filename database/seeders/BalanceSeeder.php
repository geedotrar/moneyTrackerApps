<?php

namespace Database\Seeders;

use App\Models\Balance;
use Illuminate\Database\Seeder;

class BalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $balances = [
            [
                'user_id' => 1,
                'financial_account_id' => 1,
                'amount' => 90000000
            ],
            [
                'user_id' => 1,
                'financial_account_id' => 2,
                'amount' => 90000000
            ],
            [
                'user_id' => 2,
                'financial_account_id' => 1,
                'amount' => 2000000
            ],
            [
                'user_id' => 2,
                'financial_account_id' => 2,
                'amount' => 2000000
            ],
        ];

        foreach ($balances as $abalance) {
                Balance::create($abalance);
        }
    }
}
