<?php

namespace App\Http\Controllers;

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

            $responseData = $expenses->map(function($expense){
                return[
                    'id' => $expense->id,
                    'user' => $expense ->user, 
                    'sub_category' => $expense ->subCategory,
                    'financial_account' => $expense ->financialAccount, 
                    'amount' => $expense ->amount,
                    'description' => $expense ->description,
                    'date' => $expense ->date,
                    'created_at' => $expense ->created_at,
                    'updated_at' => $expense ->updated_at,
                ]; 
            });

            return $this->responseJson(200,'Get Expenses Successfully',$responseData);
        } catch (Exception $e) {
            return $this->responseJson(500,'An Error Occured', $e->getMessage());
        }
    }

    public function show($id): JsonResponse
    { 
        try{
            if(!auth()->check()){
                return $this->responseJson(401,'Unauthorized');
            }
            $expense = Expense::find($id);

            if(!$expense){
                return $this->responseJson(404,'Expense Not Found');
            }

            return $this->responseJson(200,'Get Expense Successfully');
        } catch(Exception $e){
            return $this->responseJson(500,'An Error Occured', $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if(!auth()->check()){
                return $this->responseJson(401,'Unauthorized');
            }
            $validatedData = $request->validate([
                'financial_account_id'=>'required',
                'amount'=>'required',
                'description'=>'required',
                'sub_category_id' => 'required'
            ]);

            $validatedData['user_id'] = auth()->id();
            $validatedData['date'] = Carbon::now();
            
            $expense = Expense::create([
                'user_id' => $validatedData['user_id'],
                'sub_category_id' => $validatedData['sub_category_id'],
                'financial_account_id'=>$validatedData['financial_account_id'],
                'amount' => $validatedData['amount'],
                'description'=> $validatedData['description'],
                'date'=> $validatedData['date'],
            ]);

            $financialAccount = FinancialAccount::find($validatedData['financial_account_id']);
            if(!$financialAccount){
                return $this->responseJson(404, 'Payment Method Not Found');
            }
            $subCategory = SubCategory::find($validatedData['sub_category_id']);

            if(!$subCategory){
                return $this->responseJson(404,'Sub Category Not Found');
            }

            $expense->load('user','financialAccount','subCategory');

            $responseData = [
                'id' => $expense->id,
                'user' => $expense->user, 
                'amount' => $expense->amount,
                'description' => $expense->description,
                'date' => $expense->date,
                'financial_account' => $expense->financialAccount, 
                'sub_category' => $expense->subCategory, 
                'created_at' => $expense->created_at,
                'updated_at' => $expense->updated_at,
            ];
            return $this->responseJson(201, 'Expense Created Successfully', $responseData);
        } catch (Exception $e) {
            return $this->responseJson(500,'An Error Occured', $e->getMessage());
        }
    }

    public function update(Request $request, $id):JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }
            
            $validatedData = $request->validate([
                'financial_account_id'=> 'nullable|exists:financial_accounts,id',
                'amount'=>'required',
                'description'=>'required',
                'sub_category_id'=> 'nullable|exists:sub_categories,id',
            ]);

            $validatedData['user_id'] = auth()->id();
            $validatedData['date'] = Carbon::now();
            $expense = Expense::find($id);

            if (empty($expense)) {  
                return $this->responseJson(404, 'Expense Not Found');
            }

            if (array_key_exists('financial_account_id', $validatedData) && $validatedData['financial_account_id'] === null) {
                return $this->responseJson(400, 'Payment Method ID cannot be null');
            }
            if (array_key_exists('sub_category_id', $validatedData) && $validatedData['sub_category_id'] === null) {
                return $this->responseJson(400, 'Sub Category  ID cannot be null');
            }

            $expense->update([
                'user_id' => $validatedData['user_id'],
                'sub_category_id' => $validatedData['sub_category_id'] ?? $expense->sub_category_id, 
                'financial_account_id' => $validatedData['financial_account_id'] ?? $expense->financial_account_id, 
                'amount' => $validatedData['amount'],
                'description'=> $validatedData['description'],
                'date'=> $validatedData['date'],
            ]);

            $responseData = [
                'id' => $expense->id,
                'user' => $expense->user, 
                'amount' => $expense->amount,
                'description' => $expense->description,
                'date' => $expense->date,
                'financial_account' => $expense->financialAccount, 
                'sub_category' => $expense->subCategory, 
                'created_at' => $expense->created_at,
                'updated_at' => $expense->updated_at,
            ];
            return $this->responseJson(201,"Expense Updated Successfully",$responseData);
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

            $expense = expense::findOrFail($id);

            $expense->delete();

            $responseData = [
                'id' => $expense->id,
                'user' => $expense->user, 
                'sub_category' => $expense->subCategory,
                'financial_account' => $expense->financialAccount, 
                'amount' => $expense->amount,
                'description' => $expense->description,
                'date' => $expense->date,
                'created_at' => $expense->created_at,
                'updated_at' => $expense->updated_at,
            ];
            
            return $this->responseJson(200, 'Expense deleted successfully', $responseData);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Expense Not Found');
        } catch (Exception $e) {
            return $this->responseJson(500, 'An error occurred', $e->getMessage());
        } 
    }    private function responseJson(int $status, string $message, $data = null)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
