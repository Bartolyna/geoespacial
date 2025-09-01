<?php

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
        Schema::create('technical_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->text('summary')->nullable();
            $table->string('type', 50)->default('general');
            $table->string('status', 50)->default('draft');
            $table->string('llm_provider', 50)->default('simulation');
            $table->text('prompt_template')->nullable();
            $table->decimal('generation_time', 8, 3)->nullable();
            $table->json('token_usage')->nullable();
            $table->json('data_sources')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('cached_until')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('language', 10)->default('es');
            
            // Relaciones
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['type', 'status']);
            $table->index(['llm_provider', 'created_at']);
            $table->index(['location_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'cached_until']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_reports');
    }
};
