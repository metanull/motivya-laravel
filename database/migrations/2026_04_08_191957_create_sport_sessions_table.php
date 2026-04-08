<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sport_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('activity_type');
            $table->string('level');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location');
            $table->string('postal_code');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('price_per_person');
            $table->unsignedInteger('min_participants')->default(1);
            $table->unsignedInteger('max_participants');
            $table->unsignedInteger('current_participants')->default(0);
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('cover_image_id')->nullable();
            $table->uuid('recurrence_group_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('date');
            $table->index('postal_code');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sport_sessions');
    }
};
