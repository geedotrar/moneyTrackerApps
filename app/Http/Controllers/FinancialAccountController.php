<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\Role;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException; 

class FinancialAccountController extends Controller
{
    public function index():JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }
            $financialAccount = FinancialAccount::get();

            if($financialAccount->isEmpty()) {
                return $this->responseJson(404,'Payment Method Not Found');
            }

            $responseData = $financialAccount->map(function ($income) {
                return [
                    'id' => $income->id,
                    'name' => $income->name,
                    'amount' => $income->formatted_balance,
                    'created_at' => $income->created_at,
                    'updated_at' => $income->updated_at,
                ];
            });

            return $this->responseJson(200,'Get Payment Methods Succesfully',$responseData);
        }catch(Exception $e){
            return $this->responseJson(500,'An Error Occured', $e->getMessage());
        }
    }

    public function show($id):JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }
            
            $financialAccount = FinancialAccount::find($id);

            if(empty($financialAccount)){
                return $this->responseJson(404,'Payment Method Not Found');
            }

            return $this->responseJson(200,"Get Payment Method Succesfully",$financialAccount);
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

            $validatedData = $request->validate([
                'name' => 'required'
            ]);

            if (FinancialAccount::where('name', $validatedData['name'])->exists()) {
                return $this->responseJson(409, 'Payment method already exists');
            }

            $financialAccount = FinancialAccount::create([
                'name' => $validatedData['name'],
            ]);

            return $this->responseJson(201, 'Payment Method Created Successfully', $financialAccount);
        } catch (Exception $e) {
            return $this->responseJson(500, 'An Error Occured', $e->getMessage());
        }
    }

    public function update(Request $request, $id):JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }
            
            $validatedData = $request->validate([
                'name' => 'required'
            ]);

            $financialAccount = FinancialAccount::find($id);
            
            if(empty($financialAccount)){
                return $this->responseJson(404,'Payment Method Not Found');
            }            

            if (FinancialAccount::where('name', $validatedData['name'])->where('id', '!=', $id)->exists()) {
                return $this->responseJson(409, 'Payment Method Already Exists');
            }

            $financialAccount->update([    
                'name' => $validatedData['name'],
            ]);            

            return $this->responseJson(201,"Payment Method Updated Successfully",$financialAccount);
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

            $financialAccount = FinancialAccount::findOrFail($id);

           $financialAccount->delete();

            return $this->responseJson(200, 'Payment Method deleted successfully', $financialAccount);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Payment Method Not Found');
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
