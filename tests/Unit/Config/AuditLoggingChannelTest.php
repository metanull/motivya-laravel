<?php

declare(strict_types=1);

describe('audit logging channel config', function () {

    it('defines the audit channel with the daily driver', function () {
        expect(config('logging.channels.audit.driver'))->toBe('daily');
    });

    it('defines the audit channel at info level', function () {
        expect(config('logging.channels.audit.level'))->toBe('info');
    });

    it('defines the audit channel with 90-day retention', function () {
        expect(config('logging.channels.audit.days'))->toBe(90);
    });

    it('points the audit channel to storage/logs/audit.log', function () {
        $expected = storage_path('logs/audit.log');

        expect(config('logging.channels.audit.path'))->toBe($expected);
    });

});
