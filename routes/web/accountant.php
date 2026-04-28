<?php

declare(strict_types=1);

use App\Http\Controllers\Accountant\FinancialExportController;
use App\Http\Controllers\Accountant\InvoiceXmlController;
use App\Livewire\Accountant\Dashboard as AccountantDashboard;
use App\Livewire\Accountant\InvoiceDetail as AccountantInvoiceDetail;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', AccountantDashboard::class)->name('dashboard');
Route::get('/invoices/{invoice}', AccountantInvoiceDetail::class)->name('invoices.show');
Route::get('/invoices/{invoice}/xml', [InvoiceXmlController::class, 'download'])->name('invoices.xml');
Route::get('/export', [FinancialExportController::class, 'download'])->name('export');
