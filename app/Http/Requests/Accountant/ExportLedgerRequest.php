<?php

declare(strict_types=1);

namespace App\Http\Requests\Accountant;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ExportLedgerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Invoice::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['sometimes', 'string', Rule::in(['csv', 'excel'])],
            'dateFrom' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'dateTo' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'coachId' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'sessionStatus' => ['sometimes', 'nullable', Rule::in(array_column(SessionStatus::cases(), 'value'))],
            'bookingStatus' => ['sometimes', 'nullable', Rule::in(array_column(BookingStatus::cases(), 'value'))],
            'anomalyFlag' => ['sometimes', 'nullable', Rule::in(['anomalies_only', 'paid_without_invoice', 'paid_without_payment_intent'])],
        ];
    }

    public function exportFormat(): string
    {
        return $this->query('format', 'csv') ?? 'csv';
    }

    public function dateFrom(): string
    {
        return (string) ($this->query('dateFrom') ?? '');
    }

    public function dateTo(): string
    {
        return (string) ($this->query('dateTo') ?? '');
    }

    public function coachId(): string
    {
        return (string) ($this->query('coachId') ?? '');
    }

    public function sessionStatus(): string
    {
        return (string) ($this->query('sessionStatus') ?? '');
    }

    public function bookingStatus(): string
    {
        return (string) ($this->query('bookingStatus') ?? '');
    }

    public function anomalyFlag(): string
    {
        return $this->query('anomalyFlag', '') ?? '';
    }
}
