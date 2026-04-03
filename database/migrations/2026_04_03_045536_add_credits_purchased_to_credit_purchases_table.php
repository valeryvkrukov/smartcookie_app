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
            // Credits purchased (e.g. 1, 4, 6, 8, 10) - separate from total_paid in USD
            $table->decimal('credits_purchased', 8, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_purchases', function (Blueprint $table) {
            $table->dropColumn('credits_purchased');
        });
    }
};
