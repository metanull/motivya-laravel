<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_payout_statements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->string('status')->default('draft');
            $table->unsignedInteger('sessions_count')->default(0);
            $table->unsignedInteger('paid_bookings_count')->default(0);
            $table->unsignedBigInteger('revenue_ttc')->default(0);
            $table->unsignedBigInteger('revenue_htva')->default(0);
            $table->unsignedBigInteger('vat_amount')->default(0);
            $table->unsignedBigInteger('payment_fees')->default(0);
            $table->string('subscription_tier')->nullable();
            $table->unsignedTinyInteger('commission_rate')->default(0);
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->unsignedBigInteger('coach_payout')->default(0);
            $table->boolean('is_vat_subject')->default(false);
            $table->text('block_reason')->nullable();
            $table->timestamp('invoice_submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['coach_id', 'period_year', 'period_month']);
            $table->index(['coach_id', 'status']);
            $table->index(['period_year', 'period_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_payout_statements');
    }
};
