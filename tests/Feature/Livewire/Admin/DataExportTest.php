<?php

declare(strict_types=1);

use App\Livewire\Admin\DataExport;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\DatabaseExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('DatabaseExportService', function () {
    it('exports coaches CSV with correct headers and data', function () {
        $coach = User::factory()->coach()->create(['name' => 'Test Coach', 'email' => 'coach@test.com']);

        $service = app(DatabaseExportService::class);
        $response = $service->exportCoaches();

        expect($response->headers->get('Content-Type'))->toContain('text/csv');
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');
        expect($response->headers->get('Content-Disposition'))->toContain('coaches_export_');
        expect($response->headers->get('Content-Disposition'))->toContain('.csv');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('name');
        expect($content)->toContain('Test Coach');
        expect($content)->toContain('coach@test.com');
    });

    it('exports sessions CSV with correct headers and data', function () {
        $coach = User::factory()->coach()->create(['name' => 'Session Coach']);
        $session = SportSession::factory()->for($coach, 'coach')->create([
            'title' => 'Morning Yoga',
            'price_per_person' => 2500,
        ]);

        $service = app(DatabaseExportService::class);
        $response = $service->exportSessions();

        expect($response->headers->get('Content-Type'))->toContain('text/csv');
        expect($response->headers->get('Content-Disposition'))->toContain('sessions_export_');
        expect($response->headers->get('Content-Disposition'))->toContain('.csv');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('title');
        expect($content)->toContain('Morning Yoga');
        expect($content)->toContain('Session Coach');
        // price_per_person = 2500 cents = 25.00 EUR
        expect($content)->toContain('25');
    });

    it('exports payments CSV with correct headers and data', function () {
        $athlete = User::factory()->athlete()->create(['name' => 'Test Athlete', 'email' => 'athlete@test.com']);
        $session = SportSession::factory()->create(['title' => 'Swim Lesson']);
        $booking = Booking::factory()->for($session, 'sportSession')->create([
            'athlete_id' => $athlete->id,
            'amount_paid' => 3000,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $service = app(DatabaseExportService::class);
        $response = $service->exportPayments();

        expect($response->headers->get('Content-Type'))->toContain('text/csv');
        expect($response->headers->get('Content-Disposition'))->toContain('payments_export_');
        expect($response->headers->get('Content-Disposition'))->toContain('.csv');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('athlete_name');
        expect($content)->toContain('Test Athlete');
        expect($content)->toContain('athlete@test.com');
        expect($content)->toContain('Swim Lesson');
        // amount_paid = 3000 cents = 30.00 EUR
        expect($content)->toContain('30');
        expect($content)->toContain('pi_test_123');
    });

    it('coaches export only includes users with coach role', function () {
        $coach = User::factory()->coach()->create(['name' => 'Real Coach']);
        $athlete = User::factory()->athlete()->create(['name' => 'Not A Coach']);

        $service = app(DatabaseExportService::class);

        ob_start();
        $service->exportCoaches()->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('Real Coach');
        expect($content)->not->toContain('Not A Coach');
    });

    it('handles coach without a profile gracefully', function () {
        User::factory()->coach()->create(['name' => 'Coach No Profile', 'email' => 'noprofile@test.com']);

        $service = app(DatabaseExportService::class);

        ob_start();
        $service->exportCoaches()->sendContent();
        $content = ob_get_clean();

        // Coach appears in export even without a profile; profile fields are empty
        expect($content)->toContain('Coach No Profile');
        expect($content)->toContain('noprofile@test.com');
    });
});

describe('Admin DataExport page', function () {
    it('renders for admin with 2FA enabled', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.data-export'))
            ->assertOk();
    });

    it('redirects admin without 2FA to profile setup', function () {
        $admin = User::factory()->admin()->create([
            'two_factor_confirmed_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.data-export'))
            ->assertRedirect(route('profile.edit'));
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('admin.data-export'))
            ->assertForbidden();
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.data-export'))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $this->get(route('admin.data-export'))
            ->assertRedirect(route('login'));
    });

    it('shows export buttons', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(DataExport::class)
            ->assertSee(__('admin.export_coaches_title'))
            ->assertSee(__('admin.export_sessions_title'))
            ->assertSee(__('admin.export_payments_title'))
            ->assertSee(__('admin.export_csv'));
    });

    it('exportCoaches action redirects to export route', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(DataExport::class)
            ->call('exportCoaches')
            ->assertRedirect(route('admin.export', ['type' => 'coaches']));
    });

    it('exportSessions action redirects to export route', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(DataExport::class)
            ->call('exportSessions')
            ->assertRedirect(route('admin.export', ['type' => 'sessions']));
    });

    it('exportPayments action redirects to export route', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(DataExport::class)
            ->call('exportPayments')
            ->assertRedirect(route('admin.export', ['type' => 'payments']));
    });

    it('denies exportCoaches to non-admin users', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(DataExport::class)
            ->call('exportCoaches')
            ->assertForbidden();
    });

    it('denies exportSessions to non-admin users', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(DataExport::class)
            ->call('exportSessions')
            ->assertForbidden();
    });

    it('denies exportPayments to non-admin users', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(DataExport::class)
            ->call('exportPayments')
            ->assertForbidden();
    });
});

describe('Admin DatabaseExportController', function () {
    it('downloads coaches CSV for admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        User::factory()->coach()->create(['name' => 'Coach Exported']);

        $this->actingAs($admin)
            ->get(route('admin.export', ['type' => 'coaches']))
            ->assertOk()
            ->assertHeader('Content-Disposition');
    });

    it('downloads sessions CSV for admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.export', ['type' => 'sessions']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('downloads payments CSV for admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.export', ['type' => 'payments']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('returns 404 for unknown export type', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.export', ['type' => 'unknown']))
            ->assertNotFound();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('admin.export', ['type' => 'coaches']))
            ->assertForbidden();
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.export', ['type' => 'coaches']))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $this->get(route('admin.export', ['type' => 'coaches']))
            ->assertRedirect(route('login'));
    });

    it('exports correct data in coaches CSV', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        User::factory()->coach()->create(['name' => 'Exported Coach', 'email' => 'exported@test.com']);

        ob_start();
        $this->actingAs($admin)
            ->get(route('admin.export', ['type' => 'coaches']))
            ->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('Exported Coach');
        expect($content)->toContain('exported@test.com');
    });

    it('exports correct data in sessions CSV', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        SportSession::factory()->create(['title' => 'Exported Session']);

        ob_start();
        $this->actingAs($admin)
            ->get(route('admin.export', ['type' => 'sessions']))
            ->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('Exported Session');
    });

    it('exports correct data in payments CSV', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $athlete = User::factory()->athlete()->create(['name' => 'Exported Athlete']);
        Booking::factory()->create(['athlete_id' => $athlete->id]);

        ob_start();
        $this->actingAs($admin)
            ->get(route('admin.export', ['type' => 'payments']))
            ->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('Exported Athlete');
    });
});
