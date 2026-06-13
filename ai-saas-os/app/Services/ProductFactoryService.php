<?php

namespace App\Services;

use App\Models\ProductFactoryDraft;
use App\Models\ProductFactoryLaunchChecklist;
use App\Models\ProductFactoryTemplate;
use Illuminate\Database\Eloquent\Collection;

class ProductFactoryService
{
    public function dashboard(): array
    {
        return [
            'simulation_mode' => true,
            'templates_count' => ProductFactoryTemplate::count(),
            'drafts_count' => ProductFactoryDraft::count(),
            'launch_checklists_count' => ProductFactoryLaunchChecklist::count(),
            'pending_approval_count' => ProductFactoryTemplate::where('requires_approval', true)->count()
                + ProductFactoryDraft::where('requires_approval', true)->count()
                + ProductFactoryLaunchChecklist::where('requires_approval', true)->count(),
            'recent_templates' => ProductFactoryTemplate::latest('id')->limit(8)->get(),
            'recent_drafts' => ProductFactoryDraft::with('template')->latest('id')->limit(8)->get(),
        ];
    }

    public function templates(string $type, int $limit = 50): Collection
    {
        return ProductFactoryTemplate::query()
            ->where('type', $type)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function packageTemplates(int $limit = 50): Collection
    {
        return ProductFactoryTemplate::query()
            ->whereIn('type', ['pricing_package', 'license_package'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function launchChecklists(int $limit = 50): Collection
    {
        return ProductFactoryLaunchChecklist::query()->latest('id')->limit($limit)->get();
    }

    public function generateDrafts(): array
    {
        $templates = collect([
            ['product', '可售卖 SaaS 产品模板', '面向中小企业的 AI SaaS 控制台产品规划模板。'],
            ['plugin', '授权下载插件模板', '围绕 License 授权和版本交付的插件规划模板。'],
            ['landing_page', '中文落地页模板', '适合宝塔部署和企业软件采购场景的落地页结构。'],
            ['pricing_package', '三档价格套餐模板', '基础版、专业版、企业版价格建议模板。'],
            ['license_package', 'License 授权包模板', '按域名、激活次数、有效期和插件权限组合授权。'],
        ])->map(fn (array $template) => ProductFactoryTemplate::create([
            'type' => $template[0],
            'name' => $template[1],
            'description' => $template[2],
            'schema' => [
                'fields' => ['name', 'positioning', 'features', 'pricing', 'license_rules'],
                'output' => 'draft_only',
            ],
            'status' => 'draft',
            'requires_approval' => true,
            'simulation_mode' => true,
            'metadata' => [
                'command' => 'product-factory:generate-drafts',
                'external_actions' => false,
            ],
        ]));

        $templateByType = $templates->keyBy('type');
        $drafts = collect([
            ['product_plan', '新产品规划草案', '生成一个可售卖 AI SaaS 运营控制台产品规划，包含目标客户、核心模块和交付范围。', 'product'],
            ['plugin_plan', '新插件规划草案', '生成一个订单导出插件规划，包含版本、授权下载和更新检查。', 'plugin'],
            ['landing_page_copy', '落地页文案草案', '生成中文落地页文案：强调宝塔可部署、模拟支付验收、License 授权和客户门户。', 'landing_page'],
            ['pricing_suggestion', '价格套餐建议草案', '建议基础版 99 元/月、专业版 299 元/月、企业版按年报价，需人工确认。', 'pricing_package'],
            ['license_rule_suggestion', 'License 规则建议草案', '建议按域名、激活数量、有效期、插件下载权限和 AI 用量额度组合授权。', 'license_package'],
            ['codex_prompt', 'Codex 开发指令草案', '为下一阶段生成任务：实现产品工厂审批流，不直接创建真实外部网站或自动售卖。', 'product'],
        ])->map(function (array $draft) use ($templateByType) {
            return ProductFactoryDraft::create([
                'product_factory_template_id' => $templateByType[$draft[3]]->id ?? null,
                'type' => $draft[0],
                'title' => $draft[1],
                'content' => $draft[2],
                'status' => 'draft',
                'requires_approval' => true,
                'simulation_mode' => true,
                'generated_at' => now(),
                'metadata' => [
                    'template_type' => $draft[3],
                    'external_actions' => false,
                    'command' => 'product-factory:generate-drafts',
                ],
            ]);
        });

        $checklist = ProductFactoryLaunchChecklist::create([
            'title' => '产品发布清单草案',
            'status' => 'draft',
            'items' => [
                ['name' => '确认产品定位', 'required' => true],
                ['name' => '确认价格套餐', 'required' => true],
                ['name' => '确认 License 规则', 'required' => true],
                ['name' => '确认落地页文案', 'required' => true],
                ['name' => '通过完整质量门禁', 'required' => true],
                ['name' => '人工批准后再发布', 'required' => true],
            ],
            'requires_approval' => true,
            'simulation_mode' => true,
            'generated_at' => now(),
            'metadata' => [
                'external_actions' => false,
                'command' => 'product-factory:generate-drafts',
            ],
        ]);

        return [
            'templates' => $templates,
            'drafts' => $drafts,
            'launch_checklist' => $checklist,
        ];
    }
}
