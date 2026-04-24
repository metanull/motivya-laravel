<?php

declare(strict_types=1);

use App\Livewire\Athlete\Dashboard as AthleteDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', AthleteDashboard::class)->name('dashboard');
