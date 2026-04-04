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
        Schema::table('users', function (Blueprint $table) {
            // ── Profile: general user profile fields
            $table->string('first_name')->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('time_zone')->default('UTC');
            $table->boolean('is_subscribed')->default(true);

            // ── Cleanup: drop the standard 'name' column if it exists
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }

            // ── Roles: role enum and admin flag
            $table->enum('role', ['admin', 'tutor', 'customer', 'student'])->default('customer');
            $table->boolean('is_admin')->default(false);

            // ── Student: student-specific columns
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('student_grade')->nullable();
            $table->string('student_school')->nullable();
            $table->text('tutoring_goals')->nullable();

            // ── Tutor: tutor-specific columns
            $table->text('blurb')->nullable(); // tutor description
            $table->string('photo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
