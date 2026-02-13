<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('request_type', 30)->default('incident')->after('category_id');
            // incident | service_request | access_request | maintenance

            $table->string('asset_tag', 50)->nullable()->after('request_type');
            // contoh: LAP-ITS-0231, PRN-2F-010

            $table->string('location', 120)->nullable()->after('asset_tag');
            // contoh: Ruang Finance Lt 2, Gedung A

            $table->string('contact_phone', 30)->nullable()->after('requester_email');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['request_type', 'asset_tag', 'location', 'contact_phone']);
        });
    }
};
