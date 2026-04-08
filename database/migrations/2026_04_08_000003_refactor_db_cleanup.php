<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Create tutor_profiles ──────────────────────────────────────
        Schema::create('tutor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('blurb')->nullable();
            $table->string('photo')->nullable();
            $table->string('tutoring_subject')->nullable();
            $table->timestamps();
        });

        // ── 2. Create student_profiles ────────────────────────────────────
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('student_grade')->nullable();
            $table->string('student_school')->nullable();
            $table->text('tutoring_goals')->nullable();
            $table->text('blurb')->nullable();
            $table->timestamps();
        });

        // ── 3. Create session_series ──────────────────────────────────────
        Schema::create('session_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject');
            $table->string('location')->nullable();
            $table->smallInteger('duration')->unsigned();
            $table->timestamps();
        });

        // ── 4. Add series_id FK to tutoring_sessions ─────────────────────
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()
                  ->after('recurring_id')
                  ->constrained('session_series')
                  ->nullOnDelete();
        });

        // ── 5. Data migration: tutor_profiles ─────────────────────────────
        // Insert one profile row per tutor/admin-who-can-tutor user
        DB::statement("
            INSERT INTO tutor_profiles (user_id, blurb, photo, tutoring_subject, created_at, updated_at)
            SELECT id, blurb, photo, tutoring_subject, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM users
            WHERE role = 'tutor' OR can_tutor = 1
        ");

        // ── 6. Data migration: student_profiles ───────────────────────────
        DB::statement("
            INSERT INTO student_profiles (user_id, student_grade, student_school, tutoring_goals, blurb, created_at, updated_at)
            SELECT id, student_grade, student_school, tutoring_goals, blurb, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM users
            WHERE role = 'student'
        ");

        // ── 7. Data migration: session_series from recurring_id groups ─────
        $recurringGroups = DB::table('tutoring_sessions')
            ->whereNotNull('recurring_id')
            ->select('recurring_id', 'tutor_id', 'student_id', 'subject', 'location', 'duration')
            ->selectRaw('MIN(created_at) as first_created')
            ->groupBy('recurring_id', 'tutor_id', 'student_id', 'subject', 'location', 'duration')
            ->get();

        foreach ($recurringGroups as $group) {
            $seriesId = DB::table('session_series')->insertGetId([
                'tutor_id'   => $group->tutor_id,
                'student_id' => $group->student_id,
                'subject'    => $group->subject,
                'location'   => $group->location,
                'duration'   => $group->duration,
                'created_at' => $group->first_created,
                'updated_at' => $group->first_created,
            ]);

            DB::table('tutoring_sessions')
                ->where('recurring_id', $group->recurring_id)
                ->update(['series_id' => $seriesId]);
        }

        // ── 8. Remove profile columns from users ──────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'blurb',
                'photo',
                'tutoring_subject',
                'student_grade',
                'student_school',
                'tutoring_goals',
                'is_admin',
            ]);
        });

        // ── 9. Remove recurring_id from tutoring_sessions ─────────────────
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->dropIndex('tutoring_sessions_recurring_id_index');
            $table->dropColumn('recurring_id');
        });

        // ── 10. Rename timesheets.parent_id → billed_user_id ─────────────
        Schema::table('timesheets', function (Blueprint $table) {
            $table->renameColumn('parent_id', 'billed_user_id');
        });

        // ── 11. Remove timesheets.period (now a computed accessor) ────────
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn('period');
        });

        // ── 12. Remove credit_purchases.amount (duplicate of credits_purchased) ──
        Schema::table('credit_purchases', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }

    public function down(): void
    {
        // ── Restore credit_purchases.amount
        Schema::table('credit_purchases', function (Blueprint $table) {
            $table->decimal('amount', 8, 2)->nullable()->after('user_id');
        });

        // ── Restore timesheets.period
        Schema::table('timesheets', function (Blueprint $table) {
            $table->string('period')->nullable()->after('tutor_payout');
        });

        // ── Rename timesheets.billed_user_id → parent_id
        Schema::table('timesheets', function (Blueprint $table) {
            $table->renameColumn('billed_user_id', 'parent_id');
        });

        // ── Restore recurring_id to tutoring_sessions
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->string('recurring_id')->nullable()->after('series_id')->index();
        });

        // ── Restore profile columns to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('role');
            $table->string('blurb')->nullable();
            $table->string('photo')->nullable();
            $table->string('tutoring_subject')->nullable();
            $table->string('student_grade')->nullable();
            $table->string('student_school')->nullable();
            $table->text('tutoring_goals')->nullable();
        });

        // ── Restore data from profile tables (best-effort; no guarantee of full fidelity)
        DB::statement("
            UPDATE users u
            INNER JOIN tutor_profiles tp ON tp.user_id = u.id
            SET u.blurb = tp.blurb, u.photo = tp.photo, u.tutoring_subject = tp.tutoring_subject
        ");

        DB::statement("
            UPDATE users u
            INNER JOIN student_profiles sp ON sp.user_id = u.id
            SET u.student_grade = sp.student_grade,
                u.student_school = sp.student_school,
                u.tutoring_goals = sp.tutoring_goals,
                u.blurb = sp.blurb
        ");

        // ── Remove series_id from tutoring_sessions
        Schema::table('tutoring_sessions', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropColumn('series_id');
        });

        // ── Drop new tables
        Schema::dropIfExists('session_series');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('tutor_profiles');
    }
};
