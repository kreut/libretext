<?php

use App\Exceptions\Handler;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateDefaultCompletionScoringModeToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('default_completion_scoring_mode')->after('scoring_type')->nullable();
        });


        Schema::table('assignment_question', function (Blueprint $table) {
            $table->string('completion_scoring_mode')->after('points')->nullable();
        });
        $completion_assignment_ids = DB::table('assignments')
            ->where('scoring_type', 'c')
            ->select('id')
            ->get()
            ->pluck('id');
        try {
            DB::beginTransaction();

            DB::table('assignments')->where('scoring_type', 'c')
                ->update(['default_completion_scoring_mode' => '50% for auto-graded']);

            DB::table('assignment_question')->whereIn('assignment_id', $completion_assignment_ids)
                ->update(['completion_scoring_mode' => '50% for auto-graded']);
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
        }
        DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('default_completion_scoring_mode');
        });
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->dropColumn('completion_scoring_mode');
        });
    }
}
