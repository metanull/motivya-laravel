<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\BookingStatus;
use App\Enums\CoachPayoutStatementStatus;
use App\Enums\SessionStatus;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CoachPayoutStatementService
{
    public function __construct(
        private readonly PayoutService $payoutService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Generate (create or refresh) a draft monthly statement for the given coach/period.
     *
     * If a non-draft statement already exists for the same period, it is returned as-is
     * without modification — the workflow has progressed beyond the draft stage.
     */
    public function generateForCoach(User $coach, int $year, int $month): CoachPayoutStatement
    {
        /** @var CoachPayoutStatement|null $existing */
        $existing = CoachPayoutStatement::where('coach_id', $coach->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        if ($existing !== null && $existing->status !== CoachPayoutStatementStatus::Draft) {
            return $existing;
        }

        /** @var CoachProfile $coachProfile */
        $coachProfile = $coach->coachProfile;

        if ($coachProfile === null) {
            throw new InvalidArgumentException(
                'Coach does not have a coach profile. Cannot generate payout statement.'
            );
        }

        // Collect completed sessions in the given month/year for this coach.
        $sessions = SportSession::where('coach_id', $coach->id)
            ->where('status', SessionStatus::Completed)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with([
                'bookings' => fn ($q) => $q->where('status', BookingStatus::Confirmed),
            ])
            ->get();

        $sessionsCount = $sessions->count();
        $paidBookings = $sessions->flatMap->bookings;
        $paidBookingsCount = $paidBookings->count();
        $revenueTtc = (int) $paidBookings->sum('amount_paid');

        // Payment fees: 1.5% of total revenue + €0.25 per booking (half-up rounding).
        $paymentFees = (int) round($revenueTtc * 1.5 / 100) + ($paidBookingsCount * 25);

        $breakdown = $this->payoutService->calculatePayout($coachProfile, $revenueTtc, $paymentFees);

        $isVatSubject = $coachProfile->is_vat_subject ?? false;
        $vatAmount = $isVatSubject ? ($revenueTtc - $breakdown->revenue_htva) : 0;

        $data = [
            'status' => CoachPayoutStatementStatus::Draft->value,
            'sessions_count' => $sessionsCount,
            'paid_bookings_count' => $paidBookingsCount,
            'revenue_ttc' => $revenueTtc,
            'revenue_htva' => $breakdown->revenue_htva,
            'vat_amount' => $vatAmount,
            'payment_fees' => $paymentFees,
            'subscription_tier' => $breakdown->applied_plan,
            'commission_rate' => $breakdown->commission_rate,
            'commission_amount' => $breakdown->commission_amount,
            'coach_payout' => $breakdown->coach_payout,
            'is_vat_subject' => $isVatSubject,
        ];

        return DB::transaction(function () use ($coach, $year, $month, $data): CoachPayoutStatement {
            $statement = CoachPayoutStatement::updateOrCreate(
                ['coach_id' => $coach->id, 'period_year' => $year, 'period_month' => $month],
                $data,
            );

            $this->auditService->record(
                AuditEventType::PayoutStatementGenerated,
                AuditOperation::Create,
                $statement,
                subjects: [
                    AuditSubject::primary($statement),
                    AuditSubject::related($coach, 'coach'),
                ],
                newValues: [
                    'period_year' => $year,
                    'period_month' => $month,
                    'coach_payout' => $data['coach_payout'],
                    'status' => CoachPayoutStatementStatus::Draft->value,
                ],
            );

            return $statement;
        });
    }

    /**
     * Coach requests payout: draft → ready_for_invoice.
     */
    public function requestPayout(CoachPayoutStatement $statement): void
    {
        if ($statement->status !== CoachPayoutStatementStatus::Draft) {
            throw new InvalidArgumentException(
                'Only draft statements can be requested for payout.'
            );
        }

        $statement->update(['status' => CoachPayoutStatementStatus::ReadyForInvoice->value]);
    }

    /**
     * Coach marks invoice as submitted: ready_for_invoice → invoice_submitted.
     */
    public function markInvoiceSubmitted(CoachPayoutStatement $statement): void
    {
        if ($statement->status !== CoachPayoutStatementStatus::ReadyForInvoice) {
            throw new InvalidArgumentException(
                'Statement must be in ready_for_invoice status to mark invoice as submitted.'
            );
        }

        DB::transaction(function () use ($statement): void {
            $oldStatus = $statement->status;

            $statement->update([
                'status' => CoachPayoutStatementStatus::InvoiceSubmitted->value,
                'invoice_submitted_at' => now(),
            ]);

            $this->auditService->record(
                AuditEventType::PayoutStatementSubmitted,
                AuditOperation::StateChange,
                $statement,
                subjects: [AuditSubject::primary($statement)],
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => CoachPayoutStatementStatus::InvoiceSubmitted->value],
            );
        });
    }

    /**
     * Accountant approves: invoice_submitted → approved.
     */
    public function approve(CoachPayoutStatement $statement, User $approver): void
    {
        if ($statement->status !== CoachPayoutStatementStatus::InvoiceSubmitted) {
            throw new InvalidArgumentException(
                'Only invoice_submitted statements can be approved.'
            );
        }

        DB::transaction(function () use ($statement, $approver): void {
            $oldStatus = $statement->status;

            $statement->update([
                'status' => CoachPayoutStatementStatus::Approved->value,
                'approved_at' => now(),
                'approved_by' => $approver->id,
            ]);

            $this->auditService->record(
                AuditEventType::PayoutStatementApproved,
                AuditOperation::StateChange,
                $statement,
                subjects: [
                    AuditSubject::primary($statement),
                    AuditSubject::related($approver, 'approver'),
                ],
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => CoachPayoutStatementStatus::Approved->value],
            );
        });
    }

    /**
     * Accountant blocks with a reason (can block from any non-terminal status).
     */
    public function block(CoachPayoutStatement $statement, User $approver, string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A block reason is required.');
        }

        DB::transaction(function () use ($statement, $approver, $reason): void {
            $oldStatus = $statement->status;

            $statement->update([
                'status' => CoachPayoutStatementStatus::Blocked->value,
                'block_reason' => $reason,
                'approved_by' => $approver->id,
            ]);

            $this->auditService->record(
                AuditEventType::PayoutStatementBlocked,
                AuditOperation::StateChange,
                $statement,
                subjects: [
                    AuditSubject::primary($statement),
                    AuditSubject::related($approver, 'approver'),
                ],
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => CoachPayoutStatementStatus::Blocked->value],
                metadata: ['reason' => $reason],
            );
        });
    }

    /**
     * Accountant marks as paid: approved → paid.
     */
    public function markPaid(CoachPayoutStatement $statement, User $approver): void
    {
        if ($statement->status !== CoachPayoutStatementStatus::Approved) {
            throw new InvalidArgumentException(
                'Only approved statements can be marked as paid.'
            );
        }

        DB::transaction(function () use ($statement, $approver): void {
            $oldStatus = $statement->status;

            $statement->update([
                'status' => CoachPayoutStatementStatus::Paid->value,
                'paid_at' => now(),
                'approved_by' => $approver->id,
            ]);

            $this->auditService->record(
                AuditEventType::PayoutStatementPaid,
                AuditOperation::StateChange,
                $statement,
                subjects: [
                    AuditSubject::primary($statement),
                    AuditSubject::related($approver, 'approver'),
                ],
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => CoachPayoutStatementStatus::Paid->value],
            );
        });
    }
}
