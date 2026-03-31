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
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tutoring_session_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('users');
            $table->foreignId('parent_id')->constrained('users'); // Who's pay

            $table->decimal('credits_spent', 5, 2);
            $table->decimal('tutor_payout', 8, 2);

            $table->string('period');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
