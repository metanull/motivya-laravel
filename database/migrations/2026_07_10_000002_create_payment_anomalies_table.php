<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_anomalies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('anomaly_type');
            $table->string('anomalous_model_type')->nullable();
            $table->unsignedBigInteger('anomalous_model_id')->nullable();
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('related_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('related_session_id')->nullable()->constrained('sport_sessions')->nullOnDelete();
            $table->foreignId('related_coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('related_statement_id')->nullable()->constrained('coach_payout_statements')->nullOnDelete();
            $table->string('resolution_status')->default('open');
            $table->text('resolution_reason')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('description')->nullable();
            $table->text('recommended_action')->nullable();
            $table->timestamps();

            $table->index(['anomaly_type', 'resolution_status']);
            $table->index(['anomalous_model_type', 'anomalous_model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_anomalies');
    }
};
