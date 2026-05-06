<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceType;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\CoachPayoutStatement;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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

    // ─── Ledger exports ──────────────────────────────────────────────────────

    /**
     * Export filtered booking ledger as a streamed CSV download.
     */
    public function exportLedgerCsv(
        string $dateFrom = '',
        string $dateTo = '',
        string $coachId = '',
        string $sessionStatus = '',
        string $bookingStatus = '',
        string $anomalyFlag = '',
    ): StreamedResponse {
        $bookings = $this->buildLedgerQuery($dateFrom, $dateTo, $coachId, $sessionStatus, $bookingStatus, $anomalyFlag)->get();

        $payoutStmtMap = $this->buildPayoutStatementMap($bookings);

        return response()->streamDownload(function () use ($bookings, $payoutStmtMap): void {
            $writer = new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->ledgerHeaders()));

            foreach ($bookings as $booking) {
                $writer->addRow(Row::fromValues($this->ledgerRow($booking, $payoutStmtMap)));
            }

            $writer->close();
        }, $this->ledgerFilename('csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export filtered booking ledger as a streamed Excel (.xlsx) download.
     */
    public function exportLedgerExcel(
        string $dateFrom = '',
        string $dateTo = '',
        string $coachId = '',
        string $sessionStatus = '',
        string $bookingStatus = '',
        string $anomalyFlag = '',
    ): StreamedResponse {
        $bookings = $this->buildLedgerQuery($dateFrom, $dateTo, $coachId, $sessionStatus, $bookingStatus, $anomalyFlag)->get();

        $payoutStmtMap = $this->buildPayoutStatementMap($bookings);

        return response()->streamDownload(function () use ($bookings, $payoutStmtMap): void {
            $writer = new XlsxWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->ledgerHeaders()));

            foreach ($bookings as $booking) {
                $writer->addRow(Row::fromValues($this->ledgerRow($booking, $payoutStmtMap)));
            }

            $writer->close();
        }, $this->ledgerFilename('xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Build the base Booking query for the ledger with optional filters.
     *
     * @return Builder<Booking>
     */
    public function buildLedgerQuery(
        string $dateFrom = '',
        string $dateTo = '',
        string $coachId = '',
        string $sessionStatus = '',
        string $bookingStatus = '',
        string $anomalyFlag = '',
    ): Builder {
        $parsedSessionStatus = $sessionStatus !== '' ? SessionStatus::tryFrom($sessionStatus) : null;
        $parsedBookingStatus = $bookingStatus !== '' ? BookingStatus::tryFrom($bookingStatus) : null;

        return Booking::query()
            ->with(['sportSession.coach', 'athlete', 'sportSession.invoices'])
            ->when($dateFrom !== '', fn (Builder $q) => $q->where('bookings.created_at', '>=', $dateFrom.' 00:00:00'))
            ->when($dateTo !== '', fn (Builder $q) => $q->where('bookings.created_at', '<=', $dateTo.' 23:59:59'))
            ->when(
                $coachId !== '',
                fn (Builder $q) => $q->whereHas(
                    'sportSession',
                    fn (Builder $sq) => $sq->where('coach_id', $coachId),
                ),
            )
            ->when(
                $parsedSessionStatus !== null,
                fn (Builder $q) => $q->whereHas(
                    'sportSession',
                    fn (Builder $sq) => $sq->where('status', $parsedSessionStatus),
                ),
            )
            ->when($parsedBookingStatus !== null, fn (Builder $q) => $q->where('status', $parsedBookingStatus))
            ->when(
                $anomalyFlag === 'anomalies_only',
                fn (Builder $q) => $q->where(function (Builder $q): void {
                    $q->where(fn (Builder $s) => $s
                        ->where('status', BookingStatus::Confirmed->value)
                        ->where('amount_paid', '>', 0)
                        ->whereNull('stripe_payment_intent_id'),
                    )
                        ->orWhere(fn (Builder $s) => $s
                            ->where('status', BookingStatus::Confirmed->value)
                            ->where('amount_paid', '<=', 0),
                        )
                        ->orWhere(fn (Builder $s) => $s
                            ->where('status', BookingStatus::Cancelled->value)
                            ->where('amount_paid', '>', 0)
                            ->whereNull('refunded_at'),
                        );
                }),
            )
            ->when(
                $anomalyFlag === 'paid_without_invoice',
                fn (Builder $q) => $q
                    ->where('status', BookingStatus::Confirmed->value)
                    ->where('amount_paid', '>', 0)
                    ->whereDoesntHave('sportSession.invoices'),
            )
            ->when(
                $anomalyFlag === 'paid_without_payment_intent',
                fn (Builder $q) => $q
                    ->where('status', BookingStatus::Confirmed->value)
                    ->where('amount_paid', '>', 0)
                    ->whereNull('stripe_payment_intent_id'),
            )
            ->orderBy('bookings.created_at', 'desc');
    }

    /**
     * Column headers for the ledger export.
     *
     * @return list<string>
     */
    private function ledgerHeaders(): array
    {
        return [
            'date',
            'type',
            'athlete',
            'coach',
            'session_title',
            'booking_status',
            'amount_ttc_eur',
            'commission_eur',
            'payment_fee_eur',
            'coach_payout_eur',
            'stripe_payment_intent_id',
            'stripe_checkout_session_id',
            'refunded_at',
            'session_status',
            'has_invoice',
            'has_payout_statement',
            'anomaly_flag',
        ];
    }

    /**
     * Map a single Booking to a ledger export row.
     *
     * Monetary amounts are converted from integer cents to decimal EUR.
     * Missing invoice values are exported as empty strings.
     *
     * @return list<string|float|null>
     */
    private function ledgerRow(Booking $booking, array $payoutStmtMap = []): array
    {
        $invoice = $booking->sportSession?->invoices?->first();

        $coachId = $booking->sportSession?->coach_id;
        $stmtKey = $coachId !== null && $booking->created_at !== null
            ? $coachId.'-'.$booking->created_at->month.'-'.$booking->created_at->year
            : null;
        $payoutStatementExists = $stmtKey !== null && isset($payoutStmtMap[$stmtKey]);

        $anomalyFlag = match (true) {
            $booking->status === BookingStatus::Confirmed
                && ($booking->amount_paid ?? 0) > 0
                && $booking->stripe_payment_intent_id === null => 'missing_payment_intent',
            $booking->status === BookingStatus::Confirmed
                && ($booking->amount_paid ?? 0) <= 0 => 'confirmed_without_payment',
            $booking->status === BookingStatus::Cancelled
                && ($booking->amount_paid ?? 0) > 0
                && $booking->refunded_at === null => 'paid_cancelled_without_refund',
            default => '',
        };

        return [
            $booking->created_at?->format('Y-m-d H:i:s') ?? '',
            $booking->status === BookingStatus::Refunded ? 'refund' : 'booking',
            $booking->athlete?->name ?? '',
            $booking->sportSession?->coach?->name ?? '',
            $booking->sportSession?->title ?? '',
            $booking->status->value,
            $this->toEur($booking->amount_paid ?? 0),
            $invoice !== null ? $this->toEur($invoice->commission_amount) : null,
            $invoice !== null ? $this->toEur($invoice->stripe_fee) : null,
            $invoice !== null ? $this->toEur($invoice->coach_payout) : null,
            $booking->stripe_payment_intent_id ?? '',
            $booking->stripe_checkout_session_id ?? '',
            $booking->refunded_at?->format('Y-m-d H:i:s') ?? '',
            $booking->sportSession?->status?->value ?? '',
            $invoice !== null ? 'yes' : 'no',
            $payoutStatementExists ? 'yes' : 'no',
            $anomalyFlag,
        ];
    }

    /**
     * Build a "coach_id-month-year => true" lookup map from a collection of bookings.
     * Used to avoid N+1 queries when checking payout statement existence per booking.
     *
     * @param  Collection<int, Booking>  $bookings
     * @return array<string, bool>
     */
    private function buildPayoutStatementMap(Collection $bookings): array
    {
        $tuples = $bookings->map(fn (Booking $b): array => [
            'coach_id' => $b->sportSession?->coach_id,
            'month' => $b->created_at?->month,
            'year' => $b->created_at?->year,
        ])->filter(fn (array $t): bool => $t['coach_id'] !== null && $t['month'] !== null)
            ->unique()
            ->values();

        if ($tuples->isEmpty()) {
            return [];
        }

        return CoachPayoutStatement::where(function ($q) use ($tuples): void {
            foreach ($tuples as $tuple) {
                $q->orWhere(function ($s) use ($tuple): void {
                    $s->where('coach_id', $tuple['coach_id'])
                        ->where('period_month', $tuple['month'])
                        ->where('period_year', $tuple['year']);
                });
            }
        })->get(['coach_id', 'period_month', 'period_year'])
            ->mapWithKeys(fn (CoachPayoutStatement $stmt): array => [
                $stmt->coach_id.'-'.$stmt->period_month.'-'.$stmt->period_year => true,
            ])->all();
    }

    /**
     * Generate a timestamped filename for the ledger export.
     */
    private function ledgerFilename(string $extension): string
    {
        return 'ledger_export_'.now()->format('Y-m-d').'.'.$extension;
    }
}
