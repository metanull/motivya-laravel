<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geocoding_cache', function (Blueprint $table) {
            $table->id();
            $table->string('query_hash', 64)->unique();
            $table->string('query');
            $table->string('locale', 10)->default('en');
            $table->string('country', 10)->default('BE');
            $table->string('provider', 50)->default('google');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('found')->default(false);
            $table->timestamp('cached_at');
            $table->timestamps();

            $table->index(['query_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geocoding_cache');
    }
};
