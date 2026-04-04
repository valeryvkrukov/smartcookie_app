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
            // ── Snapshot: PDF filename recorded at signing time so the record is self-contained
            $table->string('pdf_filename')->nullable()->after('signed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreement_requests', function (Blueprint $table) {
            $table->dropColumn('pdf_filename');
        });
    }
};
