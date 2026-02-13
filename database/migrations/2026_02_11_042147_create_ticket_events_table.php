<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // contoh: created|assigned|status_changed|commented|resolved|closed|reopened
            $table->string('event', 30);

            // metadata tambahan (mis. old_status/new_status, assignee_id, message_id, dll)
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['ticket_id', 'event']);
            $table->index('actor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_events');
    }
};
