<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'type',
        'coach_id',
        'sport_session_id',
        'billing_period_start',
        'billing_period_end',
        'revenue_ttc',
        'revenue_htva',
        'vat_amount',
        'stripe_fee',
        'subscription_fee',
        'commission_amount',
        'coach_payout',
        'platform_margin',
        'plan_applied',
        'tax_category_code',
        'xml_path',
        'issued_at',
        'status',
        'related_invoice_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'issued_at' => 'datetime',
        ];
    }

    /**
     * Auto-generate a sequential invoice number before creating.
     */
    protected static function booted(): void
    {
        static::creating(function (self $invoice): void {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber($invoice->type);
            }
        });
    }

    /**
     * Generates the next sequential invoice number for the given type and current year.
     *
     * Format:
     *   - Invoice:     INV-{year}-{6-digit sequence}  e.g. INV-2026-000001
     *   - Credit note: CN-{year}-{6-digit sequence}   e.g. CN-2026-000001
     *
     * Sequences are independent per type and reset each calendar year.
     */
    public static function generateInvoiceNumber(InvoiceType|string $type): string
    {
        if (is_string($type)) {
            $type = InvoiceType::from($type);
        }

        $year = (int) now()->format('Y');
        $prefix = $type === InvoiceType::Invoice ? 'INV' : 'CN';
        $pattern = "{$prefix}-{$year}-%";

        $last = self::query()
            ->where('invoice_number', 'like', $pattern)
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $sequence = 1;

        if ($last !== null) {
            $parts = explode('-', $last);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('%s-%d-%06d', $prefix, $year, $sequence);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /**
     * @return BelongsTo<SportSession, $this>
     */
    public function sportSession(): BelongsTo
    {
        return $this->belongsTo(SportSession::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function relatedInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_invoice_id');
    }
}
