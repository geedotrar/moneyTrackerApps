<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Category;
use App\Models\Income;
use App\Models\FinancialAccount;
use App\Models\SubCategory;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index():JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401,'Unauthorized');
            }
            $incomes = Income::with('user','financialAccount','subCategory.category')->get();

            if($incomes->isEmpty()){
                return $this->responseJson(404,'Incomes Not Found');
            }

            $responseData = $incomes->map(function ($income) {
                $financialAccount = $income->financialAccount;
                $subCategory = $income->subCategory;
                $category = $subCategory ? $subCategory->category : null;

                $balance = Balance::where('user_id', $income->user_id)
                    ->where('financial_account_id', $financialAccount->id)
                    ->first();

                $amountBeforeAdding = $balance ? $balance->amount : 0;
                $amountAfterPay = $balance ? $balance->amount + $income->amount : 0;

                return [
                    'id' => $income->id,
                    'user' => $income->user,
                    'amount' => $income->amount,
                    'source' => $income->source,
                    'description' => $income->description,
                    'date' => $income->date,
                    'financial_account' => [
                        'id' => $financialAccount->id,
                        'name' => $financialAccount->name,
                        'balance_before_adding' => number_format($amountBeforeAdding, 2, ',', '.'),
                        'balance_after_adding' => number_format($amountAfterPay, 2, ',', '.'),
                        'created_at' => $financialAccount->created_at,
                        'updated_at' => $financialAccount->updated_at,
                    ],
                    'category' => $category ? [
                        'id' => $category->id,
                        'name' => $category->name,
                        'type' => $category->type,
                        'description' => $category->description,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                        'sub_category' => $subCategory ? [
                            'id' => $subCategory->id,
                            'name' => $subCategory->name,
                            'description' => $subCategory->description,
                            'created_at' => $subCategory->created_at,
                            'updated_at' => $subCategory->updated_at,
                            'deleted_at' => $subCategory->deleted_at,
                        ] : null,
                    ] : null,
                    'created_at' => $income->created_at,
                    'updated_at' => $income->updated_at,
                ];
            });

            return $this->responseJson(200,'Get Incomes Successfully',$responseData);
        }catch(Exception $e){
            return $this->responseJson(500,'An Error Occurred', $e->getMessage());
        }
    }
    
    public function show($id):JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401,'Unauthorized');
            }
            $incomes = Income::find($id);

            if (!$incomes) {
                return $this->responseJson(404, 'Income Not Found');
            }
            
            $incomes->load('user','financialAccount');

            $responseData = [
                'id' => $incomes->id,
                'user' => $incomes->user, 
                'amount' => $incomes->amount,
                'source' => $incomes->source,
                'description' => $incomes->description,
                'date' => $incomes->date,
                'financial_account' => $incomes->financialAccount, 
                'created_at' => $incomes->created_at,
                'updated_at' => $incomes->updated_at,
            ];
    
            
            return $this->responseJson(200,'Get Incomes Successfully',$responseData);
        }catch(Exception $e){
            return $this->responseJson(500,'An Error Occured', $e->getMessage());
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
                'amount'=> 'required', 
                'source'=> 'required', 
                'description'=> 'nullable',  
                'financial_account_id'=> 'required',
                'sub_category_id' => 'nullable', 
                'new_category' => 'nullable', 
                'new_sub_category' => 'nullable', 
            ]);

            if ($user->hasRole('admin')) {
                $request->validate(['user_id' => 'required']);
                $validatedData['user_id'] = $request->input('user_id');
            } else {
                $validatedData['user_id'] = auth()->id();
            }
            
            $validatedData['date'] = Carbon::now();

            if ($request->filled('new_sub_category')) {
                if ($request->filled('new_category')) {
                    $category = Category::create([
                        'name' => $request->input('new_category'),
                        'type' => 'income',
                    ]);
                } else {
                    $subCategory = SubCategory::find($validatedData['sub_category_id']);
                    $category = $subCategory->category;
                }

                $subCategory = SubCategory::create([
                    'category_id' => $category->id,
                    'name' => $validatedData['new_sub_category'],
                    'description' => $request->input('sub_category_description', ''),
                ]);
                $validatedData['sub_category_id'] = $subCategory->id;
            }

            if (!$request->filled('new_sub_category') && !$validatedData['sub_category_id']) {
                return $this->responseJson(400, 'Sub Category is required');
            }

            $income = Income::create($validatedData);

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

            $amountBeforeAdding = $balance->amount;
            $amountAfterPay = $balance->amount + $validatedData['amount'];
    
            $balance->amount = $amountAfterPay;
            $balance->save();

            $subCategory = SubCategory::with('category')->find($validatedData['sub_category_id']);
            if (!$subCategory) {
                return $this->responseJson(404, 'Sub Category Not Found');
            }

            if(!$subCategory->category || $subCategory->category->type !== 'income') {
                return $this->responseJson(400, 'The selected subcategory must belong to a category of type income.');
            }

            $income->load('user','financialAccount');

            $responseData = [
                "id" => $income->id,
                "user" => $income->user,
                "financial_accounts" => [
                    $financialAccount->name => [
                        "id" => $financialAccount->id,
                        "name" => $financialAccount->name,
                        "balance_before_adding" => number_format($amountBeforeAdding, 2, ',', '.'),
                        "balance_after_adding" => number_format($amountAfterPay, 2, ',', '.'),
                        "created_at" => $financialAccount->created_at,
                        "updated_at" => $financialAccount->updated_at,
                    ]
                ],
                "income" => [
                    'amount' => $income->amount,
                    'source' => $income->source,
                    'description' => $income->description,
                    'date' => $income->date,
                ],
                "category" => [
                    "id" => $subCategory->category->id,
                    "name" => $subCategory->category->name,
                    "type" => $subCategory->category->type,
                    "description" => $subCategory->category->description,
                    "created_at" => $subCategory->category->created_at,
                    "updated_at" => $subCategory->category->updated_at,
                    "sub_category" => [
                        "id" => $subCategory->id,
                        "category_id" => $subCategory->category_id,
                        "name" => $subCategory->name,
                        "description" => $subCategory->description,
                        "created_at" => $subCategory->created_at,
                        "updated_at" => $subCategory->updated_at,
                        "deleted_at" => $subCategory->deleted_at,
                    ],
                ],
                "created_at" => $income->created_at,
                "updated_at" => $income->updated_at,
            ];
            return $this->responseJson(201, 'Income Created Successfully', $responseData);
        } catch (Exception $e) {
            return $this->responseJson(500, 'An Error Occurred', $e->getMessage());
        }
    }


    public function update(Request $request, $id): JsonResponse
    {
        try{
            if(!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $user = auth()->user();

            $validatedData = $request->validate([
                'amount'=> 'required', 
                'source'=> 'required', 
                'description'=> 'nullable',  
                'financial_account_id' => 'required',
                'sub_category_id' => 'nullable', 
                'new_category' => 'nullable', 
                'new_sub_category' => 'nullable', 
            ]);

            if ($user->hasRole('admin')) {
                $request->validate(['user_id' => 'required']);
                $validatedData['user_id'] = $request->input('user_id');
            } else {
                $validatedData['user_id'] = auth()->id();
            }

            $validatedData['date'] = Carbon::now();

            if ($request->filled('new_sub_category')) {
                if ($request->filled('new_category')) {
                    $category = Category::create([
                        'name' => $request->input('new_category'),
                        'type' => 'income',
                    ]);
                } else {
                    $subCategory = SubCategory::find($validatedData['sub_category_id']);
                    $category = $subCategory->category;
                }

                $subCategory = SubCategory::create([
                    'category_id' => $category->id,
                    'name' => $validatedData['new_sub_category'],
                    'description' => $request->input('sub_category_description', ''),
                ]);
                $validatedData['sub_category_id'] = $subCategory->id;
            }

            if (!$request->filled('new_sub_category') && !$validatedData['sub_category_id']) {
                return $this->responseJson(400, 'Sub Category is required');
            }

            $income = Income::find($id);

            if (empty($income)) {  
                return $this->responseJson(404, 'Income Not Found');
            }

            $income->update($validatedData);

            $financialAccount = FinancialAccount::find($validatedData['financial_account_id']);

            if (!$financialAccount) {
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

            $amountBeforeAdding = $balance->amount;
            $amountAfterPay = $balance->amount + $validatedData['amount'];

            $balance->amount = $amountAfterPay;
            $balance->save();

            $subCategory = SubCategory::with('category')->find($validatedData['sub_category_id']);
            if (!$subCategory) {
                return $this->responseJson(404, 'Sub Category Not Found');
            }

            if (!$subCategory->category || $subCategory->category->type !== 'income') {
                return $this->responseJson(400, 'The selected subcategory must belong to a category of type income.');
            }

            $income->load('user', 'financialAccount');

            $responseData = [
                "id" => $income->id,
                "user" => $income->user,
                "financial_accounts" => [
                    $financialAccount->name => [
                        "id" => $financialAccount->id,
                        "name" => $financialAccount->name,
                        "balance_before_adding" => number_format($amountBeforeAdding, 2, ',', '.'),
                        "balance_after_adding" => number_format($amountAfterPay, 2, ',', '.'),
                        "created_at" => $financialAccount->created_at,
                        "updated_at" => $financialAccount->updated_at,
                    ]
                ],
                "income" => [
                    'amount' => $income->amount,
                    'source' => $income->source,
                    'description' => $income->description,
                    'date' => $income->date,
                ],
                "category" => [
                    "id" => $subCategory->category->id,
                    "name" => $subCategory->category->name,
                    "type" => $subCategory->category->type,
                    "description" => $subCategory->category->description,
                    "created_at" => $subCategory->category->created_at,
                    "updated_at" => $subCategory->category->updated_at,
                    "sub_category" => [
                        "id" => $subCategory->id,
                        "category_id" => $subCategory->category_id,
                        "name" => $subCategory->name,
                        "description" => $subCategory->description,
                        "created_at" => $subCategory->created_at,
                        "updated_at" => $subCategory->updated_at,
                        "deleted_at" => $subCategory->deleted_at,
                    ],
                ],
                "created_at" => $income->created_at,
                "updated_at" => $income->updated_at,
            ];
            return $this->responseJson(201,'Income Updated Successfully',$responseData);
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

            $income = Income::findOrFail($id);

            $income->delete();

            $responseData = [
                'id' => $income->id,
                'user' => $income->user, 
                'amount' => $income->amount,
                'source' => $income->source,
                'description' => $income->description,
                'date' => $income->date,
                'financial_account' => $income->financialAccount, 
                'created_at' => $income->created_at,
                'updated_at' => $income->updated_at,
            ];
            
            return $this->responseJson(200, 'Income deleted successfully', $responseData);
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