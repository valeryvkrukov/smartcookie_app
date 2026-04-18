<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add cascadeOnDelete to timesheets.tutor_id and timesheets.billed_user_id
     * so that deleting a user does not trigger a FK constraint violation.
     *
     * billed_user_id: FK exists but named 'timesheets_parent_id_foreign' (column was renamed)
     * tutor_id: only has an index, no FK constraint — add one with cascade
     */
    public function up(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('timesheets', 'parent_id')) {
                $table->dropForeign(['parent_id']); 
            }
            // billed_user_id: drop old FK (kept the original name after column rename) and re-add with cascade
            if (Schema::hasColumn('timesheets', 'timesheets_parent_id_foreign')) {
                $table->dropForeign('timesheets_billed_user_id_foreign');
                $table->dropForeign('timesheets_parent_id_foreign');
            }
            $table->foreign('billed_user_id')->references('id')->on('users')->cascadeOnDelete();

            // tutor_id: no FK constraint exists yet, just add one with cascade
            $table->foreign('tutor_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('timesheets', 'parent_id')) {
                $table->dropForeign(['parent_id']); 
            }
            if (Schema::hasColumn('timesheets', 'timesheets_parent_id_foreign')) {
                $table->dropForeign('timesheets_billed_user_id_foreign');
                $table->dropForeign('timesheets_parent_id_foreign');
            }
            $table->dropForeign(['billed_user_id']);
            $table->dropForeign(['tutor_id']);

            $table->foreign('billed_user_id')->references('id')->on('users');
            // tutor_id had no FK before, so nothing to restore
        });
    }
};
