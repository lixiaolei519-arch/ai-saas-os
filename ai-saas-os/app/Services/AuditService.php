<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(string $action, ?int $tenantId = null, ?int $actorUserId = null, ?Model $subject = null, array $metadata = []): AuditEvent
    {
        return AuditEvent::create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
