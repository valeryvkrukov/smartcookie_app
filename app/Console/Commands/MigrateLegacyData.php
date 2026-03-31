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

        // Migrate users
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
            /*$user = User::updateOrCreate(
                ['email' => $old->email],
            );*/

            // Migrate credits
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

        // Migrate students
        $legacyStudents = DB::connection('legacy_mysql')->table('students')->get();

        foreach ($legacyStudents as $oldStudent) {
            // Get `old` parent 
            $oldParent = DB::connection('legacy_mysql')->table('users')
                ->where('id', $oldStudent->user_id)
                ->first();
            
            if ($oldParent) {
                // Get `new` parent by email
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
                            'password'         => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
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
            // Get `old` user
            $oldUser = DB::connection('legacy_mysql')->table('users')->where('id', $sig->user_id)->first();
            
            // Get old agreement by ID for naming
            $oldAg = DB::connection('legacy_mysql')->table('aggreements')->where('aggreement_id', $sig->aggreement_id)->first();

            if ($oldUser && $oldAg) {
                // Get that user and agreement in the modified DB
                $newUser = User::where('email', $oldUser->email)->first();
                $newAg = Agreement::where('name', $oldAg->aggreement_name)->first();

                if ($newUser && $newAg) {
                    // Make record about signment
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

        \Illuminate\Database\Eloquent\Model::reguard();


        $this->info('Migration complete!');
    }
}
