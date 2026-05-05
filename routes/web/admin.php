<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DatabaseExportController;
use App\Livewire\Admin\ActivityImages;
use App\Livewire\Admin\Anomalies\Index as AdminAnomaliesIndex;
use App\Livewire\Admin\AuditEvents\Index as AdminAuditEventsIndex;
use App\Livewire\Admin\AuditEvents\Show as AdminAuditEventsShow;
use App\Livewire\Admin\CoachApproval;
use App\Livewire\Admin\Configuration\Billing as BillingConfiguration;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DataExport;
use App\Livewire\Admin\Refunds\Index as AdminRefundsIndex;
use App\Livewire\Admin\Sessions\Index as AdminSessionsIndex;
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
Route::get('/configuration/billing', BillingConfiguration::class)->name('configuration.billing');
Route::get('/sessions', AdminSessionsIndex::class)->name('sessions.index');
Route::get('/refunds', AdminRefundsIndex::class)->name('refunds.index');
Route::get('/anomalies', AdminAnomaliesIndex::class)->name('anomalies.index');
Route::get('/audit-events', AdminAuditEventsIndex::class)->name('audit-events.index');
Route::get('/audit-events/{auditEvent}', AdminAuditEventsShow::class)->name('audit-events.show');
