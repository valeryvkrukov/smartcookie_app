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
            // General profile fields
            $table->string('first_name')->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('time_zone')->default('UTC');
            $table->boolean('is_subscribed')->default(true);

            // Remove standard field 'name' if exists
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }

            // Roles logic
            $table->enum('role', ['admin', 'tutor', 'customer', 'student'])->default('customer');
            $table->boolean('is_admin')->default(false);

            // Student fields
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('student_grade')->nullable();
            $table->string('student_school')->nullable();
            $table->text('tutoring_goals')->nullable();

            // Tutor fields
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
