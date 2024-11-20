<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index():JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }
            $category = Category::get();

            if($category->isEmpty()) {
                return $this->responseJson(404,'Categories Not Found');
            }

            return $this->responseJson(200,'Get Categories Succesfully',$category);
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
            
            $category = Category::find($id);

            if(empty($category)){
                return $this->responseJson(404,'Category Not Found');
            }

            return $this->responseJson(200,"Get Category Succesfully",$category);
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
                'name' => 'required',
                'type' => 'required|in:income,expense',
                'description' => 'required'
            ]);

            if (Category::where('name', $validatedData['name'])->exists()) {
                return $this->responseJson(409, 'Category already exists');
            }

            $financialAccount = Category::create($validatedData);

            return $this->responseJson(201, 'Category Created Successfully', $financialAccount);
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
                'name' => 'required',
                'type' => 'required|in:income,expense',
                'description' => 'required'
            ]);

            $financialAccount = Category::find($id);
            
            if(empty($financialAccount)){
                return $this->responseJson(404,'Category Not Found');
            }            

            if (Category::where('name', $validatedData['name'])->where('id', '!=', $id)->exists()) {
                return $this->responseJson(409, 'Category Already Exists');
            }

            $financialAccount->update($validatedData);            

            return $this->responseJson(201,"Category Updated Successfully",$financialAccount);
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

            $financialAccount = Category::findOrFail($id);

           $financialAccount->delete();

            return $this->responseJson(200, 'Category deleted successfully', $financialAccount);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Category Not Found');
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
