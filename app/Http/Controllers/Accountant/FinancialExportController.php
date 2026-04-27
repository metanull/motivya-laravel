<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accountant\ExportFinancialDataRequest;
use App\Services\FinancialExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FinancialExportController extends Controller
{
    /**
     * Stream a financial export file (CSV or Excel) applying the given filters.
     *
     * Query parameters:
     *   - format   (string)  'csv' | 'excel'  — default 'csv'
     *   - dateFrom (string)  Y-m-d            — billing_period_start >=
     *   - dateTo   (string)  Y-m-d            — billing_period_start <=
     *   - coachId  (string)  numeric ID       — filter by coach
     *   - type     (string)  'invoice' | 'credit_note'
     */
    public function download(ExportFinancialDataRequest $request, FinancialExportService $service): StreamedResponse
    {
        if ($request->exportFormat() === 'excel') {
            return $service->exportExcel($request->dateFrom(), $request->dateTo(), $request->coachId(), $request->type());
        }

        return $service->exportCsv($request->dateFrom(), $request->dateTo(), $request->coachId(), $request->type());
    }
}
