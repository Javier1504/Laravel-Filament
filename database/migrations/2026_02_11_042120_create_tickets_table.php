<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Identitas ticket
            $table->string('code')->unique(); // contoh: TCK-000001
            $table->string('title', 150);
            $table->text('description')->nullable();

            // Relasi
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->foreignId('assignee_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Metadata request
            $table->string('requester_name', 120)->nullable();
            $table->string('requester_email', 120)->nullable();

            // Workflow
            $table->string('priority', 10)->default('medium'); // low|medium|high|urgent
            $table->string('status', 15)->default('open');     // open|in_progress|resolved|closed

            // SLA / waktu
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // Index biar cepat untuk list/filter di Filament
            $table->index(['status', 'priority']);
            $table->index(['category_id', 'assignee_id']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
