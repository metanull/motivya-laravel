<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_refund_audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->integer('refund_amount')->nullable();
            $table->string('reason', 1000);
            $table->string('stripe_refund_id')->nullable();
            $table->string('status')->default('attempted'); // attempted | succeeded | failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('admin_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->nullOnDelete();

            $table->index('booking_id');
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_refund_audits');
    }
};
