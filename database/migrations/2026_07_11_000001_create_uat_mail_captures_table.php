<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uat_mail_captures', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('run_id')->nullable()->index();
            $table->json('to')->nullable();
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->json('headers')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('captured_at')->nullable()->index();
            $table->timestamps();

            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uat_mail_captures');
    }
};
