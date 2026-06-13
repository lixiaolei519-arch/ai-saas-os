<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request, AuditService $auditService): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_admin || $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['The provided admin credentials are invalid.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $auditService->record('admin.logged_in', null, $user->id, $user);

        return response()->json([
            'data' => [
                'user' => $user->fresh(),
                'token' => $user->createToken('admin', ['admin'])->plainTextToken,
            ],
        ]);
    }
}
