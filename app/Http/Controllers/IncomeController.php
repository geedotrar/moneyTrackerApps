<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\PaymentMethod;
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
            $incomes = Income::with('user','paymentMethod')->get();

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
                    'payment_method' => $income->paymentMethod,
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
            
            $incomes->load('user','paymentMethod');

            $responseData = [
                'id' => $incomes->id,
                'user' => $incomes->user, 
                'amount' => $incomes->amount,
                'source' => $incomes->source,
                'description' => $incomes->description,
                'date' => $incomes->date,
                'payment_method' => $incomes->paymentMethod, 
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

            $validatedData = $request->validate([
                'amount'=> 'required', 
                'source'=> 'required', 
                'description'=> 'required',  
                'payment_method_id'=> 'required', 
            ]);

            $validatedData['user_id'] = auth()->id();
            $validatedData['date'] = Carbon::now();

            $income = Income::create([
                'user_id' => $validatedData['user_id'],
                'amount' => $validatedData['amount'],
                'source' => $validatedData['source'],
                'description' => $validatedData['description'],
                'date' => $validatedData['date'],
                'payment_method_id' => $validatedData['payment_method_id'],
            ]);

            $paymentMethod = PaymentMethod::find($validatedData['payment_method_id']);

            if(!$paymentMethod){
                return $this->responseJson(404, 'Payment Method Not Found');
            }

            $income->load('user','paymentMethod');

            $responseData = [
                'id' => $income->id,
                'user' => $income->user, 
                'amount' => $income->amount,
                'source' => $income->source,
                'description' => $income->description,
                'date' => $income->date,
                'payment_method' => $income->paymentMethod, 
                'created_at' => $income->created_at,
                'updated_at' => $income->updated_at,
            ];
    
        return $this->responseJson(201, 'Income Created Successfully', $responseData);
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
                'amount'=> 'required', 
                'source'=> 'required', 
                'description'=> 'required',  
                'payment_method_id'=> 'nullable|exists:payment_methods,id',
            ]);

            $validatedData['date'] = Carbon::now();
            $income = Income::find($id);

            if (empty($income)) {  
                return $this->responseJson(404, 'Income Not Found');
            }

            if (array_key_exists('payment_method_id', $validatedData) && $validatedData['payment_method_id'] === null) {
                return $this->responseJson(400, 'Payment Method ID cannot be null');
            }

            $income->update([    
                'amount' => $validatedData['amount'],
                'source' => $validatedData['source'],
                'description' => $validatedData['description'],
                'date' => $validatedData['date'],
                'payment_method_id' => $validatedData['payment_method_id'] ?? $income->payment_method_id, 
            ]);          
            
            $responseData = [
                'id' => $income->id,
                'user' => $income->user, 
                'amount' => $income->amount,
                'source' => $income->source,
                'description' => $income->description,
                'date' => $income->date,
                'payment_method' => $income->paymentMethod, 
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
                'payment_method' => $income->paymentMethod, 
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