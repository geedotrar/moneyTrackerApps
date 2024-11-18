<?php

namespace App\Http\Controllers;

use App\Models\Balance;
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
            $incomes = Income::with('user','financialAccount')->get();

            if($incomes->isEmpty()){
                return $this->responseJson(404,'Incomes Not Found');
            }

            $responseData = $incomes->map(function ($income) {
                return [
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
            });

            return $this->responseJson(200,'Get Incomes Successfully',$responseData);
        }catch(Exception $e){
            return $this->responseJson(500,'An Error Occured', $e->getMessage());
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
                'description'=> 'required',  
                'financial_account_id'=> 'required', 
            ]);

            if ($user->hasRole('admin')) {
                $request->validate(['user_id' => 'required']);
                $validatedData['user_id'] = $request->input('user_id');
            } else {
                $validatedData['user_id'] = auth()->id();
            }
            
            $validatedData['date'] = Carbon::now();

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
            $amountAfterPay = $balance->amount - $validatedData['amount'];
    
            $balance->amount = $amountAfterPay;
            $balance->save();

            $subCategory = SubCategory::find($validatedData['sub_category_id']);

            if(!$subCategory){
                return $this->responseJson(404,'Sub Category Not Found');
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
                "sub_category" => [
                    "id" => $subCategory->id,
                    "category_id" => $subCategory->category_id,
                    "name" => $subCategory->name,
                    "description" => $subCategory->description,
                    "created_at" => $subCategory->created_at,
                    "updated_at" => $subCategory->updated_at,
                    "deleted_at" => $subCategory->deleted_at,
                ],
                "created_at" => $income->created_at,
                "updated_at" => $income->updated_at,
            ];
            return $this->responseJson(201, 'Income Created Successfully', $responseData);
        } catch (Exception $e) {
            return $this->responseJson(500, 'An Error Occurred', $e->getMessage());
        }
    }


            // $responseData = [
            //     'id' => $income->id,
            //     'user' => $income->user, 
            //     'amount' => $income->amount,
            //     'source' => $income->source,
            //     'description' => $income->description,
            //     'date' => $income->date,
            //     'financial_account' => $income->financialAccount, 
            //     'created_at' => $income->created_at,
            //     'updated_at' => $income->updated_at,
            // ];
    
        

    public function update(Request $request, $id):JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }
            
            $validatedData = $request->validate([
                'amount'=> 'required', 
                'source'=> 'required', 
                'description'=> 'required',  
                'financial_account_id'=> 'nullable|exists:financial_accounts,id',
            ]);

            $validatedData['date'] = Carbon::now();
            $income = Income::find($id);

            if (empty($income)) {  
                return $this->responseJson(404, 'Income Not Found');
            }

            if (array_key_exists('financial_account_id', $validatedData) && $validatedData['financial_account_id'] === null) {
                return $this->responseJson(400, 'Financial Account ID cannot be null');
            }

            $income->update([    
                'amount' => $validatedData['amount'],
                'source' => $validatedData['source'],
                'description' => $validatedData['description'],
                'date' => $validatedData['date'],
                'financial_account_id' => $validatedData['financial_account_id'] ?? $income->financial_account_id, 
            ]);          
            
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
            return $this->responseJson(201,"Income Updated Successfully",$responseData);
        }catch(Exception $e){
            return $this->responseJson(500,'An Error Occured', $e->getMessage());
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