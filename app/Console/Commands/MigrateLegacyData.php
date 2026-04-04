<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Credit;
use App\Models\Agreement;
use App\Models\TutoringSession;


#[Signature('app:migrate-legacy-data')]
#[Description('Migrate users and credits from legacy-db to modern-db')]
class MigrateLegacyData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting legacy data migration...');

        \Illuminate\Database\Eloquent\Model::unguard();

        // ── Users: migrate user records from legacy database
        $legacyUsers = DB::connection('legacy_mysql')->table('users')->get();

        foreach ($legacyUsers as $old) {
            DB::table('users')->updateOrInsert(
                ['email' => $old->email],
                [
                    'first_name'    => $old->first_name,
                    'last_name'     => $old->last_name,
                    'role'          => $old->role,
                    'password'      => $old->password,
                    'address'       => $old->address,
                    'phone'         => $old->phone,
                    'is_subscribed' => ($old->automated_email === 'Subscribe'),
                    'is_admin'      => ($old->role === 'admin'),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );

            // ── Credits: migrate credit balance for this user
            $oldCredit = DB::connection('legacy_mysql')->table('credits')
                ->where('user_id', $old->id)
                ->first();

            if ($oldCredit) {
                $newUserId = DB::table('users')->where('email', $old->email)->value('id');

                Credit::updateOrInsert(
                    ['user_id' => $newUserId],
                    [
                        'credit_balance' => (float)$oldCredit->credit_balance,
                        'dollar_cost_per_credit' => (float)$oldCredit->credit_cost,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->info('Users and Credits migrated successfully!');

        $this->info('Migrating Students...');

        // ── Students: migrate student records linked to parent accounts
        $legacyStudents = DB::connection('legacy_mysql')->table('students')->get();

        foreach ($legacyStudents as $oldStudent) {
            // ── Legacy lookup: find original parent in legacy database
            $oldParent = DB::connection('legacy_mysql')->table('users')
                ->where('id', $oldStudent->user_id)
                ->first();
            
            if ($oldParent) {
                // ── New lookup: find migrated parent by matching email
                $newParent = User::where('email', $oldParent->email)->first();

                if ($newParent) {
                    DB::table('users')->updateOrInsert(
                        ['email' => $oldStudent->email ?? 'student_' . $oldStudent->student_id . '@tutor.com'],
                        [
                            'first_name'       => $oldStudent->student_name,
                            'parent_id'        => $newParent->id,
                            'role'             => 'student',
                            'student_grade'    => $oldStudent->grade,
                            'student_school'   => $oldStudent->college, 
                            'tutoring_subject' => $oldStudent->subject,
                            'tutoring_goals'   => $oldStudent->goal,
                            'password'         => Hash::make(Str::random(16)),
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]
                    );
                }
            }
        }
        $this->info('Users and Credits migrated successfully!');

        $this->info('Migrating Agreements...');

        $legacy = DB::connection('legacy_mysql')->table('aggreements')->get();

        foreach ($legacy as $item) {
            DB::table('agreements')->updateOrInsert(
                ['name' => $item->aggreement_name],
                [
                    'pdf_path'      => 'agreements/' . $item->file,
                    'content'       => 'Please review the attached document.',
                    'is_active'     => true,
                    'created_at'    => $item->created_at ?? now(),
                    'updated_at'    => $item->updated_at ?? now(),
                ]
            );
        }

        $this->info('Agreements migrated successfully!');

        $this->info('Migrating Signatures...');

        $legacySignatures = DB::connection('legacy_mysql')->table('signed_aggreements')->get();

        foreach ($legacySignatures as $sig) {
            // ── Legacy lookup: find original user in legacy database
            $oldUser = DB::connection('legacy_mysql')->table('users')->where('id', $sig->user_id)->first();
            
            // ── Legacy lookup: find original agreement for name matching
            $oldAg = DB::connection('legacy_mysql')->table('aggreements')->where('aggreement_id', $sig->aggreement_id)->first();

            if ($oldUser && $oldAg) {
                // ── New lookup: find migrated user and agreement by email/name
                $newUser = User::where('email', $oldUser->email)->first();
                $newAg = Agreement::where('name', $oldAg->aggreement_name)->first();

                if ($newUser && $newAg) {
                    // ── Signature: create agreement request record
                    DB::table('agreement_requests')->updateOrInsert(
                        ['user_id' => $newUser->id, 'agreement_id' => $newAg->id],
                        [
                            'status' => $sig->status,
                            'signed_at' => $sig->created_at ?? now(),
                            'created_at' => $sig->created_at ?? now(),
                            'updated_at' => $sig->updated_at ?? now(),
                        ]
                    );
                }
            }
        }

        $this->info('Signatures migrated successfully!');

        $this->info('Migrating Sessions...');

        $legacySessions = DB::connection('legacy_mysql')->table('sessions')->get();
        $statusMap = [
            'End' => 'Completed',
            'Cancel' => 'Cancelled',
            'Canceled' => 'Cancelled',
            'Cancelled' => 'Cancelled',
        ];

        foreach ($legacySessions as $oldSession) {
            $oldTutor = DB::connection('legacy_mysql')->table('users')->where('id', $oldSession->tutor_id)->first();
            $oldStudent = DB::connection('legacy_mysql')->table('users')->where('id', $oldSession->student_id)->first();

            if (! $oldTutor || ! $oldStudent) {
                $this->warn("Skipping legacy session {$oldSession->session_id}: missing tutor or student record.");
                continue;
            }

            $newTutor = User::where('email', $oldTutor->email)->first();
            $studentEmail = $oldStudent->email ?: 'student_' . $oldStudent->id . '@tutor.com';
            $newStudent = User::where('email', $studentEmail)->first();

            if (! $newTutor || ! $newStudent) {
                $this->warn("Skipping legacy session {$oldSession->session_id}: mapped tutor or student not found in new DB.");
                continue;
            }

            $startTime = $oldSession->time;
            if ($startTime && strlen($startTime) === 5) {
                $startTime = $startTime . ':00';
            }

            TutoringSession::updateOrInsert(
                [
                    'tutor_id' => $newTutor->id,
                    'student_id' => $newStudent->id,
                    'date' => $oldSession->date,
                    'start_time' => $startTime,
                    'subject' => $oldSession->subject,
                ],
                [
                    'duration' => $oldSession->duration,
                    'location' => $oldSession->location ?: null,
                    'status' => $statusMap[$oldSession->status] ?? $oldSession->status,
                    'recurs_weekly' => in_array(strtolower($oldSession->recurs_weekly), ['yes', '1', 'true', 'on'], true),
                    'is_initial' => false,
                    'created_at' => $oldSession->created_at ?? now(),
                    'updated_at' => $oldSession->updated_at ?? now(),
                ]
            );
        }

        $this->info('Sessions migrated successfully!');

        \Illuminate\Database\Eloquent\Model::reguard();


        $this->info('Migration complete!');
    }
}
