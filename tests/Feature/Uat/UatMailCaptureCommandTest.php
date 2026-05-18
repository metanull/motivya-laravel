<?php

declare(strict_types=1);

use App\Models\UatMailCapture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

describe('UAT mail capture commands', function () {
    it('lists and shows captured UAT mail', function (): void {
        $capture = UatMailCapture::create([
            'run_id' => 'uat_test',
            'to' => [['email' => 'athlete@example.test', 'name' => null]],
            'subject' => 'Captured subject',
            'text_body' => 'Captured text body',
            'captured_at' => now(),
        ]);

        expect(Artisan::call('uat:mail:list', ['--run-id' => 'uat_test', '--json' => true]))->toBe(0);
        $payload = json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR);

        expect($payload['mails'][0]['id'])->toBe($capture->id);

        $this->artisan("uat:mail:show {$capture->id}")
            ->expectsOutputToContain('Captured subject')
            ->expectsOutputToContain('Captured text body')
            ->assertSuccessful();
    });

    it('requires force when clearing captured mail', function (): void {
        UatMailCapture::create([
            'run_id' => 'uat_test',
            'subject' => 'Captured subject',
            'captured_at' => now(),
        ]);

        $this->artisan('uat:mail:clear --run-id=uat_test')
            ->assertFailed();

        $this->artisan('uat:mail:clear --run-id=uat_test --force')
            ->assertSuccessful();

        expect(UatMailCapture::count())->toBe(0);
    });
});
