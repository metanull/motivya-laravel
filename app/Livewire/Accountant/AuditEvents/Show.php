<?php

declare(strict_types=1);

namespace App\Livewire\Accountant\AuditEvents;

use App\Models\AuditEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class Show extends Component
{
    public AuditEvent $auditEvent;

    public function mount(AuditEvent $auditEvent): void
    {
        Gate::authorize('view', $auditEvent);

        $this->auditEvent = $auditEvent->load('subjects');
    }

    public function render(): View
    {
        return view('livewire.accountant.audit-events.show', [
            'auditEvent' => $this->auditEvent,
            'subjectsByRelation' => $this->auditEvent->subjects->groupBy('relation'),
        ])->title(__('accountant.audit_events_detail_title'));
    }
}
