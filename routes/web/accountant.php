<?php

declare(strict_types=1);

use App\Http\Controllers\Accountant\FinancialExportController;
use App\Http\Controllers\Accountant\InvoiceXmlController;
use App\Http\Controllers\Accountant\LedgerExportController;
use App\Livewire\Accountant\Anomalies\Index as AccountantAnomaliesIndex;
use App\Livewire\Accountant\Dashboard as AccountantDashboard;
use App\Livewire\Accountant\InvoiceDetail as AccountantInvoiceDetail;
use App\Livewire\Accountant\PayoutStatements\Index as AccountantPayoutStatementsIndex;
use App\Livewire\Accountant\Transactions\Index as TransactionsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', AccountantDashboard::class)->name('dashboard');
Route::get('/invoices/{invoice}', AccountantInvoiceDetail::class)->name('invoices.show');
Route::get('/invoices/{invoice}/xml', [InvoiceXmlController::class, 'download'])->name('invoices.xml');
Route::get('/export', [FinancialExportController::class, 'download'])->name('export');
Route::get('/transactions', TransactionsIndex::class)->name('transactions.index');
Route::get('/transactions/export', [LedgerExportController::class, 'download'])->name('transactions.export');
Route::get('/payout-statements', AccountantPayoutStatementsIndex::class)->name('payout-statements.index');
Route::get('/anomalies', AccountantAnomaliesIndex::class)->name('anomalies.index');
