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
        Schema::create('tutor_student_assignments', function (Blueprint $table) {
            $table->id();

            // Relation to Tutor (User with role 'tutor')
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');

            // Relation to Student (User with role 'student')
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');

            // Hourly pay rate for Tutor
            $table->decimal('hourly_payout', 8, 2)->default(25.00);

            $table->timestamps();

            // Unique index to avoid appointing same Tutor to same Student twice
            $table->unique(['tutor_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutor_student_assignments');
    }
};
