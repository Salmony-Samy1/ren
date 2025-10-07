<?php

namespace App\Services;

use App\Events\AdminRealtimeAlert;
use App\Models\Alert;
use Illuminate\Support\Facades\DB;

class AlertService
{
    public function raise(string $type, string $severity, string $title, array $meta = [], ?int $raisedBy = null, ?string $description = null): Alert
    {
        return DB::transaction(function () use ($type, $severity, $title, $meta, $raisedBy, $description) {
            $alert = Alert::create([
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'meta' => $meta,
                'status' => 'open',
                'raised_by' => $raisedBy,
            ]);

            event(new AdminRealtimeAlert($type, [
                'id' => $alert->id,
                'severity' => $alert->severity,
                'title' => $alert->title,
                'meta' => $alert->meta,
                'created_at' => $alert->created_at?->toIso8601String(),
            ]));

            return $alert;
        });
    }

    public function acknowledge(Alert $alert, int $adminId): Alert
    {
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $adminId,
        ]);
        event(new AdminRealtimeAlert('alert.acknowledged', [
            'id' => $alert->id,
            'acknowledged_by' => $adminId,
            'acknowledged_at' => $alert->acknowledged_at?->toIso8601String(),
        ]));
        return $alert;
    }
}

