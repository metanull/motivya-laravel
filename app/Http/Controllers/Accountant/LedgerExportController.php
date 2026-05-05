<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accountant\ExportLedgerRequest;
use App\Services\FinancialExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LedgerExportController extends Controller
{
    /**
     * Stream a ledger export file (CSV or Excel) applying the given filters.
     *
     * Query parameters:
     *   - format        (string)  'csv' | 'excel'  — default 'csv'
     *   - dateFrom      (string)  Y-m-d            — bookings.created_at >=
     *   - dateTo        (string)  Y-m-d            — bookings.created_at <=
     *   - coachId       (string)  numeric ID       — filter by coach via sport_session
     *   - sessionStatus (string)  SessionStatus value
     *   - bookingStatus (string)  BookingStatus value
     */
    public function download(ExportLedgerRequest $request, FinancialExportService $service): StreamedResponse
    {
        if ($request->exportFormat() === 'excel') {
            return $service->exportLedgerExcel(
                $request->dateFrom(),
                $request->dateTo(),
                $request->coachId(),
                $request->sessionStatus(),
                $request->bookingStatus(),
            );
        }

        return $service->exportLedgerCsv(
            $request->dateFrom(),
            $request->dateTo(),
            $request->coachId(),
            $request->sessionStatus(),
            $request->bookingStatus(),
        );
    }
}
