<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accountant;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InvoiceXmlController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Download the PEPPOL XML file for an invoice.
     *
     * Returns a streamed download response with the XML content.
     * Returns 404 when the invoice has no associated XML file.
     */
    public function download(Invoice $invoice): StreamedResponse|Response
    {
        Gate::authorize('view', $invoice);

        if ($invoice->xml_path === null || ! Storage::exists($invoice->xml_path)) {
            abort(404, __('accountant.xml_not_found'));
        }

        $filename = basename($invoice->xml_path);

        DB::transaction(function () use ($invoice): void {
            $this->auditService->record(
                AuditEventType::InvoiceXmlDownloaded,
                AuditOperation::Export,
                $invoice,
                subjects: [
                    AuditSubject::primary($invoice),
                ],
                metadata: [
                    'filename' => basename($invoice->xml_path),
                    'xml_path' => $invoice->xml_path,
                    'actor_id' => auth()->id(),
                ],
            );
        });

        return Storage::download($invoice->xml_path, $filename, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
