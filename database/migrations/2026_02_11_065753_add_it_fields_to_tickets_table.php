<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // NOTE: SQLite tidak punya "IF NOT EXISTS" untuk add column,
            // jadi kita cek dulu via Schema::hasColumn sebelum menambah.
        });

        // lakukan check di luar closure supaya aman di sqlite
        if (! Schema::hasColumn('tickets', 'request_type')) {
            Schema::table('tickets', fn (Blueprint $table) =>
                $table->string('request_type', 40)->default('incident')
            );
        }

        if (! Schema::hasColumn('tickets', 'asset_tag')) {
            Schema::table('tickets', fn (Blueprint $table) =>
                $table->string('asset_tag', 80)->nullable()
            );
        }

        if (! Schema::hasColumn('tickets', 'location')) {
            Schema::table('tickets', fn (Blueprint $table) =>
                $table->string('location', 120)->nullable()
            );
        }

        if (! Schema::hasColumn('tickets', 'priority')) {
            Schema::table('tickets', fn (Blueprint $table) =>
                $table->string('priority', 20)->default('low')
            );
        }

        if (! Schema::hasColumn('tickets', 'status')) {
            Schema::table('tickets', fn (Blueprint $table) =>
                $table->string('status', 30)->default('open')
            );
        }
    }

    public function down(): void
    {
        // Optional: biasanya tidak perlu drop kolom di sqlite karena ribet.
        // Biarkan kosong atau implement dropColumn jika kamu pakai DB lain.
    }
};
