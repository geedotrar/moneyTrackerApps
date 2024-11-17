<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\SubCategory;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            if(!auth()->check()){
                return $this->responseJson(401,'Unauthorized');
            }
            $expenses = Expense::with('user','financialAccount','subCategory')->get();

            if($expenses->isEmpty()){
                return $this->responseJson(404,'Expenses Not Found');
            }

            $balances = Balance::where('user_id', auth()->id())->get()->keyBy('financial_account_id');

            $responseData = $expenses->map(function ($expense) use ($balances) {
                $balance = $balances->get($expense->financial_account_id);
                $balanceAmount = $balance->amount ?? 0;
    
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
        } catch (Exception $e) {
            return $this->responseJson(500,'An Error Occurred', $e->getMessage());
        }
    }

    public function show($id): JsonResponse
    { 
        try{
            if(!auth()->check()){
                return $this->responseJson(401,'Unauthorized');
            }
            $expense = Expense::with('user', 'financialAccount', 'subCategory')->find($id);

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
        } catch(Exception $e){
            return $this->responseJson(500,'An Error Occurred', $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if(!auth()->check()){
                return $this->responseJson(401,'Unauthorized');
            }

            $user = auth()->user();

            $validatedData = $request->validate([
                'financial_account_id'=>'required',
                'amount'=>'required|numeric',
                'description'=>'required',
                'sub_category_id' => 'required',
            ]);

            if ($user->hasRole('admin')) {
                $request->validate(['user_id' => 'required']);
                $validatedData['user_id'] = $request->input('user_id');
            } else {
                $validatedData['user_id'] = auth()->id();
            }
            
            $validatedData['date'] = Carbon::now();
            
            $expense = Expense::create($validatedData);

            $financialAccount = FinancialAccount::find($validatedData['financial_account_id']);
            if(!$financialAccount){
                return $this->responseJson(404, 'Financial Account Not Found');
            }

            $balance = Balance::where('user_id', $validatedData['user_id'])
                            ->where('financial_account_id', $validatedData['financial_account_id'])
                            ->first();

            if (!$balance) {
                return $this->responseJson(404, 'Balance Not Found');
            }

            if ($balance->amount < $validatedData['amount']) {
                return $this->responseJson(400, 'Insufficient funds in balance');
            }

            $amountBeforePay = $balance->amount;
            $amountAfterPay = $balance->amount - $validatedData['amount'];
    
            $balance->amount = $amountAfterPay;
            $balance->save();

            $subCategory = SubCategory::find($validatedData['sub_category_id']);

            if(!$subCategory){
                return $this->responseJson(404,'Sub Category Not Found');
            }

            $expense->load('user','financialAccount','subCategory');

            $responseData = [
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
                    "Cost" => number_format($validatedData['amount'],2,',','.'),
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
            return $this->responseJson(201, 'Expense Created Successfully', $responseData);
        } catch (Exception $e) {
            return $this->responseJson(500,'An Error Occured', $e->getMessage());
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
                'financial_account_id' => 'nullable|exists:financial_accounts,id',
                'amount' => 'required|numeric',
                'description' => 'required',
                'sub_category_id' => 'nullable|exists:sub_categories,id',
            ]);

            if ($user->hasRole('admin')) {
                $request->validate(['user_id' => 'required|exists:users,id']); 
                $validatedData['user_id'] = $request->input('user_id'); 
            } else {
                $validatedData['user_id'] = auth()->id();
            }
            $validatedData['date'] = Carbon::now();

            $expense = Expense::find($id);

            if (empty($expense)) {  
                return $this->responseJson(404, 'Expense Not Found');
            }

            if (array_key_exists('financial_account_id', $validatedData) && $validatedData['financial_account_id'] === null) {
                return $this->responseJson(400, 'Financial Account ID cannot be null');
            }
            if (array_key_exists('sub_category_id', $validatedData) && $validatedData['sub_category_id'] === null) {
                return $this->responseJson(400, 'Sub Category ID cannot be null');
            }

            $expense->update([
                'user_id' => $validatedData['user_id'],
                'sub_category_id' => $validatedData['sub_category_id'] ?? $expense->sub_category_id,
                'financial_account_id' => $validatedData['financial_account_id'] ?? $expense->financial_account_id, 
                'amount' => $validatedData['amount'],
                'description' => $validatedData['description'],
                'date' => $validatedData['date'],
            ]);

            $financialAccount = FinancialAccount::find($validatedData['financial_account_id'] ?? $expense->financial_account_id);
            if (!$financialAccount) {
                return $this->responseJson(404, 'Financial Account Not Found');
            }

            $balance = Balance::where('user_id', $validatedData['user_id'])
                ->where('financial_account_id', $validatedData['financial_account_id'] ?? $expense->financial_account_id)
                ->first();

            if (!$balance) {
                return $this->responseJson(404, 'Balance Not Found');
            }

            $amountBeforePay = $balance->amount;
            $amountAfterPay = $balance->amount - $validatedData['amount'];

            $responseData = [
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

            return $this->responseJson(201, 'Expense Updated Successfully',$responseData);
        }catch(Exception $e){
            return $this->responseJson(500,'An Error Occurred', $e->getMessage());
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
    
            if (!$user->hasRole('admin') && $expense->user_id !== $user->id) {
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
