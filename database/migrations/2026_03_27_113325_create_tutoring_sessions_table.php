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
        Schema::create('tutoring_sessions', function (Blueprint $table) {
            $table->id();

            // ── Relations: foreign keys for tutor and student
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');

            // ── Session data: subject, date, time, duration, location
            $table->string('subject'); // Tutoring Subject: text box
            $table->date('date'); // Date: monthly calendar selector
            $table->time('start_time'); // Start Time: drop-down (H, M, AM/PM)
            $table->string('duration'); // Duration: 0:30, 1:00, 1:30, 2:00
            $table->string('location')->nullable(); // Location: text box

            // ── Flags: boolean flags for session type and recurrence
            $table->boolean('is_initial')->default(false); // Initial Session checkbox
            $table->boolean('recurs_weekly')->default(false); // Recurs Weekly checkbox

            // ── Status: scheduling state (Scheduled, Cancelled, Completed)
            $table->string('status')->default('Scheduled');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutoring_sessions');
    }
};
