<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class AuthController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        // Set validation
        $validator = Validator::make($request->all(), [
            'username'  => 'required|unique:users',
            'email'     => 'required|email|unique:users',
            'name'      => 'required',
            'password'  => 'required|min:8|confirmed'
        ]);

        // If validation fails
        if ($validator->fails()) {
            return $this->responseJson(422, $validator->errors()->first());
        }

        // Create user
        $user = User::create([
            'username'  => $request->username,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => bcrypt($request->password)
        ]);

        $userRole = Role::where('name', 'user')->first();
            if ($userRole) {
                $user->roles()->attach($userRole);
            }
            
        // Return response JSON user is created
        if ($user) {
            return $this->responseJson(201, 'User created successfully', $user);
        }

        // Return JSON process insert failed 
        return $this->responseJson(500, 'User creation failed');
    }

    public function login(Request $request)
    {
         // Set validation for login
         $validator = Validator::make($request->all(), [
            'identity'     => 'required',
            'password'  => 'required'
        ]);

        // If validation fails
        if ($validator->fails()) {
            return $this->responseJson(422, $validator->errors()->first());
        }

        $identity = $request->input('identity');
        $password = $request->input('password');

        $user = User::where('email',$identity)
                    ->orWhere('username',$identity)
                    ->first();
                    
        // If auth failed
        if (!$user || !Hash::check($password, $user->password)) {
            return $this->responseJson(401, 'Email/Username or Password wrong');
        }
    
        $token = auth()->guard('api')->login($user);
    
        // If auth success
        return $this->responseJson(200, 'Login successful', [
            'user' => $user,
            'token' => $token   
        ]);
    }

    public function logout(Request $request)
    {
        try {
            // Invalidate the token
            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->responseJson(200, 'Logout successful!');
        } catch (JWTException $e) {
            return $this->responseJson(500, 'Logout failed, please try again.');
        }
    }


    /**
     * Create a JSON response.
     *
     * @param int $status
     * @param string $message
     * @param mixed|null $data
     * @return \Illuminate\Http\JsonResponse
     */
    private function responseJson(int $status, string $message, $data = null)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}

