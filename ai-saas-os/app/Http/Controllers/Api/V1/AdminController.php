<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {
    }

    public function users(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->users($this->limit($request)),
        ]);
    }

    public function tenants(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->tenants($this->limit($request)),
        ]);
    }

    public function licenses(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->licenses($this->limit($request)),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->orders($this->limit($request)),
        ]);
    }

    public function paymentCallbacks(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->paymentCallbacks($this->limit($request)),
        ]);
    }

    public function channels(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->channels($this->limit($request)),
        ]);
    }

    public function commissions(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->commissions($this->limit($request)),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->stats(),
        ]);
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->dashboard(),
        ]);
    }

    public function system(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->system(),
        ]);
    }

    private function limit(Request $request): int
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return (int) ($data['limit'] ?? 50);
    }
}
