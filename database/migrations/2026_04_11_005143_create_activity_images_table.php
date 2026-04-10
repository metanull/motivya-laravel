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
        Schema::create('activity_images', function (Blueprint $table) {
            $table->id();
            $table->string('activity_type');
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index('activity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_images');
    }
};
