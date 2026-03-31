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
        Schema::create('credits', function (Blueprint $table) {
            $table->id();

            // Relation to the Client (Parent)
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');

            // Balance: using decimal to support fractional values (0.5, 1.5, etc)  
            $table->decimal('credit_balance', 10, 2)->default(0.00);

            // Until the admin fills in this field, the purchase for the client is blocked (grayed out)
            $table->decimal('dollar_cost_per_credit', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
