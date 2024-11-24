<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\SubCategory;
use App\Models\User;
use Carbon\Carbon;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        try {
            if(!auth()->check()){
                return $this->responseJson(401,'Unauthorized');
            }

            $user = auth()->user();

            $this->authorize('viewAny', Expense::class);

            if ($user->hasRole('admin')) {
                $expenses = Expense::with('user', 'financialAccount', 'subCategory')->get();
            }else {
                $expenses = Expense::with('user', 'financialAccount', 'subCategory')
                    ->where('user_id', $user->id)
                    ->get();
            }

            if ($expenses->isEmpty()) {
                return $this->responseJson(404,'Expenses Not Found');
            }

            $balances = Balance::where('user_id', $user->id)->get()->keyBy('financial_account_id');

            $responseData = $expenses->map(function ($expense) use ($balances) {
                $balance = $balances->get($expense->financial_account_id);
    
                $amountBeforePay = $balance ? $balance->amount + $expense->amount : $expense->amount;
                $amountAfterPay = $balance ? $balance->amount : 0;

                return [
                    "id" => $expense->id,
                    "user" => $expense->user,
                    "financial_accounts" => [
                        $expense->financialAccount->name => [
                            "id" => $expense->financialAccount->id,
                            "name" => $expense->financialAccount->name,
                            "amount_before_pay" => number_format($amountBeforePay, 2, ',', '.'),
                            "amount_after_pay" => number_format($amountAfterPay, 2, ',', '.'),
                            "created_at" => $expense->financialAccount->created_at,
                            "updated_at" => $expense->financialAccount->updated_at,
                        ]
                    ],
                    "Expense" => [
                        "Cost" => number_format($expense->amount, 2, ',', '.'),
                        "description" => $expense->description,
                        "date" => $expense->date,
                    ],
                    "sub_category" => [
                        "id" => $expense->subCategory->id,
                        "category_id" => $expense->subCategory->category_id,
                        "name" => $expense->subCategory->name,
                        "description" => $expense->subCategory->description,
                        "created_at" => $expense->subCategory->created_at,
                        "updated_at" => $expense->subCategory->updated_at,
                        "deleted_at" => $expense->subCategory->deleted_at,
                    ],
                    "created_at" => $expense->created_at,
                    "updated_at" => $expense->updated_at,
                ];
            });

            return $this->responseJson(200,'Get Expenses Successfully',$responseData);
        } catch (AuthorizationException $e) {
            return $this->responseJson(403, 'No Access');
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Resource not found');
        } catch(Exception $e){
            return $this->responseJson(500,'An Error Occurred', $e->getMessage());
        }
    }

    public function show($id): JsonResponse
    { 
        try{
            $expense = Expense::with('user', 'financialAccount', 'subCategory')->find($id);

            $this->authorize('view', $expense);

            if(!$expense){
                return $this->responseJson(404,'Expense Not Found');
            }

            $balance = Balance::where('user_id', auth()->id())
                ->where('financial_account_id', $expense->financial_account_id)
                ->first();
            
            $amountBeforePay = $balance ? $balance->amount + $expense->amount : $expense->amount;
            $amountAfterPay = $balance ? $balance->amount : 0;

            $responseData = [
                "id" => $expense->id,
                "user" => $expense->user,
                "financial_accounts" => [
                    $expense->financialAccount->name => [
                        "id" => $expense->financialAccount->id,
                        "name" => $expense->financialAccount->name,
                        "amount_before_pay" => number_format($amountBeforePay, 2, ',', '.'),
                        "amount_after_pay" => number_format($amountAfterPay, 2, ',', '.'),
                        "created_at" => $expense->financialAccount->created_at,
                        "updated_at" => $expense->financialAccount->updated_at,
                    ]
                ],
                "Expense" => [
                    "Cost" => number_format($expense->amount, 2, ',', '.'),
                    "description" => $expense->description,
                    "date" => $expense->date,
                ],
                "sub_category" => [
                    "id" => $expense->subCategory->id,
                    "category_id" => $expense->subCategory->category_id,
                    "name" => $expense->subCategory->name,
                    "description" => $expense->subCategory->description,
                    "created_at" => $expense->subCategory->created_at,
                    "updated_at" => $expense->subCategory->updated_at,
                    "deleted_at" => $expense->subCategory->deleted_at,
                ],
                "created_at" => $expense->created_at,
                "updated_at" => $expense->updated_at,
            ];

            return $this->responseJson(200,'Get Expense Successfully', $responseData);
        } catch (AuthorizationException $e) {
            return $this->responseJson(403, 'No Access');
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Resource not found');
        } catch(Exception $e){
            return $this->responseJson(500,'An Error Occurred', $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $user = auth()->user();

            $validatedData = $request->validate([
                'financial_account_id' => 'required|exists:financial_accounts,id',
                'amount' => 'required|numeric|min:1',
                'description' => 'required|string',
                'sub_category_id' => 'required|exists:sub_categories,id',
            ]);

            if ($user->hasRole('admin') && $user->hasPermission('admin-create-expenses')) {
                $request->validate(['user_id' => 'required|exists:users,id']);
                $validatedData['user_id'] = $request->input('user_id');
            } else {
                $validatedData['user_id'] = auth()->id();
            }

            $selectedUser = User::find($validatedData['user_id']);
            if (!$selectedUser) {
                return $this->responseJson(404, 'User ID Not Found');
            }

            $financialAccount = FinancialAccount::find($validatedData['financial_account_id']);
            if (!$financialAccount) {
                return $this->responseJson(404, 'Financial Account Not Found');
            }

            $responseData = DB::transaction(function () use ($validatedData, $financialAccount) {
                $balance = Balance::where('user_id', $validatedData['user_id'])
                    ->where('financial_account_id', $validatedData['financial_account_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$balance) {
                    throw new Exception('Balance Not Found');
                }

                if ($balance->amount < $validatedData['amount']) {
                    throw new Exception('Insufficient funds in balance');
                }

                $amountBeforePay = $balance->amount;
                $amountAfterPay = $balance->amount - $validatedData['amount'];

                $balance->amount = $amountAfterPay;
                $balance->save();

                $validatedData['date'] = $validatedData['date'] ?? Carbon::now();

                $expense = Expense::create($validatedData);

                $subCategory = SubCategory::find($validatedData['sub_category_id']);
                if(!$subCategory){
                    throw new Exception('Sub Category Not Found');
                }

                $expense->load('user','financialAccount','subCategory');


                return [
                    "id" => $expense->id,
                    "user" => $expense->user,
                    "financial_accounts" => [
                        $financialAccount->name => [
                            "id" => $financialAccount->id,
                            "name" => $financialAccount->name,
                            "amount_before_pay" => number_format($amountBeforePay, 2, ',', '.'),
                            "amount_after_pay" => number_format($amountAfterPay, 2, ',', '.'),
                            "created_at" => $financialAccount->created_at,
                            "updated_at" => $financialAccount->updated_at,
                        ]
                    ],
                    "Expense" => [
                        "Cost" => number_format($validatedData['amount'], 2, ',', '.'),
                        "description" => $validatedData['description'],
                        "date" => $validatedData['date'],
                    ],
                    "sub_category" => [
                        "id" => $subCategory->id,
                        "category_id" => $subCategory->category_id,
                        "name" => $subCategory->name,
                        "description" => $subCategory->description,
                        "created_at" => $subCategory->created_at,
                        "updated_at" => $subCategory->updated_at,
                        "deleted_at" => $subCategory->deleted_at,
                    ],
                    "created_at" => $expense->created_at,
                    "updated_at" => $expense->updated_at,
                ];
            });

            return $this->responseJson(201, 'Expense Created Successfully', $responseData);
        } catch (AuthorizationException $e) {
            return $this->responseJson(403, 'No Access');
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Resource not found');
        } catch (ValidationException $e) {
            return $this->responseJson(422, 'Validation Error', $e->errors());
        } catch (Exception $e) {
            return $this->responseJson(500, 'An Error Occurred', $e->getMessage());
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }

            $user = auth()->user();

            $validatedData = $request->validate([
                'financial_account_id' => 'required|exists:financial_accounts,id',
                'amount' => 'required|numeric|min:1',
                'description' => 'required|string',
                'sub_category_id' => 'nullable|exists:sub_categories,id',
            ]);

            $expense = Expense::find($id);
            if (!$expense){
                return $this->responseJson(404, 'Expense Not Found');
            }

            if ($expense->user_id !== $user->id && !$user->hasRole('admin')) {
                return $this->responseJson(403, 'You are not authorized to update this expense');
            }

            $validatedData['date'] = Carbon::now();

            $responseData = DB::transaction(function () use ($expense, $validatedData) {
                $financialAccountId = $validatedData['financial_account_id'];

                $balance = Balance::where('user_id', $expense->user_id)
                    ->where('financial_account_id', $financialAccountId)
                    ->lockForUpdate()
                    ->first();

                if (!$balance) {
                    throw new Exception('Balance Not Found');
                }

                $amountBeforePay = $balance->amount;
                $amountAfterPay = $balance->amount - $validatedData['amount'];

                if ($amountAfterPay < 0) {
                    throw new Exception('Insufficient funds in balance');
                }

                $balance->amount = $amountAfterPay;
                $balance->save();

                $expense->update([
                    'financial_account_id' => $financialAccountId,
                    'sub_category_id' => $validatedData['sub_category_id'] ?? $expense->sub_category_id,
                    'amount' => $validatedData['amount'],
                    'description' => $validatedData['description'],
                    'date' => $validatedData['date'],
                ]);

                $financialAccount = FinancialAccount::find($financialAccountId);
                $subCategory = $expense->subCategory;

                return [
                    "id" => $expense->id,
                    "user" => $expense->user,
                    "financial_accounts" => [
                        $financialAccount->name => [
                            "id" => $financialAccount->id,
                            "name" => $financialAccount->name,
                            "amount_before_pay" => number_format($amountBeforePay, 2, ',', '.'),
                            "amount_after_pay" => number_format($amountAfterPay, 2, ',', '.'),
                            "created_at" => $financialAccount->created_at,
                            "updated_at" => $financialAccount->updated_at,
                        ]
                    ],
                    "Expense" => [
                        "Cost" => number_format($validatedData['amount'], 2, ',', '.'),
                        "description" => $validatedData['description'],
                        "date" => $validatedData['date'],
                    ],
                    "sub_category" => [
                        "id" => $subCategory->id,
                        "category_id" => $subCategory->category_id,
                        "name" => $subCategory->name,
                        "description" => $subCategory->description,
                        "created_at" => $subCategory->created_at,
                        "updated_at" => $subCategory->updated_at,
                        "deleted_at" => $subCategory->deleted_at,
                    ],
                    "created_at" => $expense->created_at,
                    "updated_at" => $expense->updated_at,
                ];
            });

            return $this->responseJson(201, 'Expense Updated Successfully', $responseData);
        } catch (AuthorizationException $e) {
            return $this->responseJson(403, 'No Access');
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Resource not found');
        } catch (ValidationException $e) {
            return $this->responseJson(422, 'Validation Error', $e->errors());
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

            $expense = Expense::findOrFail($id);
    
            $financialAccount = $expense->financialAccount;
            $subCategory = $expense->subCategory;
    
            $user = auth()->user();
    
            if (!$user->hasRole('admin') && !$user->hasPermission('user-delete-expenses') && $expense->user_id !== $user->id) {
                return $this->responseJson(403, 'Forbidden: You can only delete your own expense');
            }

            $expense->delete();

            $responseData = [
                "id" => $expense->id,
                "user" => $expense->user,
                "financial_accounts" => [
                    $financialAccount->name => [
                        "id" => $financialAccount->id,
                        "name" => $financialAccount->name,
                        "created_at" => $financialAccount->created_at,
                        "updated_at" => $financialAccount->updated_at,
                    ]
                ],
                "Expense" => [
                    "Cost" => number_format($expense->amount, 2, ',', '.'),
                    "description" => $expense->description,
                    "date" => $expense->date,
                ],
                "sub_category" => [
                    "id" => $subCategory->id,
                    "category_id" => $subCategory->category_id,
                    "name" => $subCategory->name,
                    "description" => $subCategory->description,
                    "created_at" => $subCategory->created_at,
                    "updated_at" => $subCategory->updated_at,
                    "deleted_at" => $subCategory->deleted_at,
                ],
                "created_at" => $expense->created_at,
                "updated_at" => $expense->updated_at,
            ];
            
            return $this->responseJson(200, 'Expense deleted successfully', $responseData);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Expense Not Found');
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
