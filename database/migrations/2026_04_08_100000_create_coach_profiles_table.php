<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->json('specialties');
            $table->text('bio')->nullable();
            $table->string('experience_level')->nullable();
            $table->string('postal_code');
            $table->string('country')->default('BE');
            $table->string('enterprise_number');
            $table->boolean('is_vat_subject')->default(false);
            $table->string('stripe_account_id')->nullable();
            $table->boolean('stripe_onboarding_complete')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_profiles');
    }
};
