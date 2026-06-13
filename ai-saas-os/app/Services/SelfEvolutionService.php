<?php

namespace App\Services;

use App\Models\AiCompanyCodexPrompt;
use App\Models\AiCompanyTask;
use App\Models\AiUsageRecord;
use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\Order;
use App\Models\Plugin;
use App\Models\SelfEvolutionPlan;
use App\Models\SelfEvolutionReleaseReview;
use App\Models\SelfEvolutionScan;
use App\Models\SelfEvolutionScore;
use App\Models\SelfEvolutionSuggestion;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

class SelfEvolutionService
{
    public function dashboard(): array
    {
        return [
            'simulation_mode' => true,
            'scans_count' => SelfEvolutionScan::count(),
            'scores_count' => SelfEvolutionScore::count(),
            'plans_count' => SelfEvolutionPlan::count(),
            'release_reviews_count' => SelfEvolutionReleaseReview::count(),
            'suggestions_count' => SelfEvolutionSuggestion::count(),
            'pending_approval_count' => SelfEvolutionPlan::where('requires_approval', true)->count()
                + SelfEvolutionReleaseReview::where('requires_approval', true)->count()
                + SelfEvolutionSuggestion::where('requires_approval', true)->count(),
            'latest_scan' => SelfEvolutionScan::latest('id')->first(),
            'latest_score' => SelfEvolutionScore::latest('id')->first(),
            'latest_plan' => SelfEvolutionPlan::latest('id')->first(),
            'latest_release_review' => SelfEvolutionReleaseReview::latest('id')->first(),
            'recent_suggestions' => SelfEvolutionSuggestion::latest('id')->limit(8)->get(),
        ];
    }

    public function scans(int $limit = 50): Collection
    {
        return SelfEvolutionScan::query()->latest('id')->limit($limit)->get();
    }

    public function scores(int $limit = 50): Collection
    {
        return SelfEvolutionScore::query()->latest('id')->limit($limit)->get();
    }

    public function plans(int $limit = 50): Collection
    {
        return SelfEvolutionPlan::query()->latest('id')->limit($limit)->get();
    }

    public function releaseReviews(int $limit = 50): Collection
    {
        return SelfEvolutionReleaseReview::query()->latest('id')->limit($limit)->get();
    }

    public function suggestions(int $limit = 50): Collection
    {
        return SelfEvolutionSuggestion::query()->latest('id')->limit($limit)->get();
    }

    public function scan(?string $version = null): SelfEvolutionScan
    {
        $version = $version ?: $this->currentStableVersion();
        $metrics = [
            'users' => User::count(),
            'tenants' => Tenant::count(),
            'licenses' => License::count(),
            'orders' => Order::count(),
            'paid_orders' => Order::where('status', 'paid')->count(),
            'plugins' => Plugin::count(),
            'workflows' => WorkflowDefinition::count(),
            'ai_usage_records' => AiUsageRecord::count(),
            'ai_company_tasks' => AiCompanyTask::count(),
            'ai_company_prompts' => AiCompanyCodexPrompt::count(),
            'feature_tests' => $this->countFiles(base_path('tests/Feature'), ['php']),
            'migrations' => count(File::glob(database_path('migrations/*.php')) ?: []),
            'console_build_exists' => is_file(public_path('console/index.html')),
            'required_docs_exist' => $this->requiredDocsExist(),
        ];
        $findings = [
            [
                'category' => 'product',
                'title' => 'Commercial SaaS foundation is present',
                'evidence' => ['licenses' => $metrics['licenses'], 'orders' => $metrics['orders']],
            ],
            [
                'category' => 'technology',
                'title' => 'Automated tests and migrations are available',
                'evidence' => ['feature_tests' => $metrics['feature_tests'], 'migrations' => $metrics['migrations']],
            ],
            [
                'category' => 'safety',
                'title' => 'Self-evolution must remain draft-only',
                'evidence' => ['simulation_mode' => true, 'external_actions' => false],
            ],
        ];

        return SelfEvolutionScan::create([
            'version' => $version,
            'status' => 'draft',
            'summary' => 'Self-evolution scan generated local project signals for safe internal review.',
            'findings' => $findings,
            'metrics' => $metrics,
            'simulation_mode' => true,
            'generated_at' => now(),
            'metadata' => [
                'command' => 'self-evolve:scan',
                'external_calls' => false,
            ],
        ]);
    }

    public function score(?string $version = null): SelfEvolutionScore
    {
        $version = $version ?: $this->currentStableVersion();
        $scan = SelfEvolutionScan::latest('id')->first() ?: $this->scan($version);
        $metrics = $scan->metrics ?? [];

        $dimensions = [
            ['name' => '产品完整度', 'score' => $metrics['licenses'] > 0 || $metrics['orders'] > 0 ? 82 : 72],
            ['name' => '技术稳定性', 'score' => $metrics['migrations'] >= 10 ? 88 : 75],
            ['name' => '测试覆盖', 'score' => $metrics['feature_tests'] >= 20 ? 86 : 70],
            ['name' => '部署安全', 'score' => $metrics['required_docs_exist'] ? 90 : 68],
            ['name' => '商业化程度', 'score' => 82],
            ['name' => '客户体验', 'score' => is_file(public_path('console/index.html')) ? 84 : 65],
            ['name' => '运维风险', 'score' => 78],
            ['name' => '收入能力', 'score' => CommissionRecord::count() > 0 || Order::count() > 0 ? 80 : 70],
            ['name' => '自动化程度', 'score' => AiCompanyTask::count() > 0 ? 84 : 74],
            ['name' => '下一步优先级', 'score' => 88],
        ];
        $overall = (int) round(collect($dimensions)->avg('score'));

        return SelfEvolutionScore::create([
            'version' => $version,
            'status' => 'draft',
            'overall_score' => $overall,
            'dimensions' => $dimensions,
            'recommendations' => [
                'Prioritize approval workflow and auditability before any semi-automatic execution.',
                'Keep generated plans tied to quality gates and rollback guidance.',
                'Add operator-facing views before increasing autonomy.',
            ],
            'simulation_mode' => true,
            'generated_at' => now(),
            'metadata' => [
                'scan_id' => $scan->id,
                'external_calls' => false,
            ],
        ]);
    }

    public function plan(?string $targetVersion = null): SelfEvolutionPlan
    {
        $targetVersion = $targetVersion ?: 'v2.1.0';
        $score = SelfEvolutionScore::latest('id')->first() ?: $this->score($this->currentStableVersion());

        return SelfEvolutionPlan::create([
            'target_version' => $targetVersion,
            'title' => 'Autonomous Operations Center safe draft plan',
            'status' => 'draft',
            'tasks' => [
                ['name' => 'Generate product daily report drafts', 'priority' => 'high', 'requires_approval' => true],
                ['name' => 'Generate operations weekly report drafts', 'priority' => 'high', 'requires_approval' => true],
                ['name' => 'Generate sales lead task drafts', 'priority' => 'medium', 'requires_approval' => true],
                ['name' => 'Add manual approval queue before any outbound action', 'priority' => 'high', 'requires_approval' => true],
            ],
            'version_plan' => [
                'target_version' => $targetVersion,
                'goal' => 'Internal autonomous operations drafts only.',
                'blocked_actions' => ['send_email', 'send_sms', 'publish_ads', 'contact_customer', 'deploy_production'],
                'quality_gate' => [
                    'composer audit --no-interaction',
                    'php artisan migrate:fresh --env=testing --force',
                    'php artisan db:seed --env=testing --force',
                    'php artisan test',
                    'npm install && npm run build when frontend changes',
                ],
            ],
            'requires_approval' => true,
            'simulation_mode' => true,
            'generated_at' => now(),
            'metadata' => [
                'score_id' => $score->id,
                'external_calls' => false,
            ],
        ]);
    }

    public function reviewRelease(?string $version = null): SelfEvolutionReleaseReview
    {
        $version = $version ?: $this->currentStableVersion();
        $score = SelfEvolutionScore::latest('id')->first() ?: $this->score($version);
        $checklist = [
            ['name' => 'Composer audit passed', 'required' => true],
            ['name' => 'Testing migration passed', 'required' => true],
            ['name' => 'Testing seed passed', 'required' => true],
            ['name' => 'Full test suite passed', 'required' => true],
            ['name' => 'Frontend build passed when changed', 'required' => true],
            ['name' => 'Rollback guidance updated', 'required' => true],
            ['name' => 'Manual approval retained', 'required' => true],
        ];
        $rollback = ['Keep previous stable commit hash available and avoid destructive database rollback without backup validation.'];
        $deployment = ['Deploy code, run migrations, rebuild caches, restart queue workers, then run app:smoke-test.'];
        $testing = ['Add focused tests for new modules and run the full suite before tagging stable.'];
        $security = ['Do not expose .env, APP_KEY, database credentials, payment keys, or real model-provider keys.'];
        $business = ['Validate customer-facing value before enabling any outbound automation.'];

        $review = SelfEvolutionReleaseReview::create([
            'version' => $version,
            'status' => 'draft',
            'decision' => $score->overall_score >= 80 ? 'ready_for_manual_review' : 'needs_improvement',
            'checklist' => $checklist,
            'rollback_suggestions' => $rollback,
            'deployment_suggestions' => $deployment,
            'testing_suggestions' => $testing,
            'security_suggestions' => $security,
            'business_suggestions' => $business,
            'requires_approval' => true,
            'simulation_mode' => true,
            'generated_at' => now(),
            'metadata' => [
                'score_id' => $score->id,
                'external_calls' => false,
            ],
        ]);

        collect([
            ['category' => 'rollback', 'title' => 'Prepare rollback checkpoint', 'body' => $rollback[0], 'priority' => 'high'],
            ['category' => 'deployment', 'title' => 'Run deployment verification sequence', 'body' => $deployment[0], 'priority' => 'high'],
            ['category' => 'testing', 'title' => 'Maintain full quality gate', 'body' => $testing[0], 'priority' => 'high'],
            ['category' => 'security', 'title' => 'Keep secrets out of generated drafts', 'body' => $security[0], 'priority' => 'high'],
            ['category' => 'business', 'title' => 'Review customer value before automation', 'body' => $business[0], 'priority' => 'medium'],
        ])->each(function (array $suggestion) use ($version, $review) {
            SelfEvolutionSuggestion::create(array_merge($suggestion, [
                'version' => $version,
                'status' => 'draft',
                'requires_approval' => true,
                'simulation_mode' => true,
                'metadata' => [
                    'release_review_id' => $review->id,
                    'external_calls' => false,
                ],
            ]));
        });

        return $review;
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

    /**
     * @param array<int, string> $extensions
     */
    private function countFiles(string $directory, array $extensions): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        return collect(File::allFiles($directory))
            ->filter(fn ($file) => in_array($file->getExtension(), $extensions, true))
            ->count();
    }

    private function requiredDocsExist(): bool
    {
        foreach (['README.md', 'CHANGELOG.md', 'STABLE_TAG.md', 'BT_PANEL_DEPLOY.md', 'PRODUCTION_CHECKLIST.md'] as $path) {
            if (! is_file(base_path($path))) {
                return false;
            }
        }

        return true;
    }
}
