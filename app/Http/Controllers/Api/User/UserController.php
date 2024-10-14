<?php

namespace App\Http\Controllers\Api\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $users = User::all();

            if ($users->isEmpty()) {
                return $this->responseJson(404, 'No users found');
            }

            return $this->responseJson(200, 'Users retrieved successfully', $users);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Resource not found');
        } catch (Exception $e) {
            return $this->responseJson(500, 'An error occurred', $e->getMessage());
        }
    }

    public function show($id): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $users = User::find($id);

            if (!$users) {
                return $this->responseJson(404, 'No users found');
            }

            return $this->responseJson(200, 'Users retrieved successfully', $users);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'Resource not found');
        } catch (Exception $e) {
            return $this->responseJson(500, 'An error occurred', $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
            ]);

            return $this->responseJson(200, 'User Created Successfully', $user);
        } catch (Exception $e) {
            return $this->responseJson(500, 'An error occurred', $e->getMessage());
        }
    }
    public function update(Request $request, $id): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if (isset($validatedData['password'])) {
                $validatedData['password'] = bcrypt($validatedData['password']);
            }

            $user->update($validatedData);


            return $this->responseJson(200, 'User updated successfully', $user);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'User not found');
        } catch (Exception $e) {
            return $this->responseJson(500, 'An error occurred', $e->getMessage());
        }
    }
    
    public function destroy($id): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return $this->responseJson(401, 'Unauthorized');
            }

            $user = User::findOrFail($id);

           $user->delete();

            return $this->responseJson(200, 'User deleted successfully', $user);
        } catch (ModelNotFoundException $e) {
            return $this->responseJson(404, 'User not found');
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
