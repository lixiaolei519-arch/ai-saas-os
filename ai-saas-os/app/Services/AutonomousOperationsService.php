<?php

namespace App\Services;

use App\Models\AutonomousOperationDraft;
use App\Models\AutonomousOperationTask;
use App\Models\License;
use App\Models\Order;
use App\Models\Plugin;
use App\Models\ProductPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AutonomousOperationsService
{
    public const REPORT_TYPES = ['product_daily_report', 'operations_weekly_report'];

    public function dashboard(): array
    {
        return [
            'simulation_mode' => true,
            'drafts_count' => AutonomousOperationDraft::count(),
            'tasks_count' => AutonomousOperationTask::count(),
            'pending_approval_count' => AutonomousOperationDraft::where('requires_approval', true)->count()
                + AutonomousOperationTask::where('requires_approval', true)->count(),
            'reports_count' => AutonomousOperationDraft::whereIn('type', self::REPORT_TYPES)->count(),
            'seo_plans_count' => AutonomousOperationDraft::where('type', 'seo_content_plan')->count(),
            'landing_pages_count' => AutonomousOperationDraft::where('type', 'landing_page_copy')->count(),
            'customer_emails_count' => AutonomousOperationDraft::where('type', 'customer_email')->count(),
            'recent_drafts' => AutonomousOperationDraft::latest('id')->limit(8)->get(),
            'recent_tasks' => AutonomousOperationTask::latest('id')->limit(8)->get(),
        ];
    }

    public function reports(int $limit = 50): Collection
    {
        return $this->draftsByTypes(self::REPORT_TYPES, $limit);
    }

    public function seoPlans(int $limit = 50): Collection
    {
        return $this->draftsByTypes(['seo_content_plan'], $limit);
    }

    public function landingPages(int $limit = 50): Collection
    {
        return $this->draftsByTypes(['landing_page_copy'], $limit);
    }

    public function pricing(int $limit = 50): Collection
    {
        return $this->draftsByTypes(['pricing_strategy'], $limit);
    }

    public function releaseAnnouncements(int $limit = 50): Collection
    {
        return $this->draftsByTypes(['release_announcement'], $limit);
    }

    public function customerEmails(int $limit = 50): Collection
    {
        return $this->draftsByTypes(['customer_email'], $limit);
    }

    public function faq(int $limit = 50): Collection
    {
        return $this->draftsByTypes(['support_faq'], $limit);
    }

    public function partnerRecruiting(int $limit = 50): Collection
    {
        return $this->draftsByTypes(['partner_recruiting_copy'], $limit);
    }

    public function tasks(int $limit = 50): Collection
    {
        return AutonomousOperationTask::query()->latest('id')->limit($limit)->get();
    }

    public function generateDrafts(): array
    {
        $context = [
            'users' => User::count(),
            'tenants' => Tenant::count(),
            'orders' => Order::count(),
            'paid_orders' => Order::where('status', 'paid')->count(),
            'licenses' => License::count(),
            'plans' => ProductPlan::count(),
            'plugins' => Plugin::count(),
        ];

        $drafts = collect([
            ['product_daily_report', '产品日报草案', '今日产品信号：用户、租户、订单、License 和插件交付能力保持可观测。', 'internal_report', 'management'],
            ['operations_weekly_report', '运营周报草案', '本周运营重点：继续验证商业闭环，保持模拟支付、客户门户和后台可用。', 'internal_report', 'management'],
            ['seo_content_plan', 'SEO 内容计划草案', '围绕 Laravel SaaS、License 授权、宝塔部署、模拟支付和 AI 计费生成内容主题。', 'content_plan', 'search_users'],
            ['landing_page_copy', '落地页文案草案', '主张：可部署的 AI SaaS OS，包含授权、订单、支付回调、客户门户和运营控制台。', 'landing_page', 'prospects'],
            ['pricing_strategy', '价格策略建议草案', '建议保留基础版、专业版、企业版三档，并用 License、AI 用量和插件交付区分套餐。', 'pricing', 'management'],
            ['release_announcement', '版本发布公告草案', 'v2.1.0 引入无人运营中心草案生成能力，所有内容仍需人工审批。', 'release_notes', 'customers'],
            ['customer_email', '客户邮件草案', '主题：您的 AI SaaS OS 控制台已支持更多运营草案生成能力，请登录查看。', 'email', 'customers'],
            ['support_faq', '售后 FAQ 草案', '问：系统会自动发送邮件吗？答：不会，本版本只生成草案，必须人工审批。', 'support', 'customers'],
            ['partner_recruiting_copy', '代理招募文案草案', '招募熟悉企业软件和宝塔部署的渠道伙伴，共同推广 AI SaaS OS。', 'partner', 'partners'],
        ])->map(function (array $draft) use ($context) {
            return AutonomousOperationDraft::create([
                'type' => $draft[0],
                'title' => $draft[1],
                'content' => $draft[2],
                'status' => 'draft',
                'channel' => $draft[3],
                'target_audience' => $draft[4],
                'requires_approval' => true,
                'simulation_mode' => true,
                'generated_at' => now(),
                'metadata' => [
                    'context' => $context,
                    'external_actions' => false,
                    'command' => 'operations:generate-drafts',
                ],
            ]);
        });

        $tasks = collect([
            ['sales_lead_task', '销售线索任务草案', '整理来自演示账号、咨询和渠道推广的潜在线索，人工确认后再跟进。', 'high'],
            ['customer_follow_up_task', '客户跟进任务草案', '对已创建 License 的客户进行人工回访，确认部署体验和续费意向。', 'medium'],
            ['promotion_task', '推广任务草案', '准备宝塔部署案例和模拟支付验收教程，人工审批后发布。', 'medium'],
        ])->map(fn (array $task) => AutonomousOperationTask::create([
            'type' => $task[0],
            'title' => $task[1],
            'description' => $task[2],
            'priority' => $task[3],
            'status' => 'draft',
            'requires_approval' => true,
            'simulation_mode' => true,
            'metadata' => [
                'external_actions' => false,
                'command' => 'operations:generate-drafts',
            ],
        ]));

        return [
            'drafts' => $drafts,
            'tasks' => $tasks,
        ];
    }

    /**
     * @param array<int, string> $types
     */
    private function draftsByTypes(array $types, int $limit): Collection
    {
        return AutonomousOperationDraft::query()
            ->whereIn('type', $types)
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
