<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GET /privacy', function () {

    it('returns a 200 response', function () {
        $this->get(route('privacy'))
            ->assertOk();
    });

    it('renders the privacy page title in French', function () {
        $this->withSession(['locale' => 'fr'])
            ->get(route('privacy'))
            ->assertOk()
            ->assertSee(__('privacy.title', [], 'fr'));
    });

    it('renders all section headings in French', function () {
        $sections = [
            'intro',
            'controller',
            'data_collected',
            'purpose',
            'legal_basis',
            'retention',
            'rights',
            'third_parties',
            'cookies',
            'security',
            'changes',
        ];

        $response = $this->withSession(['locale' => 'fr'])
            ->get(route('privacy'));
        $response->assertOk();

        foreach ($sections as $section) {
            $response->assertSee(__("privacy.{$section}.heading", [], 'fr'));
        }
    });

    it('contains a print button', function () {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('window.print()', false)
            ->assertSee(__('privacy.print'));
    });

    it('renders correctly in English locale', function () {
        $this->withSession(['locale' => 'en'])
            ->get(route('privacy'))
            ->assertOk()
            ->assertSee(__('privacy.title', [], 'en'))
            ->assertSee(__('privacy.intro.heading', [], 'en'));
    });

    it('renders correctly in Dutch locale', function () {
        $this->withSession(['locale' => 'nl'])
            ->get(route('privacy'))
            ->assertOk()
            ->assertSee(__('privacy.title', [], 'nl'))
            ->assertSee(__('privacy.intro.heading', [], 'nl'));
    });

    it('is accessible from the footer on the home page', function () {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('privacy'));
    });

    it('contains print-friendly CSS media query', function () {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('@media print', false);
    });

});
