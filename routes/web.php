<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    $checks = ['status' => 'ok'];

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (Throwable) {
        $checks['database'] = 'error';
        $checks['status'] = 'degraded';
    }

    try {
        $cache = config('cache.default');
        cache()->store($cache)->put('health-check', true, 10);
        $checks['cache'] = 'ok';
    } catch (Throwable) {
        $checks['cache'] = 'error';
        $checks['status'] = 'degraded';
    }

    $httpStatus = $checks['status'] === 'ok' ? 200 : 503;

    return response()->json($checks, $httpStatus);
})->name('health');
