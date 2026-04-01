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
        Schema::table('credit_purchases', function (Blueprint $table) {
            $table->string('type')->default('deposit')->after('total_paid'); // deposit or withdrawal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_purchases', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
