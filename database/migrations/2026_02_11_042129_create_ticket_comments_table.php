<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // public: terlihat requester (kalau nanti ada portal)
            // internal: catatan internal agent/admin
            $table->string('type', 10)->default('public'); // public|internal

            $table->text('message');

            $table->timestamps();

            $table->index(['ticket_id', 'type']);
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
