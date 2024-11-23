<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        try {
            $this->authorize('viewAny', User::class);

            $balances = Balance::with('user', 'financialAccount')->get();

            if ($balances->isEmpty()) {
                return $this->responseJson(404, 'Financial Account Not Found');
            }

            $responseData = $balances->groupBy('user_id')->map(function ($userBalances) {
                $user = $userBalances->first()->user;

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
        } catch (AuthorizationException $e) {
            return $this->responseJson(403, 'No Access');
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Resource not found');
        } catch (Exception $e) {
            return $this->responseJson(500, 'An Error Occurred', $e->getMessage());
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $balances = Balance::with('financialAccount')
                ->where('user_id', $user->id)
                ->get();

            if ($balances->isEmpty()) {
                return $this->responseJson(404, 'Financial Account Not Found');
            }

            $this->authorize('view', $user);

            $financialAccounts = $balances->groupBy(function ($balance) {
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

            return $this->responseJson(200, 'Get Amounts Successfully', [
                'user' => $user,
                'financial_accounts' => $financialAccounts
            ]);

        } catch (AuthorizationException $e) {
            return $this->responseJson(403, 'No Access');
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Resource not found');
        } catch (Exception $e) {
            return $this->responseJson(500, 'An Error Occurred', $e->getMessage());
        }
    }


    public function destroy($id): JsonResponse
    {
        try {
            $this->authorize('deleteAny', User::class);

            $income = Balance::findOrFail($id);

            $income->delete();
            
            return $this->responseJson(200, 'Income deleted successfully', $income);
        } catch (AuthorizationException $e) {
            return $this->responseJson(403, 'No Access');
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
