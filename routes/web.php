<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Coach\Application as CoachApplication;
use App\Livewire\Coach\Profile as CoachProfile;
use App\Livewire\Profile\Edit as ProfileEdit;
use App\Livewire\Session\Index as SessionIndex;
use App\Livewire\Session\Show as SessionShow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Auth views (Livewire components — Fortify handles POST actions)
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::get('/two-factor-challenge', TwoFactorChallenge::class)->name('two-factor.login');

    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::get('/email/verify', VerifyEmail::class)
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/profile', ProfileEdit::class)
    ->middleware('auth')
    ->name('profile.edit');

Route::get('/coach/apply', CoachApplication::class)
    ->middleware(['auth', 'verified'])
    ->name('coach.apply');

Route::get('/sessions', SessionIndex::class)
    ->middleware('auth')
    ->name('sessions.index');

Route::get('/sessions/{sportSession}', SessionShow::class)
    ->middleware('auth')
    ->name('sessions.show');

Route::get('/coaches/{user}', CoachProfile::class)
    ->name('coaches.show');

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

Route::get('/privacy', function () {
    return view('pages.privacy');
})->name('privacy');

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

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook');
