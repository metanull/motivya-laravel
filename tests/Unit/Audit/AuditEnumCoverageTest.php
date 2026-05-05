<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Services\Audit\AuditService;

describe('Audit — Enum Coverage and Architectural Safeguards', function () {

    // ── Enum completeness: all MVP event types must exist ─────────────────

    it('AuditEventType enum covers all MVP coach events', function () {
        $values = array_map(fn ($c) => $c->value, AuditEventType::cases());

        expect($values)->toContain('coach.application_submitted')
            ->toContain('coach.approved')
            ->toContain('coach.rejected');
    });

    it('AuditEventType enum covers all MVP user-admin events', function () {
        $values = array_map(fn ($c) => $c->value, AuditEventType::cases());

        expect($values)->toContain('user.created_by_admin')
            ->toContain('user.role_changed')
            ->toContain('user.suspended')
            ->toContain('user.reactivated');
    });

    it('AuditEventType enum covers all MVP session events', function () {
        $values = array_map(fn ($c) => $c->value, AuditEventType::cases());

        expect($values)->toContain('session.created')
            ->toContain('session.updated')
            ->toContain('session.published')
            ->toContain('session.cancelled')
            ->toContain('session.completed')
            ->toContain('session.deleted');
    });

    it('AuditEventType enum covers all MVP booking events', function () {
        $values = array_map(fn ($c) => $c->value, AuditEventType::cases());

        expect($values)->toContain('booking.created')
            ->toContain('booking.payment_started')
            ->toContain('booking.payment_confirmed')
            ->toContain('booking.payment_failed')
            ->toContain('booking.cancelled')
            ->toContain('booking.expired');
    });

    it('AuditEventType enum covers all MVP refund events', function () {
        $values = array_map(fn ($c) => $c->value, AuditEventType::cases());

        expect($values)->toContain('refund.requested')
            ->toContain('refund.completed')
            ->toContain('refund.failed');
    });

    it('AuditEventType enum covers all MVP invoice events', function () {
        $values = array_map(fn ($c) => $c->value, AuditEventType::cases());

        expect($values)->toContain('invoice.generated')
            ->toContain('invoice.credit_note_generated')
            ->toContain('invoice.xml_downloaded');
    });

    it('AuditEventType enum covers all MVP payout statement events', function () {
        $values = array_map(fn ($c) => $c->value, AuditEventType::cases());

        expect($values)->toContain('payout_statement.generated')
            ->toContain('payout_statement.submitted')
            ->toContain('payout_statement.approved')
            ->toContain('payout_statement.blocked')
            ->toContain('payout_statement.paid');
    });

    it('AuditEventType enum covers all MVP anomaly events', function () {
        $values = array_map(fn ($c) => $c->value, AuditEventType::cases());

        expect($values)->toContain('anomaly.resolved')
            ->toContain('anomaly.ignored');
    });

    // ── Service-layer integration: AuditService must be injectable ────────

    it('AuditService resolves from the container', function () {
        $service = app(AuditService::class);

        expect($service)->toBeInstanceOf(AuditService::class);
    });

    // ── Enum values must be strings (not arbitrary ints or constants) ─────

    it('all AuditEventType values are non-empty strings', function () {
        foreach (AuditEventType::cases() as $case) {
            expect($case->value)->toBeString()
                ->not->toBeEmpty();
        }
    });

    it('AuditEventType values follow domain.action string convention', function () {
        foreach (AuditEventType::cases() as $case) {
            expect($case->value)->toMatch('/^[a-z_]+\.[a-z_]+$/', "{$case->name} value '{$case->value}' must match domain.action format");
        }
    });

    // ── Audit service must NOT come from controllers or Blade ─────────────

    it('AuditService is not referenced from controller files', function () {
        $controllerDir = base_path('app/Http/Controllers');
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllerDir));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // Allow the CaptureAuditContext middleware (it configures context, not records events)
            if (str_contains($file->getPathname(), 'Middleware')) {
                continue;
            }

            expect($content)->not->toContain('AuditService::record(', "Controller {$file->getFilename()} must not call AuditService::record() directly")
                ->not->toContain('$auditService->record(', "Controller {$file->getFilename()} must not call \$auditService->record() directly");
        }
    });

    it('AuditService is not referenced from Livewire component files', function () {
        $livewireDir = base_path('app/Livewire');

        if (! is_dir($livewireDir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($livewireDir));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            expect($content)->not->toContain('AuditService::record(', "Livewire component {$file->getFilename()} must not call AuditService::record() directly")
                ->not->toContain('$auditService->record(', "Livewire component {$file->getFilename()} must not call \$auditService->record() directly");
        }
    });

});
