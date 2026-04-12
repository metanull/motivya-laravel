<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_session_id')->constrained('sport_sessions')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending_payment');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->unsignedInteger('amount_paid')->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->unique(['sport_session_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
