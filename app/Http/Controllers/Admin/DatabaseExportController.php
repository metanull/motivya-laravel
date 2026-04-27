<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DatabaseExportController extends Controller
{
    /**
     * Stream a database CSV export for the given type.
     *
     * Route parameter:
     *   - type  (string)  'coaches' | 'sessions' | 'payments'
     */
    public function download(string $type, DatabaseExportService $service): StreamedResponse
    {
        return match ($type) {
            'coaches' => $service->exportCoaches(),
            'sessions' => $service->exportSessions(),
            'payments' => $service->exportPayments(),
            default => abort(404),
        };
    }
}
