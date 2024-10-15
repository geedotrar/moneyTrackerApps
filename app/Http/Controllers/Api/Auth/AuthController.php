<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
            'name'      => 'required',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:8|confirmed'
        ]);

        // If validation fails
        if ($validator->fails()) {
            return $this->responseJson(422, $validator->errors()->first());
        }

        // Create user
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => bcrypt($request->password)
        ]);

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
            'email'     => 'required|email',
            'password'  => 'required'
        ]);

        // If validation fails
        if ($validator->fails()) {
            return $this->responseJson(422, $validator->errors()->first());
        }

        // Get credentials from request
        $credentials = $request->only('email', 'password');

        // If auth failed
        if (!$token = auth()->guard('api')->attempt($credentials)) {
            return $this->responseJson(401, 'Email or Password wrong');
        }

        // If auth success
        return $this->responseJson(200, 'Login successful', [
            'user' => auth()->guard('api')->user(),
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

