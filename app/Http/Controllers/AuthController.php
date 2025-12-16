<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register
     * POST /auth/register
     */
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'] ?? null,
            'phone'    => $data['phone'],
            'location' => $data['location'], // FIXED
            'role'     => $data['role'],
            'status'   => 'active',
            'password' => Hash::make($data['password']),
        ]);

        if ($user->role === 'vendor') {
            Vendor::create([
                'user_id'           => $user->id,
                'business_name'     => $data['business_name'],
                'business_category' => $data['business_category'],
                'status'            => 'pending',
                'email'             => $user->email,
                'phone'             => $user->phone,
                'location'          => $user->location,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ], 201);
    }

    /**
     * Login
     * POST /auth/login
     */
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::when(
                isset($data['email']),
                fn ($q) => $q->where('email', $data['email'])
            )
            ->when(
                isset($data['phone']),
                fn ($q) => $q->orWhere('phone', $data['phone'])
            )
            ->first();

        if (
            !$user ||
            !Hash::check($data['password'], $user->password)
        ) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account suspended'], 403);
        }

        // Vendor approval check
        if ($user->role === 'vendor' && optional($user->vendor)->status !== 'approved') {
            return response()->json([
                'message' => 'Vendor account not approved'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    /**
     * Get current user
     * GET /auth/me
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Logout
     * POST /auth/logout
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
