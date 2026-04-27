<?php

declare(strict_types=1);

use App\Livewire\Accountant\Dashboard as AccountantDashboard;
use App\Livewire\Accountant\InvoiceDetail as AccountantInvoiceDetail;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', AccountantDashboard::class)->name('dashboard');
Route::get('/invoices/{invoice}', AccountantInvoiceDetail::class)->name('invoices.show');
