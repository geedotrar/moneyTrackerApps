<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $balances = Balance::with('user', 'financialAccount')->get();

            if ($balances->isEmpty()) {
                return $this->responseJson(404, 'Financial Account Not Found');
            }

            // Grouping balances by user_id
            $responseData = $balances->groupBy('user_id')->map(function ($userBalances) {
                $user = $userBalances->first()->user;

                // Grouping by 'financial_account_id' and summing the 'amount'
                $financialAccounts = $userBalances->groupBy(function ($balance) {
                    return $balance->financialAccount->name;
                })->map(function ($groupedBalances, $accountName) {
                    $financialAccount = $groupedBalances->first()->financialAccount;
                    $totalAmount = $groupedBalances->sum('amount');

                    return [
                        'id' => $financialAccount->id,
                        'name' => $accountName,
                        'amount' => $totalAmount,
                        'created_at' => $financialAccount->created_at,
                        'updated_at' => $financialAccount->updated_at,
                    ];
                });

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'deleted_at' => $user->deleted_at,
                    'financial_accounts' => $financialAccounts,
                ];
            });

            return $this->responseJson(200, 'Get Amounts Successfully', $responseData->values()->all());

        } catch (Exception $e) {
            return $this->responseJson(500, 'An Error Occurred', $e->getMessage());
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user->hasRole('admin') && $user->id !== (int) $id) {
                return $this->responseJson(403, 'Forbidden');
            }

            $user = User::findOrFail($id);

            $balances = Balance::with('financialAccount')
                ->where('user_id', $user->id)
                ->get();

            if ($balances->isEmpty()) {
                return $this->responseJson(404, 'Financial Account Not Found');
            }

            // Grouping dan penjumlahan saldo (sama seperti sebelumnya)
            $financialAccounts = $balances->groupBy(function ($balance) {
                return $balance->financialAccount->name;
            })->map(function ($groupedBalances, $accountName) {
                // Sum the amount for grouped financial accounts
                $financialAccount = $groupedBalances->first()->financialAccount;
                $totalAmount = $groupedBalances->sum('amount');

                return [
                    'id' => $financialAccount->id,
                    'name' => $accountName,
                    'amount' => $totalAmount,
                    'created_at' => $financialAccount->created_at,
                    'updated_at' => $financialAccount->updated_at,
                ];
            });

            return $this->responseJson(200, 'Get Amounts Successfully', [
                'user' => $user,
                'financial_accounts' => $financialAccounts
            ]);

        } catch (Exception $e) {
            return $this->responseJson(500, 'An Error Occurred', $e->getMessage());
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $income = Balance::findOrFail($id);

            $income->delete();
            
            return $this->responseJson(200, 'Income deleted successfully', $income);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Income Not Found');
        } catch (Exception $e) {
            return $this->responseJson(500, 'An error occurred', $e->getMessage());
        } 
    }

    private function responseJson(int $status, string $message, $data = null)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
