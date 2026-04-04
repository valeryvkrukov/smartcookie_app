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
        Schema::table('agreement_requests', function (Blueprint $table) {
            // ── Audit: client IP address at time of signing
            $table->string('ip_address', 45)->nullable()->after('pdf_filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreement_requests', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
};
