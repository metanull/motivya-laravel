<?php

declare(strict_types=1);

use App\Livewire\Admin\CoachApproval;
use Illuminate\Support\Facades\Route;

Route::get('/coach-approval', CoachApproval::class)->name('coach-approval');
