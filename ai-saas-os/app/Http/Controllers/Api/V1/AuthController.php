<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, AuditService $auditService): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => 'active',
        ]);

        $auditService->record('user.registered', null, $user->id, $user);

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $user->createToken('api')->plainTextToken,
            ],
        ], 201);
    }

    public function login(Request $request, AuditService $auditService): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $auditService->record('user.logged_in', null, $user->id, $user);

        return response()->json([
            'data' => [
                'user' => $user->fresh('tenants'),
                'token' => $user->createToken('api')->plainTextToken,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load('tenants'),
        ]);
    }

    public function logout(Request $request, AuditService $auditService): JsonResponse
    {
        $request->user()->tokens()->delete();

        $auditService->record('user.logged_out', null, $request->user()->id, $request->user());
        Auth::forgetGuards();

        return response()->json(['data' => ['logged_out' => true]]);
    }
}
