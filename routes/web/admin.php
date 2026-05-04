<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DatabaseExportController;
use App\Livewire\Admin\ActivityImages;
use App\Livewire\Admin\CoachApproval;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DataExport;
use App\Livewire\Admin\Users\Create as AdminUserCreate;
use App\Livewire\Admin\Users\Index as AdminUserIndex;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', Dashboard::class)->name('dashboard');
Route::get('/users', AdminUserIndex::class)->name('users.index');
Route::get('/users/create', AdminUserCreate::class)->name('users.create');
Route::get('/coach-approval', CoachApproval::class)->name('coach-approval');
Route::get('/activity-images', ActivityImages::class)->name('activity-images');
Route::get('/data-export', DataExport::class)->name('data-export');
Route::get('/export/{type}', [DatabaseExportController::class, 'download'])->name('export');
