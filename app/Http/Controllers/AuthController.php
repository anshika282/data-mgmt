<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'username' => 'required|string|unique:users',
            'address' => 'required|string',
            'role' => 'required|in:USER,ADMIN',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'address' => $request->address,
            'role' => $request->role,
        ]);

        return $this->respondWithToken(JWTAuth::fromUser($user));
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'username' => 'required|string',
        ]);
        $user = User::where('email', $request->email)
            ->where('username', $request->username)
            ->first();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized: User not found'], 401);
        }

        $token = JWTAuth::fromUser($user);

        return $this->respondWithToken($token);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to log out'], 500);
        }
    }

    // Helper method to respond with token
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
