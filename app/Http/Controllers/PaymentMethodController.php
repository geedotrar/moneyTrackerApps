<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Role;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException; 

class PaymentMethodController extends Controller
{
    public function index():JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }
            $paymentMethod = PaymentMethod::all();

            if(empty($paymentMethod)){
                return $this->responseJson(404,'Payment Method Not Found');
            }

            return $this->responseJson(200,'Get Payment Methods Succesfully',$paymentMethod);
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
            
            $paymentMethod = PaymentMethod::find($id);

            if(empty($paymentMethod)){
                return $this->responseJson(404,'Payment Method Not Found');
            }

            return $this->responseJson(200,"Get Payment Method Succesfully",$paymentMethod);
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

            if (PaymentMethod::where('name', $validatedData['name'])->exists()) {
                return $this->responseJson(409, 'Payment method already exists');
            }

            $paymentMethod = PaymentMethod::create([
                'name' => $validatedData['name'],
            ]);

            return $this->responseJson(201, 'Payment Method Created Successfully', $paymentMethod);
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

            $paymentMethod = PaymentMethod::find($id);
            
            if(empty($paymentMethod)){
                return $this->responseJson(404,'Payment Method Not Found');
            }            

            if (PaymentMethod::where('name', $validatedData['name'])->where('id', '!=', $id)->exists()) {
                return $this->responseJson(409, 'Payment Method Already Exists');
            }

            $paymentMethod->update([    
                'name' => $validatedData['name'],
            ]);            

            return $this->responseJson(201,"Payment Method Updated Successfully",$paymentMethod);
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

            $paymentMethod = PaymentMethod::findOrFail($id);

           $paymentMethod->delete();

            return $this->responseJson(200, 'Payment Method deleted successfully', $paymentMethod);
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
