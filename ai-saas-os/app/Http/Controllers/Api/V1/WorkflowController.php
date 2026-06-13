<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function store(Request $request, WorkflowService $workflowService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', 'string', 'max:128'],
            'status' => ['nullable', 'string', 'max:32'],
            'nodes' => ['nullable', 'array'],
            'edges' => ['nullable', 'array'],
            'rules' => ['nullable', 'array'],
            'rules.*.node_key' => ['required_with:rules', 'string', 'max:128'],
            'rules.*.field' => ['required_with:rules', 'string', 'max:255'],
            'rules.*.operator' => ['nullable', 'string', 'max:32'],
            'rules.*.expected_value' => ['nullable'],
            'rules.*.action_on_fail' => ['nullable', 'string', 'max:32'],
            'rules.*.metadata' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $workflowService->createDefinition($data),
        ], 201);
    }

    public function run(Request $request, WorkflowService $workflowService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'trigger_event' => ['required', 'string', 'max:128'],
            'payload' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $workflowService->run($data),
        ], 201);
    }

    public function retry(int $run, Request $request, WorkflowService $workflowService): JsonResponse
    {
        $data = $request->validate([
            'payload' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $workflowService->retryRun($run, $data['payload'] ?? null),
        ], 201);
    }
}
