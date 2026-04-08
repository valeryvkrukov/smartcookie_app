<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Credit;
use App\Models\Timesheet;
use Carbon\Carbon;

class ImportLegacyData extends Command
{
    protected $signature = 'app:import-legacy-data
                            {--fresh    : Truncate all app data tables before importing}
                            {--dry-run  : Preview what would be migrated without writing}';

    protected $description = 'Migrate legacy MySQL DB → new schema. Requires LEGACY_DB_* env vars.';

    // ── FK resolution maps  (legacy_id → new_id)
    private array $userMap      = []; // legacy users.id          → new users.id
    private array $studentMap   = []; // legacy students.student_id → new users.id
    private array $agreementMap = []; // legacy aggreements.aggreement_id → new agreements.id
    private array $sessionMap   = []; // legacy sessions.session_id → new tutoring_sessions.id

    private const TIMEZONE_MAP = [
        'Eastern Time'           => 'America/New_York',
        'Central Time'           => 'America/Chicago',
        'Mountain Time'          => 'America/Denver',
        'Pacific Time'           => 'America/Los_Angeles',
        'Alaska'                 => 'America/Anchorage',
        'Hawaii'                 => 'Pacific/Honolulu',
        'Arizona'                => 'America/Phoenix',
        'Indiana (East)'         => 'America/Indiana/Indianapolis',
        'Atlantic Time (Canada)' => 'America/Halifax',
        'UTC'                    => 'UTC',
    ];

    private const STATUS_MAP = [
        'Confirm'             => 'Scheduled',
        'End'                 => 'Completed',
        'Cancel'              => 'Cancelled',
        'Canceled'            => 'Cancelled',
        'Cancelled'           => 'Cancelled',
        'Insufficient Credit' => 'Cancelled',
    ];

    // Tables to clear in FK-safe order (innermost first)
    private const TRUNCATE_ORDER = [
        'timesheets',
        'credit_purchases',
        'tutoring_sessions',
        'session_series',
        'subject_rates',
        'tutor_student_assignments',
        'agreement_requests',
        'agreements',
        'credits',
        'notifications',
        'tutor_profiles',
        'student_profiles',
        'sessions',
        'cache',
        'jobs',
        'users',
    ];

    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ── ENTRY POINT
    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function handle(): int
    {
        $this->newLine();

        try {
            DB::connection('legacy_mysql')->getPdo();
        } catch (\Exception $e) {
            $this->error('Cannot connect to legacy DB. Add the following to .env:');
            $this->line('  LEGACY_DB_HOST=127.0.0.1');
            $this->line('  LEGACY_DB_DATABASE=old_database_name');
            $this->line('  LEGACY_DB_USERNAME=root');
            $this->line('  LEGACY_DB_PASSWORD=secret');
            return 1;
        }

        if ($this->option('fresh')) {
            if (! $this->confirm('⚠  This will TRUNCATE all application data tables. Continue?', false)) {
                $this->warn('Aborted.');
                return 1;
            }
            if (! $this->option('dry-run')) {
                $this->truncateTables();
            } else {
                $this->warn('[dry-run] Would truncate all tables.');
            }
        }

        $this->line('╔══════════════════════════════════════╗');
        $this->line('║   Legacy → New  Migration            ║');
        $this->line('╚══════════════════════════════════════╝');
        $this->newLine();

        $dry = $this->option('dry-run');

        Model::unguard();

        $this->migrateUsers($dry);
        $this->migrateStudents($dry);
        $this->migrateCredits($dry);
        $this->migrateAgreements($dry);
        $this->migrateAgreementRequests($dry);
        $this->migrateTutorAssignments($dry);
        $this->migrateSessions($dry);
        $this->detectSessionSeries($dry);
        $this->migrateTimesheets($dry);
        $this->migrateSubjectRates($dry);

        Model::reguard();

        $this->newLine();
        $this->line($dry
            ? '<fg=yellow>✓ Dry-run complete — no data was written.</>'
            : '<fg=green>✓ Migration complete.</>');

        return 0;
    }

    // ── 1. Users (admin / customer / tutor) ─────────────────────

    private function migrateUsers(bool $dry): void
    {
        $this->line('<fg=yellow>→ [1/9] Users (admin / customer / tutor)…</>');

        $rows = DB::connection('legacy_mysql')->table('users')->get();
        $bar  = $this->output->createProgressBar($rows->count());
        $bar->start();

        $inserted = $updated = 0;

        foreach ($rows as $old) {
            $address = $this->buildAddress($old);
            $tz      = $this->mapTimezone($old->time_zone ?? '');

            $payload = [
                'first_name'        => $old->first_name,
                'last_name'         => $old->last_name,
                'role'              => $old->role,
                'password'          => $old->password,
                'address'           => $address,
                'phone'             => $old->phone,
                'time_zone'         => $tz,
                //'stripe_id'         => $old->stripe_id,
                'is_subscribed'     => ($old->automated_email === 'Subscribe'),
                'can_tutor'         => ($old->role === 'tutor'),
                'email_verified_at' => now(),
                'updated_at'        => now(),
            ];

            if (! $dry) {
                $existing = DB::table('users')->where('email', $old->email)->first();

                if ($existing) {
                    DB::table('users')->where('id', $existing->id)->update($payload);
                    $newId = $existing->id;
                    $updated++;
                } else {
                    $newId = DB::table('users')->insertGetId(array_merge($payload, [
                        'email'      => $old->email,
                        'created_at' => $old->created_at ?? now(),
                    ]));
                    $inserted++;
                }

                $this->userMap[$old->id] = $newId;

                // Migrate profile fields for tutors / admins
                if ($old->role === 'tutor' || $old->role === 'admin') {
                    DB::table('tutor_profiles')->updateOrInsert(
                        ['user_id' => $newId],
                        [
                            'blurb'            => $old->description,
                            'photo'            => $old->image,
                            'tutoring_subject' => $old->subject ?? null,
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]
                    );
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$rows->count()} users (inserted: {$inserted}, updated: {$updated})</>");
    }

    // ── 2. Students (legacy `students` → users with role='student') ──

    private function migrateStudents(bool $dry): void
    {
        $this->line('<fg=yellow>→ [2/9] Students…</>');

        $rows    = DB::connection('legacy_mysql')->table('students')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $inserted = $updated = $skipped = 0;

        foreach ($rows as $old) {
            $parentNewId = $this->userMap[$old->user_id] ?? null;

            if (! $parentNewId) {
                $this->newLine();
                $this->warn("  ⚠ Student #{$old->student_id} ({$old->student_name}): parent legacy_id={$old->user_id} not found. Skipping.");
                $skipped++;
                $bar->advance();
                continue;
            }

            [$firstName, $lastName] = User::splitName($old->student_name);

            $email = $old->email ?: ('student_' . $old->student_id . '@migrated.local');

            $payload = [
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'role'              => 'student',
                'parent_id'         => $parentNewId,
                'time_zone'         => 'America/New_York',
                'is_subscribed'     => false,
                'email_verified_at' => now(),
                'updated_at'        => now(),
            ];

            if (! $dry) {
                $existing = DB::table('users')->where('email', $email)->first();

                if ($existing) {
                    DB::table('users')->where('id', $existing->id)->update($payload);
                    $newId = $existing->id;
                    $updated++;
                } else {
                    $newId = DB::table('users')->insertGetId(array_merge($payload, [
                        'email'      => $email,
                        'password'   => Hash::make(Str::random(16)),
                        'created_at' => $old->created_at ?? now(),
                    ]));
                    $inserted++;
                }

                $this->studentMap[$old->student_id] = $newId;

                DB::table('student_profiles')->updateOrInsert(
                    ['user_id' => $newId],
                    [
                        'student_grade'  => $old->grade,
                        'student_school' => $old->college,
                        'tutoring_goals' => $old->goal,
                        'blurb'          => null,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]
                );
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$rows->count()} students (inserted: {$inserted}, updated: {$updated}, skipped: {$skipped})</>");
    }

    // ── 3. Credits ───────────────────────────────────────────────

    private function migrateCredits(bool $dry): void
    {
        $this->line('<fg=yellow>→ [3/9] Credits…</>');

        $rows    = DB::connection('legacy_mysql')->table('credits')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            $newUserId = $this->userMap[$old->user_id] ?? null;

            if (! $newUserId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $role = DB::table('users')->where('id', $newUserId)->value('role');
            if ($role !== 'customer') {
                $bar->advance();
                continue;
            }

            $balance = max(0.0, (float)($old->credit_balance ?? 0));
            $rate    = (float)($old->credit_cost ?? 0) ?: null;

            if (! $dry) {
                Credit::updateOrCreate(
                    ['user_id' => $newUserId],
                    [
                        'credit_balance'         => $balance,
                        'dollar_cost_per_credit' => $rate,
                        'created_at'             => $old->created_at ?? now(),
                        'updated_at'             => now(),
                    ]
                );
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} credits (skipped: {$skipped})</>");
    }

    // ── 4. Agreements (aggreements → agreements) ─────────────────

    private function migrateAgreements(bool $dry): void
    {
        $this->line('<fg=yellow>→ [4/9] Agreements…</>');

        $rows     = DB::connection('legacy_mysql')->table('aggreements')->get();
        $migrated = 0;

        foreach ($rows as $old) {
            $pdfPath = 'agreements/' . basename($old->file);

            if (! $dry) {
                $newId = DB::table('agreements')
                    ->where('name', $old->aggreement_name)
                    ->value('id');

                if (! $newId) {
                    $newId = DB::table('agreements')->insertGetId([
                        'name'       => $old->aggreement_name,
                        'pdf_path'   => $pdfPath,
                        'created_at' => $old->created_at ?? now(),
                        'updated_at' => $old->updated_at ?? now(),
                    ]);
                }

                $this->agreementMap[$old->aggreement_id] = $newId;
            }

            $migrated++;
        }

        $this->line(" <fg=green>✓ {$migrated} agreements</>");
    }

    // ── 5. Agreement requests (signed_aggreements) ───────────────

    private function migrateAgreementRequests(bool $dry): void
    {
        $this->line('<fg=yellow>→ [5/9] Agreement requests…</>');

        $rows    = DB::connection('legacy_mysql')->table('signed_aggreements')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            $newUserId      = $this->userMap[$old->user_id] ?? null;
            $newAgreementId = $this->agreementMap[$old->aggreement_id] ?? null;

            if (! $newUserId || ! $newAgreementId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $status = match ($old->status) {
                'Signed'  => 'Signed',
                'Pending' => 'Awaiting signature',
                default   => 'Awaiting signature',
            };

            if (! $dry) {
                DB::table('agreement_requests')->updateOrInsert(
                    ['user_id' => $newUserId, 'agreement_id' => $newAgreementId],
                    [
                        'status'           => $status,
                        'signed_full_name' => $old->user_name ?? null,
                        'signed_at'        => $this->parseDate($old->date),
                        'created_at'       => $old->created_at ?? now(),
                        'updated_at'       => $old->updated_at ?? now(),
                    ]
                );
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} request records (skipped: {$skipped})</>");
    }

    // ── 6. Tutor assignments (tutor_assign → tutor_student_assignments) ──

    private function migrateTutorAssignments(bool $dry): void
    {
        $this->line('<fg=yellow>→ [6/9] Tutor assignments…</>');

        $rows    = DB::connection('legacy_mysql')->table('tutor_assign')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            $newTutorId   = $this->userMap[$old->tutor_id] ?? null;
            $newStudentId = $this->studentMap[$old->student_id] ?? null;

            if (! $newTutorId || ! $newStudentId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (! $dry) {
                DB::table('users')->where('id', $newTutorId)->update(['can_tutor' => true]);

                $exists = DB::table('tutor_student_assignments')
                    ->where('tutor_id', $newTutorId)
                    ->where('student_id', $newStudentId)
                    ->exists();

                if (! $exists) {
                    DB::table('tutor_student_assignments')->insert([
                        'tutor_id'      => $newTutorId,
                        'student_id'    => $newStudentId,
                        'hourly_payout' => (float)($old->hourly_pay_rate ?? 25),
                        'created_at'    => $old->created_at ?? now(),
                        'updated_at'    => now(),
                    ]);
                }
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} assignments (skipped: {$skipped})</>");
    }

    // ── 7. Sessions ──────────────────────────────────────────────

    private function migrateSessions(bool $dry): void
    {
        $this->line('<fg=yellow>→ [7/9] Tutoring sessions…</>');

        $rows    = DB::connection('legacy_mysql')->table('sessions')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            $newTutorId   = $this->userMap[$old->tutor_id] ?? null;
            $newStudentId = $this->studentMap[$old->student_id] ?? null;

            if (! $newTutorId || ! $newStudentId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $startTime = $old->time ?? '00:00:00';
            if (strlen($startTime) === 5) {
                $startTime .= ':00';
            }

            $status          = self::STATUS_MAP[$old->status] ?? 'Scheduled';
            $isInitial       = ($old->session_type === 'First Session');
            $recurringWeekly = in_array(strtolower($old->recurs_weekly ?? ''), ['yes', '1', 'true', 'on'], true);
            $tutorNotes      = ($status === 'Cancelled' && $old->reason) ? $old->reason : null;

            if (! $dry) {
                $existing = DB::table('tutoring_sessions')
                    ->where('tutor_id', $newTutorId)
                    ->where('student_id', $newStudentId)
                    ->where('date', $this->parseDate($old->date))
                    ->where('start_time', $startTime)
                    ->where('subject', $old->subject)
                    ->first();

                if ($existing) {
                    $newId = $existing->id;
                } else {
                    $newId = DB::table('tutoring_sessions')->insertGetId([
                        'tutor_id'      => $newTutorId,
                        'student_id'    => $newStudentId,
                        'subject'       => $old->subject,
                        'date'          => $this->parseDate($old->date),
                        'start_time'    => $startTime,
                        'duration'      => $this->parseDuration($old->duration),
                        'location'      => $old->location ?: null,
                        'is_initial'    => $isInitial,
                        'recurs_weekly' => $recurringWeekly,
                        'status'        => $status,
                        'tutor_notes'   => $tutorNotes,
                        'created_at'    => $old->created_at ?? now(),
                        'updated_at'    => now(),
                    ]);
                }

                $this->sessionMap[$old->session_id] = $newId;
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} sessions (skipped: {$skipped})</>");
    }

    // ── 8. Timesheets ────────────────────────────────────────────

    private function migrateTimesheets(bool $dry): void
    {
        $this->line('<fg=yellow>→ [8/9] Timesheets…</>');

        $rows    = DB::connection('legacy_mysql')->table('timesheets')->get();
        $bar     = $this->output->createProgressBar($rows->count());
        $bar->start();

        $migrated = $skipped = 0;

        foreach ($rows as $old) {
            $newTutorId   = $this->userMap[$old->tutor_id] ?? null;
            $newStudentId = $this->studentMap[$old->student_id] ?? null;

            if (! $newTutorId || ! $newStudentId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $session = DB::table('tutoring_sessions')
                ->where('tutor_id', $newTutorId)
                ->where('student_id', $newStudentId)
                ->where('date', $this->parseDate($old->date))
                ->where('duration', $this->parseDuration($old->duration))
                ->where('status', 'Completed')
                ->first();

            if (! $session) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $alreadyExists = DB::table('timesheets')
                ->where('tutoring_session_id', $session->id)
                ->exists();

            if ($alreadyExists) {
                $bar->advance();
                continue;
            }

            $student      = DB::table('users')->where('id', $newStudentId)->first();
            $billedUserId = $student->parent_id ?? $newStudentId;

            $creditsSpent = Timesheet::calculateCredits($this->parseDuration($old->duration));
            $tutorPayout  = $creditsSpent * (float)($old->hourly_pay_rate ?? 0);

            if (! $dry) {
                DB::table('timesheets')->insert([
                    'tutoring_session_id' => $session->id,
                    'tutor_id'            => $newTutorId,
                    'billed_user_id'      => $billedUserId,
                    'credits_spent'       => $creditsSpent,
                    'tutor_payout'        => $tutorPayout,
                    'created_at'          => $old->created_at ?? now(),
                    'updated_at'          => now(),
                ]);
            }

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->line(" <fg=green>✓ {$migrated} timesheets (skipped/unmatched: {$skipped})</>");
    }

    // ── 9. Subject rates ─────────────────────────────────────────

    private function migrateSubjectRates(bool $dry): void
    {
        $this->line('<fg=yellow>→ [9/9] Subject rates…</>');

        $pairs = DB::table('tutoring_sessions')
            ->select('student_id', 'subject')
            ->distinct()
            ->get();

        $migrated = 0;

        foreach ($pairs as $pair) {
            $student  = DB::table('users')->where('id', $pair->student_id)->first();
            $parentId = $student?->parent_id ?? $pair->student_id;
            $rate     = DB::table('credits')->where('user_id', $parentId)->value('dollar_cost_per_credit');

            if (! $rate) {
                continue;
            }

            if (! $dry) {
                $exists = DB::table('subject_rates')
                    ->where('student_id', $pair->student_id)
                    ->where('subject', $pair->subject)
                    ->exists();

                if (! $exists) {
                    DB::table('subject_rates')->insert([
                        'student_id' => $pair->student_id,
                        'subject'    => $pair->subject,
                        'rate'       => $rate,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $migrated++;
        }

        $this->line(" <fg=green>✓ {$migrated} subject rates</>");
    }

    // ── 7b. Detect session_series from recurs_weekly sessions ─────

    private function detectSessionSeries(bool $dry): void
    {
        $this->line('<fg=yellow>→ [7b] Detecting session series (recurs_weekly, ≥3 consecutive sessions)…</>');

        // Fetch all recurs_weekly sessions that have no series yet, ordered so that
        // consecutive sessions in the same "stream" are adjacent in the result set.
        // location is intentionally excluded from the grouping key.
        $rows = DB::table('tutoring_sessions')
            ->where('recurs_weekly', true)
            ->whereNull('series_id')
            ->select('id', 'tutor_id', 'student_id', 'subject', 'duration', 'start_time',
                     'date', 'location', 'created_at')
            ->orderBy('tutor_id')
            ->orderBy('student_id')
            ->orderBy('subject')
            ->orderBy('duration')
            ->orderBy('start_time')
            ->orderBy('date')
            ->get();

        // Split the flat list into "runs": continuous weekly sequences.
        // A new run starts when the key (tutor/student/subject/duration/time) changes
        // OR the gap between consecutive session dates exceeds 14 days (one missed week allowed).
        $runs       = [];
        $currentKey = null;
        $currentRun = [];
        $lastDate   = null;

        foreach ($rows as $row) {
            $key  = "{$row->tutor_id}|{$row->student_id}|{$row->subject}|{$row->duration}|{$row->start_time}";
            $date = Carbon::parse($row->date);

            $gapBroken = $lastDate && $date->diffInDays($lastDate) > 14;

            if ($key !== $currentKey || $gapBroken) {
                if (count($currentRun) >= 3) {
                    $runs[] = $currentRun;
                }
                $currentRun = [];
                $currentKey = $key;
            }

            $currentRun[] = $row;
            $lastDate = $date;
        }
        if (count($currentRun) >= 3) {
            $runs[] = $currentRun;
        }

        $this->line("  Found " . count($runs) . " run(s) qualifying for a series.");

        $created = 0;

        foreach ($runs as $run) {
            $first = $run[0];
            $ids   = array_column((array) $run, 'id');

            if ($dry) {
                $label = "{$first->tutor_id}|{$first->student_id}|{$first->subject}|{$first->start_time}";
                $this->line("  [dry] Series {$label}  dates {$first->date}…" . end($run)->date . " (" . count($run) . " sessions)");
                continue;
            }

            $seriesId = DB::table('session_series')->insertGetId([
                'tutor_id'   => $first->tutor_id,
                'student_id' => $first->student_id,
                'subject'    => $first->subject,
                'location'   => $first->location ?? null,
                'duration'   => $first->duration,
                'created_at' => $first->created_at ?? now(),
                'updated_at' => now(),
            ]);

            DB::table('tutoring_sessions')
                ->whereIn('id', $ids)
                ->update(['series_id' => $seriesId]);

            $created++;
        }

        $suffix = $dry
            ? " <fg=yellow>✓ Dry-run: " . count($runs) . " series would be created.</>"
            : " <fg=green>✓ {$created} series created.</>";
        $this->line($suffix);
    }

    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ── HELPERS
    // ── ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function truncateTables(): void
    {
        $this->line('<fg=red>→ Truncating tables…</>');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::TRUNCATE_ORDER as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->line('  <fg=green>✓ Done</>');
    }

    private function mapTimezone(string $legacy): string
    {
        $legacy = trim($legacy);

        if (isset(self::TIMEZONE_MAP[$legacy])) {
            return self::TIMEZONE_MAP[$legacy];
        }

        if ($legacy && in_array($legacy, \DateTimeZone::listIdentifiers(), true)) {
            return $legacy;
        }

        return 'America/New_York';
    }

    private function buildAddress(object $old): ?string
    {
        $parts = array_filter([
            $old->address ?? null,
            $old->city    ?? null,
            $old->state   ?? null,
            $old->zip     ?? null,
        ]);

        if (empty($parts)) {
            return null;
        }

        if (($old->address ?? null) && ($old->city ?? null) && str_contains($old->address, $old->city)) {
            return $old->address;
        }

        return implode(', ', $parts);
    }

    private function parseDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $date)) {
                return Carbon::createFromFormat('m/d/y', $date)->toDateString();
            }

            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $date)) {
                return Carbon::createFromFormat('m/d/Y', $date)->toDateString();
            }

            return Carbon::parse($date)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Convert legacy HH:MM duration strings to integer minutes.
     * '0:30' → 30, '1:00' → 60, '1:30' → 90, '2:00' → 120, etc.
     */
    private function parseDuration(mixed $raw): int
    {
        $map = ['0:30' => 30, '1:00' => 60, '1:30' => 90, '2:00' => 120, '2:30' => 150, '3:00' => 180];

        if (is_numeric($raw)) {
            return (int) $raw;
        }

        return $map[(string) $raw] ?? 60;
    }
}
