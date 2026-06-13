<?php

namespace App\Services;

use App\Models\AiUsageRecord;
use App\Models\AiCompanyCodexPrompt;
use App\Models\AiCompanyDailyReport;
use App\Models\AiCompanyIdea;
use App\Models\AiCompanyQualityReport;
use App\Models\AiCompanyReleasePlan;
use App\Models\AiCompanyRiskReport;
use App\Models\AiCompanyRoadmap;
use App\Models\AiCompanyTask;
use App\Models\AutonomousOperationDraft;
use App\Models\AutonomousOperationTask;
use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\MarketingChannel;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Models\Plugin;
use App\Models\PluginDownloadRecord;
use App\Models\ProductFactoryDraft;
use App\Models\ProductFactoryLaunchChecklist;
use App\Models\ProductFactoryTemplate;
use App\Models\SelfEvolutionPlan;
use App\Models\SelfEvolutionReleaseReview;
use App\Models\SelfEvolutionScan;
use App\Models\SelfEvolutionScore;
use App\Models\SelfEvolutionSuggestion;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowEventLog;
use App\Models\WorkflowRun;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class AdminService
{
    public function users(int $limit = 50): Collection
    {
        return User::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function tenants(int $limit = 50): Collection
    {
        return Tenant::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function licenses(int $limit = 50): Collection
    {
        return License::query()
            ->with(['tenant', 'productPlan'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function orders(int $limit = 50): Collection
    {
        return Order::query()
            ->with(['tenant', 'items', 'payments'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function paymentCallbacks(int $limit = 50): Collection
    {
        return PaymentCallback::query()
            ->with('payment')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function channels(int $limit = 50): Collection
    {
        $channels = MarketingChannel::query()
            ->with('promotionLinks')
            ->latest('id')
            ->limit($limit)
            ->get();

        $channels->each(function (MarketingChannel $channel) {
            $channel->setAttribute('orders_count', CommissionRecord::where('marketing_channel_id', $channel->id)->count());
            $channel->setAttribute('commission_amount_cents', CommissionRecord::where('marketing_channel_id', $channel->id)->sum('commission_amount_cents'));
        });

        return $channels;
    }

    public function commissions(int $limit = 50): Collection
    {
        return CommissionRecord::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function aiUsageRecords(int $limit = 50): Collection
    {
        return AiUsageRecord::query()
            ->with(['tenant', 'user'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function plugins(int $limit = 50): Collection
    {
        return Plugin::query()
            ->with('releases.packages')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function pluginDownloadRecords(int $limit = 50): Collection
    {
        return PluginDownloadRecord::query()
            ->with(['tenant', 'plugin', 'release', 'package'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function workflowDefinitions(int $limit = 50): Collection
    {
        return WorkflowDefinition::query()
            ->with(['rules'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function workflowRuns(int $limit = 50): Collection
    {
        return WorkflowRun::query()
            ->with(['tenant', 'workflowDefinition', 'steps'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function workflowEventLogs(int $limit = 50): Collection
    {
        return WorkflowEventLog::query()
            ->with('tenant')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function aiCompanyDashboard(): array
    {
        return app(AiCompanyService::class)->dashboard();
    }

    public function aiCompanyTasks(int $limit = 50): Collection
    {
        return app(AiCompanyService::class)->tasks($limit);
    }

    public function aiCompanyIdeas(int $limit = 50): Collection
    {
        return app(AiCompanyService::class)->ideas($limit);
    }

    public function aiCompanyRoadmaps(int $limit = 50): Collection
    {
        return app(AiCompanyService::class)->roadmaps($limit);
    }

    public function aiCompanyReleasePlans(int $limit = 50): Collection
    {
        return app(AiCompanyService::class)->releasePlans($limit);
    }

    public function aiCompanyQualityReports(int $limit = 50): Collection
    {
        return app(AiCompanyService::class)->qualityReports($limit);
    }

    public function aiCompanyRiskReports(int $limit = 50): Collection
    {
        return app(AiCompanyService::class)->riskReports($limit);
    }

    public function aiCompanyCodexPrompts(int $limit = 50): Collection
    {
        return app(AiCompanyService::class)->codexPrompts($limit);
    }

    public function aiCompanyDailyReports(int $limit = 50): Collection
    {
        return app(AiCompanyService::class)->dailyReports($limit);
    }

    public function selfEvolutionDashboard(): array
    {
        return app(SelfEvolutionService::class)->dashboard();
    }

    public function selfEvolutionScans(int $limit = 50): Collection
    {
        return app(SelfEvolutionService::class)->scans($limit);
    }

    public function selfEvolutionScores(int $limit = 50): Collection
    {
        return app(SelfEvolutionService::class)->scores($limit);
    }

    public function selfEvolutionPlans(int $limit = 50): Collection
    {
        return app(SelfEvolutionService::class)->plans($limit);
    }

    public function selfEvolutionReleaseReviews(int $limit = 50): Collection
    {
        return app(SelfEvolutionService::class)->releaseReviews($limit);
    }

    public function selfEvolutionSuggestions(int $limit = 50): Collection
    {
        return app(SelfEvolutionService::class)->suggestions($limit);
    }

    public function autonomousOperationsDashboard(): array
    {
        return app(AutonomousOperationsService::class)->dashboard();
    }

    public function autonomousOperationReports(int $limit = 50): Collection
    {
        return app(AutonomousOperationsService::class)->reports($limit);
    }

    public function autonomousOperationSeoPlans(int $limit = 50): Collection
    {
        return app(AutonomousOperationsService::class)->seoPlans($limit);
    }

    public function autonomousOperationLandingPages(int $limit = 50): Collection
    {
        return app(AutonomousOperationsService::class)->landingPages($limit);
    }

    public function autonomousOperationPricing(int $limit = 50): Collection
    {
        return app(AutonomousOperationsService::class)->pricing($limit);
    }

    public function autonomousOperationReleaseAnnouncements(int $limit = 50): Collection
    {
        return app(AutonomousOperationsService::class)->releaseAnnouncements($limit);
    }

    public function autonomousOperationCustomerEmails(int $limit = 50): Collection
    {
        return app(AutonomousOperationsService::class)->customerEmails($limit);
    }

    public function autonomousOperationFaq(int $limit = 50): Collection
    {
        return app(AutonomousOperationsService::class)->faq($limit);
    }

    public function autonomousOperationPartnerRecruiting(int $limit = 50): Collection
    {
        return app(AutonomousOperationsService::class)->partnerRecruiting($limit);
    }

    public function productFactoryDashboard(): array
    {
        return app(ProductFactoryService::class)->dashboard();
    }

    public function productFactoryProductTemplates(int $limit = 50): Collection
    {
        return app(ProductFactoryService::class)->templates('product', $limit);
    }

    public function productFactoryPluginTemplates(int $limit = 50): Collection
    {
        return app(ProductFactoryService::class)->templates('plugin', $limit);
    }

    public function productFactoryLandingPageTemplates(int $limit = 50): Collection
    {
        return app(ProductFactoryService::class)->templates('landing_page', $limit);
    }

    public function productFactoryPackageTemplates(int $limit = 50): Collection
    {
        return app(ProductFactoryService::class)->packageTemplates($limit);
    }

    public function productFactoryLaunchChecklists(int $limit = 50): Collection
    {
        return app(ProductFactoryService::class)->launchChecklists($limit);
    }

    public function stats(): array
    {
        return [
            'users_count' => User::count(),
            'tenants_count' => Tenant::count(),
            'licenses_count' => License::count(),
            'orders_count' => Order::count(),
            'paid_orders_count' => Order::where('status', 'paid')->count(),
            'payment_callbacks_count' => PaymentCallback::count(),
            'marketing_channels_count' => MarketingChannel::count(),
            'commission_records_count' => CommissionRecord::count(),
            'commission_amount_cents' => CommissionRecord::sum('commission_amount_cents'),
            'today_orders_count' => Order::whereDate('created_at', today())->count(),
            'today_users_count' => User::whereDate('created_at', today())->count(),
            'ai_usage_records_count' => AiUsageRecord::count(),
            'ai_tokens_used' => AiUsageRecord::sum('total_tokens'),
            'ai_cost_amount' => AiUsageRecord::sum('total_cost_amount'),
            'plugins_count' => Plugin::count(),
            'plugin_download_records_count' => PluginDownloadRecord::count(),
            'workflow_definitions_count' => WorkflowDefinition::count(),
            'workflow_runs_count' => WorkflowRun::count(),
            'workflow_event_logs_count' => WorkflowEventLog::count(),
            'ai_company_tasks_count' => AiCompanyTask::count(),
            'ai_company_ideas_count' => AiCompanyIdea::count(),
            'ai_company_roadmaps_count' => AiCompanyRoadmap::count(),
            'ai_company_release_plans_count' => AiCompanyReleasePlan::count(),
            'ai_company_quality_reports_count' => AiCompanyQualityReport::count(),
            'ai_company_risk_reports_count' => AiCompanyRiskReport::count(),
            'ai_company_codex_prompts_count' => AiCompanyCodexPrompt::count(),
            'ai_company_daily_reports_count' => AiCompanyDailyReport::count(),
            'self_evolution_scans_count' => SelfEvolutionScan::count(),
            'self_evolution_scores_count' => SelfEvolutionScore::count(),
            'self_evolution_plans_count' => SelfEvolutionPlan::count(),
            'self_evolution_release_reviews_count' => SelfEvolutionReleaseReview::count(),
            'self_evolution_suggestions_count' => SelfEvolutionSuggestion::count(),
            'autonomous_operation_drafts_count' => AutonomousOperationDraft::count(),
            'autonomous_operation_tasks_count' => AutonomousOperationTask::count(),
            'product_factory_templates_count' => ProductFactoryTemplate::count(),
            'product_factory_drafts_count' => ProductFactoryDraft::count(),
            'product_factory_launch_checklists_count' => ProductFactoryLaunchChecklist::count(),
        ];
    }

    public function dashboard(): array
    {
        $stats = $this->stats();

        return array_merge($stats, [
            'today_revenue_cents' => Order::where('status', 'paid')->whereDate('paid_at', today())->sum('total_cents'),
            'month_revenue_cents' => Order::where('status', 'paid')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_cents'),
            'pending_orders_count' => Order::where('status', 'pending')->count(),
            'order_trend' => $this->dailyTrend('orders_count'),
            'revenue_trend' => $this->dailyTrend('revenue_cents'),
            'license_status_distribution' => $this->statusDistribution(License::class),
            'commission_status_distribution' => $this->statusDistribution(CommissionRecord::class),
            'recent_orders' => Order::query()
                ->with(['tenant', 'items', 'payments'])
                ->latest('id')
                ->limit(8)
                ->get(),
            'recent_payment_callbacks' => PaymentCallback::query()
                ->with('payment')
                ->latest('id')
                ->limit(8)
                ->get(),
            'recent_licenses' => License::query()
                ->with(['tenant', 'productPlan'])
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }

    public function system(): array
    {
        return [
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug'),
            'database_connected' => $this->databaseConnected(),
            'health_ok' => $this->healthOk(),
            'stable_version' => $this->stableVersion(),
            'git_commit' => $this->gitCommit(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    private function databaseConnected(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function dailyTrend(string $metric): array
    {
        return collect(range(6, 0))->map(function (int $daysAgo) use ($metric) {
            $date = today()->subDays($daysAgo);
            $value = $metric === 'orders_count'
                ? Order::whereDate('created_at', $date)->count()
                : Order::where('status', 'paid')->whereDate('paid_at', $date)->sum('total_cents');

            return [
                'date' => $date->toDateString(),
                $metric => $value,
            ];
        })->values()->all();
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    private function statusDistribution(string $model): array
    {
        return $model::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    private function healthOk(): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create('/health', 'GET'));
            $response = $route->run();
            $payload = json_decode((string) $response->getContent(), true);

            return $response->getStatusCode() === 200 && ($payload['status'] ?? null) === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    private function stableVersion(): string
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

    private function gitCommit(): string
    {
        $headPath = base_path('.git/HEAD');
        if (! is_file($headPath)) {
            return 'unknown';
        }

        $head = trim((string) file_get_contents($headPath));
        if (str_starts_with($head, 'ref: ')) {
            $refPath = base_path('.git/'.substr($head, 5));

            return is_file($refPath) ? substr(trim((string) file_get_contents($refPath)), 0, 7) : 'unknown';
        }

        return substr($head, 0, 7);
    }
}
