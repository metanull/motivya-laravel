<?php

declare(strict_types=1);

use App\Livewire\Admin\Readiness;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Readiness French labels (Story 3.2)', function () {

    it('French scheduler check label is Exécution des tâches planifiées', function (): void {
        App::setLocale('fr');
        expect(__('admin.readiness_check_scheduler'))->toBe('Exécution des tâches planifiées');
    });

    it('French scheduler detail heading is Exécution des tâches planifiées', function (): void {
        App::setLocale('fr');
        expect(__('admin.readiness_scheduler_detail_heading'))->toBe('Exécution des tâches planifiées');
    });

    it('French scheduler detail subtitle mentions successful execution', function (): void {
        App::setLocale('fr');
        expect(__('admin.readiness_scheduler_detail_subtitle'))->toContain('réussie');
    });

    it('French scheduler detail subtitle mentions scheduler service', function (): void {
        App::setLocale('fr');
        expect(__('admin.readiness_scheduler_detail_subtitle'))->toContain('motivya-scheduler.timer');
    });

    it('French readiness_scheduler_never_run mentions scheduler service', function (): void {
        App::setLocale('fr');
        expect(__('admin.readiness_scheduler_never_run'))->toContain('motivya-scheduler.timer');
    });

    it('French readiness_scheduler_never_run explains scheduler is not configured or has not triggered', function (): void {
        App::setLocale('fr');
        $msg = __('admin.readiness_scheduler_never_run');
        expect($msg)->toContain('pas configuré')
            ->and($msg)->toContain('pas encore');
    });

    it('French postal code reference missing message mentions geo load command', function (): void {
        App::setLocale('fr');
        expect(__('admin.readiness_postal_code_reference_missing'))->toContain('geo:load-postal-codes');
    });

    it('French postal code reference missing message mentions backfill command', function (): void {
        App::setLocale('fr');
        expect(__('admin.readiness_postal_code_reference_missing'))->toContain('sessions:backfill-coordinates');
    });

    it('readiness page renders French scheduler section heading', function (): void {
        App::setLocale('fr');
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertSee('Exécution des tâches planifiées');
    });

    it('readiness page renders French scheduler detail subtitle with successful execution wording', function (): void {
        App::setLocale('fr');
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertSee('réussie');
    });

    it('readiness page renders French postal code missing message with both action commands', function (): void {
        App::setLocale('fr');
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Readiness::class)
            ->assertSee('geo:load-postal-codes')
            ->assertSee('sessions:backfill-coordinates');
    });

});
