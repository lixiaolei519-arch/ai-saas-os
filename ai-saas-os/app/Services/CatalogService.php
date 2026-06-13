<?php

namespace App\Services;

use App\Models\ProductPlan;

class CatalogService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function createPlan(array $data): ProductPlan
    {
        $plan = ProductPlan::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'] ?? 'subscription',
            'status' => $data['status'] ?? 'active',
            'billing_cycle' => $data['billing_cycle'] ?? 'month',
            'price_cents' => $data['price_cents'] ?? 0,
            'currency' => $data['currency'] ?? 'CNY',
            'features' => $data['features'] ?? [],
            'limits' => $data['limits'] ?? [],
        ]);

        $this->auditService->record('product_plan.created', null, null, $plan);

        return $plan;
    }

    public function activePlans()
    {
        return ProductPlan::query()
            ->where('status', 'active')
            ->orderBy('price_cents')
            ->get();
    }
}
