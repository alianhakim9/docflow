<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Email atau kata sandi tidak sesuai.'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'user' => $this->formatUser($user),
                'token' => $token,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'data' => $this->formatUser($user),
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id,
            'department' => [
                'id' => $user->department->id,
                'name' => $user->department->name,
            ],
            'role' => [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'slug' => $user->role->slug,
            ],
            'permissions' => $user->role->permissions ?? [],
        ];
    }
}
