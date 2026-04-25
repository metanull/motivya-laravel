<?php

declare(strict_types=1);

use App\Livewire\Athlete\Dashboard as AthleteDashboard;
use App\Livewire\Athlete\Favourites as AthleteFavourites;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', AthleteDashboard::class)->name('dashboard');
Route::get('/favourites', AthleteFavourites::class)->name('favourites');
