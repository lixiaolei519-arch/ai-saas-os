<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PluginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function store(Request $request, PluginService $pluginService): JsonResponse
    {
        $data = $request->validate([
            'developer_tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:plugins,slug'],
            'category' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'manifest' => ['nullable', 'array'],
            'version' => ['nullable', 'string', 'max:64'],
            'package_path' => ['nullable', 'string', 'max:255'],
            'checksum' => ['nullable', 'string', 'max:128'],
            'size_bytes' => ['nullable', 'integer', 'min:0'],
            'package_metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $pluginService->publish($data),
        ], 201);
    }

    public function install(Request $request, PluginService $pluginService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'plugin_id' => ['required', 'integer', 'exists:plugins,id'],
            'config' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $pluginService->install($data),
        ], 201);
    }

    public function uploadRelease(int $plugin, Request $request, PluginService $pluginService): JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
            'package_path' => ['required', 'string', 'max:255'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'checksum' => ['nullable', 'string', 'max:128'],
            'size_bytes' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'package_metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $pluginService->uploadReleasePackage($plugin, $data),
        ], 201);
    }

    public function issueDownloadToken(Request $request, PluginService $pluginService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'plugin_release_id' => ['required', 'integer', 'exists:plugin_releases,id'],
            'license_key' => ['required', 'string'],
            'domain' => ['nullable', 'string', 'max:255'],
            'fingerprint' => ['required', 'string', 'max:255'],
            'ttl_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'metadata' => ['nullable', 'array'],
        ]);

        $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        return response()->json([
            'data' => $pluginService->issueDownloadToken($data),
        ], 201);
    }

    public function verifyDownloadToken(Request $request, PluginService $pluginService): JsonResponse
    {
        $data = $request->validate([
            'download_token' => ['required', 'string'],
        ]);

        return response()->json([
            'data' => $pluginService->verifyDownloadToken($data['download_token']),
        ]);
    }

    public function checkUpdate(Request $request, PluginService $pluginService): JsonResponse
    {
        $data = $request->validate([
            'plugin_id' => ['required', 'integer', 'exists:plugins,id'],
            'current_version' => ['required', 'string', 'max:64'],
        ]);

        return response()->json([
            'data' => $pluginService->checkUpdate($data),
        ]);
    }
}
