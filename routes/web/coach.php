<?php

declare(strict_types=1);

use App\Http\Controllers\Coach\StripeOnboardingController;
use App\Livewire\Coach\Dashboard as CoachDashboard;
use App\Livewire\Coach\PayoutHistory as CoachPayoutHistory;
use App\Livewire\Coach\PayoutStatements\Index as CoachPayoutStatementsIndex;
use App\Livewire\Coach\ProfileEdit as CoachProfileEdit;
use App\Livewire\Session\Create as SessionCreate;
use App\Livewire\Session\Edit as SessionEdit;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', CoachDashboard::class)->name('dashboard');
Route::get('/profile/edit', CoachProfileEdit::class)->name('profile.edit');
Route::get('/stripe/onboard', [StripeOnboardingController::class, 'start'])->name('stripe.onboard');
Route::get('/stripe/return', [StripeOnboardingController::class, 'handleReturn'])->name('stripe.return');
Route::get('/stripe/refresh', [StripeOnboardingController::class, 'refresh'])->name('stripe.refresh');
Route::get('/sessions/create', SessionCreate::class)->name('sessions.create');
Route::get('/sessions/{sportSession}/edit', SessionEdit::class)->name('sessions.edit');
Route::get('/payout-history', CoachPayoutHistory::class)->name('payout-history');
Route::get('/payout-statements', CoachPayoutStatementsIndex::class)->name('payout-statements.index');
