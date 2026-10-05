<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public static function log(string $action, ?string $description = null, ?Model $model = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'ip_address' => request()?->ip(),
            'user_agent' => mb_substr((string) request()?->userAgent(), 0, 255),
        ]);
    }
}
