<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Placeholder GET routes for auth pages (views to be implemented in E1-S04)
Route::get('/login', function () {
    return redirect('/')
        ->with('flash', ['type' => 'info', 'message' => __('common.auth_coming_soon')]);
})->name('login');

Route::get('/register', function () {
    return redirect('/')
        ->with('flash', ['type' => 'info', 'message' => __('common.auth_coming_soon')]);
})->name('register');

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

Route::get('/locale/{locale}', function (string $locale) {
    $supported = ['fr', 'en', 'nl'];

    if (in_array($locale, $supported, strict: true)) {
        session(['locale' => $locale]);

        $user = request()->user();
        if ($user !== null) {
            $user->update(['locale' => $locale]);
        }
    }

    return redirect()->back(fallback: '/');
})->name('locale.switch');
