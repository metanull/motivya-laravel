<?php

declare(strict_types=1);

use App\Livewire\Session\Create as SessionCreate;
use App\Livewire\Session\Edit as SessionEdit;
use Illuminate\Support\Facades\Route;

Route::get('/sessions/create', SessionCreate::class)->name('sessions.create');
Route::get('/sessions/{sportSession}/edit', SessionEdit::class)->name('sessions.edit');
