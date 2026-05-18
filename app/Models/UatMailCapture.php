<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UatMailCapture extends Model
{
    protected $fillable = [
        'run_id',
        'to',
        'cc',
        'bcc',
        'subject',
        'html_body',
        'text_body',
        'headers',
        'metadata',
        'captured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'to' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'headers' => 'array',
            'metadata' => 'array',
            'captured_at' => 'datetime',
        ];
    }
}
