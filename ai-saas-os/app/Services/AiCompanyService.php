<?php

namespace App\Services;

use App\Models\AiCompanyCodexPrompt;
use App\Models\AiCompanyDailyReport;
use App\Models\AiCompanyIdea;
use App\Models\AiCompanyQualityReport;
use App\Models\AiCompanyReleasePlan;
use App\Models\AiCompanyRiskReport;
use App\Models\AiCompanyRoadmap;
use App\Models\AiCompanyTask;
use App\Models\AiUsageRecord;
use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\Order;
use App\Models\Plugin;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

class AiCompanyService
{
    public function dashboard(): array
    {
        return [
            'simulation_mode' => true,
            'tasks_count' => AiCompanyTask::count(),
            'ideas_count' => AiCompanyIdea::count(),
            'roadmaps_count' => AiCompanyRoadmap::count(),
            'release_plans_count' => AiCompanyReleasePlan::count(),
            'quality_reports_count' => AiCompanyQualityReport::count(),
            'risk_reports_count' => AiCompanyRiskReport::count(),
            'codex_prompts_count' => AiCompanyCodexPrompt::count(),
            'daily_reports_count' => AiCompanyDailyReport::count(),
            'pending_approval_count' => AiCompanyTask::where('requires_approval', true)->whereNull('approved_at')->count()
                + AiCompanyCodexPrompt::where('requires_approval', true)->count(),
            'latest_quality_report' => AiCompanyQualityReport::latest('id')->first(),
            'latest_risk_report' => AiCompanyRiskReport::latest('id')->first(),
            'latest_daily_report' => AiCompanyDailyReport::latest('report_date')->first(),
            'recent_tasks' => AiCompanyTask::latest('id')->limit(8)->get(),
            'recent_prompts' => AiCompanyCodexPrompt::latest('id')->limit(8)->get(),
        ];
    }

    public function tasks(int $limit = 50): Collection
    {
        return AiCompanyTask::query()->with('tenant')->latest('id')->limit($limit)->get();
    }

    public function ideas(int $limit = 50): Collection
    {
        return AiCompanyIdea::query()->latest('id')->limit($limit)->get();
    }

    public function roadmaps(int $limit = 50): Collection
    {
        return AiCompanyRoadmap::query()->latest('id')->limit($limit)->get();
    }

    public function releasePlans(int $limit = 50): Collection
    {
        return AiCompanyReleasePlan::query()->latest('id')->limit($limit)->get();
    }

    public function qualityReports(int $limit = 50): Collection
    {
        return AiCompanyQualityReport::query()->latest('id')->limit($limit)->get();
    }

    public function riskReports(int $limit = 50): Collection
    {
        return AiCompanyRiskReport::query()->latest('id')->limit($limit)->get();
    }

    public function codexPrompts(int $limit = 50): Collection
    {
        return AiCompanyCodexPrompt::query()->latest('id')->limit($limit)->get();
    }

    public function dailyReports(int $limit = 50): Collection
    {
        return AiCompanyDailyReport::query()->latest('report_date')->limit($limit)->get();
    }

    public function scan(?string $version = null): array
    {
        $version = $version ?: $this->currentStableVersion();
        $requiredDocs = [
            'README.md',
            'BT_PANEL_DEPLOY.md',
            'PRODUCTION_CHECKLIST.md',
            'CHANGELOG.md',
            'STABLE_TAG.md',
            'DEPLOY_AFTER_SLEEP.md',
        ];
        $missingDocs = array_values(array_filter($requiredDocs, fn (string $path) => ! is_file(base_path($path))));
        $todoCount = $this->todoCount();
        $migrationCount = count(File::glob(database_path('migrations/*.php')) ?: []);
        $frontendBuildExists = is_file(public_path('console/index.html'));
        $apiRouteExists = is_file(base_path('routes/api.php'));
        $testCount = collect([base_path('tests/Feature'), base_path('tests/Unit')])
            ->filter(fn (string $directory) => is_dir($directory))
            ->flatMap(fn (string $directory) => File::allFiles($directory))
            ->count();

        $checks = [
            ['name' => 'stable_version', 'status' => $version !== 'unknown' ? 'ok' : 'warning', 'value' => $version],
            ['name' => 'migrations', 'status' => $migrationCount > 0 ? 'ok' : 'warning', 'value' => $migrationCount],
            ['name' => 'feature_tests', 'status' => $testCount > 0 ? 'ok' : 'warning', 'value' => $testCount],
            ['name' => 'frontend_build', 'status' => $frontendBuildExists ? 'ok' : 'warning', 'value' => $frontendBuildExists],
            ['name' => 'api_routes', 'status' => $apiRouteExists ? 'ok' : 'warning', 'value' => $apiRouteExists],
            ['name' => 'required_docs', 'status' => $missingDocs === [] ? 'ok' : 'warning', 'value' => $requiredDocs],
            ['name' => 'todo_markers', 'status' => $todoCount === 0 ? 'ok' : 'observe', 'value' => $todoCount],
        ];
        $gaps = [];
        if ($missingDocs !== []) {
            $gaps[] = [
                'type' => 'documentation',
                'message' => 'Missing production documents: '.implode(', ', $missingDocs),
                'requires_approval' => true,
            ];
        }
        if (! $frontendBuildExists) {
            $gaps[] = [
                'type' => 'frontend_build',
                'message' => 'React console build output is missing.',
                'requires_approval' => true,
            ];
        }
        if ($todoCount > 0) {
            $gaps[] = [
                'type' => 'todo',
                'message' => 'Repository contains TODO/FIXME markers that should be triaged.',
                'count' => $todoCount,
                'requires_approval' => true,
            ];
        }
        $score = max(60, 100 - (count($gaps) * 8) - min(15, $todoCount));

        $qualityReport = AiCompanyQualityReport::create([
            'version' => $version,
            'status' => 'draft',
            'score' => $score,
            'checks' => $checks,
            'gaps' => $gaps,
            'recommendations' => [
                'Keep release gates mandatory before each stable tag.',
                'Continue expanding high-value feature tests before autonomous execution is allowed.',
                'Keep all AI Company OS output in draft and approval-required mode.',
            ],
            'simulation_mode' => true,
            'generated_at' => now(),
            'metadata' => [
                'command' => 'ai-company:scan',
                'external_calls' => false,
            ],
        ]);

        $riskReport = AiCompanyRiskReport::create([
            'version' => $version,
            'status' => 'draft',
            'severity' => $gaps === [] ? 'low' : 'medium',
            'risks' => [
                [
                    'name' => 'autonomous_execution',
                    'severity' => 'high',
                    'description' => 'AI Company OS must not directly modify production code, deploy, spend money, or contact users.',
                ],
                [
                    'name' => 'credential_exposure',
                    'severity' => 'high',
                    'description' => 'Generated prompts and reports must not include .env secrets, APP_KEY, database passwords, or payment credentials.',
                ],
                [
                    'name' => 'quality_gate_bypass',
                    'severity' => 'medium',
                    'description' => 'Stable releases require audit, migration, seed, tests, and frontend build when applicable.',
                ],
            ],
            'mitigations' => [
                'All records are stored as draft simulation output.',
                'Approval is required before any suggested task can be executed.',
                'Commands only write database records and do not call external services.',
            ],
            'simulation_mode' => true,
            'generated_at' => now(),
            'metadata' => [
                'quality_report_id' => $qualityReport->id,
                'external_calls' => false,
            ],
        ]);

        return [
            'quality_report' => $qualityReport,
            'risk_report' => $riskReport,
        ];
    }

    public function plan(?string $targetVersion = null): array
    {
        $targetVersion = $targetVersion ?: 'v2.0.0';
        $qualityReport = AiCompanyQualityReport::latest('id')->first()
            ?: $this->scan($this->currentStableVersion())['quality_report'];

        $idea = AiCompanyIdea::create([
            'title' => 'Build Self-Evolution Engine in approval mode',
            'description' => 'Create an internal self-evolution planning layer that proposes improvements without changing code or deployment state.',
            'source' => 'ai-company:plan',
            'status' => 'draft',
            'score' => 88,
            'simulation_mode' => true,
            'metadata' => [
                'target_version' => $targetVersion,
                'quality_report_id' => $qualityReport->id,
            ],
        ]);

        $roadmap = AiCompanyRoadmap::create([
            'title' => 'AI Company OS safe evolution roadmap',
            'version' => $targetVersion,
            'status' => 'draft',
            'summary' => 'Keep the next evolution step limited to internal issue discovery, improvement proposals, prompt drafts, and manual approval.',
            'items' => [
                ['name' => 'Self-evolution issue scanner', 'status' => 'draft'],
                ['name' => 'Improvement proposal queue', 'status' => 'draft'],
                ['name' => 'Manual approval workflow', 'status' => 'draft'],
                ['name' => 'Release-readiness scoring', 'status' => 'draft'],
            ],
            'simulation_mode' => true,
            'metadata' => [
                'idea_id' => $idea->id,
                'external_calls' => false,
            ],
        ]);

        $releasePlan = AiCompanyReleasePlan::create([
            'version' => $targetVersion,
            'title' => 'Self-Evolution Engine safe foundation',
            'status' => 'draft',
            'scope' => [
                'issue discovery records',
                'improvement proposal records',
                'manual approval status',
                'quality/risk scoring',
                'Codex prompt drafts',
            ],
            'quality_gate' => [
                'composer audit --no-interaction',
                'php artisan migrate:fresh --env=testing --force',
                'php artisan db:seed --env=testing --force',
                'php artisan test',
                'npm install && npm run build when frontend changes',
            ],
            'deployment_notes' => 'Simulation only. Do not execute external actions or deploy production changes automatically.',
            'requires_approval' => true,
            'simulation_mode' => true,
            'metadata' => [
                'roadmap_id' => $roadmap->id,
                'quality_report_id' => $qualityReport->id,
            ],
        ]);

        $tasks = collect([
            [
                'title' => 'Design self-evolution proposal tables',
                'category' => 'product',
                'priority' => 'high',
                'recommendation' => 'Add database records for discovered issues, proposed improvements, approval state, and generated Codex prompts.',
            ],
            [
                'title' => 'Add safe scan command for improvement discovery',
                'category' => 'engineering',
                'priority' => 'high',
                'recommendation' => 'Create a command that scans local project metadata and writes draft findings without modifying code.',
            ],
            [
                'title' => 'Expose manual approval console views',
                'category' => 'operations',
                'priority' => 'medium',
                'recommendation' => 'Show proposed improvements and generated prompts to administrators before any execution step exists.',
            ],
        ])->map(fn (array $task) => AiCompanyTask::create(array_merge($task, [
            'status' => 'draft',
            'requires_approval' => true,
            'simulation_mode' => true,
            'source' => 'ai-company:plan',
            'metadata' => [
                'target_version' => $targetVersion,
                'idea_id' => $idea->id,
                'roadmap_id' => $roadmap->id,
                'release_plan_id' => $releasePlan->id,
                'external_calls' => false,
            ],
        ])));

        return [
            'idea' => $idea,
            'roadmap' => $roadmap,
            'release_plan' => $releasePlan,
            'tasks' => $tasks,
        ];
    }

    public function generatePrompts(?string $targetVersion = null): Collection
    {
        $targetVersion = $targetVersion ?: 'v2.0.0';
        if (AiCompanyTask::count() === 0) {
            $this->plan($targetVersion);
        }

        return AiCompanyTask::query()
            ->where('status', 'draft')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function (AiCompanyTask $task) use ($targetVersion) {
                $prompt = $this->promptForTask($task, $targetVersion);
                $task->update(['codex_prompt' => $prompt]);

                return AiCompanyCodexPrompt::create([
                    'title' => 'Codex draft: '.$task->title,
                    'target_version' => $targetVersion,
                    'prompt' => $prompt,
                    'status' => 'draft',
                    'requires_approval' => true,
                    'simulation_mode' => true,
                    'source_type' => AiCompanyTask::class,
                    'source_id' => $task->id,
                    'generated_at' => now(),
                    'metadata' => [
                        'task_priority' => $task->priority,
                        'task_category' => $task->category,
                        'external_calls' => false,
                    ],
                ]);
            });
    }

    public function dailyReport(?string $date = null): AiCompanyDailyReport
    {
        $reportDate = $date ?: today()->toDateString();

        return AiCompanyDailyReport::updateOrCreate([
            'report_date' => $reportDate,
        ], [
            'status' => 'draft',
            'summary' => 'AI Company OS simulation report generated for internal review. No external actions were executed.',
            'product' => [
                'licenses' => License::count(),
                'plugins' => Plugin::count(),
                'workflows' => WorkflowDefinition::count(),
                'open_ai_company_tasks' => AiCompanyTask::where('status', 'draft')->count(),
            ],
            'technology' => [
                'users' => User::count(),
                'tenants' => Tenant::count(),
                'ai_usage_records' => AiUsageRecord::count(),
                'latest_quality_score' => AiCompanyQualityReport::latest('id')->value('score'),
            ],
            'sales' => [
                'orders' => Order::count(),
                'paid_orders' => Order::where('status', 'paid')->count(),
                'commission_records' => CommissionRecord::count(),
                'commission_amount_cents' => CommissionRecord::sum('commission_amount_cents'),
            ],
            'risks' => [
                'latest_risk_severity' => AiCompanyRiskReport::latest('id')->value('severity') ?: 'unknown',
                'requires_manual_approval' => true,
                'external_actions_executed' => false,
            ],
            'next_steps' => [
                'Review draft tasks and prompts before execution.',
                'Keep self-evolution features behind manual approval.',
                'Run the full quality gate before marking the next version stable.',
            ],
            'simulation_mode' => true,
            'generated_at' => now(),
            'metadata' => [
                'command' => 'ai-company:daily-report',
                'external_calls' => false,
            ],
        ]);
    }

    private function promptForTask(AiCompanyTask $task, string $targetVersion): string
    {
        return <<<PROMPT
You are working in ai-saas-os.

Target version: {$targetVersion}
Task: {$task->title}
Priority: {$task->priority}
Mode: simulation draft only.

Implement only the minimum safe change needed for this task. Do not call external services, do not deploy, do not push production changes, do not write secrets, and keep all AI Company OS actions behind manual approval.

Recommendation:
{$task->recommendation}

Required gate:
- composer audit --no-interaction
- php artisan migrate:fresh --env=testing --force
- php artisan db:seed --env=testing --force
- php artisan test
- npm install && npm run build if frontend files change
PROMPT;
    }

    private function currentStableVersion(): string
    {
        $path = base_path('STABLE_TAG.md');
        if (! is_file($path)) {
            return 'unknown';
        }

        $contents = (string) file_get_contents($path);

        return preg_match('/Current stable version:\s*(.+)/', $contents, $matches)
            ? trim($matches[1])
            : 'unknown';
    }

    private function todoCount(): int
    {
        $directories = [
            app_path(),
            base_path('routes'),
            database_path(),
            base_path('tests'),
            base_path('frontend/admin-console/src'),
            base_path('docs'),
        ];

        return collect($directories)
            ->filter(fn (string $directory) => is_dir($directory))
            ->flatMap(fn (string $directory) => File::allFiles($directory))
            ->filter(fn ($file) => in_array($file->getExtension(), ['php', 'js', 'jsx', 'md'], true))
            ->sum(function ($file) {
                $contents = (string) file_get_contents($file->getPathname());

                return preg_match_all('/\b(TODO|FIXME)\b/i', $contents);
            });
    }
}
