<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('stripe_transfer_id')->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_charge_id')->nullable()->index();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('sport_session_id')->nullable()->constrained('sport_sessions')->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('destination_account_id')->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('eur');
            $table->string('status');
            $table->timestamp('stripe_created_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'stripe_transfer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_transfers');
    }
};
