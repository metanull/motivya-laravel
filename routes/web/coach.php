<?php

declare(strict_types=1);

use App\Livewire\Coach\Dashboard as CoachDashboard;
use App\Livewire\Coach\ProfileEdit as CoachProfileEdit;
use App\Livewire\Session\Create as SessionCreate;
use App\Livewire\Session\Edit as SessionEdit;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', CoachDashboard::class)->name('dashboard');
Route::get('/profile/edit', CoachProfileEdit::class)->name('profile.edit');
Route::get('/sessions/create', SessionCreate::class)->name('sessions.create');
Route::get('/sessions/{sportSession}/edit', SessionEdit::class)->name('sessions.edit');
