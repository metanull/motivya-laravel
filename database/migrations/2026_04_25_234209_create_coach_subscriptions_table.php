<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->restrictOnDelete();
            $table->string('plan', 10);
            $table->date('month');
            $table->unsignedInteger('revenue_ttc')->default(0);
            $table->string('applied_plan', 10);
            $table->unsignedInteger('subscription_fee')->default(0);
            $table->unsignedInteger('commission_rate')->default(30);
            $table->timestamps();

            $table->unique(['coach_id', 'month']);
            $table->index('coach_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_subscriptions');
    }
};
