<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDefaultOpenEndedSubmissionToAssignmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('default_open_ended_submission_type', 10)->after('show_points_per_question');
            $table->dropColumn('submission_files');
            $table->dropColumn('submission_texts');
        });
        DB::table('assignments')->update(['default_open_ended_submission_type' => 'file']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment', function (Blueprint $table) {
            //
        });
    }
}
