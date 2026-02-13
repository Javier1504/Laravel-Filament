<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();          // nama kategori (General, Bug, Access, dll)
            $table->unsignedInteger('sla_hours')->default(24); // SLA dalam jam

            $table->timestamps();

            $table->index('sla_hours');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
