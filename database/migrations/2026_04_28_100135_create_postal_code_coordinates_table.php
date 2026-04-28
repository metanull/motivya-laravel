<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postal_code_coordinates', function (Blueprint $table): void {
            $table->string('postal_code', 4)->primary();
            $table->string('municipality');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postal_code_coordinates');
    }
};
