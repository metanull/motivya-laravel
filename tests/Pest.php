<?php

declare(strict_types=1);

use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

// Vite dev server is not running during tests; mock it for all Feature tests that render views.
uses()->beforeEach(fn () => $this->withoutVite())->in('Feature');
