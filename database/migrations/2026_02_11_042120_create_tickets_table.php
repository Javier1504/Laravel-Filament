<?php

use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('title', 150);
            $table->text('description')->nullable();

            $table->string('request_type', 40)->default('incident');
            $table->string('asset_tag', 80)->nullable();
            $table->string('location', 120)->nullable();

            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('priority', 20)->default('medium');
            $table->string('status', 30)->default(Ticket::STATUS_NEW);

            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
