<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

describe('Master layout & navigation', function () {

    describe('Welcome page', function () {

        it('returns a 200 response', function () {
            $this->get('/')
                ->assertOk();
        });

        it('renders the app layout with a title', function () {
            $this->get('/')
                ->assertOk()
                ->assertSee(config('app.name'));
        });

        it('contains login and register links for guests', function () {
            $this->get('/')
                ->assertOk()
                ->assertSee(route('login'))
                ->assertSee(route('register'));
        });

        it('contains the footer copyright text', function () {
            $this->get('/')
                ->assertOk()
                ->assertSee(config('app.name'));
        });

    });

    describe('Locale switcher', function () {

        it('switches locale and redirects back', function () {
            $this->get(route('locale.switch', 'en'))
                ->assertRedirect();

            $this->get(route('locale.switch', 'nl'))
                ->assertRedirect();

            $this->get(route('locale.switch', 'fr'))
                ->assertRedirect();
        });

        it('ignores unsupported locales', function () {
            $this->get(route('locale.switch', 'de'))
                ->assertRedirect();

            expect(session('locale'))->toBeNull();
        });

        it('stores supported locale in session', function () {
            $this->get(route('locale.switch', 'en'));

            expect(session('locale'))->toBe('en');
        });

    });

    describe('Translation parity', function () {

        it('has matching keys in en and nl for every key defined in every fr file', function () {
            $frFiles = glob(base_path('lang/fr/*.php')) ?: [];

            foreach ($frFiles as $frPath) {
                $file = basename($frPath);
                $enPath = base_path("lang/en/{$file}");
                $nlPath = base_path("lang/nl/{$file}");

                // Only assert parity for files that exist in all three locales.
                if (! file_exists($enPath) || ! file_exists($nlPath)) {
                    continue;
                }

                $fr = array_keys(Arr::dot(require $frPath));
                $en = array_keys(Arr::dot(require $enPath));
                $nl = array_keys(Arr::dot(require $nlPath));

                $missingEn = array_diff($fr, $en);
                $missingNl = array_diff($fr, $nl);

                expect($missingEn)->toBeEmpty("[{$file}] Missing EN keys: ".implode(', ', $missingEn));
                expect($missingNl)->toBeEmpty("[{$file}] Missing NL keys: ".implode(', ', $missingNl));
            }
        });

    });

});
