<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('type')->default('invoice');
            $table->foreignId('coach_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sport_session_id')->nullable()->constrained('sport_sessions')->restrictOnDelete();
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->unsignedInteger('revenue_ttc')->default(0);
            $table->unsignedInteger('revenue_htva')->default(0);
            $table->unsignedInteger('vat_amount')->default(0);
            $table->unsignedInteger('stripe_fee')->default(0);
            $table->unsignedInteger('subscription_fee')->default(0);
            $table->unsignedInteger('commission_amount')->default(0);
            $table->unsignedInteger('coach_payout')->default(0);
            $table->unsignedInteger('platform_margin')->default(0);
            $table->string('plan_applied');
            $table->string('tax_category_code');
            $table->string('xml_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('related_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();

            $table->index(['coach_id', 'status']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
