<?php

namespace App\Foundation\Audit;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;

/**
 * Schreibt minimierte Audit-Ereignisse über eine einheitliche Anwendungsgrenze.
 *
 * Aufrufer dürfen ausschließlich bereits redigierte Metadaten übergeben. Der Dienst
 * akzeptiert absichtlich keine Requestobjekte, damit Token, Passwort oder Header nicht
 * versehentlich als vollständiger Datenblock protokolliert werden.
 */
final class AuditRecorder
{
    /**
     * Hängt ein Ereignis an das unveränderliche Audit an.
     *
     * @param  array<string, bool|int|string|null>  $metadata
     */
    public function record(
        string $eventType,
        string $subjectType,
        string $subjectId,
        string $correlationId,
        array $metadata = [],
        ?Tenant $tenant = null,
        ?User $actor = null,
        string $channel = 'web',
    ): AuditEvent {
        $event = new AuditEvent;
        $event->tenant_id = $tenant?->getKey();
        $event->actor_user_id = $actor?->getKey();
        $event->correlation_id = $correlationId;
        $event->event_type = $eventType;
        $event->subject_type = $subjectType;
        $event->subject_id = $subjectId;
        $event->channel = $channel;
        $event->metadata = $metadata;
        $event->occurred_at = now();
        $event->save();

        return $event;
    }
}
