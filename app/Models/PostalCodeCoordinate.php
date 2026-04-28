<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PostalCodeCoordinate extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'postal_code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'postal_code',
        'municipality',
        'latitude',
        'longitude',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}
