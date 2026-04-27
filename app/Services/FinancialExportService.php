<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports financial data (invoices, credit notes, coach payouts) to CSV or Excel.
 *
 * All monetary amounts are exported in EUR (divided by 100 from the stored integer cents).
 * Filters: date range (billing_period_start), coach, invoice type.
 */
final class FinancialExportService
{
    /**
     * Export filtered invoices as a streamed CSV download.
     */
    public function exportCsv(
        string $dateFrom = '',
        string $dateTo = '',
        string $coachId = '',
        string $type = '',
    ): StreamedResponse {
        $invoices = $this->buildQuery($dateFrom, $dateTo, $coachId, $type)->get();

        return response()->streamDownload(function () use ($invoices): void {
            $writer = new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->headers()));

            foreach ($invoices as $invoice) {
                $writer->addRow(Row::fromValues($this->row($invoice)));
            }

            $writer->close();
        }, $this->filename('csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export filtered invoices as a streamed Excel (.xlsx) download.
     */
    public function exportExcel(
        string $dateFrom = '',
        string $dateTo = '',
        string $coachId = '',
        string $type = '',
    ): StreamedResponse {
        $invoices = $this->buildQuery($dateFrom, $dateTo, $coachId, $type)->get();

        return response()->streamDownload(function () use ($invoices): void {
            $writer = new XlsxWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->headers()));

            foreach ($invoices as $invoice) {
                $writer->addRow(Row::fromValues($this->row($invoice)));
            }

            $writer->close();
        }, $this->filename('xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Build the base Invoice query with optional filters applied.
     *
     * @return Builder<Invoice>
     */
    public function buildQuery(
        string $dateFrom = '',
        string $dateTo = '',
        string $coachId = '',
        string $type = '',
    ): Builder {
        $invoiceType = $type !== '' ? InvoiceType::tryFrom($type) : null;

        return Invoice::query()
            ->with(['coach'])
            ->when($dateFrom !== '', fn (Builder $q) => $q->where('billing_period_start', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $q) => $q->where('billing_period_start', '<=', $dateTo))
            ->when($coachId !== '', fn (Builder $q) => $q->where('coach_id', $coachId))
            ->when($invoiceType !== null, fn (Builder $q) => $q->where('type', $invoiceType))
            ->orderBy('issued_at', 'desc');
    }

    /**
     * Column headers for the export.
     *
     * @return list<string>
     */
    private function headers(): array
    {
        return [
            'invoice_number',
            'type',
            'coach',
            'billing_period_start',
            'billing_period_end',
            'revenue_ttc_eur',
            'revenue_htva_eur',
            'vat_amount_eur',
            'stripe_fee_eur',
            'subscription_fee_eur',
            'commission_amount_eur',
            'coach_payout_eur',
            'platform_margin_eur',
            'plan_applied',
            'tax_category_code',
            'status',
            'issued_at',
        ];
    }

    /**
     * Map a single Invoice to an export row.
     *
     * Monetary amounts are converted from integer cents to decimal EUR.
     *
     * @return list<string|float|null>
     */
    private function row(Invoice $invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->type->value,
            $invoice->coach?->name ?? '',
            $invoice->billing_period_start?->format('Y-m-d') ?? '',
            $invoice->billing_period_end?->format('Y-m-d') ?? '',
            $this->toEur($invoice->revenue_ttc),
            $this->toEur($invoice->revenue_htva),
            $this->toEur($invoice->vat_amount),
            $this->toEur($invoice->stripe_fee),
            $this->toEur($invoice->subscription_fee),
            $this->toEur($invoice->commission_amount),
            $this->toEur($invoice->coach_payout),
            $this->toEur($invoice->platform_margin),
            $invoice->plan_applied ?? '',
            $invoice->tax_category_code ?? '',
            $invoice->status->value,
            $invoice->issued_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * Convert an integer cent amount to a decimal EUR value.
     */
    private function toEur(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * Generate a timestamped filename for the export.
     */
    private function filename(string $extension): string
    {
        return 'financial_export_'.now()->format('Y-m-d').'.'.$extension;
    }
}
