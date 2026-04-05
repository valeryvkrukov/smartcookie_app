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
        Schema::table('credits', function (Blueprint $table) {
            // Customer-submitted "I've sent payment" notification to admin
            $table->decimal('pending_payment_amount', 10, 2)->nullable()->after('dollar_cost_per_credit');
            $table->string('pending_payment_method', 20)->nullable()->after('pending_payment_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn(['pending_payment_amount', 'pending_payment_method']);
        });
    }
};
