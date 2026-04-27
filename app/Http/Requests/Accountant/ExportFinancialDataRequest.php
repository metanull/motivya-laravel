<?php

declare(strict_types=1);

namespace App\Http\Requests\Accountant;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ExportFinancialDataRequest extends FormRequest
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
            'type' => ['sometimes', 'nullable', Rule::in(array_column(InvoiceType::cases(), 'value'))],
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

    public function type(): string
    {
        return (string) ($this->query('type') ?? '');
    }
}
