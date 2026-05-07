<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds normalised geocoding / address columns to the coach_profiles table.
 *
 * Unlike sport_sessions, coach_profiles has no existing lat/lng columns, so
 * those are also added here.  All columns are nullable so existing rows are
 * unaffected and the migration is safely reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table): void {
            $table->string('formatted_address', 500)->nullable()->after('country');
            $table->string('street_address', 255)->nullable()->after('formatted_address');
            $table->string('locality', 255)->nullable()->after('street_address');
            $table->decimal('latitude', 10, 7)->nullable()->after('locality');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('geocoding_provider', 50)->nullable()->after('longitude');
            $table->string('geocoding_place_id', 255)->nullable()->after('geocoding_provider');
            $table->timestamp('geocoded_at')->nullable()->after('geocoding_place_id');
            $table->json('geocoding_payload')->nullable()->after('geocoded_at');
        });
    }

    public function down(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'formatted_address',
                'street_address',
                'locality',
                'latitude',
                'longitude',
                'geocoding_provider',
                'geocoding_place_id',
                'geocoded_at',
                'geocoding_payload',
            ]);
        });
    }
};
