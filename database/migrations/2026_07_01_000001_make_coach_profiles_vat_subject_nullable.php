<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make is_vat_subject nullable so that null = "not yet captured by admin"
 * while true/false = explicitly set VAT status.
 * This enables the onboarding checklist to detect whether VAT status
 * has been reviewed by an admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->boolean('is_vat_subject')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('coach_profiles', function (Blueprint $table) {
            $table->boolean('is_vat_subject')->nullable(false)->default(false)->change();
        });
    }
};
