<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for the most common query patterns:
 *
 * tutoring_sessions:
 *   - (tutor_id, date)    → calendar: load tutor's sessions for a date range
 *   - (student_id, date)  → customer calendar: load student sessions
 *   - (date, status)      → conflict check + pending-log queries
 *
 * agreement_requests:
 *   - (user_id, status)   → check.agreements middleware (runs on EVERY authenticated request)
 *
 * users:
 *   - (role, is_inactive) → admin/tutor student dropdowns, student global scopes
 *   - (role, parent_id)   → customer's children queries
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->index(['tutor_id', 'date'],    'ts_tutor_date');
            $table->index(['student_id', 'date'],  'ts_student_date');
            $table->index(['date', 'status'],      'ts_date_status');
        });

        Schema::table('agreement_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'ar_user_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_inactive'], 'u_role_inactive');
            $table->index(['role', 'parent_id'],   'u_role_parent');
        });
    }

    public function down(): void
    {
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->dropIndex('ts_tutor_date');
            $table->dropIndex('ts_student_date');
            $table->dropIndex('ts_date_status');
        });

        Schema::table('agreement_requests', function (Blueprint $table) {
            $table->dropIndex('ar_user_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('u_role_inactive');
            $table->dropIndex('u_role_parent');
        });
    }
};
