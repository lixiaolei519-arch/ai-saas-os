<?php

namespace App\Services;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowEventLog;
use App\Models\WorkflowRun;
use App\Models\WorkflowRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkflowService
{
    public const SUPPORTED_EVENTS = [
        'order.created',
        'order.paid',
        'license.created',
        'commission.generated',
        'user.registered',
        'lead.created',
    ];

    public const SUPPORTED_ACTIONS = [
        'noop',
        'notification',
        'webhook',
        'renewal',
        'create_notification',
        'create_commission',
        'create_license',
        'write_audit_log',
    ];

    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function createDefinition(array $data): WorkflowDefinition
    {
        $workflow = DB::transaction(function () use ($data) {
            $workflow = WorkflowDefinition::create([
                'tenant_id' => $data['tenant_id'],
                'name' => $data['name'],
                'trigger_event' => $data['trigger_event'],
                'status' => $data['status'] ?? 'active',
                'nodes' => $data['nodes'] ?? [],
                'edges' => $data['edges'] ?? [],
                'metadata' => $data['metadata'] ?? [],
            ]);

            foreach (($data['rules'] ?? []) as $rule) {
                WorkflowRule::create([
                    'workflow_definition_id' => $workflow->id,
                    'node_key' => $rule['node_key'],
                    'field' => $rule['field'],
                    'operator' => $rule['operator'] ?? 'equals',
                    'expected_value' => ['value' => $rule['expected_value'] ?? null],
                    'action_on_fail' => $rule['action_on_fail'] ?? 'fail',
                    'metadata' => $rule['metadata'] ?? [],
                ]);
            }

            return $workflow->fresh('rules');
        });

        $this->auditService->record('workflow.created', $workflow->tenant_id, null, $workflow);

        return $workflow;
    }

    public function run(array $data): WorkflowRun
    {
        return DB::transaction(function () use ($data) {
            $workflow = WorkflowDefinition::where('tenant_id', $data['tenant_id'])
                ->where('trigger_event', $data['trigger_event'])
                ->where('status', 'active')
                ->firstOrFail();
            $this->recordEvent($data['tenant_id'], $data['trigger_event'], $data['payload'] ?? [], 1, [
                'source' => 'manual_run',
            ]);

            return $this->executeWorkflow($workflow, $data['payload'] ?? []);
        });
    }

    public function triggerEvent(int $tenantId, string $triggerEvent, array $payload = []): Collection
    {
        $workflows = WorkflowDefinition::with('rules')
            ->where('tenant_id', $tenantId)
            ->where('trigger_event', $triggerEvent)
            ->where('status', 'active')
            ->get();

        $this->recordEvent($tenantId, $triggerEvent, $payload, $workflows->count(), [
            'source' => 'event_trigger',
        ]);

        return $workflows->map(fn (WorkflowDefinition $workflow) => $this->executeWorkflow($workflow, $payload));
    }

    public function retryRun(int $runId, ?array $payload = null): WorkflowRun
    {
        $run = WorkflowRun::findOrFail($runId);
        $workflow = WorkflowDefinition::with('rules')->findOrFail($run->workflow_definition_id);

        return $this->executeWorkflow($workflow, $payload ?? $run->payload ?? []);
    }

    private function executeWorkflow(WorkflowDefinition $workflow, array $payload): WorkflowRun
    {
        $workflow->loadMissing('rules');

        $run = WorkflowRun::create([
            'tenant_id' => $workflow->tenant_id,
            'workflow_definition_id' => $workflow->id,
            'status' => 'running',
            'trigger_event' => $workflow->trigger_event,
            'payload' => $payload,
            'started_at' => now(),
        ]);

        $failed = false;

        foreach (($workflow->nodes ?? []) as $index => $node) {
            $nodeKey = $node['key'] ?? 'node_'.$index;
            $step = $run->steps()->create([
                'node_key' => $nodeKey,
                'status' => 'running',
                'input' => $payload,
                'started_at' => now(),
            ]);

            $ruleResult = $this->evaluateRules($workflow, $nodeKey, $payload);

            if (! $ruleResult['passed']) {
                $status = $ruleResult['action_on_fail'] === 'skip' ? 'skipped' : 'failed';
                $step->update([
                    'status' => $status,
                    'output' => $ruleResult,
                    'error_message' => $status === 'failed' ? 'workflow_rule_failed' : null,
                    'finished_at' => now(),
                ]);

                if ($status === 'failed') {
                    $failed = true;
                    break;
                }

                continue;
            }

            try {
                $step->update([
                    'status' => 'completed',
                    'output' => $this->executeAction($node, $payload),
                    'finished_at' => now(),
                ]);
            } catch (InvalidArgumentException $exception) {
                $step->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'finished_at' => now(),
                ]);
                $failed = true;
                break;
            }
        }

        $run->update([
            'status' => $failed ? 'failed' : 'completed',
            'finished_at' => now(),
            'error_message' => $failed ? 'workflow_execution_failed' : null,
        ]);

        $this->auditService->record($failed ? 'workflow.failed' : 'workflow.completed', $run->tenant_id, null, $run);

        return $run->fresh(['steps']);
    }

    private function evaluateRules(WorkflowDefinition $workflow, string $nodeKey, array $payload): array
    {
        $rules = $workflow->rules->where('node_key', $nodeKey);

        foreach ($rules as $rule) {
            $actual = data_get($payload, $rule->field);
            $expected = $rule->expected_value['value'] ?? null;
            $passed = match ($rule->operator) {
                'not_equals' => $actual != $expected,
                'greater_than' => $actual > $expected,
                'less_than' => $actual < $expected,
                'present' => $actual !== null,
                default => $actual == $expected,
            };

            if (! $passed) {
                return [
                    'passed' => false,
                    'failed_rule_id' => $rule->id,
                    'field' => $rule->field,
                    'operator' => $rule->operator,
                    'expected' => $expected,
                    'actual' => $actual,
                    'action_on_fail' => $rule->action_on_fail,
                ];
            }
        }

        return ['passed' => true];
    }

    private function executeAction(array $node, array $payload): array
    {
        $type = $node['type'] ?? 'noop';
        $action = $node['action'] ?? null;
        $handler = in_array($action, self::SUPPORTED_ACTIONS, true) ? $action : $type;

        if (! in_array($handler, self::SUPPORTED_ACTIONS, true)) {
            throw new InvalidArgumentException('unsupported_workflow_action');
        }

        return [
            'handled_by' => $handler,
            'payload_keys' => array_keys($payload),
            'action' => $action,
            'simulation' => true,
        ];
    }

    private function recordEvent(int $tenantId, string $eventName, array $payload, int $matchedWorkflowsCount, array $metadata = []): WorkflowEventLog
    {
        return WorkflowEventLog::create([
            'tenant_id' => $tenantId,
            'event_name' => $eventName,
            'status' => 'processed',
            'matched_workflows_count' => $matchedWorkflowsCount,
            'payload' => $payload,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
