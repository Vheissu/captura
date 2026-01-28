<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screenshots', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->text('url');
            $table->string('params_hash', 64)->index();
            $table->jsonb('params');

            $table->string('status', 20)->default('pending');
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();

            $table->string('file_path')->nullable();
            $table->string('file_type', 10)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            $table->unsignedInteger('render_time_ms')->nullable();

            $table->text('extracted_html')->nullable();
            $table->text('extracted_text')->nullable();

            $table->string('webhook_url')->nullable();
            $table->string('webhook_status', 20)->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->boolean('from_cache')->default(false);

            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screenshots');
    }
};
