<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    public function index():JsonResponse
    {
        try{
            if(!auth()->check()){
                return $this->responseJson(401, 'Unauthorized');
            }
            $subCategory = SubCategory::get();

            if($subCategory->isEmpty()) {
                return $this->responseJson(404,'Sub Categories Not Found');
            }

            return $this->responseJson(200,'Get Sub Categories Succesfully',$subCategory);
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
            
            $subCategory = SubCategory::find($id);

            if(empty($subCategory)){
                return $this->responseJson(404,'Sub Category Not Found');
            }

            return $this->responseJson(200,"Get Sub Category Succesfully",$subCategory);
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
                'category_id' => 'required',
                'name' => 'required',
                'description' => 'required'
            ]);

            $financialAccount = Category::find($validatedData['category_id']);

            if(!$financialAccount){
                return $this->responseJson(404, 'Category Not Found');
            }


            if (SubCategory::where('name', $validatedData['name'])->exists()) {
                return $this->responseJson(409, 'Sub Category already exists');
            }

            $subCategory = SubCategory::create([
                'category_id' => $validatedData['category_id'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
            ]);

            return $this->responseJson(201, 'Sub Category Created Successfully', $subCategory);
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
                'description' => 'required'
            ]);

            $subCategory = SubCategory::find($id);
            
            if(empty($subCategory)){
                return $this->responseJson(404,'Sub Category Not Found');
            }            

            if (SubCategory::where('name', $validatedData['name'])->where('id', '!=', $id)->exists()) {
                return $this->responseJson(409, 'Sub Category Already Exists');
            }

            $subCategory->update([    
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
            ]);            

            return $this->responseJson(201,"Sub Category Updated Successfully",$subCategory);
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

            $subCategory = SubCategory::findOrFail($id);

           $subCategory->delete();

            return $this->responseJson(200, 'Sub Category deleted successfully', $subCategory);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Sub Category Not Found');
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

