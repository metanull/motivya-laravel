<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds normalised geocoding / address columns to the sport_sessions table.
 *
 * All columns are nullable so existing rows are unaffected.
 * The `geocoding_payload` column stores the raw provider response as JSON
 * for auditing and re-processing purposes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table): void {
            $table->string('formatted_address', 500)->nullable()->after('longitude');
            $table->string('street_address', 255)->nullable()->after('formatted_address');
            $table->string('locality', 255)->nullable()->after('street_address');
            $table->string('country', 2)->nullable()->default(null)->after('locality');
            $table->string('geocoding_provider', 50)->nullable()->after('country');
            $table->string('geocoding_place_id', 255)->nullable()->after('geocoding_provider');
            $table->timestamp('geocoded_at')->nullable()->after('geocoding_place_id');
            $table->json('geocoding_payload')->nullable()->after('geocoded_at');
        });
    }

    public function down(): void
    {
        Schema::table('sport_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'formatted_address',
                'street_address',
                'locality',
                'country',
                'geocoding_provider',
                'geocoding_place_id',
                'geocoded_at',
                'geocoding_payload',
            ]);
        });
    }
};
