<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTypeAndReasonForEditToQuestionRevisions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->string('action', 10)->after('revision_number');
        });
        Schema::table('question_revisions', function (Blueprint $table) {
            DB::table('question_revisions')->update(['action' => 'notify']);
        });
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->text('reason_for_edit')->after('action')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->dropColumn(['action', 'reason_for_edit']);
        });
    }
}
