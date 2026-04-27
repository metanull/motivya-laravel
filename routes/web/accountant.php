<?php

declare(strict_types=1);

use App\Livewire\Accountant\Dashboard as AccountantDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', AccountantDashboard::class)->name('dashboard');
