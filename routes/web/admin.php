<?php

declare(strict_types=1);

use App\Livewire\Admin\ActivityImages;
use App\Livewire\Admin\CoachApproval;
use Illuminate\Support\Facades\Route;

Route::get('/coach-approval', CoachApproval::class)->name('coach-approval');
Route::get('/activity-images', ActivityImages::class)->name('activity-images');
