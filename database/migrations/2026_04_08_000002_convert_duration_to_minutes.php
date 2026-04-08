<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert tutoring_sessions.duration from varchar (e.g. '1:00') to
 * unsignedSmallInteger storing minutes (e.g. 60).
 *
 * Motivation:
 *  - Eliminates string-parsing (explode(':',…)) scattered across 10+ locations.
 *  - All duration arithmetic (session end-time, credit calculation, conflict
 *    detection) becomes a single integer addition: addMinutes($session->duration).
 *  - Slightly smaller storage; integer comparison for future index use.
 *
 * Known values in production: '0:30'→30, '1:00'→60, '1:30'→90, '2:00'→120.
 * Ad-hoc logging also allows '2:30'→150 and '3:00'→180.
 * Any unrecognised value is mapped to 60 (1 hour) as a safe default.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add a temporary column to hold the integer minutes.
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_min')->default(60)->after('duration');
        });

        // 2. Populate from the existing string values.
        DB::statement("
            UPDATE tutoring_sessions
            SET duration_min = CASE duration
                WHEN '0:30' THEN 30
                WHEN '1:00' THEN 60
                WHEN '1:30' THEN 90
                WHEN '2:00' THEN 120
                WHEN '2:30' THEN 150
                WHEN '3:00' THEN 180
                ELSE 60
            END
        ");

        // 3. Drop the old varchar column.
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->dropColumn('duration');
        });

        // 4. Rename the new column to 'duration'.
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->renameColumn('duration_min', 'duration');
        });
    }

    public function down(): void
    {
        // 1. Add a temporary varchar column.
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->string('duration_str', 10)->default('1:00')->after('duration');
        });

        // 2. Convert integers back to HH:MM strings.
        DB::statement("
            UPDATE tutoring_sessions
            SET duration_str = CASE duration
                WHEN 30  THEN '0:30'
                WHEN 60  THEN '1:00'
                WHEN 90  THEN '1:30'
                WHEN 120 THEN '2:00'
                WHEN 150 THEN '2:30'
                WHEN 180 THEN '3:00'
                ELSE '1:00'
            END
        ");

        // 3. Drop the integer column.
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->dropColumn('duration');
        });

        // 4. Rename back to 'duration'.
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->renameColumn('duration_str', 'duration');
        });
    }
};
