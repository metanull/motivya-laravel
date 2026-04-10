<?php

declare(strict_types=1);

use App\Livewire\Session\Create as SessionCreate;
use Illuminate\Support\Facades\Route;

Route::get('/sessions/create', SessionCreate::class)->name('sessions.create');
