<?php

namespace App\Services;

use App\Models\CommissionRecord;
use App\Models\MarketingChannel;
use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\PromotionAttribution;
use App\Models\PromotionLink;
use App\Models\RenewalSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketingService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly OrderService $orderService,
    ) {
    }

    public function createTemplate(array $data): NotificationTemplate
    {
        $template = NotificationTemplate::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'code' => $data['code'],
            'name' => $data['name'],
            'channel' => $data['channel'] ?? 'email',
            'status' => $data['status'] ?? 'active',
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'variables' => $data['variables'] ?? [],
        ]);

        $this->auditService->record('notification_template.created', $template->tenant_id, null, $template);

        return $template;
    }

    public function createChannel(array $data): MarketingChannel
    {
        $channel = MarketingChannel::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'] ?? 'affiliate',
            'status' => $data['status'] ?? 'active',
            'commission_rate_basis_points' => $data['commission_rate_basis_points'] ?? 0,
            'metadata' => $data['metadata'] ?? [],
        ]);

        $this->auditService->record('marketing_channel.created', $channel->tenant_id, null, $channel);

        return $channel;
    }

    public function createPromotionLink(array $data): PromotionLink
    {
        $code = $data['code'] ?? $this->newPromotionCode();
        $link = PromotionLink::create([
            'marketing_channel_id' => $data['marketing_channel_id'],
            'code' => $code,
            'destination_url' => $data['destination_url'],
            'status' => $data['status'] ?? 'active',
            'metadata' => $data['metadata'] ?? [],
        ]);

        $channel = MarketingChannel::find($link->marketing_channel_id);
        $this->auditService->record('promotion_link.created', $channel?->tenant_id, null, $link);

        return $link;
    }

    public function attributePromotion(array $data): PromotionAttribution
    {
        $link = $this->findActivePromotionLink($data);
        $channel = MarketingChannel::whereKey($link->marketing_channel_id)
            ->where('status', 'active')
            ->firstOrFail();

        $link->increment('click_count');
        $attribution = PromotionAttribution::updateOrCreate([
            'tenant_id' => $data['tenant_id'],
            'promotion_link_id' => $link->id,
            'status' => 'active',
        ], [
            'user_id' => $data['user_id'] ?? null,
            'marketing_channel_id' => $channel->id,
            'metadata' => $data['metadata'] ?? [],
            'attributed_at' => isset($data['attributed_at']) ? Carbon::parse($data['attributed_at']) : now(),
        ]);

        $this->auditService->record('promotion_attribution.recorded', $attribution->tenant_id, $attribution->user_id, $attribution, [
            'promotion_link_id' => $link->id,
            'marketing_channel_id' => $channel->id,
        ]);

        return $attribution;
    }

    public function calculateCommission(array $data): ?CommissionRecord
    {
        $order = Order::findOrFail($data['order_id']);

        return $this->calculateCommissionForOrder($order);
    }

    public function calculateCommissionForOrder(Order $order): ?CommissionRecord
    {
        if ($order->status !== 'paid') {
            throw ValidationException::withMessages([
                'order_id' => 'Only paid orders can generate commission records.',
            ]);
        }

        $existing = CommissionRecord::where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $attribution = PromotionAttribution::where('tenant_id', $order->tenant_id)
            ->where('status', 'active')
            ->latest('attributed_at')
            ->first();

        if (! $attribution) {
            return null;
        }

        $channel = MarketingChannel::whereKey($attribution->marketing_channel_id)
            ->where('status', 'active')
            ->first();

        if (! $channel || $channel->commission_rate_basis_points === 0) {
            return null;
        }

        $existing = CommissionRecord::where('order_id', $order->id)
            ->where('promotion_attribution_id', $attribution->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $commission = CommissionRecord::create([
            'tenant_id' => $order->tenant_id,
            'marketing_channel_id' => $channel->id,
            'promotion_attribution_id' => $attribution->id,
            'order_id' => $order->id,
            'base_amount_cents' => $order->total_cents,
            'commission_rate_basis_points' => $channel->commission_rate_basis_points,
            'commission_amount_cents' => intdiv($order->total_cents * $channel->commission_rate_basis_points, 10000),
            'currency' => $order->currency,
            'status' => 'pending',
            'metadata' => [
                'order_no' => $order->order_no,
            ],
            'calculated_at' => now(),
        ]);

        $this->auditService->record('commission_record.created', $order->tenant_id, $order->user_id, $commission, [
            'order_id' => $order->id,
            'marketing_channel_id' => $channel->id,
        ]);

        return $commission;
    }

    public function sendNotification(array $data): NotificationDelivery
    {
        $template = NotificationTemplate::where('code', $data['template_code'])
            ->where(function ($query) use ($data) {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $data['tenant_id'] ?? null);
            })
            ->where('status', 'active')
            ->firstOrFail();

        $variables = $data['variables'] ?? [];
        $delivery = NotificationDelivery::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'notification_template_id' => $template->id,
            'channel' => $template->channel,
            'recipient' => $data['recipient'],
            'subject' => $this->render($template->subject, $variables),
            'body' => $this->render($template->body, $variables),
            'status' => 'sent',
            'sent_at' => now(),
            'metadata' => $data['metadata'] ?? [],
        ]);

        $this->auditService->record('notification.sent', $delivery->tenant_id, $delivery->user_id, $delivery);

        return $delivery;
    }

    public function scheduleRenewal(array $data): RenewalSchedule
    {
        $schedule = RenewalSchedule::create([
            'tenant_id' => $data['tenant_id'],
            'license_id' => $data['license_id'] ?? null,
            'product_plan_id' => $data['product_plan_id'],
            'interval' => $data['interval'] ?? 'month',
            'status' => 'active',
            'next_run_at' => Carbon::parse($data['next_run_at']),
            'metadata' => $data['metadata'] ?? [],
        ]);

        $this->auditService->record('renewal_schedule.created', $schedule->tenant_id, null, $schedule);

        return $schedule;
    }

    public function processDueRenewals(): Collection
    {
        return DB::transaction(function () {
            return RenewalSchedule::where('status', 'active')
                ->where('next_run_at', '<=', now())
                ->lockForUpdate()
                ->get()
                ->map(function (RenewalSchedule $schedule) {
                    $metadata = $schedule->metadata ?? [];
                    $order = $this->orderService->createOrder([
                        'tenant_id' => $schedule->tenant_id,
                        'product_plan_id' => $schedule->product_plan_id,
                        'quantity' => 1,
                        'payment_channel' => $metadata['payment_channel'] ?? 'wechat',
                        'metadata' => [
                            'source' => 'renewal_schedule',
                            'renewal_schedule_id' => $schedule->id,
                        ],
                    ]);

                    $schedule->update([
                        'last_order_id' => $order->id,
                        'last_run_at' => now(),
                        'next_run_at' => $this->nextRunAt($schedule->interval),
                    ]);

                    $this->auditService->record('renewal_schedule.processed', $schedule->tenant_id, null, $schedule, [
                        'order_id' => $order->id,
                    ]);

                    return $order;
                });
        });
    }

    public function processDueRenewalReminders(): Collection
    {
        return RenewalSchedule::where('status', 'active')
            ->get()
            ->filter(function (RenewalSchedule $schedule) {
                $metadata = $schedule->metadata ?? [];
                if (empty($metadata['reminder_template_code']) || empty($metadata['reminder_recipient'])) {
                    return false;
                }

                $remindBeforeDays = (int) ($metadata['remind_before_days'] ?? 7);
                $reminderCutoff = now()->addDays($remindBeforeDays);
                $reminderKey = $schedule->next_run_at->toIso8601String();

                return $schedule->next_run_at->lte($reminderCutoff)
                    && ($metadata['last_reminded_for'] ?? null) !== $reminderKey;
            })
            ->map(function (RenewalSchedule $schedule) {
                $metadata = $schedule->metadata ?? [];
                $reminderKey = $schedule->next_run_at->toIso8601String();
                $delivery = $this->sendNotification([
                    'tenant_id' => $schedule->tenant_id,
                    'template_code' => $metadata['reminder_template_code'],
                    'recipient' => $metadata['reminder_recipient'],
                    'variables' => array_merge([
                        'next_run_at' => $reminderKey,
                    ], $metadata['reminder_variables'] ?? []),
                    'metadata' => [
                        'source' => 'renewal_reminder',
                        'renewal_schedule_id' => $schedule->id,
                    ],
                ]);

                $schedule->update([
                    'metadata' => array_merge($metadata, [
                        'last_reminded_at' => now()->toIso8601String(),
                        'last_reminded_for' => $reminderKey,
                    ]),
                ]);

                $this->auditService->record('renewal_reminder.sent', $schedule->tenant_id, null, $delivery, [
                    'renewal_schedule_id' => $schedule->id,
                ]);

                return $delivery;
            })
            ->values();
    }

    private function render(?string $template, array $variables): ?string
    {
        if ($template === null) {
            return null;
        }

        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{{'.$key.'}}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }

    private function nextRunAt(string $interval): Carbon
    {
        return match ($interval) {
            'day' => now()->addDay(),
            'quarter' => now()->addQuarter(),
            'year' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    private function newPromotionCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
        } while (PromotionLink::where('code', $code)->exists());

        return $code;
    }

    private function findActivePromotionLink(array $data): PromotionLink
    {
        $query = PromotionLink::query()->where('status', 'active');

        if (! empty($data['promotion_link_id'])) {
            return $query->whereKey($data['promotion_link_id'])->firstOrFail();
        }

        return $query->where('code', $data['promotion_link_code'])->firstOrFail();
    }
}
