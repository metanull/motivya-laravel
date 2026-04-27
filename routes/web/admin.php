<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DatabaseExportController;
use App\Livewire\Admin\ActivityImages;
use App\Livewire\Admin\CoachApproval;
use App\Livewire\Admin\DataExport;
use Illuminate\Support\Facades\Route;

Route::get('/coach-approval', CoachApproval::class)->name('coach-approval');
Route::get('/activity-images', ActivityImages::class)->name('activity-images');
Route::get('/data-export', DataExport::class)->name('data-export');
Route::get('/export/{type}', [DatabaseExportController::class, 'download'])->name('export');
